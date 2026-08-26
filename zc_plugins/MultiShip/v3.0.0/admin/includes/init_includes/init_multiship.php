<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
// Under the encapsulated (zc_plugin) structure, installation, upgrade and
// configuration-group creation are all handled by Installer/ScriptedInstaller.php.
// This script now carries only the plugin's per-request admin behaviour.
//
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

// -----
// If the current page-request is for an order's invoice or packingslip, check to
// see if the order includes multiple ship-to addresses and, if so, redirect to the
// multi-ship version of the script.
//
if (($current_page == FILENAME_ORDERS_INVOICE . '.php' || $current_page == FILENAME_ORDERS_PACKINGSLIP . '.php') && !empty($_GET['oID'])) {
    $oID = (int)$_GET['oID'];
    if ($multiship->isMultiShipOrder($oID)) {
        if ($current_page == FILENAME_ORDERS_INVOICE . '.php') {
            zen_redirect(zen_href_link(FILENAME_INVOICE_MULTISHIP, "oID=$oID"));
        } else {
            zen_redirect(zen_href_link(FILENAME_PACKINGSLIP_MULTISHIP, "oID=$oID"));
        }
    }
}

// -----
// If the current page-request is for "Edit Orders" and the current order contains multiple
// ship-to addresses, deny that request (with message to the admin), since that edit would
// destroy the order's multiple addresses' recording.
//
// -----
// Compared with the extension stripped from both sides, because the two names are not
// written the same way.
//
// admin/includes/application_bootstrap.php sets
//     $PHP_SELF = isset($_GET['cmd']) ? basename($_GET['cmd'] . '.php') : $PHP_SELF;
// so on the 2.x admin's single entry point, index.php?cmd=edit_orders, $current_page is
// "edit_orders.php". FILENAME_ constants carry no extension -- this plugin's own
// FILENAME_INVOICE_MULTISHIP is 'invoice_multiship' -- so a direct comparison against
// FILENAME_EDIT_ORDERS never matched and this block never ran. Edit Orders was free to open
// a multiship order and destroy the very recording this exists to protect.
//
// The invoice and packing-slip test above appends '.php' to get the same result. Normalising
// instead of appending keeps this correct whichever way Edit Orders defines its constant.
//
if (defined('FILENAME_EDIT_ORDERS') && !empty($_GET['oID'])
    && pathinfo($current_page, PATHINFO_FILENAME) === pathinfo(FILENAME_EDIT_ORDERS, PATHINFO_FILENAME)
) {
    if ($multiship->isMultiShipOrder($_GET['oID'])) {
        $messageStack->add_session(MULTISHIP_ORDER_CANT_EDIT, 'error');
        zen_redirect(zen_href_link(FILENAME_ORDERS, 'oID=' . (int)$_GET['oID'] . '&amp;action=edit'));
    }
}
