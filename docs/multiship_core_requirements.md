# Zen Cart Core: Dependencies and Requests

Notes gathered while converting **Multiple Ship-To Addresses** into an encapsulated
`zc_plugin` for Zen Cart v2.0.0–v2.3, with the constraint that the plugin modify **no core
file and no store template**.

References are to the `zencart/zencart` **2.3** branch, HEAD `0db1041` (2026-07-23).

Two kinds of note here:

- **Part 1** — core behaviours the plugin now depends on. Not requests; things that would
  break a shipping plugin if they regressed.
- **Part 2** — core changes worth considering, each with the concrete problem that
  prompted it.

---

## Part 1 — Core behaviours depended upon

### 1.1 Notifiers (all present, no action needed)

All **24** notifiers the plugin's two observers attach to are emitted by 2.3. Five of them
were previously supplied by the plugin shipping *patched copies of core files* —
`NOTIFY_ADMIN_ORDERS_UPDATE_ORDER_START`, `NOTIFY_ADMIN_ORDERS_EXTRA_STATUS_INPUTS`,
`NOTIFY_ADMIN_ORDERS_MENU_LEGEND`, `NOTIFY_ADMIN_ORDERS_SHOW_ORDER_DIFFERENCE` and
`NOTIFY_OT_SHIPPING_TAX_CALCS`. Having those upstream is what makes a zero-core-change
plugin possible at all, and the patched `zc155/` and `zc156/` trees have been deleted.

### 1.2 Plugin resolution in `PageLoader`

The plugin relies on four merge points, all working as documented:

| Mechanism | Used for |
|---|---|
| `findModulePageDirectory()` | mod-owned pages (`multiship_choice`, `multiship_address`) |
| `getTemplateDirectory()` step 4 | `tpl_*` files for those pages |
| `listModulePagesFiles('on_load_', '.js')` — `index.php:63` | login-focus workaround |
| language loader plugin merging | `lang.*.php` and `extra_definitions` |

A mod-owned page is the *only* way this plugin can present UI without touching a store
template, because plugin templates resolve **behind** the active template. That makes
`findModulePageDirectory()` and step 4 of `getTemplateDirectory()` load-bearing.

### 1.3 `address_book_process` ordering — fragile, please see §2.2

`includes/modules/pages/address_book_process/header_php.php` handles the POST **before**
it checks the address-book limit:

| Line | Action |
|---|---|
| 53 | POST processing begins |
| 69–170 | validation |
| 222 | `$db->perform(TABLE_ADDRESS_BOOK, …)` — the insert |
| 253 | redirect on success |
| **332** | `count(…) >= MAX_ADDRESS_BOOK_ENTRIES` → redirect |

Because the limit is checked last, it guards only the branch that renders a *blank* add
form; a valid POST inserts and redirects before reaching it. The plugin depends on that
ordering to let a multiship customer add a delivery address without the store owner
raising a store-wide setting.

This is the plugin's most fragile dependency. It fails closed — the page would redirect to
the address book rather than doing anything unsafe — but it would stop working, and it is
being relied on rather than promised.

---

## Part 2 — Requests

### 2.1 Login page moves the viewport away from the create-account section

**File:** `includes/modules/pages/login/on_load_main.js` — entire contents:

```js
document.loginForm.email_address.focus();
```

Read by `index.php` into `<body onload="…">`. In split-login mode the sign-in field sits
*below* the create-account section, so the browser scrolls past it on load. Split-login is
not niche: `tpl_login_default.php:17` selects it whenever `USE_SPLIT_LOGIN_MODE` is `True`
**or** PayPal Express Checkout is enabled.

A visitor redirected to log in therefore arrives with the create-account option — and any
`messageStack` output above it — already off screen, before they have seen either.

`tpl_login_default.php:44` adds `autofocus` to the same field, and only in the split-login
block. The tabbed layout at line 72, where the sign-in form comes first and focus would
cause no scroll, has none. The attribute is applied only where it does harm.

**Suggested change:**

```diff
-document.loginForm.email_address.focus();
+if (document.loginForm && document.loginForm.email_address) { document.loginForm.email_address.focus({preventScroll: true}); }
```

and remove `autofocus` at `tpl_login_default.php:44`. Both are needed; either alone leaves
the other mechanism scrolling.

`focus({preventScroll: true})` keeps the convenience of a focused field and stops only the
viewport movement. `autofocus` has no equivalent option, which is why it must go rather
than be tamed.

**Precedent already in core.** Four pages neutralise this same file with
`javascript:void(0);` — `create_account`, `checkout_shipping_address`,
`checkout_payment_address`, `address_book_process`. And the sibling
`time_out/on_load_main.js` already guards its call:

```js
if(document.login) document.login.email_address.focus();
```

The login page's copy has no such guard and throws if `document.loginForm` is absent.

### 2.2 `MAX_ADDRESS_BOOK_ENTRIES` cannot be adjusted by a plugin

A plugin has legitimate reason to raise the effective limit for a subset of customers — a
multiship order may go to a dozen recipients — but cannot, for two reasons.

**It is a constant.** Defined at breakpoint 40 (`init_db_config_read.php` →
`ConfigurationRepository::loadConfigSettings()`). The earliest a plugin observer can
attach is well after that, and racing core to `define()` it first would produce
"already defined" warnings.

**It is read in five places, only one of which is enforcement:**

| Location | Purpose |
|---|---|
| `address_book_process/header_php.php:332` | the block |
| `tpl_address_book_default.php:27,47` | limit notice, Add button |
| `tpl_checkout_shipping_address_default.php:27,31` | "create new" link |
| `tpl_checkout_payment_address_default.php:27,28` | same |
| `tpl_checkout_payment_default.php:41` | address controls |

So even a notifier at the enforcement point would leave four templates hiding the *Add*
link at the old value — a customer granted more addresses would be blocked by UI that
never heard about it.

**Request:** route the effective limit through one filterable value — a notifier, or a
`zen_get_max_address_book_entries()` helper used by all five call sites. That would also
remove the need to depend on the ordering described in §1.3.

### 2.3 Plugin stylesheets are unreachable when another plugin installs into the template

`html_header_css_loader.php:35` looks for a stylesheet named after the current page, and
`get_template_dir()` resolves it through `PageLoader::getTemplateDirectory()`. Plugin CSS
sits at **step 4**, behind the active template's own `css` directory at **step 3**.

Legacy-structure plugins install their files *into the active template*. One Page Checkout
ships a `login.css`, so on any store running OPC a `zc_plugin` cannot deliver a `login.css`
at all — not overridden, never loaded. This plugin wrote one, found it silently dead, and
removed it.

**Request:** consider a plugin CSS layer that loads *in addition to* the template's file
rather than instead of it — the pattern `InteractsWithPlugins::linkCatalogStylesheet()`
already implements for plugins that link their own. Same-named files from two independent
plugins are not really an override relationship.

### 2.4 Version-floor note (informational)

`PageLoader::listModulePagesFiles()` is `@since v2.2.0`. Plugins declaring support back to
v2.0.0 cannot rely on it, which rules out both `jscript_` and `on_load_` contributions to
core pages on v2.0.0 and v2.1.0. Not a defect — worth knowing when advising plugin authors
on a support floor.

---

## Summary

| # | Item | Kind | Severity |
|---|---|---|---|
| 1.1 | 24 notifiers present | dependency | satisfied |
| 1.2 | PageLoader plugin resolution | dependency | must not regress |
| 1.3 | `address_book_process` ordering | dependency | fragile — see 2.2 |
| 2.1 | Login focus scrolls page | request | accessibility |
| 2.2 | Address limit not filterable | request | blocks plugin capability |
| 2.3 | Plugin CSS unreachable | request | silent failure |
| 2.4 | `listModulePagesFiles` v2.2.0+ | note | informational |

2.1 stands alone and is the smallest, most defensible change. 2.2 is the one that would let
this plugin drop its most fragile dependency.
