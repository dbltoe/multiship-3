# Multiple Ship-To Addresses

A Zen Cart plugin that lets a customer send the individual products in one order to two or
more different addresses. The order is split into per-address sub-orders, each quoted and
taxed for its own destination, and each trackable and status-updatable independently in the
admin.

**v3.0.0** is a rewrite as a fully encapsulated `zc_plugin`. It changes no core file and no
template file, installs and uninstalls from Plugin Manager, and runs on **Zen Cart 2.0.0
through 2.3.0** on PHP 8.

## Documentation

- **[Read Me](https://dbltoe.github.io/multiship-3/docs/readme.html)**
  — what it does, how it behaves at each step of checkout, what it does about One Page
  Checkout, Edit Orders, coupons and group pricing, and how to install and remove it. The same
  page is reachable from the Read Me button in Plugin Manager once installed.
- [Architecture notes](docs/ARCHITECTURE-3.0.0.md) — why the plugin is built the way it is,
  which core seams it depends on, what has been verified live, and what is known not to work.
- [Store owner notes](docs/STORE-OWNER-NOTES.md)
- [Core requirements](docs/multiship_core_requirements.md) — behavior this plugin needs from
  Zen Cart, including the places where core offers no hook and the plugin works around it.

## Installing

Upload `zc_plugins/MultiShip/v3.0.0/` to your store root and install from **Admin → Modules →
Plugin Manager**.

**Upgrading from 2.x: delete the old plugin's files, do not upload over them.** The previous
version shipped files into `includes/modules/pages/`, `includes/templates/YOUR_TEMPLATE/` and
`includes/classes/`. Those files still execute if left behind, and some of them duplicate or
contradict work this version now does elsewhere.

Uninstalling drops the `orders_multiship` table and the `orders_multiship_id` column from
`orders`. Per-address data on historical orders does not survive it.

## Known limitations

- **Edit Orders cannot be used on a multiship order.** Opening one returns the admin to the
  order's normal detail page with a message. Editing it there would destroy the record of
  which items went to which address, so the block is deliberate. Orders that are not multiship
  are unaffected. What is blocked is restructuring the line items, not editing the order at
  all: the order's own **Details** page still does status changes, comments and customer
  notifications, and this plugin adds per-address status updates to it so each parcel can be
  advanced on its own.
- **Free shipping does not survive a split order.** Thresholds are measured per address, so an
  order that qualified whole may not qualify in parts.
- **One shipping method applies to the whole order**, rather than the cheapest carrier per
  destination.
- **Order emails carry Zen Cart's stock `/email/header.jpg`** unless you have replaced it, and
  this plugin sends one email per parcel rather than one per order.

Each is described, with its cause, in the Read Me and in the architecture notes.

## Credits

Created by **lat9** of Vinos de Frutas Tropicales, whose original work provides the order
splitting, the per-address shipping costs and the destination-based tax handling that v3.0.0
still relies on. Copyright © 2014-2019 Vinos de Frutas Tropicales.

v3.0.0 rewrite and encapsulation copyright © 2026 **My Zen Cart Host (dbltoe)**.

Released under the [GNU General Public License v2](LICENSE.txt).

## Download and support

- Plugin listing: https://www.zen-cart.com/plugins/multiple-ship-to-addresses-vb1823
- Support thread: https://www.zen-cart.com/threads/189154
