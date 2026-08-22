# MultiShip v3.0.0 — Encapsulated Plugin Architecture

Target: a fully encapsulated Zen Cart `zc_plugin` supporting **v2.0.0 through v2.3**,
requiring **no core file changes** and **no store-template changes**.

Verified against the `zencart/zencart` **2.3** branch (HEAD `0db1041`, 2026-07-23).

---

## 1. Design intent

1. Installing the plugin *is* the decision to use it — no store-wide enable toggle.
2. The mod never activates unless the cart holds more than one shippable unit
   (either 2+ items, or one item with quantity > 1).
3. Only then is "ship to multiple addresses" offered, on a mod-owned interstitial
   reached when the customer clicks Checkout.
4. Choosing it sends unregistered customers to register first, then to the mod's
   address grid. Registered customers go straight there.
5. Checkout is **three steps**, the same count as an ordinary Zen Cart order.
6. Only one of those three is a mod page. Payment and confirmation stay core's,
   with the mod correcting what they say about delivery.

## 2. Decisions

| Decision | Choice |
|---|---|
| How the question is put | A mod-owned Yes/No interstitial on entry to checkout, asked once |
| Secondary hint | A cart-page `messageStack` notice, **only** where an express-checkout button can bypass the interstitial |
| Mod-owned pages | `multiship_choice`, `checkout_multiship`, `multiship_address` |
| Core-owned pages | `checkout_payment`, `checkout_confirmation`, `checkout_process`, `checkout_success` |
| Enable toggle | Dropped; installation implies enabled |
| Correctness guards | **Kept** (see §5) |

The offer must appear *before* login for requirement 4 to be meaningful, and it does:
core fires `NOTIFY_HEADER_START_CHECKOUT_SHIPPING` **ahead of its own login check**, so
an observer on that notifier can redirect to the interstitial while the customer is
still anonymous. An unregistered customer who answers "yes" is then sent to register;
one who answers "no" carries on into whatever checkout the store normally runs.

This supersedes an earlier reading recorded here, that `checkout_shipping` requires
login and therefore the cart page was "the only placement consistent with the design".
The premise was wrong about *when* the login check happens. The cart notice survives,
but only for the case the interstitial genuinely cannot cover — see §3.

### The three steps

| Step | Page | Owner |
|---|---|---|
| 1 | `checkout_multiship` — shipping method **and** an address per item | mod |
| 2 | `checkout_payment` | core |
| 3 | `checkout_confirmation` | core, delivery block replaced |

`multiship_choice` sits before step 1 and is not numbered, because it is a question
rather than a step. `multiship_address` is reached from step 1 and returns to it.

The shipping method used to be chosen on core's `checkout_shipping`, with a further
page to confirm it — five pages in total. Both questions moved onto the grid, which
was the point: the method has to be known before per-address quoting can happen, and
asking for it on the page that does the quoting removes two pages and a round trip.

`checkout_shipping` is consequently **no longer part of the multiship flow at all**.
An early-observer redirect sends anyone with multiship chosen from that page to the
grid, which also closes a hole: a customer could otherwise return to the cart, click
Checkout again, and reach payment with multiship chosen and no addresses assigned.

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

The cart-page notice avoids templates entirely: an observer on
`NOTIFY_HEADER_START_SHOPPING_CART` adds a message to the `shopping_cart` messageStack,
which every template renders (verified in `template_default`, `responsive_classic`,
and ZCA Bootstrap 3.8.0 `tpl_shopping_cart_default.php:33`).

That notice is now **conditional**, and the condition is the whole reason it still
exists. Once the question moved to an interstitial that every customer entering
checkout must pass, a second offer on the cart was redundant — for customers who
reach checkout by clicking Checkout. Express-checkout buttons do not: PayPal Express
on the cart page takes the customer straight to PayPal, past the interstitial, and
they never get asked. So the notice is raised only when such a button is present:

```php
$show_cart_notice = (defined('MODULE_PAYMENT_PAYPALWPP_STATUS') && MODULE_PAYMENT_PAYPALWPP_STATUS === 'True');
$this->notify('NOTIFY_MULTISHIP_CART_NOTICE_NEEDED', [], $show_cart_notice);
```

The notifier lets another express-checkout plugin claim the same treatment without
this plugin having to enumerate them. On a store with no express button the cart is
left alone entirely, which is the common case and the quieter one.

### The trap this plugin keeps falling into

Encapsulation silently kills any feature delivered by a file that core resolves **by
fixed name or hardcoded path**. Template resolution (above) is only the best-known
instance. Confirmed cases, all of which worked before encapsulation and silently
stopped afterwards:

| What broke | Why | Fix |
|---|---|---|
| Admin order detail's per-address products | `multiship_orders_products.php` included by nobody | `NOTIFY_ADMIN_ORDERS_CONTENT_UNDER_PRODUCTS` |
| Account order-history breakdown | `main_template_vars.php` resolved by fixed name | `NOTIFY_MAIN_TEMPLATE_VARS_END` |
| Confirmation-page breakdown | reached by overriding `tpl_checkout_confirmation_default.php` | own `jscript_` file, DOM replacement |
| `checkout_success` breakdown | reached by overriding `tpl_account_history_info_default.php`, which core's success page includes | own `header_php_` + `jscript_` pair |
| Edit Orders block | `$current_page` carries `.php`, `FILENAME_EDIT_ORDERS` does not | compare with `pathinfo(..., PATHINFO_FILENAME)` |

The failure mode is always the same and always quiet: no error, no log entry, just a
feature that is no longer there. **Anything the legacy plugin delivered by shipping a
file with a core-owned name has to be re-checked**, because nothing will announce that
it stopped working.

A second lesson, learned twice on the DOM-replacement fixes: an element id that is
right on one template is worth nothing on the next. Core's confirmation delivery block
is `#checkoutShipto`, ZCA's is `#deliveryAddress-card`; core's account-history block is
`#myAccountShipInfo` and ZCA's is something else again. The `checkout_success` script
therefore falls back to matching the *heading text*, taken from the same language
constant the template printed, and then to a chain of insertion points — because a
breakdown in an awkward place beats no breakdown at all on a receipt for an order that
is already paid for.

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
The same three tests are duplicated in `multiship_early_observer`, which is attached at
`[90]` and answers `NOTIFY_OPC_SET_DISABLED` when OPC fires it from its own constructor
at `[97]` — well before `isEnabled()` runs at `[130]`. Relying on `isEnabled()` there
would also clear the intent flag as a side effect, which is why the tests are repeated
rather than delegated.

That handler answers **two** questions, not one, and the second was missed at first:

```php
$on_a_multiship_page = isset($_GET['main_page']) && in_array(
    $_GET['main_page'],
    [FILENAME_CHECKOUT_MULTISHIP, FILENAME_MULTISHIP_ADDRESS],
    true
);
if ((!empty($_SESSION['multiship_chosen']) || $on_a_multiship_page) && !multiship::inExpressOrGuestCheckout()) {
    $p2 = true;
}
```

OPC decides once per request, before any page code runs. `checkout_multiship` sets the
intent flag *itself*, so on the first load of that page the flag is still unset when
this is asked, OPC stays enabled for the whole request, and the `new order()` a few
lines later fires `NOTIFY_ORDER_CART_AFTER_ADDRESSES_SET` — which OPC answers by writing
tax country and zone from `$_SESSION['sendto']`, in the middle of building an order
bound for somebody else. Hence the page test: a page that exists only because multiship
is happening is not somewhere OPC has any business being enabled, whatever the session
flag says at `[97]`. `multiship_choice` is deliberately excluded — a customer on the
interstitial has decided nothing yet.

### Two different shipping-module tests, deliberately

`offerAvailable()` — which decides whether to raise the question — tests
`MODULE_SHIPPING_INSTALLED`, configuration readable from anywhere:

```php
$has_shipping_modules = (defined('MODULE_SHIPPING_INSTALLED') && MODULE_SHIPPING_INSTALLED !== '');
```

`checkoutInitialize()` still uses `zen_count_shipping_modules()`, and that is correct
for *it*: by the time it runs the modules are instantiated, and it needs to know how
many actually loaded rather than how many are configured. The two are not
interchangeable, and the comment at the top of `offerAvailable()` says so, because the
copy in the other direction is exactly the bug §7 records.

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
- Checkout reduced from five pages to three (§2); `checkout_shipping` removed from the
  flow entirely, with an early-observer redirect closing the side door into payment
- Confirmation-page breakdown, delivered by DOM replacement rather than the template
  override the legacy version relied on
- `checkout_success` breakdown and delivery-address correction — a page the plugin had
  never touched, rebuilt from the database because the session is gone by then
- Admin order detail, invoice, packing slip and account order history all re-reached
  through notifiers after encapsulation silently broke each of them
- The Edit Orders block made to actually fire; it never had
- `MODULE_MULTISHIP_MAX_ADDRESSES` added, so a multiship order is not capped by the
  store-wide address-book limit
- The shipped `readme.html` rewritten for the encapsulated plugin

Outstanding:

- **Free shipping across a split is untested and may be a live defect.** See §8: each
  sub-order is judged against its own total, so a cart that qualifies may produce sub-orders
  that do not, and the customer is returned to the grid with every address flagged
  unshippable and no explanation. One order settles it, using the same setup as the no-ship
  test.
- ~~Two dead template files~~ — **done.** `tpl_checkout_shipping_multiship.php` and
  `tpl_checkout_confirmation_multiship_address.php` removed, along with the unreachable
  `NOTIFY_HEADER_START_CHECKOUT_SHIPPING` case in `multiship_observer` and the four
  language constants only it used.
- **Accessibility pass, deferred by decision** — deliberately postponed until the
  flow works end to end. To be set by dbltoe, whose field this is. Known items so far:

  **`multiship_choice` interstitial** — each button's help text sits in a sibling
  `div` with no programmatic association, so it is announced to sighted users only
  and needs `aria-describedby`. Also unresolved: whether the three actions should be
  a `fieldset`/`legend` group.

  **`docs/readme.html`** — now shipped inside the plugin and linked from the Plugin
  Manager listing, so it is a page store owners will actually open. Two Level A
  failures found by inspection:

  - ~~no `lang` attribute on `<html>`~~ — **fixed**, `lang="en"` added
  - still open: six `<h1>` elements, now in the sequence
    `h1 h1 h2 h2 h1 h1 h2 h2 h1 h2 h2 h1 h2 h2 h1` — the document has no single
    top-level heading and its outline does not nest (1.3.1 Info and Relationships).
    Fixing it means demoting every section heading and restyling `style.css` to
    match, which is why it was not folded into the content rewrite.

  Otherwise cleaner than expected: no images, so no missing `alt`; no tables; no
  "click here" link text; no inline styles; no `<font>` or `<center>`. Contrast and
  focus visibility still need checking against `docs/style.css` and `menu.css`, which
  a static read cannot settle, as does the keyboard behaviour of
  `back_to_top.min.js`.

  **Contrast target: 7:1 wherever achievable** (WCAG 2.2 AAA, 1.4.6 Contrast
  Enhanced), not the 4.5:1 of AA. This is the project standard set by dbltoe and
  applies to anything this plugin ships with colour of its own — currently the
  readme's stylesheets. The storefront pages deliberately inherit the store
  template's colours, so their contrast is the store's to answer for, not ours;
  that is a consequence of shipping almost no CSS and is the right trade, but it
  means the standard binds only where we actually choose colours.
- Retire the six legacy core-template overrides still parked at
  `includes/templates/YOUR_TEMPLATE/templates/`; confirm what, if anything, is
  still needed for `account_history` / `account_history_info`

## 7. Verification

Retitled: this section was headed "Not yet verified" when almost nothing had been run.
Most of what follows is now a record of what *was* verified and how. What genuinely
remains unverified is listed in §6.

All 53 PHP files pass `php -l` under **PHP 8.5.9**, matching the version on the test
site. The `lang.*.php` files were additionally *executed* under `E_ALL` and each returns
a well-formed `string => string` array with the same key count as the `define()`-based
file it replaced.

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

**What the plugin ships:**
`catalog/includes/modules/pages/login/jscript_multiship_login_focus.php`.

There are **two** causes, not one, and both scroll:

1. the `autofocus` attribute on the input, applied by the browser at parse time
2. `on_load_main.js`, read into the body's `onload` attribute

An earlier version of this fix — `on_load_multiship.js` — ran at body `onload`, which is
*after* both, and undid them with `blur()` and `scrollTo(0, 0)`. It worked, and it was
visibly wrong: the page arrived, jumped down, and jumped back. A correction the customer
can watch happen reads as a fault, not a fix. It has been removed.

The replacement loads in the **head**. `html_header_js_loader.php:52` gathers
`listModulePagesFiles('jscript_', '.php')` and `require`s each file inline, so it runs
before the form is parsed and can get in front of both causes:

- **`autofocus`** — a `MutationObserver` on `document.documentElement` catches the input
  the moment it is inserted and strips the attribute. Observer callbacks are microtasks;
  autofocus candidates are flushed during the rendering steps, which come later.
- **`on_load_main.js`** — an own `focus` property is assigned to that one element,
  shadowing the prototype method, so core's call is a no-op. Nothing else on the page is
  affected, and the property is deleted on a zero timeout after `load`, giving the field
  back for ordinary use.

The timeout is load-bearing. This script registers its `load` listener from the head, so
without deferring it would run *before* the body's own `onload` attribute and hand
`focus()` back just in time to be called.

**Scoped server-side**, on `$_SESSION['multiship']->isChosen()` and the customer not being
signed in — so the store's other shoppers keep core's behaviour untouched. That scoping
moved out of the DOM: the old file keyed off `.multishipLoginNotice` being present, which
is why that span used to be load-bearing and is now only a styling hook.

It degrades well. The problem is caused by scripting, so with JavaScript off there is no
focus to prevent. `listModulePagesFiles()` is `@since v2.2.0`, so on v2.0.0 and v2.1.0 the
file is never read — inert, not broken, and those stores keep the behaviour they have
today.

This is a stopgap. The real fix is upstream, and is a one-line change with precedent:
four core pages — `create_account`, `checkout_shipping_address`,
`checkout_payment_address`, `address_book_process` — already neutralise that same file
with `javascript:void(0);`, and the sibling `time_out/on_load_main.js` already carries the
`if (document.login)` guard that the login page's copy lacks. The recommended core change
is `focus({preventScroll: true})` plus removal of the `autofocus` attribute at
`tpl_login_default.php:44`, which keeps the convenience of a focused field without moving
the viewport. Once that lands, this file can be deleted.

### Add-address flow, verified live

Tested with the store's own limit left at its default of 5. A customer part-way through
a multiship order added a sixth and then a seventh delivery address through the plugin's
own page, saw the over-limit notice naming both counts, and returned to the grid with the
new addresses selectable. **The store owner changed no setting.**

Also confirmed: two entries for the same person — a PO box and a street address — are
distinguishable in the Send To dropdown, because it labels options with
`zen_address_label()`, the full formatted address, rather than the name alone.

## 8. Shipping: what works, what does not

### One method for the whole order — a real limitation

Multiship applies a **single** shipping method to every sub-order, because
`$_SESSION['shipping']['id']` is one value chosen once, on the address grid, before any
address has been quoted.

(`orders_multiship` has no shipping-method column either, which used to be cited here as
part of the difficulty. It is not — see below. The method is already recorded per sub-order
as the title of that sub-order's `ot_shipping` row.)

That is wrong for a catalogue where different items need different carriers. A telescoping
flag pole may only go UPS while a flag could go USPS. Today the customer picks USPS,
USPS declines to quote for the sub-order containing the pole, `addressValidation()` flags
that address, and they get the no-ship icon telling them the address is not supported by
the selected method. Detected and surfaced — but their only remedies are to move the item
or put the *whole* order on UPS.

Worth being precise about where the waste actually is. Items going to the **same** address
travel in the same parcel, so one carrier for them is correct, not wasteful. The loss only
appears **across** addresses: a flag going to one address could have used USPS while the
pole going to another needed UPS.

The proper fix is a shipping method **per sub-order**, since the constraint is "this method
must carry everything going to this address".

### The per-address fix is much smaller than this document used to claim

The paragraph that stood here said the fix needed a shipping-method column on
`orders_multiship`, rework of `getShippingId()`, `checkoutInitialize()` and order creation,
and was "a version's work, not a patch". That was written without checking what the data
model already carries. It is wrong, and wrong in the direction that stops someone attempting
it.

What is already in place:

- `checkoutInitialize()` **already** builds a fresh `shipping` object per address and
  **already** composes a per-address method title at line 1171:
  `$shipping_quote[0]['module'] . ' (' . $shipping_quote[0]['methods'][0]['title'] . ')'`
- `orders_multiship_total` stores `title`, `text`, `value`, `class` **per sub-order**, so the
  method name and its cost are already recorded per address. No new column is needed.
- the confirmation, order-history and checkout_success pages already render those per-address
  totals, so per-address methods would display correctly with no template work
- core's `shipping::quote($method = '', $module = '')` already supports *all methods of one
  module* and *all methods of all modules*
- `getShippingId()` has exactly one caller, inside an `isSelected()` block in
  `header_php_checkout_shipping_multiship.php` — on a page multiship customers are now
  redirected away from before it runs. It needs no rework because nothing reaches it.

The remaining work is the selection policy and what to put in the main order's single
`orders.shipping_method` column. Twenty-five lines and two decisions, not a version.

### The design to build (dbltoe)

**Do not ask the customer for a method at all. Quote every module against each address and
take the cheapest that answers.**

The elegance is that constraint satisfaction falls out for free. A carrier that cannot take
the consignment returns nothing, and core drops it silently:

```php
$quotes = $GLOBALS[$quoting_module]->quote($method);
…
if (!empty($quotes) && is_array($quotes)) { $quotes_array[] = $quotes; }
```

So the flag pole eliminates USPS by arithmetic rather than by a special case, and the flag
travelling to the same address goes UPS with it. One quote, one carrier, one `ot_shipping`
row per address — nothing downstream changes.

This also settles a question that had been posed the wrong way round. An earlier sketch here
proposed *partitioning* an address's items into separate consignments — the pole by UPS, the
flag by USPS, using core's `product_ships_in_own_box` as the partition rule. That optimises
the wrong quantity: two consignments means paying two base rates, and one dearer parcel
usually beats two cheaper ones. dbltoe's "only one method winning out per address" is both
the simpler implementation and, in most carts, the cheaper answer for the customer.

Two things have to come with it:

1. **The exclusion setting becomes a prerequisite, not an interim step.** Cheapest-across-
   everything picks Store Pickup or Free Shipping for every address otherwise. Store Pickup
   should arguably be excluded from multiship unconditionally — a customer cannot collect
   three parcels addressed to three other people.
2. **`orders.shipping_method`** is one column and there is no longer one answer. Probably
   "Multiple methods", with the truth in the per-address rows that already hold it.

And one dependency outside this plugin's control: **it works exactly as well as the installed
modules' willingness to decline.** `shipping.php` never reads dimensions — `products_length`,
`products_width`, `products_height` and `product_ships_in_own_box` are stored per product and
exposed per cart line by `shopping_cart::get_products()` at lines 1440-43, but the shipping
*class* is weight-only and whether a module honours dimensions is its own business. A module
that prices a ten-foot pole on weight alone eliminates nothing, and the customer buys a label
the carrier will reject.

### Free shipping across a split — probably a live defect

Not a future concern. `freeoptions.php:96` qualifies on

```php
$cart_total = $_SESSION['cart']->show_total();
```

and `checkoutInitialize()` swaps `$_SESSION['cart']->contents` for one address's share before
quoting, so each sub-order is judged against **its own** total rather than the cart's.

The reachable scenario: a store offers free shipping over $50, the customer's cart is $60 and
the cart page says so, they split it two ways at $30 each, neither sub-order reaches the
threshold, `freeoptions` returns nothing for either, and **every address flags as
unshippable**. They are returned to the grid with warning icons and no explanation.

Whether a split order *should* keep free shipping is a policy question — the store is now
paying for two parcels, so charging is defensible. The current outcome is not a policy. It is
a dead end with no message, and it is the same shape as the quantity-discount loss recorded
in section 7: a figure shown on the cart page that does not survive the split.

Untested. One order settles it, and it costs the same setup as the no-ship test.

### Heavy sub-orders are handled, by core

A concern that turns out to be already solved. `includes/classes/shipping.php:214-217`
splits a consignment into multiple boxes once it exceeds `SHIPPING_MAX_WEIGHT`:

```php
if ($shipping_weight > SHIPPING_MAX_WEIGHT) { // Split into many boxes
    $zc_boxes = zen_round(($shipping_weight / SHIPPING_MAX_WEIGHT), 2);
    $shipping_num_boxes = ceil($zc_boxes);
    $shipping_weight = $shipping_weight / $shipping_num_boxes;
}
```

That last line is the one to notice: the total is divided evenly by the box count.

`checkoutInitialize()` creates a fresh `shipping` object per address (line 1148) against
that sub-order's own weight, so three items totalling 72 lb going to one address are boxed
independently of everything else in the order. Per-address quoting makes this work rather
than breaking it — and it is more accurate than a single-address order, where the whole
cart weight is boxed together.

**With one caveat this section originally missed.** That calculation is pure arithmetic on
the *total* weight and knows nothing about item boundaries. A single indivisible 80 lb item
against a 70 lb maximum becomes "two boxes of 40 lb" — a quote for a shipment that cannot
physically exist. Nothing splits an item in half.

That is true of an ordinary single-address order too, so it is a core limitation rather than
one this plugin introduces. Multiship only makes it easier to notice, by quoting each address
separately. The cheapest-per-address design above would improve it without fixing it: a
carrier that refuses the weight drops out and another wins, which is the right outcome by
accident rather than by understanding.

### Outside the plugin's reach

If a store owner has not entered product weights, or has not set dimensions, shipping is
wrong — for every order, not only multiship ones. Nothing here can compensate for absent
data, and it should not try: a guessed weight is worse than a visibly wrong one.

Multiship does make bad data **more visible**, since a wrong weight is quoted separately
against each address rather than being absorbed into one total. That is a diagnostic, not a
defect.

**Surfacing it is fair game, though, and cheap** (dbltoe). Not compensating for the data —
reporting on it:

1. A count on the plugin's own configuration page. "You have 47 products with no weight" is
   worth more than the three paragraphs currently in `STORE-OWNER-NOTES.md`, and it is one
   `COUNT(*)`.
2. A read-only admin report listing products with `products_weight = 0` or null dimensions,
   each linking to its product editor. One page, one query.

Both stay on the right side of the line above, because neither guesses at a value. The line
is worth naming, since it is where this would stop: a **bulk editor** for product data does
not belong here. Editing weights is a general Zen Cart admin need with nothing to do with
shipping to several addresses, and adopting it is how a focused plugin acquires a second job.

### The multiship path itself, now verified live

Superseding an earlier note here that none of it had been exercised. Orders have been
placed end to end on the test store, against live USPS quoting, with One Page Checkout
installed. What the multiship log shows for a representative three-address order:

```
(checkout_multiship) Initialize step 1 (33) ... Quote received: 20.90
(checkout_multiship) Initialize step 1 (26) ... Quote received: 16.40
(checkout_multiship) Initialize step 1 (28) ... Quote received: 12.25
(checkout_process)   ot_shipping 49.55, ot_subtotal 119.97, ot_total 169.52
```

20.90 + 16.40 + 12.25 = 49.55, and that figure is identical at every stage from the
grid through payment, confirmation and `createOrderFixupTotal`. Confirmed alongside it:
per-address quoting, addresses persisting across the grid, OPC standing down for the
session, `orders_multiship` and `orders_multiship_total` written correctly, and the
admin loop — detail, invoice, packing slip, per-sub-order status change and its email.

This mattered more than a normal regression check. The recalculation moved pages when
the flow went from five steps to three, so the split producing *wrong money* was the
failure that outranked everything cosmetic. It does not.

### Quantity discounts across a split — found, fixed, confirmed

The last money path, and it was broken. Four of a product carrying a 10% tier at
quantity three, split across two addresses: the cart showed 129.56 and the order
charged 143.96.

`checkoutInitialize()` prices each address by replacing the cart with that address's
share, and Zen Cart prices a line with
`zen_get_products_discount_price_qty($prid, $qty)`, which needs a tier row with
`discount_qty <= $qty`. Two lots of two reach nothing. No split avoids it — 3 + 1
discounts only the three — because the discount belongs to the cart and a sub-order
does not know the cart exists.

The first answer proposed here was to refuse multiship for such carts, alongside the
coupon and group-pricing guards of §5. That was wrong, and dbltoe was right to push
back on it. The unit prices are captured in `saveOrdersBaseValues()` while the cart is
whole and restored during the per-address builds through
`NOTIFIER_CART_GET_PRODUCTS_END`, which passes the assembled line array by reference
after pricing and before `order::cart()` reads it.

`price` is the field corrected rather than `final_price`, because `order::cart()` does

```php
$products_final_price_without_tax = $products[$i]['price'] + attributes_price(...);
```

so the subtotal *and* the per-address tax both follow from it. Tax genuinely is
per-address and stays that way — it is simply computed on the right price. That is
what turned an assumed rewrite into about seventy lines.

Verified live after the fix. Confirmed present on branches 2.0, 2.1, 2.2, 2.3 and
master: both the notifier and `order.php` reading `$products[$i]['price']`. Note that
`v2.3.0` is a *branch*, not a tag — tags stop at 2.2.2 — so a tag-only check misses
the very version this plugin was developed against.

Gated twice, and inert everywhere else: it acts only while `initialization_active` is
set and only for products captured from the whole cart, both cleared before
`checkoutInitialize()` returns or redirects. The ordinary `get_products()` calls that
every page makes see nothing.

The v2.0.0 installer API question *has* been resolved: v2.0.0 ships only
`executeInstallerSql()` and `$this->dbConn` — the `ScriptedInstallHelpers` trait
arrived in v2.0.1 — and its `executeUpgrade()` takes no argument, where v2.0.1+
passes the previous version. The installer therefore uses raw SQL throughout and
declares `executeUpgrade($oldVersion = null)`, which is signature-compatible with
both. `zen_register_admin_page`, `zen_deregister_admin_pages` and
`queryFactory::prepare_input` were each confirmed present at v2.0.0 and at 2.3.
