<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
$zco_notifier->notify('NOTIFY_HEADER_START_CHECKOUT_PAYMENT_MULTISHIP');

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

    $multiship_totals = $_SESSION['multiship']->getTotals();
    $multiship_grand_total = 0;
    if (is_array($multiship_totals) && isset($multiship_totals['ot_total'])) {
        $multiship_grand_total = $multiship_totals['ot_total'];
    }
    
    // -----
    // If any payment methods are to be disallowed when an order has multiple ship-to
    // addresses, set their 'enabled' status to 'disabled'.
    //
    if (MODULE_MULTISHIP_PAYMENT_METHODS != '') {
        $multiship_unsupported_payments = explode(',', str_replace(' ', '', MODULE_MULTISHIP_PAYMENT_METHODS));
        foreach ($multiship_unsupported_payments as $multiship_payment2remove) {
            if (isset(${$multiship_payment2remove}) && is_object(${$multiship_payment2remove})) {
                ${$multiship_payment2remove}->enabled = false;
            }
        }
    }
    
    // -----
    // Cycle through the currently-defined order-totals modules.  If ot_coupon is there,
    // it'll be removed as coupons and multiple ship-to addresses are incompatible.
    //
    if (isset($order_total_modules)) {
        for ($i = 0, $n = count($order_total_modules->modules); $i < $n; $i++) {
            if ($order_total_modules->modules[$i] == 'ot_coupon.php') {
                unset($order_total_modules->modules[$i]);
            }
        }
        $order_total_modules->modules = array_values($order_total_modules->modules);
    }
}

$zco_notifier->notify('NOTIFY_HEADER_END_CHECKOUT_PAYMENT_MULTISHIP');
