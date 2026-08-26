<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart
// Original plugin Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// This file new in v3.0.0, Copyright (C) 2026 My Zen Cart Host (dbltoe)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
$define = [
    'NAVBAR_TITLE_MULTISHIP_ADDRESSES' => 'Delivery Addresses',
    'NAVBAR_TITLE_MULTISHIP_ADDRESS' => 'Add a Delivery Address',

    // -----
    // HEADING_TITLE, not a plugin-prefixed name, because the included address-form module
    // expects it.
    //
    // This page hosts the store's own tpl_modules_address_book_details.php, and that module
    // is written for the address_book_process page, so it may use any constant that page's
    // language file provides. ZCA Bootstrap's copy uses HEADING_TITLE; core's does not,
    // which is why auditing against template_default missed it. Anything hosting that
    // module has to supply the page-level constants it expects.
    //
    'HEADING_TITLE' => 'Add a Delivery Address',

    'TEXT_MULTISHIP_ADDRESS_INTRO' => 'Add someone you are sending part of this order to. It will be saved with your other addresses, so you will not have to type it again next time.',

    'TEXT_MULTISHIP_ADDRESS_CANCEL' => 'Back to Delivery Addresses',

    // ERROR_MULTISHIP_ADDRESS_MAX is deliberately NOT here. The limit is enforced by an
    // observer during address_book_process, where this page's language file is not loaded,
    // so it lives in extra_definitions/lang.multiship_common.php instead.
];
return $define;
