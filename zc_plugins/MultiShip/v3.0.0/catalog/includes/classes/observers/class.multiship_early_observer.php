<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
// Two jobs that both have to be done *early*, before lat9's One Page Checkout gets a
// say. Attached at autoLoadConfig[90]; OPC's own observer is instantiated at [97].
//
//  1. NOTIFY_OPC_SET_DISABLED -- disable One Page Checkout for this session when the
//     customer has chosen multiple ship-to addresses. A multiship order cannot be
//     expressed on a single page.
//
//  2. NOTIFY_HEADER_START_CHECKOUT_SHIPPING -- redirect a qualifying customer to the
//     multiship_choice interstitial so the question is put explicitly, once.
//
// Both depend on attaching before OPC does:
//
//  - OPC's observer calls OnePageCheckout::checkEnabled() from its *constructor*, and
//    only attaches its checkout-hijacking notifiers if that returns true. checkEnabled()
//    is what issues NOTIFY_OPC_SET_DISABLED, so an observer attached at [131], where
//    multiship's main observer lives, would never hear it at all.
//
//  - For the checkout redirect, Zen Cart notifies observers in the order they attached.
//    OPC redirects NOTIFY_HEADER_START_CHECKOUT_SHIPPING straight to checkout_one, so
//    unless this observer attached first, the interstitial would never be reached on a
//    store running OPC.
//
// Note the difference in what each handler may safely touch. NOTIFY_OPC_SET_DISABLED
// fires during autoload at [97], before $_SESSION['multiship'] is created at [130], so
// that handler reads raw session values only. NOTIFY_HEADER_START_CHECKOUT_SHIPPING
// fires while a page header runs, long after autoload has finished, so that handler can
// use the multiship object normally.
//
// If OPC is not installed the first notifier never fires and that handler is inert; the
// interstitial redirect works either way.
//
class multiship_early_observer extends base
{
    public function __construct()
    {
        $this->attach(
            $this,
            [
                'NOTIFY_OPC_SET_DISABLED',
                'NOTIFY_HEADER_START_CHECKOUT_SHIPPING',
            ]
        );
    }

    public function update(&$class, $eventID, $p1, &$p2, &$p3, &$p4, &$p5)
    {
        switch ($eventID) {
            // -----
            // OPC issues: $this->notify('NOTIFY_OPC_SET_DISABLED', [], $set_disabled);
            // so $p1 is the (empty) parameter array and $p2 is $set_disabled, by reference.
            //
            // The guest and express-checkout tests are repeated here rather than left to
            // multiship::isEnabled(), which would clear the intent flag for us. That check
            // does not run until [130], well after this answers at [97], so relying on it
            // would leave OPC suppressed for one extra request each time a customer
            // switched into one of those flows. Multiship is never available to guests, so
            // OPC must stay in charge of them.
            //
            case 'NOTIFY_OPC_SET_DISABLED':
                if (!empty($_SESSION['multiship_chosen'])
                    && empty($_SESSION['COWOA'])
                    && empty($_SESSION['customer_guest_id'])
                    && empty($_SESSION['paypal_ec_token'])
                ) {
                    $p2 = true;
                }
                break;

            // -----
            // Put the multiple-addresses question before the customer on entry to checkout.
            //
            // Core fires this notifier before its own login check, so the question is asked
            // ahead of registration -- which is the point: an unregistered customer who
            // answers "yes" is then sent to register, while one who answers "no" carries on
            // into whatever checkout the store normally runs.
            //
            // hasBeenAsked() is what stops this becoming a loop: the interstitial marks the
            // question answered either way, and that mark survives sessionCleanup().
            //
            case 'NOTIFY_HEADER_START_CHECKOUT_SHIPPING':
                if (!isset($_SESSION['multiship'])
                    || $_SESSION['multiship']->hasBeenAsked()
                    || !$_SESSION['multiship']->offerAvailable()
                ) {
                    break;
                }
                zen_redirect(zen_href_link(FILENAME_MULTISHIP_CHOICE, '', 'SSL'));
                break;

            default:
                break;
        }
    }
}
