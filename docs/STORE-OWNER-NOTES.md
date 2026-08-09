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

## The image at the top of your emails

A multiship order sends more email than a single-address one. Each sub-order has its own
status, so a customer who split an order across three addresses gets three "your order has
shipped" emails rather than one. Whatever sits at the top of those emails appears once per
parcel.

On most stores, what sits there is a file the owner has never seen.

### What it is

Zen Cart ships `email/header.jpg` — a 550×110 banner — and puts it at the top of every HTML
email the store sends, including the order-status emails this plugin's sub-orders trigger.
It is referenced as `$EMAIL_LOGO_FILE` in the templates in that same `email/` directory, and
the path is assembled in `includes/classes/Email.php` as:

```
DIR_WS_CATALOG . 'email/' . EMAIL_LOGO_FILENAME
```

The constants controlling it are in `includes/languages/english/lang.email_extras.php`:

| Constant | Default |
|---|---|
| `EMAIL_LOGO_FILENAME` | `header.jpg` |
| `EMAIL_LOGO_WIDTH` | `550` |
| `EMAIL_LOGO_HEIGHT` | `110` |
| `EMAIL_LOGO_ALT_TEXT` | `Brand Logo` |
| `EMAIL_LOGO_ALT_TITLE_TEXT` | `Zen Cart! The Art of E-commerce` |

Read that last row again. Until it is changed, every HTML email your store sends carries
Zen Cart's own branding in the image tooltip, over your logo, under your domain.

### Why you may never have noticed

Two switches both have to be on before anyone sees it:

1. **The store** — Admin → Configuration → E-Mail Options → *Send E-Mails in HTML format*
   (`EMAIL_USE_HTML`). With this off, every email is plain text and the image is never
   referenced.
2. **The customer** — each account carries its own email-format preference. A customer set
   to Plain Text gets plain text no matter what the store setting says.

So a store can run for years with the stock banner going out to the subset of customers who
have HTML enabled, while the owner, testing with a plain-text account, sees nothing.

### What to do about it

The change that needs no code is to replace `email/header.jpg` with your own artwork at the
same dimensions. Nothing else has to be touched — same filename, same size, done.

If your logo is a different shape, adjust `EMAIL_LOGO_WIDTH` and `EMAIL_LOGO_HEIGHT` too, and
fix the alt and title text while you are there. Override them the same way as the
order-comments heading above rather than editing the base language file, so a Zen Cart
upgrade cannot put Zen Cart's branding back.

If you would rather send no banner at all, an empty or transparent image at those dimensions
is safer than deleting the file, which leaves a broken image icon.

### What this plugin does about it

Nothing, deliberately. Multiship sends no email of its own — it lets Zen Cart's order-status
machinery do the sending, so a sub-order email looks exactly like every other email your
store sends, header and all. If you have set that banner up, these emails use it. If you have
not, these emails are no worse than the rest of your mail, and the fix above fixes all of
them at once rather than just ours.

---

## Login page focus

If a customer chooses multiple addresses before signing in, they are sent to log in and
the page arrives scrolled past the create-account section. That is core Zen Cart
behaviour, caused by `includes/modules/pages/login/on_load_main.js`, not by this plugin.

The plugin ships a workaround that scrolls back to the top, scoped so it only affects
logins it caused. If you would rather fix it store-wide, the change belongs in Zen Cart
core — see `docs/ARCHITECTURE-3.0.0.md` for the detail and the suggested patch.
