# Store Owner Notes

Optional customisations for stores running Multiple Ship-To Addresses. Nothing here is
required, and nothing here involves editing a Zen Cart core file.

---

## Telling customers what happens next

A customer shipping one order to several addresses is doing something they have probably
not done on any other store, and the thing they most want reassuring about is what
actually arrives at each address. The plugin says what it can, but the wording that suits
your customers is yours to write.

### The order-comments box

Zen Cart shows a **Special Instructions or Order Comments** box during checkout. Its
heading is `HEADING_ORDER_COMMENTS`, defined in `includes/languages/lang.english.php`,
and it renders in three places a multiship customer passes through:

| Template | Line (Zen Cart 2.3) |
|---|---|
| `tpl_checkout_shipping_default.php` | 120 |
| `tpl_checkout_payment_default.php` | 186 |
| `tpl_checkout_confirmation_default.php` | 83 |

**Read this before changing it.** That heading is shown on *every* order, not only
multiship ones. A single-address customer sees the same text. So wording along the lines
of "your order will be split into separate shipments" is wrong there — it will confuse
the majority of your customers, who are buying for themselves.

Keep any override neutral. Something that reads sensibly either way:

> Special Instructions or Order Comments — including anything we should know about a
> particular delivery

### How to override it safely

Do **not** edit `includes/languages/lang.english.php`. Zen Cart supports a per-template
override which survives upgrades. Create:

```
includes/languages/YOUR_TEMPLATE/lang.english.php
```

where `YOUR_TEMPLATE` is your active template directory — `bootstrap` for ZCA Bootstrap,
`responsive_classic` for the stock responsive template. The file needs only the constants
you are changing:

```php
<?php
$define = [
    'HEADING_ORDER_COMMENTS' => 'Special Instructions or Order Comments',
];
return $define;
```

Definitions in that file overwrite the base language file
(`CatalogArraysLanguageLoader::loadMainLanguageFiles()`), and because it is outside
`includes/languages/lang.english.php` it is untouched by a Zen Cart upgrade.

### Where multiship-specific wording belongs instead

Anything that is only true of a multiship order — that the order is split, that each
address ships and is tracked separately — should not go in the shared comments heading.
The plugin already has places for it, and they appear only when multiship is active:

| Constant | Where it appears |
|---|---|
| `SHIP_TO_MULTIPLE_CART_ACTIVE` | shopping cart, once multiship is chosen |
| `MULTISHIP_RETURN_TO_ADDRESSES` | `checkout_shipping`, alongside the link to the address grid |
| `TEXT_MULTISHIP_INSTRUCTIONS` | the address grid itself |
| `TEXT_MULTISHIP_CHOICE_INTRO` | the Yes/No interstitial |

Override any of those the same way, in
`includes/languages/YOUR_TEMPLATE/extra_definitions/`, or edit the plugin's own language
files if you are maintaining a fork.

---

## Before you enable this: product weights

Multiship quotes shipping **separately for each address**, so it exposes gaps in your
product data that a single-address order hides.

If a product has no weight, its shipping is wrong. That is already true of every order on
your store, but a single-address order absorbs the error into one total where nobody
notices. Split the same cart across three addresses and the same missing weight produces
three visibly wrong quotes.

The same applies to dimensions, which newer carrier modules use for dimensional weight.

Nothing in this plugin can compensate for missing data, and it deliberately does not try —
a guessed weight is worse than a visibly wrong one, because it looks right. If you are
about to enable multiship, it is worth auditing weights on anything bulky first.

One thing you do **not** need to worry about: a sub-order heavier than your carrier's
limit. Zen Cart splits a consignment into multiple boxes once it exceeds
`SHIPPING_MAX_WEIGHT`, and because multiship quotes each address independently, that
calculation is done per address. Three heavy items going to one recipient are boxed on
their own weight, not the whole order's.

## Styling the action links

The plugin's forward links carry classes so they can be rendered as buttons from your own
stylesheet — customers reported not recognising plain inline links as the way forward:

- `.multishipContinueLink` — "Continue to Addresses", on `checkout_shipping`
- `.multishipActionLink` — the links on the address grid

Put the rules in your template's own `stylesheet.css`. A stylesheet shipped by a plugin
resolves *behind* the active template's `css` directory, so it will not load on any store
whose template already provides a file of the same name.

---

## Login page focus

If a customer chooses multiple addresses before signing in, they are sent to log in and
the page arrives scrolled past the create-account section. That is core Zen Cart
behaviour, caused by `includes/modules/pages/login/on_load_main.js`, not by this plugin.

The plugin ships a workaround that scrolls back to the top, scoped so it only affects
logins it caused. If you would rather fix it store-wide, the change belongs in Zen Cart
core — see `docs/ARCHITECTURE-3.0.0.md` for the detail and the suggested patch.
