# MultiShip v3.0.0 — Encapsulated Plugin Architecture

Target: a fully encapsulated Zen Cart `zc_plugin` supporting **v2.0.0 through v2.3**,
requiring **no core file changes** and **no store-template changes**.

Verified against the `zencart/zencart` **2.3** branch (HEAD `0db1041`, 2026-07-23).

---

## 1. Design intent

1. Installing the plugin *is* the decision to use it — no store-wide enable toggle.
2. The mod never activates unless the cart holds more than one shippable unit
   (either 2+ items, or one item with quantity > 1).
3. Only then is "ship to multiple addresses" offered, on the **shopping cart page**.
4. Choosing it sends unregistered customers to register first, then into the
   mod's **own** checkout pages. Registered customers go straight there.
5. All multiship-specific work happens in the mod's own pages.
6. On confirmation, control returns to the standard Zen Cart checkout process.

## 2. Decisions

| Decision | Choice |
|---|---|
| Offer placement | Shopping cart page only |
| Offer mechanism | `messageStack` — no template file involved |
| Mod-owned pages | Shipping selection + confirmation |
| Core-owned pages | `checkout_payment`, `checkout_process`, `checkout_success` |
| Enable toggle | Dropped; installation implies enabled |
| Correctness guards | **Kept** (see §5) |

`checkout_shipping` already requires login, so the offer must appear *before* login
for requirement 3 to be meaningful. That is why the cart page is the only placement
consistent with the design.

## 3. Why this needs no template changes

`PageLoader::getTemplateDirectory()` resolves in this order:

1. active template / page-specific directory
2. `template_default/<page>`
3. **active template's `templates/` directory**
4. **plugin templates** — `zc_plugins/<name>/<ver>/catalog/includes/templates/default/<dir>/`
5. `template_default/templates`

Plugin templates beat `template_default` (5) but lose to the active template (3).
That is fatal for *overriding* a core template — ZCA Bootstrap 3.8.0 overrides all
six templates the legacy mod patched — but irrelevant for pages the mod **owns**,
because no template carries a copy of a page it has never heard of. Step 3 finds
nothing and the plugin always wins at step 4.

Page modules resolve the same way: `PageLoader::findModulePageDirectory()` searches
`zc_plugins/<name>/<ver>/catalog/includes/modules/pages/<mainPage>`, and
`listModulePagesFiles()` merges plugin page modules with core ones.

The cart-page offer avoids templates entirely: an observer on
`NOTIFY_HEADER_END_SHOPPING_CART` adds a message to the `shopping_cart` messageStack,
which every template renders (verified in `template_default`, `responsive_classic`,
and ZCA Bootstrap 3.8.0 `tpl_shopping_cart_default.php:33`).

## 4. Why no core files are needed

All 24 notifiers the two observers attach to are emitted by 2.3 core. The five that
the legacy plugin used to inject by shipping patched core files —
`NOTIFY_ADMIN_ORDERS_UPDATE_ORDER_START`, `NOTIFY_ADMIN_ORDERS_EXTRA_STATUS_INPUTS`,
and `NOTIFY_OT_SHIPPING_TAX_CALCS` among them — have all been upstreamed. The
`zc155/` and `zc156/` patch trees were removed accordingly.

## 5. Guards that must survive

`isEnabled()` currently disables the mod for group-pricing customers and when a
coupon is applied. These are **tax-calculation correctness guards**, not user
preferences, and must be retained even though the enable toggle is dropped.

The existing trigger in `class.multiship.php` already implements §1.2 exactly:

```php
$this->can_offer = ($this->cartPhysicalItemsCount() > 1 && empty($_SESSION['COWOA'])
    && empty($_SESSION['customer_guest_id']) && zen_count_shipping_modules() > 0);
```

`cartPhysicalItemsCount()` sums quantity across physical items only, so virtual and
downloadable products are already excluded, as are COWOA and PayPal-guest checkouts.

## 6. Status

Done:

- PHP 8.x compatibility fixes (dynamic properties, `E_USER_ERROR`, ungated `explode()`)
- Language files converted to array-based `lang.*.php`
- Obsolete core patches removed (`zc155/`, `zc156/`)
- Restructured to `zc_plugins/MultiShip/v3.0.0/` with `manifest.php`
- `Installer/ScriptedInstaller.php`, replacing `init_multiship_install.php`,
  `init_multiship_upgrade.php` and `uninstall_multiship.sql`; `init_multiship.php`
  reduced to per-request admin behaviour only
- `MODULE_MULTISHIP_ENABLE` retired; `isEnabled()` now starts enabled and only the
  correctness guards of §5 can disable it

Outstanding:

- Mod-owned shipping + confirmation pages, and the flow into and out of them
- Cart-page offer observer
- Retire the six legacy core-template overrides still parked at
  `includes/templates/YOUR_TEMPLATE/templates/`; confirm what, if anything, is
  still needed for `account_history` / `account_history_info`

## 7. Not yet verified

All 40 PHP files pass `php -l` under **PHP 8.5.9**, matching the version on the
test site. The five `lang.*.php` files were additionally *executed* under `E_ALL`
and each returns a well-formed `string => string` array with the same key count as
the `define()`-based file it replaced (23 / 12 / 11 / 19 / 18).

That is the limit of what local tooling proves. Syntax is verified; **behaviour is
not**. Nothing has been run inside a Zen Cart request: the SQL in the installer has
never executed, no notifier has fired, and the plugin has never been installed.
All of §3 and §4 remains static analysis of the 2.3 source and must be confirmed
against a running store before release.

The v2.0.0 installer API question *has* been resolved: v2.0.0 ships only
`executeInstallerSql()` and `$this->dbConn` — the `ScriptedInstallHelpers` trait
arrived in v2.0.1 — and its `executeUpgrade()` takes no argument, where v2.0.1+
passes the previous version. The installer therefore uses raw SQL throughout and
declares `executeUpgrade($oldVersion = null)`, which is signature-compatible with
both. `zen_register_admin_page`, `zen_deregister_admin_pages` and
`queryFactory::prepare_input` were each confirmed present at v2.0.0 and at 2.3.
