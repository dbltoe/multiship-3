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
if (defined('FILENAME_EDIT_ORDERS') && $current_page == FILENAME_EDIT_ORDERS && !empty($_GET['oID'])) {
    if ($multiship->isMultiShipOrder($_GET['oID'])) {
        $messageStack->add_session(MULTISHIP_ORDER_CANT_EDIT, 'error');
        zen_redirect(zen_href_link(FILENAME_ORDERS, 'oID=' . (int)$_GET['oID'] . '&amp;action=edit'));
    }
}
