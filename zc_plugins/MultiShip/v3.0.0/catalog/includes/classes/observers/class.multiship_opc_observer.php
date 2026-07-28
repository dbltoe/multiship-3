<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
// Disables lat9's One Page Checkout for the current session when the customer has
// chosen to ship to multiple addresses. A multiship order cannot be expressed on a
// single page, so OPC is bypassed entirely for that order -- and only that order.
// Customers who did not choose multiship keep whatever checkout the store runs.
//
// -----
// Why this is a separate observer, loaded early:
//
// OPC's own observer calls OnePageCheckout::checkEnabled() from its *constructor*
// and only attaches its checkout-hijacking notifiers if that returns true
// (class.checkout_one_observer.php). That observer is instantiated at
// autoLoadConfig[97], whereas multiship's session object is created at [130] and
// its main observer at [131]. An observer attached at 131 would therefore never
// hear NOTIFY_OPC_SET_DISABLED, which fires at 97.
//
// This class is loaded at [90] instead, and deliberately depends on nothing but a
// plain session flag, since $_SESSION['multiship'] does not exist yet at that point.
//
// If OPC is not installed the notifier never fires and this observer is inert.
//
class multiship_opc_observer extends base
{
    public function __construct()
    {
        $this->attach($this, ['NOTIFY_OPC_SET_DISABLED']);
    }

    // -----
    // OPC issues: $this->notify('NOTIFY_OPC_SET_DISABLED', [], $set_disabled);
    // so $p1 is the (empty) parameter array and $p2 is $set_disabled, by reference.
    //
    public function update(&$class, $eventID, $p1, &$p2)
    {
        if ($eventID !== 'NOTIFY_OPC_SET_DISABLED') {
            return;
        }

        // -----
        // The guest and express-checkout tests are repeated here rather than left to
        // multiship::isEnabled(), which would clear the intent flag for us. That check
        // does not run until autoLoadConfig[130], well after this observer answers at
        // [90], so relying on it would leave One Page Checkout suppressed for one extra
        // request each time a customer switched into one of those flows.
        //
        // Multiship is never available to guests, so OPC must stay in charge of them.
        //
        if (!empty($_SESSION['multiship_chosen'])
            && empty($_SESSION['COWOA'])
            && empty($_SESSION['customer_guest_id'])
            && empty($_SESSION['paypal_ec_token'])
        ) {
            $p2 = true;
        }
    }
}
