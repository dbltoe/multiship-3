<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart
// Original plugin Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// This file new in v3.0.0, Copyright (C) 2026 My Zen Cart Host (dbltoe)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
// Rebuilds the per-address breakdown for the order just placed.
//
// checkout_success has never been given one. It includes core's
// tpl_account_history_info_default.php, which prints $order->delivery -- and for a multiship
// order that column holds a single address, so the page told a customer who had just split an
// order across three people that it was all going to one. The items were listed with no
// indication of where any of them was headed, and one shipping figure covered the lot.
//
// Everything here comes from the database rather than the session. checkout_process calls
// sessionCleanup() before redirecting, so by the time this page renders $_SESSION['multiship']
// is gone -- which is correct, the order is placed. It also means this page survives a
// refresh or a back-button, where a session-driven version would blank out.
//
// $orders_id and $order are core's, set by its own header_php.php: page header files load in
// filename order and "header_php.php" sorts before "header_php_checkout_success_multiship.php".
//
$multiship_info = [];
$is_multiship_order = false;

if (!empty($orders_id) && isset($order) && is_object($order)) {
    $multiship_orders_id = (int)$orders_id;

    $multiship = $db->Execute(
        "SELECT orders_multiship_id, orders_id, delivery_name AS name, delivery_company AS company,
                delivery_street_address AS street_address, delivery_suburb as suburb,
                delivery_city as city, delivery_postcode as postcode, delivery_country as country,
                delivery_address_format_id as format_id, orders_status, content_type
           FROM " . TABLE_ORDERS_MULTISHIP . "
          WHERE orders_id = $multiship_orders_id"
    );
    $is_multiship_order = !$multiship->EOF;

    if ($is_multiship_order === true) {
        // -----
        // Which sub-order each product line belongs to, by position.
        //
        // $order->products is built by the order class from orders_products in
        // orders_products_id order, and this reads the same table in the same order, so
        // position n here is position n there. Held in a map of its own rather than written
        // onto $order->products: core's template renders that array a few inches further down
        // the page and this file has no business altering what it finds.
        //
        $line_multiship_ids = [];
        $products_info = $db->Execute(
            "SELECT orders_multiship_id
               FROM " . TABLE_ORDERS_PRODUCTS . "
              WHERE orders_id = $multiship_orders_id
              ORDER BY orders_products_id"
        );
        while (!$products_info->EOF) {
            $line_multiship_ids[] = (int)$products_info->fields['orders_multiship_id'];
            $products_info->MoveNext();
        }
        unset($products_info);

        while (!$multiship->EOF) {
            $orders_multiship_id = (int)$multiship->fields['orders_multiship_id'];

            $currentInfo = [];
            $currentInfo['delivery'] = $multiship->fields;
            $currentInfo['address'] = zen_address_format($currentInfo['delivery']['format_id'], $currentInfo['delivery'], false, '', ', ');

            $currentInfo['products'] = [];
            foreach ($order->products as $line => $product) {
                if (isset($line_multiship_ids[$line]) && $line_multiship_ids[$line] === $orders_multiship_id) {
                    $currentInfo['products'][] = $product;
                }
            }

            $currentInfo['totals'] = [];
            $totals = $db->Execute(
                "SELECT *
                   FROM " . TABLE_ORDERS_MULTISHIP_TOTAL . "
                  WHERE orders_multiship_id = $orders_multiship_id
                  ORDER BY sort_order"
            );
            while (!$totals->EOF) {
                $currentInfo['totals'][] = $totals->fields;
                $totals->MoveNext();
            }
            unset($totals);

            $multiship_info[$orders_multiship_id] = $currentInfo;
            $multiship->MoveNext();
        }
        unset($currentInfo);
    }
    unset($multiship);
}

// -----
// No grand total. Core's order totals are rendered on this page directly beneath the
// breakdown, which is where a customer looks for the figure and where it matches what they
// were charged. Same reasoning as checkout_confirmation, and the same reason
// tpl_modules_multiship.php no longer carries one of its own.
//
