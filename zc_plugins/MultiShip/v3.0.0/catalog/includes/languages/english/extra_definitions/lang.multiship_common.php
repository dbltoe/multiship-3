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

    // Appended to the above only when PayPal Express Checkout is enabled on the store.
    'SHIP_TO_MULTIPLE_CART_NOTICE_EC' => 'PayPal Express sends the whole order to one address.',

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
    // The wrapping span is not cosmetic: login.css keys off it so the scroll fix applies
    // only to logins this plugin caused. See that file.
    'MULTISHIP_LOGIN_REQUIRED' => '<span class="multishipLoginNotice">Sending this order to more than one address needs an account, so each delivery can be tracked separately. Please sign in, or create an account, and we will bring you straight back.</span>',

    // -----
    // Shown on checkout_shipping to a customer who has chosen multiple addresses. This
    // is the route back to the address grid. It replaces a link that used to be rendered
    // by an override of tpl_checkout_shipping_default.php, which is no longer shipped;
    // delivering it through the messageStack keeps the plugin free of template files.
    //
    'MULTISHIP_RETURN_TO_ADDRESSES' => 'This order is going to more than one address. Choose a shipping method below, then click %s to set a delivery address for each item.',
    'MULTISHIP_RETURN_TO_ADDRESSES_LINK' => 'continue to addresses',

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
    'MULTISHIP_PRODUCT_DECREASE_SHIP_PRIMARY' => 'All of the product <b>(%s)</b> will ship to your <b>Primary</b> address.  You will have the opportunity to change this during the checkout process.',

    'ERROR_ADDRESS_NOT_VALID_FOR_SHIPPING' => 'That address selection is not supported by the currently-selected shipping method.',
    'MULTISHIP_CHOOSE_DIFFERENT_SHIPPING' => 'One or more of your additional shipping addresses cannot be used with the currently-selected shipping method. Either change your shipping method or click the link below to make changes to your additional shipping addresses.',
    'MULTISHIP_ICON_NO_SHIP' => '<i class="fa fa-exclamation-circle fa-lg"></i>',
];
return $define;
