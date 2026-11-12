<?php
// ---------------------------------------------------------------------------
// Part of the Multiple Shipping Addresses plugin for Zen Cart
//
// Copyright (C) 2014-2017, Vinos de Frutas Tropicales (lat9)
//
// @license  https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
// ---------------------------------------------------------------------------
//
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

define('FILENAME_INVOICE_MULTISHIP', 'invoice_multiship');
define('FILENAME_PACKINGSLIP_MULTISHIP', 'packingslip_multiship');

define('TABLE_ORDERS_MULTISHIP', DB_PREFIX . 'orders_multiship');
define('TABLE_ORDERS_MULTISHIP_TOTAL', DB_PREFIX . 'orders_multiship_total');