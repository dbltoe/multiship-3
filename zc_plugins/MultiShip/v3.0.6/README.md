# Multiple Ship-To Addresses

Version 3.0.6 · Zen Cart 2.0.0 through 3.0.0 · PHP 8.0 through 8.5 · GPL v3 · by My Zen Cart Host (dbltoe)

One order, two or more delivery addresses: each address quoted, taxed, tracked and status-updated on its own, while the customer pays once.

## Overview

This plugin lets a customer send the individual products in one order to two or more different addresses. The order is split into per-address sub-orders, each quoted, taxed, tracked and status-updated independently, while the customer pays once.

**No core files are changed, and no template files are overwritten.** The plugin is an encapsulated `zc_plugin`: everything lives under `/zc_plugins/MultiShip/`, it installs and uninstalls from the admin's Plugin Manager, and a Zen Cart upgrade cannot overwrite any of it because none of it sits in Zen Cart's own directories.

The practical consequences for you:

- Nothing to merge, and no `YOUR_TEMPLATE` or `YOUR_ADMIN` directories to rename.
- Your template is untouched. The plugin's pages adopt your store's own styling rather than imposing their own — structure and behavior come from the plugin, appearance comes from your template. **That care stops at the inbox.** A multiship order also notifies the customer, and it does so through Zen Cart's own order emails rather than any of its own — so those emails carry the stock `/email/header.jpg` banner Zen Cart ships with, unless you have replaced it with your own artwork. A customer who has just come through pages that look like your store then opens mail that does not. Worth reading *The image at the top of your emails*, below, before you go live, because this plugin sends more of that mail than your store does now — one per parcel, not one per order.
- Uninstalling really does remove it.

Developed and tested against Zen Cart 2.3 with the ZCA Bootstrap template, and declared compatible with Zen Cart 2.0.0 through 3.0.0. Version 3.0.4 adds 3.0.0 after a complete two-address order was placed on a Zen Cart 3.0.0 test store.

## Usage Notes

This plugin changes how an order is built, so it has to coexist carefully with anything else that does. As of v3.0.0 most of those interactions are handled for you rather than left as a warning:

1. **One Page Checkout** — handled automatically. Once a customer chooses multiple addresses, One Page Checkout is switched off for that session and the customer follows this plugin's checkout instead. A customer who declines it keeps One Page Checkout exactly as normal, so both can be installed on the same store.
2. **Edit Orders** — blocked, with a message. Opening a multiship order in *Edit Orders* returns the admin to the order's normal detail page and explains why: editing it there would destroy the record of which items went to which address. Orders that are *not* multiship are unaffected. What is blocked is restructuring the line items, not editing the order: the order's own *Details* page works exactly as it always has, so status changes, comments and customer notifications are all still available there — and this plugin adds per-address status updates to that same page, so each parcel can be advanced independently.
3. **Guest checkout and PayPal Express Checkout** — multiple addresses are never offered because they require a registered account with a saved address book, so the question is simply not asked.
4. **Discount coupons** — not offered on a multiship order. A coupon cannot be apportioned across sub-orders without producing totals that do not add up, so `ot_coupon` is removed from checkout for those orders only.
5. **Group pricing** — customers in a group-pricing group are not offered multiple addresses, for the same reason.
6. **Free shipping** — *not* handled, and worth understanding before you enable both. A multiship order is quoted one address at a time, and every free-shipping threshold is measured against the part of the order going to that address rather than against the whole order. A $60 cart that earns free shipping over $50 stops earning it the moment the customer splits it into two $30 halves. What the customer sees depends on which kind of free shipping you run. The *Free Options* and *Free Shipping* shipping modules decide when the order is quoted, so if one of those is the method the customer picked, the split produces no quote at all: the addresses are marked with a warning and checkout will not continue until they choose a different method. The *Allow Free Shipping* setting inside the *Shipping* order-total module decides later, after the quote, so nothing is blocked — the order simply completes with shipping charged on each address, even though the shopping-cart page had said shipping was free. That second one is the one to watch, because it costs the customer money quietly. If you offer free shipping over a threshold and you enable multiple addresses, either say so in your shipping policy or set the threshold low enough that a split order still clears it. Or judge the whole order instead: Multiple Ship-To Addresses Pro (https://www.myzencartzone.com/), a paid companion to this plugin, applies the threshold to the full cart and takes the shipping off every address when it passes — and picks the cheapest carrier for each address while it's at it.
7. **Specific payment methods** — if a payment module cannot cope with a split order, name it under *Configuration → Multiple Ship-to Addresses → Unsupported Payment Methods* and it will be hidden for multiship orders only.

Still untested against this version, and worth asking about before relying on them: *Super Orders*, *Ty Package Tracker*, and *Fast and Easy Checkout*. If you have a question about another plugin's compatibility, please ask on the support thread (see Support, below).

## Zen Cart Admin Interfaces

Installing the plugin from the Plugin Manager creates two tables in your Zen Cart database:

1. **orders_multiship** — the delivery address for each sub-order.
2. **orders_multiship_total** — the order-total values for each sub-order.

It also adds one column to your existing *orders_products* table, recording which sub-order each product line belongs to, and registers the `invoice_multiship` and `packingslip_multiship` pages plus its own configuration menu entry.

### Settings

Under *Configuration → Multiple Ship-to Addresses*, or click **Settings** beside the plugin in *Modules → Plugin Manager* once it is installed:

1. **Unsupported Payment Methods** — a comma-separated list of payment modules to hide when an order is going to several addresses. Empty by default, meaning every payment method is offered.
2. **Maximum Delivery Addresses** — how many addresses one multiship order may spread across. Defaults to 10. This is deliberately separate from *Maximum Address Book Entries* under *Customer Details*, which is left alone; set this no lower than that store-wide limit.
3. **Enable debug?** — writes a trace to `/logs/multiship_YYYYMMDD.log`. Off by default. Turn it on before reporting a problem.

There is no store-wide on/off switch. Installing the plugin *is* the decision to use it; uninstall it from the Plugin Manager to turn it off.

### Orders, invoices and packing slips

1. *Customers → Orders* shows a multiship order with each sub-order broken out, including what shipped where, and lets you change the status of one sub-order without touching the others. The customer is emailed about that sub-order only.
2. The invoice and packing slip are replaced automatically for multiship orders, so each address gets its own section. You do not need to pick a different menu item.
3. *Edit Orders*, if you have it installed, refuses to open a multiship order and says why. See Usage Notes, above.

### The image at the top of your emails

The plugin goes to some trouble to look like your store: it imposes no colors or typefaces of its own, taking both from your template so its pages sit inside your design rather than beside it. None of that reaches the customer's inbox. Order emails are Zen Cart's own, and they open with a file most store owners have never looked at.

Worth knowing before you take multiship orders, because this plugin will make you send more email than you do now. Each sub-order has its own status, so a customer who split an order across three addresses gets three "your order has shipped" emails rather than one. Whatever sits at the top of those appears once per parcel — and on most stores, what sits there is a file the owner has never seen.

Zen Cart ships `/email/header.jpg`, a 550×110 banner, and puts it at the top of every HTML email your store sends. The constants controlling it live in `/includes/languages/english/lang.email_extras.php`:

1. `EMAIL_LOGO_FILENAME` — `header.jpg`
2. `EMAIL_LOGO_WIDTH` / `EMAIL_LOGO_HEIGHT` — `550` / `110`
3. `EMAIL_LOGO_ALT_TEXT` — `Brand Logo`
4. `EMAIL_LOGO_ALT_TITLE_TEXT` — `Zen Cart! The Art of E-commerce`

Read that last one again. Until it is changed, every HTML email your store sends carries Zen Cart's branding in the image tooltip, over your logo, under your domain.

**Why you may never have noticed.** Two switches both have to be on before anyone sees it. The store's — *Configuration → E-Mail Options → Send E-Mails in HTML format* — and the customer's, because each account carries its own email-format preference and one set to Plain Text gets plain text regardless. A store can run for years with the stock banner going to the subset of customers who have HTML enabled, while the owner, testing with a plain-text account, sees nothing.

**What to do.** Replace `/email/header.jpg` with your own artwork at the same dimensions — same filename, same size, nothing else to change. If your logo is a different shape, adjust the width and height constants and fix the alt and title text while you are there. If you would rather send no banner, a transparent image at those dimensions is safer than deleting the file, which leaves a broken-image icon.

**What this plugin does about it: nothing, deliberately.** Multiship sends no email of its own — it lets Zen Cart's order-status machinery do the sending, so a sub-order email looks exactly like every other email your store sends. If you have set that banner up, these emails use it. If you have not, these emails are no worse than the rest of your mail, and the fix above fixes all of them at once rather than just ours.

## Zen Cart Customer Interfaces

A customer who does not want multiple addresses sees your store's ordinary checkout, unchanged. Everything below happens only once they ask for it.

### Checkout, Step by Step

Checkout is **three steps**, the same number as an ordinary Zen Cart order.

**Being asked.** When a cart could be split — two or more shippable items, a registered customer, shipping available — clicking *Checkout* presents a short page offering three choices: carry on with normal checkout, send the items to different addresses, or go back and keep shopping. It is asked once per order. Declining is remembered, and so is accepting — a customer who changes their mind can leave multiship from the address page at any point before paying.

If your store also offers PayPal Express Checkout or a similar one-click button from the cart page, a notice appears on the cart itself as well, because a customer who presses that button leaves before the question can be asked.

1. **Step 1 — Choose a Shipping Method and Addresses.** Every shippable unit is listed on its own line, with a menu beside each one holding the customer's address book. They pick the shipping method once, at the top, and then a destination for each item. Shipping is quoted *separately for each address* as they go, so the running total is real rather than estimated. An address the chosen carrier cannot deliver to is flagged. A customer needing an address they have not saved can add one without leaving checkout, and returns to the grid with their other choices intact. The Continue button appears once every item has a destination.
2. **Step 2 — Payment.** Your store's normal payment page. The customer pays once, for the whole order.
3. **Step 3 — Confirmation.** The usual confirmation page, with the single delivery address replaced by a breakdown: each recipient, what they are getting, what their shipping costs, and their sub-total.

After the order is placed, the same breakdown appears on the order-received page, so the customer's receipt records which parcel went where.

A customer who is not signed in when they choose multiple addresses is sent to log in first, and told why, since multiship needs a saved address book.

### Account — Reviewing Order History

When a customer has placed an order with *Multiple Ship-To Addresses*, that order's display within the customer's account-related pages changes to reflect the multiple addresses:

1. On the main *My Account* page, the **Ship To** column of the **Previous Orders** table shows "Multiple Addresses".
2. On the *My Account :: History* page, the **Shipped To** field shows "Multiple Addresses".
3. On the *Order Information* page, the **Delivery Address** shows "Multiple Addresses" and the order's details are broken out by ship-to address.

## Installing

There are no core-file overwrites and no template overrides. You should still back up your database and files before installing anything, but there is nothing to merge.

1. Unzip the plugin's package.
2. Copy the `zc_plugins/MultiShip` folder into your store's `zc_plugins` directory, so that you end up with `/zc_plugins/MultiShip/v3.0.6/` containing `manifest.php`. Nothing goes anywhere else — do not copy anything into `/includes/` or into your admin directory.
3. Sign in to your Zen Cart admin and go to *Modules → Plugin Manager*.
4. Find **Multiple Ship-To Addresses** in the list and click **Install**. This creates the tables, adds the configuration settings and registers the invoice and packing-slip pages.
5. Go to *Configuration → Multiple Ship-to Addresses* (or click **Settings** beside the plugin in the Plugin Manager) and review the settings described under Zen Cart Admin Interfaces, above. The defaults are usable as they stand; there is no enable switch to find, because installing the plugin is what enables it.
6. Before you take real orders, audit your **product weights** — see the warning immediately below.

**Product weights matter more than they used to.** Multiship quotes shipping *separately for each address*, so it exposes gaps in your product data that a single-address order hides.

A product with no weight is already quoted wrongly on every order you take — but a single-address order absorbs that error into one total where nobody notices. Split the same cart across three addresses and the same missing weight produces three visibly wrong quotes. The same applies to dimensions, which newer carrier modules use for dimensional weight.

Nothing in this plugin can compensate for missing data, and it deliberately does not try: a guessed weight is worse than a visibly wrong one, because it looks right. If you are about to enable multiship, audit the weights on anything bulky first.

One thing you do *not* need to worry about: a sub-order heavier than your carrier's limit. Zen Cart splits a consignment into multiple boxes once it exceeds `SHIPPING_MAX_WEIGHT`, and because multiship quotes each address independently, that calculation is done per address. Three heavy items going to one recipient are boxed on their own weight, not the whole order's.

Upgrading from v2.x: install this version from the Plugin Manager, then delete the individual files the older version had you copy into `/includes/` and your admin directory, and restore your own copies of any core or template files you merged its changes into. Your existing multiship orders are not affected — the database tables are the same, and the installer leaves existing data alone.

Once it's installed, a superuser can open this guide from the **ReadMe** button in Plugin Manager.

## Uninstalling

1. Go to *Modules → Plugin Manager*, find **Multiple Ship-To Addresses** and click **Uninstall**. This drops the two multiship tables, removes the `orders_multiship_id` column from `orders_products`, removes the configuration group and its settings, and de-registers the invoice and packing-slip pages.
2. Delete `/zc_plugins/MultiShip/` from your file system.

There is no SQL patch to run and nothing to restore, because nothing outside `/zc_plugins/` was ever changed.

**Read this before you uninstall.** The uninstall is thorough, and that cuts both ways: it discards the record of which items went to which address on every multiship order you have ever taken. The orders themselves remain, with their products, totals and payment details intact, but the per-address breakdown is gone for good — from the customer's order history, from your invoices and packing slips, and from the database. It cannot be recovered by reinstalling, because the column tying each product line to its sub-order is dropped along with the tables.

If any of that history matters — and it will if you have unshipped multiship orders on the books — take a full database backup first, and do not uninstall until those orders have been delivered.

## Known limitations

- **Edit Orders cannot be used on a multiship order.** The block is deliberate; the order's own *Details* page still does status changes, comments and customer notifications, per address.
- **Free shipping does not survive a split order.** Thresholds are measured per address, so an order that qualified whole may not qualify in parts.
- **One shipping method applies to the whole order**, rather than the cheapest carrier per destination.
- **Order emails carry Zen Cart's stock `/email/header.jpg`** unless you have replaced it, and this plugin sends one email per parcel rather than one per order.

Each is described, with its cause, in the sections above.

## Support

- Support thread: https://www.zen-cart.com/threads/189154
- Plugin listing: https://www.zen-cart.com/plugins/multiple-ship-to-addresses-vb1823
- Source and issues: https://github.com/dbltoe/multiship-3

The architecture notes and store-owner notes are in the `docs` folder of the GitHub repository.

## Changelog

File lists in the older entries are marked [+] added, [-] removed, [template] a file in your active template, and [core] a Zen Cart core file.

### v3.0.6

Released 2026-11-11.

- **License: GNU General Public License, version 3** (was version 2).
- **readme.html is replaced by README.md.** The ReadMe button in Plugin Manager opens it inside the admin, for superusers only, in its own window (`index.php?cmd=multiship_readme`); it is no longer a public file under `zc_plugins/`. The same README.md is at the root of the download.
- Nothing changes on the storefront or in the settings.

Upgrading: put the v3.0.6 folder beside the old one and click **Upgrade** in Admin > Modules > Plugin Manager. Your settings and your multiship orders are kept.

### v3.0.5

Released 2026.

- NEW: A *Settings* button in the Plugin Manager, beside *ReadMe*, that opens this plugin's settings. It appears once the plugin is installed and goes away when it's uninstalled. The buttons now read *ReadMe | Settings | Forum Thread | GitHub* in one row.

### v3.0.4

Released 2026.

- BUGFIX: On Zen Cart 2.1.x an order sent to two or more addresses was charged for the first address only. Every address after the first priced to nothing, so its sub-order was saved without totals, and the payment page, the order total and the amount charged covered the first address alone, while every address still shipped. Zen Cart 2.1's language loader reports a module's language file as not loaded the second time it is asked for it in a request, and its order totals class then leaves that module out; the plugin built a new one for each address. It now builds it once and gives each address fresh order-total modules, on every Zen Cart version.
- BUGFIX: The multiship invoice and packing slip printed each status comment through `zen_db_output()`, which Zen Cart marks as deprecated, so every view of a multiship order's invoice or packing slip added a deprecation notice to the logs. They now use `zen_output_string_protected()`, as Zen Cart itself recommends.
- CHANGE: Zen Cart 3.0.0 is now listed among the supported versions. A complete two-address order was placed on a Zen Cart 3.0.0 test store, through the address grid, payment and confirmation, and its sub-orders, invoice and packing slip checked.

### v3.0.3

Released 2026.

- BUGFIX: On Zen Cart 2.0.x every storefront page stopped with `Class "multiship_early_observer" not found`. Zen Cart 2.0 can't find a plugin's storefront class files unless the plugin names their folder explicitly, and this plugin relied on the default. It now names it on every version.
- BUGFIX: On Zen Cart 2.0.x and 2.1.x the login page, and for a signed-in customer the My Account, order history and checkout pages, stopped with `Failed opening required 'includes/modules/pages/login/jscript_multiship_login_focus.php'`. Those versions find a plugin's files for a core page but then look for them in the core folder. The plugin now points them at its own folder, on 2.0 and 2.1 only, so its page files run there just as they do on 2.2 and later. Files Zen Cart has of its own are still found first.
- BUGFIX: The multiship invoice and packing slip raised PHP warnings when opened for an order that wasn't a multiship order, or for an order number that doesn't exist, and stopped with a fatal error when opened with no order number at all. An ordinary order now goes to Zen Cart's own invoice or packing slip, and a missing or absent one to the Orders page.

### v3.0.2

Released 2026.

- BUGFIX: A product with a *checkbox* attribute in the cart, with any box ticked, stopped the shopping cart page with a MySQL error (`Unknown column '13_chk36' in 'where clause'`). Zen Cart stores a ticked checkbox under a combined key, and the plugin's check for downloadable attributes read that key as if it were a plain option number. It now reads both halves of the key, so checkbox, text and every other attribute type are checked the same way.
- BUGFIX: A multiship order couldn't be placed if the chosen shipping method's title contained an apostrophe (*Bob's Courier*, for example). The title was written into the order unescaped and broke the query. It's now escaped.

### v3.0.1

Released 2026.

- NEW: Seams for companion plugins. `NOTIFY_MULTISHIP_QUOTE_ADDRESS` and `NOTIFY_MULTISHIP_VALIDATE_ADDRESS` fire before each address is quoted, with the quote passed by reference so an observer can supply its own; `NOTIFY_MULTISHIP_INITIALIZE_END` fires once every address is priced, with the per-address and order-level totals passed by reference; and `NOTIFY_HEADER_END_CHECKOUT_MULTISHIP` now carries two variables that let an observer hide the shipping-method choice and put a note in its place. They exist for *Multiple Ship-To Addresses Pro*. With no observer attached, nothing about this plugin's behavior changes.
- BUGFIX: Choosing an address that no shipping module serves, in any row but the first, emptied every row. The offer test counted shipping modules whose state reflected whichever address had been checked last; it now asks whether the store has shipping modules installed, which is what it meant.
- BUGFIX: A sub-order that earned free shipping on its own under the *Shipping* order total's threshold still showed the carrier's charge on its shipping line, while its total and the order's shipping total disagreed with it. The order-total modules now have the last word on every sub-order, and a share they make free stays free everywhere it is shown.
- BUGFIX: The payment and confirmation pages of a split order showed shipping as $0.00, and a total without it, whenever the whole order cleared the *Shipping* order total's free-shipping threshold but the individual addresses did not — while the order was then stored, and charged, with shipping on every address. The *Shipping* module was judging the parent order against the whole cart after its parts had already been decided. The parent now reports the sum of its sub-orders.
- BUGFIX: An order that came to nothing — free products, free shipping — raised a PHP warning while its confirmation email was built, because there was no payment module to name.

### v3.0.0

Released 2026.

- CHANGE: Repackaged as an encapsulated `zc_plugin`. **No core files are changed and no template files are overwritten.** Everything lives under `/zc_plugins/MultiShip/` and installs, upgrades and uninstalls from the Plugin Manager. The `YOUR_TEMPLATE` and `YOUR_ADMIN` directories, the merge-by-hand instructions and `uninstall_multiship.sql` are all gone.
- CHANGE: Checkout reduced from five steps to three. The shipping method is now chosen on the address page, and the page that existed only to confirm that choice has been removed.
- CHANGE: The offer of multiple addresses is now made on a short interstitial page when the customer clicks Checkout, rather than as a notice on the shopping cart. The cart notice remains only for stores with an express-checkout button, where a customer can leave before being asked.
- CHANGE: Each unit is listed on its own line on the address page, and quantities are changed in the cart, where customers already expect to change them.
- CHANGE: Nothing is assumed about an unanswered line. Previously a missed item shipped silently to the customer's own address; now every item must be given a destination before checkout continues.
- CHANGE: A customer who accepts the offer can now leave multiship again at any point before paying.
- CHANGE: One Page Checkout is disabled automatically for a customer who chooses multiple addresses, and left alone for everyone else, so the two plugins can coexist on one store.
- CHANGE: The plugin's pages take their appearance from the active template rather than shipping their own look.
- CHANGE: Retired the store-wide enable switch. Installing the plugin is the decision to use it.
- CHANGE: An address the chosen shipping method cannot serve is now kept and marked rather than discarded. The row holds the address, a warning marker appears beside it, and the way onward is replaced by an explanation until it is resolved. Previously the selection was thrown away and the row reverted to the prompt, so the page looked as though it had ignored the click.
- CHANGE: The page's warnings now render immediately above the address grid rather than at the top of the page, beside the rows they are about. The shipping-method message stays with the shipping choices, which is what it refers to.
- CHANGE: Side columns are suppressed on the payment and confirmation pages of a multiship order, so the per-address breakdown has the width it needs on templates that leave them in place. An ordinary customer's checkout is untouched.
- CHANGE: Removed the compatibility code for Zen Cart 1.5.5 and 1.5.6. No version this plugin supports could reach it.
- NEW: The per-address breakdown now appears on the order-received page, which had never shown it.
- NEW: This readme rewritten for the encapsulated plugin, including a warning about product weights, which multiship exposes far more sharply than a single-address order does.
- NEW: *Read Me* and *GitHub* buttons in the Plugin Manager, with a link to the forum support thread beneath them.
- NEW: Accessibility pass. Every *Send To* menu now names the item it belongs to, so a screen reader no longer announces a column of identical unlabeled controls; table column headers are scoped; each recipient's table names its recipient; and this readme's color-coded file lists carry a word as well as a color.
- NEW: The interaction between free shipping and a split order is documented, including the case where the order completes and the customer is charged for shipping the cart page said was free.
- BUGFIX: *Edit Orders* was never actually blocked. The comparison it relied on could not match on a 2.x admin, so Edit Orders was free to open a multiship order and destroy its address records.
- BUGFIX: The admin order view, invoice, packing slip and account order history each silently lost their multiship content once the plugin was encapsulated, because each was reached by a template override that no longer resolves. All four are now delivered through notifiers.
- BUGFIX: Quantity discounts were lost when an order was split. Because each address was priced on its own share, a tier reached by the whole cart was reached by none of the parts, and the customer was charged more than the cart had shown them. Sub-orders are now priced at the cart's own unit prices, so the discount survives the split and each address is still taxed on its own destination.
- BUGFIX: A customer who declined multiple addresses and then changed their shipping method was bounced from the payment page back to the shipping page.
- BUGFIX: The confirmation page showed a single delivery address — the customer's default — for an order going to several.
- BUGFIX: Sub-order status changes were reported as "Nothing to change" and left no record, although the status itself was saved.
- BUGFIX: PHP 8.2 dynamic-property deprecation on the order class.
- BUGFIX: Corrected the Zen Cart version strings in the plugin's manifest, which could never match and so cost store owners their upgrade notices.
- BUGFIX: The address grid returned to the top of the page after every selection on any template that hides the document while it loads. The scroll was being restored against a document that had no layout yet.
- BUGFIX: Every rule keyed on the plugin's own page classes was inert on templates that assign `documentElement.className` outright rather than appending to it, which left the confirmation, payment and order-received pages without their layout.
- BUGFIX: Choosing a second address the shipping method could not serve sent the browser into a redirect loop and an error page, with nothing written to any log. The initialization redirected to the page it was already on.
- BUGFIX: The per-address breakdown was squeezed into the gap beside the floated billing address on the confirmation page.
- BUGFIX: The address grid had no cell padding and no row separators except on Bootstrap templates. The plugin's own table styling named markup that had not existed since the grid was rebuilt.
- BUGFIX: The cannot-ship marker was a Font Awesome icon, and the plugin loads no webfont. On a template without one it rendered as nothing at all, while the message beside it told the customer to look for it.
- BUGFIX: Two PHP 8 warnings raised by single-row lookups that can legitimately return no row — a session naming a deleted customer, and a product removed from the catalog while still in a cart.
- BUGFIX: `pluginId` was `0`, so the online version check could never match and store owners were never told a newer release existed.

### v2.0.0

Released 2019-06-xx.

- BUGFIX: Correct SQL uninstall script.
- BUGFIX: MySQL error when placing a multiple ship-to order that includes attributed products.
- BUGFIX: Removing a multiple ship-to-order results in a PHP error log.
- CHANGE: Move multiple shipping address selection to the "shipping" phase of checkout.
- CHANGE: Disallow *Edit Orders* from editing orders created with multiple shipping addresses.
- CHANGE: Don't offer multiple shipping addresses to customers in a *group-pricing* group.
- CHANGE: Don't offer coupon-entry for orders to be placed with multiple shipping addresses.

The following files were changed, [+] added or [-] removed:

- /uninstall_multiship.sql
- /includes/classes/class.multiship.php
- /includes/classes/observers/class.multiship.php
- /includes/languages/english/extra_definitions/multiship_common.php
- /includes/languages/english/checkout_multiship.php
- /includes/modules/pages/account/header_php_account_multiship.php
- /includes/modules/pages/account_history_info/header_php_account_history_info_multiship.php
- /includes/modules/pages/account_history_info/main_template_vars.php
- /includes/modules/pages/checkout_confirmation/header_php_checkout_confirmation_multiship.php
- /includes/modules/pages/checkout_multiship/header_php.php
- [+] /includes/modules/pages/checkout_payment/header_php_checkout_payment_multiship.php
- [+] /includes/modules/pages/checkout_shipping/header_php_checkout_shipping_multiship.php
- [+] /includes/modules/pages/checkout_shipping/jscript_checkout_shipping_multiship.php
- /includes/templates/template_default/templates/tpl_account_history_info_multiship.php
- [+] /includes/templates/template_default/templates/tpl_checkout_confirmation_multiship_address.php
- /includes/templates/template_default/templates/tpl_checkout_multiship_default.php
- [+] /includes/templates/template_default/templates/tpl_checkout_shipping_multiship.php
- /includes/templates/template_default/templates/tpl_modules_multiship.php
- [+] /includes/templates/template_default/templates/tpl_modules_order_totals_multiship.php
- /includes/templates/YOUR_TEMPLATE/css/checkout_multiship.css
- [template] /includes/templates/YOUR_TEMPLATE/templates/tpl_account_history_info_default.php (Added)
- [template] /includes/templates/YOUR_TEMPLATE/templates/tpl_ajax_checkout_confirmation_default.php (Added)
- [template] /includes/templates/YOUR_TEMPLATE/templates/tpl_checkout_confirmation_default.php
- [template] /includes/templates/YOUR_TEMPLATE/templates/tpl_checkout_shipping_default.php (Added)
- [template] /includes/templates/YOUR_TEMPLATE/templates/tpl_modules_order_totals.php (Added)
- /YOUR_ADMIN/includes/classes/class.multiship_admin_observer.php
- /YOUR_ADMIN/includes/auto_loaders/config.multiship_admin.php
- /YOUR_ADMIN/includes/classes/observers/class.multiship_admin_observer.php
- /YOUR_ADMIN/includes/init_includes/init_multiship.php
- [+] /YOUR_ADMIN/includes/init_includes/init_multiship_install.php
- [+] /YOUR_ADMIN/includes/init_includes/init_multiship_upgrade.php
- /YOUR_ADMIN/includes/languages/english/extra_definitions/multiship_names.php
- [-] /YOUR_ADMIN/includes/languages/english/orders_multiship.php (All constants moved to `multiship_names.php`)
- [+] /YOUR_ADMIN/includes/modules/multiship_orders_products.php
- [core] /zc155/includes/modules/order_total/ot_shipping.php (Added, for zc155 only)
- [core] /zc155/YOUR_ADMIN/orders.php (For zc155 only, moved from `/YOUR_ADMIN/orders.php`)
- [core] /zc156/YOUR_ADMIN/orders.php (Added, for zc156 only)

The following database changes were made:

- Added a configuration-group (and associated settings) and added the display of those settings to the admin menus.

### v1.2.0

Released 2017-07-12.

- CHANGE: Restructure plugin's distribution, dropping support for Zen Cart versions prior to 1.5.5. **Note:** All files have been "re-factored".
- BUGFIX: Correct packing-slip page-break operations.

The following files were changed or removed:

- [core] [-] /includes/classes/class.base.php (no longer distributed)
- [core] [-] /includes/classes/order.php (no longer distributed)
- [core] [-] /includes/classes/shopping_cart.php (no longer distributed)
- /includes/classes/observers/class.multiship_observer.php
- /includes/languages/english/extra_definitions/multihship_common.php (previously in the extra_definitions/YOUR_TEMPLATE directory)
- /includes/languages/english/checkout_multiship.php (previously in the /YOUR_TEMPLATE directory)
- /includes/templates/template_default//icons/multiship_noship.png (previously in the YOUR_TEMPLATE sub-directory)
- /includes/templates/template_default/templates/tpl_account_history_info_multiship.php (previously in the YOUR_TEMPLATE sub-directory)
- /includes/templates/template_default/templates/tpl_checkout_multiship_default.php (previously in the YOUR_TEMPLATE sub-directory)
- /includes/templates/template_default/templates/tpl_modules_multiship.php (previously in the YOUR_TEMPLATE sub-directory)
- [core] [-] /YOUR_ADMIN/includes/classes/order.php (no longer distributed)
- [core] [-] /YOUR_ADMIN/includes/functions/general.php (no longer distributed)
- /YOUR_ADMIN/includes/init_includes/init_multiship.php

### v1.1.4

Released 2016-01-01.

- CHANGE: Update notifier use to remove a core file!

The following files were changed:

- [core] /includes/classes/order.php
- /includes/classes/observers/class.multiship_observer.php
- /YOUR_ADMIN/includes/init_includes/init_multiship.php

### v1.1.3

Released 2015-12-31.

- CHANGE: PHP 7 compatibility; update class constructor function names to __construct.
- CHANGE: Use trigger_error instead of error_log to log interface errors.
- CHANGE: Use Zen Cart v1.5.5 as the change-basis for the core- and template-file changes.

The following files were changed:

- /includes/classes/class.multiship.php
- [core] /includes/classes/order.php
- [core] /includes/classes/shopping_cart.php
- /includes/classes/observers/class.multiship_observer.php
- [core] /includes/modules/payment/moneyorder.php
- [core] /YOUR_ADMIN/orders.php
- [core] /YOUR_ADMIN/includes/classes/order.php
- /YOUR_ADMIN/includes/classes/observers/class.multiship_admin_observer.php
- [core] /YOUR_ADMIN/includes/functions/general.php
- /YOUR_ADMIN/includes/init_includes/init_multiship.php

### v1.1.2

Released 2014-12-20.

- CHANGE: Incorporate downwardly-compatible changes introduced in Zen Cart v1.5.4.

The following files were changed or added:

- [core] /includes/classes/shopping_cart.php
- [core] /YOUR_ADMIN/orders.php
- [+] /YOUR_ADMIN/includes/auto_loaders/config.zc154_compatibility.php
- [+] /YOUR_ADMIN/includes/init_includes/init_zc154_compatibility.php
- [core] /YOUR_ADMIN/includes/functions/general.php

### v1.1.1

Released 2014-08-16.

- BUGFIX: Unwanted redirect back to *checkout_shipping* page after changing quantities on *checkout_multiship* page.
- BUGFIX: Whitescreen if UPS (or FedEx?) shipping methods selected.
- BUGFIX: Attribute values lost when cloning products with checkbox, TEXT or file-upload attributes on the checkout_multiship and checkout_confirmation pages.

The following files were changed:

- /includes/classes/class.multiship.php
- [core] /includes/classes/order.php
- /includes/classes/observers/class.multiship_observer.php
- /includes/languages/english/extra_definitions/YOUR_TEMPLATE/multiship_common.php
- /includes/modules/pages/checkout_multiship/header_php.php
- /includes/templates/YOUR_TEMPLATE/tpl_checkout_multiship_default.php

### v1.1.0

Released 2014-07-13.

- Incorporate downwardly-compatible changes introduced by Zen Cart v1.5.3.
- Updated this readme to indicate that the "Check/Money Order" plugin, supplied by this plugin, needs to be enabled to support the multiple ship-to addresses in your store's processing.

The following files were changed:

- [core] /includes/classes/class.base.php. This is the version released with Zen Cart v1.5.3.
- [core] /includes/classes/order.php
- [core] /includes/classes/shopping_cart.php. This is the version released with Zen Cart v1.5.3.
- [core] /includes/modules/payment/moneyorder.php
- [core] /YOUR_ADMIN/includes/functions/general.php
- [core] /YOUR_ADMIN/orders.php

### v1.0.0

Released 2014-02-18.

- BUGFIX: Email issues. The grand-total was not properly formatted for TEXT emails and the HTML emails required major rework.
- BUGFIX: Variable misspelling in /YOUR_ADMIN/orders.php.
- BUGFIX: Customers->Orders shows a tax percentage value even though no tax was collected for the sub-order.
- Support multiple order-status values: one for the overall order and one per multiple ship-to sub-order.
- Admin invoice and packingslip pages require change for orders with multiple ship-to addresses.

The following files were changed:

- /includes/classes/class.multiship.php
- [+] /YOUR_ADMIN/invoice_multiship.php
- [core] /YOUR_ADMIN/orders.php
- [+] /YOUR_ADMIN/packingslip_multiship.php
- /YOUR_ADMIN/includes/extra_datafiles/multiship_admin_database_names.php
- /YOUR_ADMIN/includes/init_includes/init_multiship.php
- [+] /YOUR_ADMIN/includes/languages/english/extra_datafiles/multiship_names.php
- [+] /YOUR_ADMIN/includes/languages/english/invoice_multiship.php
- [+] /YOUR_ADMIN/includes/languages/english/packingslip_multiship.php

The following database changes were made:

- Added *admin_pages* for invoice_multiship and packingslip_multiship pages.

### v1.0.0 BETA3

Released 2014-02-10.

- BUGFIX: Introduced in BETA2, stray entry in checkout_confirmation page when multiple ship-to addresses are selected.
- BUGFIX: Unneeded/unwanted code in checkout_multiship header causing PHP warnings.
- BUGFIX: HTML emails for multiple ship-to address orders missing quantity and model number.
- BUGFIX: Need to display shopping_cart messages on the checkout_multiship page, just in case the quantity was reduced based on stock.
- BUGFIX: Changes in quantity on checkout_multiship without intervening update click are lost if the continue checkout button is pressed.
- BUGFIX: Customer redirected to checkout_shipping if quantities were changed on the checkout_multiship page.
- Add observers for shopping cart updates and removals.

The following files were changed or added:

- /includes/auto_loaders/config.multiship.php
- /includes/classes/class.multiship.php
- [core] /includes/classes/shopping_cart.php [+] (Added)
- /includes/classes/observers/class.multiship_observer.php
- /includes/languages/english/extra_definitions/YOUR_TEMPLATE/multiship_common.php
- /includes/languages/english/YOUR_TEMPLATE/checkout_multiship.php
- /includes/modules/pages/checkout_multiship/header_php.php
- [+] /includes/modules/pages/checkout_multiship/jscript_goodbye.php
- /includes/templates/YOUR_TEMPLATE/templates/tpl_checkout_multiship_default.php

### v1.0.0 BETA 2

Released 2014-02-01.

- Incorporated handling of virtual products in the customer's order.

The following files were changed:

- /includes/classes/class.multiship.php
- /includes/languages/english/YOUR_TEMPLATE/checkout_multiship.php
- /includes/modules/pages/checkout_multiship/header_php.php
- /includes/templates/YOUR_TEMPLATE/css/checkout_multiship.css
- /includes/templates/YOUR_TEMPLATE/templates/tpl_checkout_multiship_default.php

### v1.0.0 BETA 1

Released 2014-01-28.

- Initial release.

## License

Multiple Ship-To Addresses is free software under the GNU General Public License, version 3 (https://www.gnu.org/licenses/gpl-3.0.html); the full text is in `LICENSE.txt`. Copyright © 2026 My Zen Cart Host (dbltoe).
