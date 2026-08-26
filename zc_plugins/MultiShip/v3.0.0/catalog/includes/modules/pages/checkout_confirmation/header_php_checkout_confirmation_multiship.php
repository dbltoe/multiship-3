<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart
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
    // -----
    // No side columns while a multiship order is being confirmed or paid for.
    //
    // Core disables columns on no checkout page at all -- checkout_shipping, checkout_payment
    // and checkout_confirmation each carry zero flag_disable in their header_php -- so whether
    // a checkout page has side columns is entirely the template's call, made from a list in
    // tpl_main_page.php. ZCA Bootstrap fills that list in and names all three. Stock
    // responsive_classic ships the placeholder Zen Cart supplies,
    // 'list_pages_to_skip_all_left_sideboxes_on_here,separated_by_commas,and_no_spaces',
    // which matches no page ever, so every checkout page there keeps both columns and the
    // centre column is left too narrow for the per-address breakdown. dbltoe reported the
    // result: the data overwriting the sideboxes on steps 2 and 3.
    //
    // This plugin's own pages have always done this for themselves -- checkout_multiship,
    // multiship_choice and multiship_address all set these two flags -- which is why the
    // address grid looked right on the same template that broke the two pages after it.
    //
    // Guarded on the order actually being multiship, so an ordinary customer's checkout is
    // untouched and keeps whatever the store intends. ??= rather than =, so anything that has
    // already made this decision keeps it, and a no-op on ZCA, where the template has
    // suppressed them before this file is reached.
    //
    // What this does not do is fix core's checkout for everyone else on that template. A store
    // owner who wants their ordinary checkout pages full width fills in the list in their own
    // tpl_main_page.php, exactly as ZCA has. That is theirs to decide, not this plugin's.
    //
    $flag_disable_left ??= true;
    $flag_disable_right ??= true;

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
