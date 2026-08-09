<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
$define = [
    'SHIPPING_TO_MULTIPLE_ADDRESSES' => 'Shipping to multiple addresses, see below.',


    // -----
    // Shown on the shopping-cart page when the cart qualifies, the question has not yet
    // been put, AND the cart offers a way out that bypasses the interstitial.
    //
    // That last condition is the whole reason it exists. Customers who leave by the
    // Checkout button are asked properly a moment later; for them this only pre-empts a
    // page they are about to see. It is for the ones who leave another way -- PayPal
    // Express Checkout above all, whose button is included straight into the cart template
    // and posts to PayPal without ever reaching checkout_shipping.
    //
    // See NOTIFY_MULTISHIP_CART_NOTICE_NEEDED in the early observer, which decides that and
    // lets a store running some other instant checkout ask for the notice too.
    //
    // Deliberately carries NO link: the interstitial reached via Checkout is the single
    // entry point, and a link here would let a customer skip it.
    //
    'SHIP_TO_MULTIPLE_CART_NOTICE' => 'Sending these items to more than one address? Choose <strong>Checkout</strong> below and we will help you send each item where it needs to go.',

    // Appended to the cart notices below only when PayPal Express Checkout is enabled.
    'SHIP_TO_MULTIPLE_CART_NOTICE_EC' => 'PayPal Express sends the whole order to one address.',

    // -----
    // Shown on the cart page once the customer has chosen multiple addresses.
    //
    // Without it the cart goes silent after the choice is made: the offer disappears, as
    // it should, but nothing confirms the choice was kept. A customer returning to their
    // cart mid-flow has no way to tell whether multiship is still active, and reasonably
    // wonders where it went.
    //
    // %s is a link that turns multiship off and puts the original question back at Checkout.
    // It is here because a customer who returns to the cart to start over is not looking at
    // the address grid, which is the only other place that offer exists.
    'SHIP_TO_MULTIPLE_CART_ACTIVE' => 'This order is going to more than one address. Choose <strong>Checkout</strong> to set a delivery address for each item, or %s.',
    'SHIP_TO_MULTIPLE_CART_ACTIVE_DECLINE' => 'send everything to one address instead',

    // -----
    // Added to the 'login' messageStack when a not-yet-signed-in customer chooses to ship
    // to multiple addresses, since checkout_multiship requires an account.
    //
    // Without it the customer clicks "send items to different addresses" and lands on a
    // login page with no explanation of why. Deliberately says neither "above" nor
    // "below": Zen Cart's stock template and ZCA Bootstrap order the sign-in and
    // create-account sections differently, and ZCA autofocuses the sign-in field, which
    // scrolls its create-account section out of view.
    //
    // The wrapping span is a styling hook, kept deliberately.
    //
    // Zen Cart focuses the login email field two ways -- the autofocus attribute on the
    // input, and includes/modules/pages/login/on_load_main.js, whose entire contents are
    // document.loginForm.email_address.focus() -- so in split-login mode, where the
    // sign-in field sits below the create-account section, the page arrives scrolled past
    // this notice and past the create-account option it is pointing at.
    //
    // Handled by catalog/includes/modules/pages/login/jscript_multiship_login_focus.php,
    // which loads in the head and disables both causes before either fires. That file
    // scopes itself server-side on the multiship session, so the span is no longer what
    // gates it -- it is purely a styling hook now, and renaming it breaks nothing.
    'MULTISHIP_LOGIN_REQUIRED' => '<span class="multishipLoginNotice">Sending this order to more than one address needs an account, so each delivery can be tracked separately. Please sign in, or create an account, and we will bring you straight back.</span>',

    // -----
    // The multiship address ceiling, MODULE_MULTISHIP_MAX_ADDRESSES. %u is that number.
    //
    // Here rather than in lang.multiship_address.php because the limit is enforced by an
    // observer during core's address_book_process -- that is where the insert happens, and
    // therefore the only place the limit can actually be enforced rather than suggested.
    // A page-specific file would not be loaded there.
    //
    // Names the limit, not the addresses. They are ordinary address-book entries usable on
    // any order; only the ceiling belongs to this plugin, so "%u saved multiple shipping
    // addresses" would imply a kind of address that does not exist.
    //
    // Saying whose limit it is matters: a customer who has seen "a maximum of 5 address
    // book entries allowed" on their account page now meets a limit of 10, with no way to
    // know those are different rules.
    //
    // "multiple shipping" rather than the plugin's name, because no other customer-facing
    // string here uses that name -- the interstitial says "send items to different
    // addresses", the grid says "going to more than one address" -- and a refusal is the
    // worst moment to introduce a term the customer has never met.
    //
    'ERROR_MULTISHIP_ADDRESS_MAX' => 'You have reached the multiple shipping limit of %u delivery addresses. Remove one from your address book if you need to add another.',
    // -----
    // Shown on checkout_payment to a customer whose order is going to several addresses.
    //
    // That page shows one address and invites them to change it. It is the billing address
    // and core's own wording says so three times -- but a customer who has just sent seven
    // items to five addresses, and is now at the point of paying, sees one address where
    // there were five and reads it as the split having been lost.
    //
    // So this says both halves: the deliveries are intact, and this address is not one of
    // them. %1$u is the number of delivery addresses.
    //
    // "will not affect where anything is delivered" is the sentence that matters. The
    // Change Address button here leads to checkout_payment_address, which writes only
    // $_SESSION['billto'] and touches no multiship state at all -- unlike the button of the
    // same name on checkout_shipping, which sets a single sendto and ends the multiship
    // order. Same label, opposite consequence, so the difference is stated rather than left
    // for the customer to risk finding out.
    //
    'MULTISHIP_PAYMENT_BILLING_ONLY' => 'Your %1$u delivery addresses are saved and unchanged. The address below is your <strong>billing</strong> address, where your payment is registered &mdash; changing it will not affect where anything is delivered.',

    // -----
    // Replaces checkout_shipping's own heading while multiple addresses are in play.
    //
    // Core sets HEADING_TITLE to "Step 1 of 3 - Delivery Information" while its own
    // breadcrumb for the same page reads "Shipping Method". The breadcrumb is the accurate
    // one, and once this plugin removes the delivery-address block the heading describes
    // content that is no longer on the page at all.
    //
    // HEADING_TITLE is a constant and cannot be redefined, and overriding it through
    // extra_definitions would change it for single-address orders too, so it is swapped
    // client-side alongside the block removal.
    //
    // -----
    // "Confirm", not "Choose". The shipping method is already settled by the time a
    // multiship customer reaches this page -- checkout_multiship shows it to them
    // ("Current shipping method: ...") with its own link to change it, and the per-address
    // quoting has already run against it. Arriving at a page headed "Choose Your Shipping
    // Method" asks for a decision they have made, which reads as though the previous step
    // did not take.
    //
    'MULTISHIP_SHIPPING_HEADING' => 'Step 2 of 3 - Confirm Your Shipping',

    // -----
    // Shown on checkout_confirmation, above the per-address breakdown. %1$u is the number of
    // delivery addresses.
    //
    // Also the whole of what a customer without JavaScript is told there: the breakdown is
    // placed by a script, and without it core's page shows one address -- their default one
    // -- for an order going to several. This at least contradicts that, on the last page
    // before they commit.
    //
    'MULTISHIP_CONFIRMATION_NOTICE' => 'This order is going to %1$u different addresses. Check below that each item is going where you intended, then place your order.',

    'TEXT_SHIPPING_TO' => 'Shipping to: ',

    'TEXT_GRAND_TOTAL' => 'Grand Total:',

    'MULTISHIP_MULTIPLE' => 'Multiple',
    'MULTISHIP_MULTIPLE_ADDRESSES' => 'Multiple Addresses',

    'ICON_MULTISHIP_NOSHIP' => 'multiship_noship.png',
    'ICON_MULTISHIP_NOSHIP_ALT' => 'Identifies that a selected ship-to address is not compatible with the currently-selected shipping method.',

    // -----
    // Note: this key was previously wrapped in a !defined() guard, since it duplicates a
    // storefront core definition.  That guard has no meaning in the array-based loader; the
    // value below matches the Zen Cart core default.
    //
    'WARNING_PRODUCT_QUANTITY_ADJUSTED' => 'Quantity has been adjusted to what is in stock. ',

    'MULTISHIP_PRODUCT_ADD_SHIP_PRIMARY' => 'The newly-added product <b>(%u x %s)</b> will ship to your <b>Primary</b> address.  You will have the opportunity to change this during the checkout process.',
    'MULTISHIP_PRODUCT_INCREASE_SHIP_PRIMARY' => 'Additional quantities of the product <b>(%s)</b> will ship to your <b>Primary</b> address.  You will have the opportunity to change this during the checkout process.',
    // -----
    // Reducing a quantity calls removeProduct(), which strips every address reference for
    // that product -- so the remaining ones are left unassigned, not on the primary
    // address. The other two messages above stay accurate because their code paths do
    // explicitly assign to the primary; this one never did.
    //
    'MULTISHIP_PRODUCT_DECREASE_SHIP_PRIMARY' => 'Changing the quantity of <b>(%s)</b> has cleared its delivery addresses. You will choose them again when you check out.',

    'ERROR_ADDRESS_NOT_VALID_FOR_SHIPPING' => 'That address selection is not supported by the currently-selected shipping method.',
    'MULTISHIP_CHOOSE_DIFFERENT_SHIPPING' => 'One or more of your additional shipping addresses cannot be used with the currently-selected shipping method. Either change your shipping method or click the link below to make changes to your additional shipping addresses.',
    'MULTISHIP_ICON_NO_SHIP' => '<i class="fa fa-exclamation-circle fa-lg"></i>',
];
return $define;
