<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
$zco_notifier->notify('NOTIFY_HEADER_START_CHECKOUT_CONFIRMATION_MULTISHIP');

// -----
// No multiship session variable? Nothing to do.
//
if (!isset($_SESSION['multiship'])) {
    return;
}

// -----
// Set the flags for the template's use.
//
$multiple_shipping_active = $_SESSION['multiship']->isSelected();
if ($multiple_shipping_active) {
    $multiship_info = $_SESSION['multiship']->getDetails();
    $multiship_totals = $_SESSION['multiship']->getTotals();
    $multiship_grand_total = 0;
    if (is_array($multiship_totals) && isset($multiship_totals['ot_total'])) {
        $multiship_grand_total = $multiship_totals['ot_total'];
    }

    // -----
    // Says the order is going to several addresses, on the page where that matters most.
    //
    // jscript_multiship_confirmation.php replaces the single delivery address with the full
    // per-address breakdown, but it needs JavaScript. Without it the customer sees core's
    // page unchanged -- one address, their default one, for an order going to five -- and
    // this notice is the only thing telling them otherwise at the point of committing.
    //
    // It is worth saying even when the breakdown does render: it names the count before the
    // customer starts reading the detail, so they know how many recipients to expect.
    //
    if (defined('MULTISHIP_CONFIRMATION_NOTICE')
        && !empty($GLOBALS['messageStack'])
        && is_object($GLOBALS['messageStack'])
    ) {
        $GLOBALS['messageStack']->add(
            'checkout_confirmation',
            sprintf(MULTISHIP_CONFIRMATION_NOTICE, $_SESSION['multiship']->addressCount()),
            'caution'
        );
    }
}

$zco_notifier->notify('NOTIFY_HEADER_END_CHECKOUT_CONFIRMATION_MULTISHIP');
