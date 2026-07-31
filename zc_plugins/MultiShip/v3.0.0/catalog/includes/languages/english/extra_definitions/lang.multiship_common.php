<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
$define = [
    'SHIP_TO_MULTIPLE_ADDRESSES' => 'Ship to multiple addresses',
    'SHIPPING_TO_MULTIPLE_ADDRESSES' => 'Shipping to multiple addresses, see below.',

    'SHIP_TO_MULTIPLE_ADDRESSES_LINK' => 'If you are shipping to one address, now is the time to make sure the address above is correct.<br><br>Shipping to multiple addresses?  Click %s to identify which products ship to what address.',
    'SHIP_TO_MULTIPLE_ADDRESSES_ACTIVE' => 'Currently shipping to %1$u addresses.  Click %2$s to make any changes.',
    'SHIP_TO_MULTIPLE_HERE' => '* HERE *',   //-Used as the anchor text for the links (%s), inserted above.

    // -----
    // Shown on the shopping-cart page when the cart qualifies and the question has not
    // yet been put. Deliberately carries NO link: the interstitial reached via Checkout
    // is the single entry point, and a link here would let a customer skip it.
    //
    // It exists mainly for customers who leave the cart by some route other than the
    // Checkout button -- PayPal Express Checkout in particular, which posts straight to
    // PayPal from the cart page and never reaches checkout_shipping, so the interstitial
    // can never be shown to them.
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
    'SHIP_TO_MULTIPLE_CART_ACTIVE' => 'This order is going to more than one address. Choose <strong>Checkout</strong> to set a delivery address for each item.',

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
    // Zen Cart focuses the login email field from core JavaScript --
    // includes/modules/pages/login/on_load_main.js contains
    // document.loginForm.email_address.focus(); -- so the login page arrives scrolled
    // past the create-account section. That hurts multiship customers particularly,
    // since they have just been told they need an account.
    //
    // This plugin cannot fix it. A plugin stylesheet resolves at step 4 of the template
    // lookup, behind the active template's own css directory at step 3, and One Page
    // Checkout installs a login.css into the template -- so on any store running OPC a
    // plugin login.css never loads at all.
    //
    // The span is what scopes the workaround in
    // catalog/includes/modules/pages/login/on_load_multiship.js, which blurs the field and
    // scrolls back to the top -- but only when this notice is present, so ordinary logins
    // are untouched. Renaming or removing the span silently disables that file.
    'MULTISHIP_LOGIN_REQUIRED' => '<span class="multishipLoginNotice">Sending this order to more than one address needs an account, so each delivery can be tracked separately. Please sign in, or create an account, and we will bring you straight back.</span>',

    // -----
    // The multiship address ceiling, MODULE_MULTISHIP_MAX_ADDRESSES. %u is that number.
    //
    // Here rather than in lang.multiship_address.php because the limit is enforced by an
    // observer during core's address_book_process -- that is where the insert happens, and
    // therefore the only place the limit can actually be enforced rather than suggested.
    // A page-specific file would not be loaded there.
    //
    'ERROR_MULTISHIP_ADDRESS_MAX' => 'You have reached the limit of %u saved delivery addresses. Remove one from your address book if you need to add another.',

    // -----
    // Shown on checkout_shipping to a customer who has chosen multiple addresses. This
    // is the route back to the address grid. It replaces a link that used to be rendered
    // by an override of tpl_checkout_shipping_default.php, which is no longer shipped;
    // delivering it through the messageStack keeps the plugin free of template files.
    //
    // The link text is title case and reads as a control rather than prose, because in
    // lowercase it looked like part of the sentence and customers did not recognise it as
    // the way forward. It stays descriptive rather than becoming "click here", so that it
    // still makes sense when read out of context in a screen reader's list of links.
    'MULTISHIP_RETURN_TO_ADDRESSES' => 'This order is going to more than one address. Choose a shipping method below, then click %s to set a delivery address for each item.',
    'MULTISHIP_RETURN_TO_ADDRESSES_LINK' => 'Continue to Addresses',

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
