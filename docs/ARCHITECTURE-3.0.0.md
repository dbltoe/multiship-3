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
| How the question is put | A mod-owned Yes/No interstitial on entry to checkout, asked once |
| Secondary hint | An optional offer on the cart page via `messageStack` |
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

### Why the question is a page, not a modal

A dimming Yes/No overlay was considered and rejected. It would need JavaScript and CSS
injected into a page the plugin does not own, which is possible only through
`PageLoader::listModulePagesFiles()` — `@since v2.2.0`, and **absent at v2.0.0**, so it
would have broken the declared support floor. Worse, "blocks until answered" means a
script that fails to initialise blocks checkout altogether, turning a missed offer into
a lost sale. A modal would also need focus trapping, ESC handling and ARIA to be usable,
and would have to survive the store template's own modal machinery and z-index stack.

The interstitial has none of those costs: a plain form, two submit buttons, no scripting,
accessible by default, and — being a mod-owned page — it always wins template resolution.
Its only price is one extra click, and only on carts that qualify.

## 4. Why no core files are needed

All 24 notifiers the two observers attach to are emitted by 2.3 core. The five that
the legacy plugin used to inject by shipping patched core files —
`NOTIFY_ADMIN_ORDERS_UPDATE_ORDER_START`, `NOTIFY_ADMIN_ORDERS_EXTRA_STATUS_INPUTS`,
and `NOTIFY_OT_SHIPPING_TAX_CALCS` among them — have all been upstreamed. The
`zc155/` and `zc156/` patch trees were removed accordingly.

## 4a. One Page Checkout

Most stores now run lat9's One Page Checkout rather than the 3-step flow, and a
multiship order cannot be expressed on a single page. When multiship is active for
an order, OPC is therefore bypassed completely.

Verified against One Page Checkout **v2.6.3** (`lat9/one_page_checkout`, HEAD
`258f15b`, 2026-07-21):

- `OnePageCheckout::checkEnabled()` fires `NOTIFY_OPC_SET_DISABLED` and any
  observer setting `$set_disabled = true` forces `isEnabled = false`
  (`includes/classes/OnePageCheckout.php:159-164`). This is a supported extension
  point: neither OPC nor Zen Cart core needs modifying.
- OPC's observer redirects **all three** core checkout steps to `checkout_one`
  (`class.checkout_one_observer.php:271-282`).

That second point is why the disable observer is mandatory rather than optional.
The design in §2 hands the payment step back to core; with OPC enabled, entering
core `checkout_payment` fires `NOTIFY_HEADER_START_CHECKOUT_PAYMENT` and redirects
the customer into one-page checkout mid-order, losing the multiship context.

The flag driving the observer is **per-session, not global**: OPC is disabled only
for orders where the customer chose multiship, so installing this plugin does not
degrade checkout for every other order. The flag must be cleared by the existing
`sessionCleanup()` so that a customer who backs out gets OPC again. If OPC is not
installed the notifier never fires and the observer is inert.

OPC also hooks `NOTIFY_HEADER_START_SHOPPING_CART`, but only to call
`saveOrdersSendtoAddress()` — it does not redirect, so the cart-page offer of §3
coexists with it.

## 5. Guards that must survive

`isEnabled()` disables the mod for group-pricing customers and when a coupon is
applied. These are **tax-calculation correctness guards**, not user preferences,
and must be retained even though the enable toggle is dropped.

It also disables the mod for **guest and express-checkout sessions** — COWOA, One
Page Checkout guest checkout, and PayPal Express Checkout (`$_SESSION['COWOA']`,
`customer_guest_id`, `paypal_ec_token`). Multiship requires a registered account:
a guest flow leaves no account for the per-address sub-orders, and Express Checkout
fixes a single delivery address from the PayPal account that cannot be reconciled
with per-item addresses.

OPC's own `guestCheckoutEnabled()` is gated on the same `isEnabled` flag the §4a
bypass clears, so a multiship order could not be taken through OPC guest checkout
in any case; this guard covers guest flows that reach the store by other routes,
and makes the restriction an invariant rather than a property of the offer alone.
The same three tests are duplicated in `multiship_opc_observer`, because it answers
at `[90]` while `isEnabled()` does not run until `[130]`.

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

- The mod-owned confirmation page; the legacy version relied on core template
  overrides that are no longer shipped
- **Accessibility pass, deferred by decision** — deliberately postponed until the
  flow works end to end. One known defect already: on the `multiship_choice`
  interstitial each button's help text sits in a sibling `div` with no
  programmatic association, so it is announced to sighted users only and needs
  `aria-describedby`. Also unresolved: whether the three actions should be a
  `fieldset`/`legend` group. To be set by dbltoe, whose field this is.
- Retire the six legacy core-template overrides still parked at
  `includes/templates/YOUR_TEMPLATE/templates/`; confirm what, if anything, is
  still needed for `account_history` / `account_history_info`

## 7. Not yet verified

All 40 PHP files pass `php -l` under **PHP 8.5.9**, matching the version on the
test site. The five `lang.*.php` files were additionally *executed* under `E_ALL`
and each returns a well-formed `string => string` array with the same key count as
the `define()`-based file it replaced (23 / 12 / 11 / 19 / 18).

### Verified on a live store

Tested 2026-07-28 on Zen Cart with PHP 8.5 and an **empty** `DB_PREFIX`, with One
Page Checkout also installed. Install and uninstall both completed with no errors
and nothing written to the logs.

After install:

- `orders_multiship` and `orders_multiship_total` created
- `orders_products.orders_multiship_id` added as `int(11) NOT NULL default '0'`
- `MODULE_MULTISHIP_PAYMENT_METHODS` and `MODULE_MULTISHIP_DEBUG` present, both
  attached to a real configuration group (id 57, i.e. not the 0 that the
  `getConfigurationGroupId()` guard exists to prevent)
- the Configuration group rendered, confirming the `configMultiship` admin page
  registered, and both settings were editable

After uninstall, both tables, the column and both configuration keys were gone.

This also confirms two branches that could not be checked statically: `DB_PREFIX`
is honoured (the tables were created unprefixed to match the store), and
`information_schema` is readable on the host, so `columnExists()` correctly drove
both the `ADD` and the `DROP` of the column.

### Storefront entry, verified live

Tested 2026-07-28/29 on the same store, template `bootstrap` (ZCA Bootstrap 3.8.0),
with One Page Checkout v2.6.3 installed and its guest checkout enabled:

- the trigger behaves as specified — one item does not offer, two items does
- the interstitial appears on entry to checkout, ahead of any login prompt
- all three answers work: multiship, single address, and back to shopping
- declining continues into OPC's one-page checkout with guest checkout intact,
  confirming the bypass is scoped to the session and does not affect normal orders
- returning to the cart after declining reopens the question

Also verified after PayPal Express Checkout was enabled on the test store:

- the cart-page notice appears once the cart qualifies, rendering as a blue
  `alert-info` with a question-mark icon under ZCA Bootstrap, which maps `caution`
  that way in `zca_message_stack.php`
- clicking PayPal Express and then abandoning it no longer disables multiship

Two bugs worth recording, since both mistakes are easy to repeat. `offerAvailable()`
originally tested `zen_count_shipping_modules()`, copied from `checkoutInitialize()`.
That function counts shipping modules **already instantiated as globals**, not modules
installed. The shipping class is not created until line ~100 of the checkout_shipping
header, while the interception runs from the notifier on line 11, and the cart page
never instantiates them at all — so it returned 0 in every context this plugin uses,
and the offer could never appear. It now tests `MODULE_SHIPPING_INSTALLED`, which is
configuration and readable anywhere.

The guest/express guard originally tested `$_SESSION['paypal_ec_token']` on its own.
That value is set the moment a customer clicks the Express Checkout button on the cart
page and **survives them abandoning it**, so glancing at PayPal and coming back left
multiship disabled for the rest of the session — and because `isEnabled()` calls
`sessionCleanup()` whenever it disables, every later page load also wiped any multiship
selection already made. It now requires `paypal_ec_token`, `paypal_ec_payer_id` and
`paypal_ec_payer_info` together, matching `OnePageCheckout::checkEnabled()`; the latter
two only exist once the customer has returned from PayPal authorised. The test lives in
`multiship::inExpressOrGuestCheckout()`, static so the early observer can call it at
`[97]` before the session object exists at `[130]`, and so both callers apply one test
rather than two hand-copied approximations.

### Known limitation: the login page scroll position

A customer who chooses multiple addresses is sent to log in, and the login page arrives
scrolled past the create-account section — the very section they need. The cause is core
JavaScript, `includes/modules/pages/login/on_load_main.js`:

```js
document.loginForm.email_address.focus();
```

Removing the `autofocus` attribute from the template does not help, because the scripted
focus runs regardless.

CSS cannot deliver a fix. A plugin stylesheet resolves at step 4 of the template lookup,
behind the active template's own `css` directory at step 3, and One Page Checkout installs
its own `login.css` into the template — so on any store running OPC, a plugin `login.css`
never loads. A `login.css` was written and then removed for exactly that reason.

**What the plugin ships instead:** `catalog/includes/modules/pages/login/on_load_multiship.js`.

`index.php` builds the `<body onload="...">` attribute from `on_load_*.js` files gathered
by `listModulePagesFiles('on_load_', '.js')`, which merges the core page directory with
plugin page directories. Plugin contents are appended after core's, so this runs *after*
`document.loginForm.email_address.focus()` and undoes it:

```js
if (document.querySelector('.multishipLoginNotice') && document.loginForm && document.loginForm.email_address) { document.loginForm.email_address.blur(); window.scrollTo(0, 0); }
```

It **blurs as well as scrolls**. Scrolling alone would leave focus in a field the visitor
cannot see, which is worse than the original problem for a keyboard or screen-reader user.
Blurring returns the page to the state an ordinary page load would produce.

Three constraints govern that file, and breaking any of them corrupts every login page:

- **No double quotes** — the contents are emitted inside `onload="…"`.
- **No `//` comments** — files are concatenated, so a line comment would silently disable
  whatever followed it. A single-line `/* … */` is safe.
- **Filename must match `^on_load_`**, the pattern `listModulePagesFiles` searches for.

Scoped to `.multishipLoginNotice`, so ordinary logins are untouched. It degrades well: the
problem is caused by JavaScript, so with scripting disabled there is no focus to undo and
nothing to fix. `listModulePagesFiles()` is `@since v2.2.0`, so on v2.0.0 and v2.1.0 the
file is simply never read — inert, not broken.

This is a stopgap. The real fix is upstream, and is a one-line change with precedent:
four core pages — `create_account`, `checkout_shipping_address`,
`checkout_payment_address`, `address_book_process` — already neutralise that same file
with `javascript:void(0);`, and the sibling `time_out/on_load_main.js` already carries the
`if (document.login)` guard that the login page's copy lacks. The recommended core change
is `focus({preventScroll: true})` plus removal of the `autofocus` attribute at
`tpl_login_default.php:44`, which keeps the convenience of a focused field without moving
the viewport. Once that lands, this file can be deleted.

### Still unverified

The multiship path itself. Choosing multiple addresses, assigning them per item,
per-address shipping quotes, OPC actually standing down for a chosen multiship
order, order creation, and the confirmation page — which is not built yet.

The v2.0.0 installer API question *has* been resolved: v2.0.0 ships only
`executeInstallerSql()` and `$this->dbConn` — the `ScriptedInstallHelpers` trait
arrived in v2.0.1 — and its `executeUpgrade()` takes no argument, where v2.0.1+
passes the previous version. The installer therefore uses raw SQL throughout and
declares `executeUpgrade($oldVersion = null)`, which is signature-compatible with
both. `zen_register_admin_page`, `zen_deregister_admin_pages` and
`queryFactory::prepare_input` were each confirmed present at v2.0.0 and at 2.3.
