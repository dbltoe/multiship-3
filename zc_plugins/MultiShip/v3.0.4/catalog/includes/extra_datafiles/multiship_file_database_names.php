<?php
// ---------------------------------------------------------------------------
// Part of the Multiple Shipping Addresses plugin for Zen Cart
//
// Copyright (C) 2014-2017, Vinos de Frutas Tropicales (lat9)
//
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
// ---------------------------------------------------------------------------
//
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

define('TABLE_ORDERS_MULTISHIP', DB_PREFIX . 'orders_multiship');
define('TABLE_ORDERS_MULTISHIP_TOTAL', DB_PREFIX . 'orders_multiship_total');

define('FILENAME_CHECKOUT_MULTISHIP', 'checkout_multiship');

// -----
// The interstitial that asks, once per session, whether this order ships to more than
// one address. A mod-owned page, so it needs no JavaScript and no template changes.
//
define('FILENAME_MULTISHIP_CHOICE', 'multiship_choice');

// -----
// Adds a delivery address without the store's address-book limit turning the customer
// away mid-order. See the page's own header for why this is safe.
//
define('FILENAME_MULTISHIP_ADDRESS', 'multiship_address');