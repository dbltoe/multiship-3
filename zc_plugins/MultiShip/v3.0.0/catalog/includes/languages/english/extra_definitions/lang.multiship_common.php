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
    // Offered on the shopping-cart page when the cart holds more than one shippable
    // unit. %s is replaced by the anchor built from SHIP_TO_MULTIPLE_CART_OFFER_LINK.
    //
    'SHIP_TO_MULTIPLE_CART_OFFER' => 'Sending these items to more than one person? You can %s and choose a delivery address for each item.',
    'SHIP_TO_MULTIPLE_CART_OFFER_LINK' => 'ship to multiple addresses',

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
