<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart
// Original plugin Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// This file new in v3.0.0, Copyright (C) 2026 My Zen Cart Host (dbltoe)
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
                'NOTIFY_HEADER_START_SHOPPING_CART',
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
                if (!empty($_SESSION['multiship_chosen']) && !multiship::inExpressOrGuestCheckout()) {
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
                if (!isset($_SESSION['multiship'])) {
                    // Cannot use debugNote here; there is no object to call it on.
                    break;
                }

                // -----
                // Multiship is chosen: this page is not part of that flow any more, so send
                // the customer to the page that is.
                //
                // checkout_shipping used to be where a multiship customer picked their
                // method and later confirmed it. Both questions moved to the address grid so
                // the flow would be three steps rather than five -- but nothing then stopped
                // a customer reaching this page anyway, by going back to the cart and
                // choosing Checkout again. It rendered normally, and its Continue goes
                // straight to payment: an order with multiship chosen, no addresses
                // assigned, and no way for the customer to tell anything was wrong.
                //
                // Redirecting rather than explaining. There is nothing to decide here now.
                //
                // The grid clears the multiship intent on every route it takes back to this
                // page -- a cart that no longer qualifies, no shipping available, an explicit
                // decline -- so isChosen() is false by the time the customer arrives and this
                // cannot bounce them back again.
                //
                if ($_SESSION['multiship']->isChosen()) {
                    $_SESSION['multiship']->debugNote('checkout intercept: multiship chosen, sending to the address grid.');
                    zen_redirect(zen_href_link(FILENAME_CHECKOUT_MULTISHIP, '', 'SSL'));
                }

                if ($_SESSION['multiship']->hasBeenAsked()) {
                    $_SESSION['multiship']->debugNote('checkout intercept: already asked, not offering again.');
                    break;
                }

                if (!$_SESSION['multiship']->offerAvailable()) {
                    $_SESSION['multiship']->debugNote('checkout intercept: cart does not qualify, no interstitial.');
                    break;
                }

                $_SESSION['multiship']->debugNote('checkout intercept: redirecting to the multiship_choice interstitial.');
                zen_redirect(zen_href_link(FILENAME_MULTISHIP_CHOICE, '', 'SSL'));
                break;

            // -----
            // Returning to the cart reopens the question.
            //
            // Declining used to be final for the whole session: the sticky asked-flag kept
            // the interstitial away, so a customer who said no and then thought better of
            // it had no route back to multiship at all.
            //
            // The cart is the right place to reset it. The redirect loop that the sticky
            // flag exists to prevent runs interstitial -> checkout_shipping -> interstitial
            // and never passes through the cart, so clearing it here cannot reintroduce
            // that loop, while "go back to your cart and you will be asked again" is
            // behaviour a customer can discover and rely on.
            //
            // Only a declined decision is reset. If multiship was chosen, the customer may
            // be part-way through assigning addresses and visiting the cart to check
            // something; re-asking them would be noise.
            //
            case 'NOTIFY_HEADER_START_SHOPPING_CART':
                if (!isset($_SESSION['multiship'])) {
                    // No object to log through; nothing further is possible either.
                    break;
                }

                $_SESSION['multiship']->debugNote('cart page: evaluating whether to show the notice.');

                // -----
                // Multiship already chosen: confirm it rather than saying nothing.
                //
                // Suppressing the offer here is right -- there is nothing left to offer --
                // but going completely silent leaves a customer who returns to their cart
                // mid-flow unable to tell whether their choice survived. Say it is still
                // active, and where to continue.
                //
                if ($_SESSION['multiship']->isChosen()) {
                    if (defined('SHIP_TO_MULTIPLE_CART_ACTIVE') && !empty($GLOBALS['messageStack']) && is_object($GLOBALS['messageStack'])) {
                        // -----
                        // The notice carries the way out as well as the way on.
                        //
                        // Choosing multiship is sticky by design -- a customer part-way
                        // through assigning addresses who nips back to the cart should not be
                        // re-asked. But that left no way to change their mind from here:
                        // Checkout went straight to the grid, and dbltoe found himself
                        // "locked in to MS versus the original questions".
                        //
                        // The grid has always carried a decline link, but a customer who has
                        // come back to the cart to start over is not looking at the grid. The
                        // same escape belongs where they are.
                        //
                        // Declining also clears the asked-flag, so Checkout puts the original
                        // question back -- which is what "start over" was reaching for.
                        //
                        $active = sprintf(
                            SHIP_TO_MULTIPLE_CART_ACTIVE,
                            '<a class="multishipActionLink" href="'
                                . zen_href_link(FILENAME_CHECKOUT_MULTISHIP, 'action=decline', 'SSL') . '">'
                                . SHIP_TO_MULTIPLE_CART_ACTIVE_DECLINE . '</a>'
                        );
                        if (defined('MODULE_PAYMENT_PAYPALWPP_STATUS') && MODULE_PAYMENT_PAYPALWPP_STATUS === 'True') {
                            $active .= ' ' . SHIP_TO_MULTIPLE_CART_NOTICE_EC;
                        }
                        $GLOBALS['messageStack']->add('shopping_cart', $active, 'caution');
                        $_SESSION['multiship']->debugNote('cart page: multiship already chosen, confirming it is still active.');
                    }
                    break;
                }

                if ($_SESSION['multiship']->hasBeenAsked()) {
                    unset($_SESSION['multiship_asked']);
                    $_SESSION['multiship']->debugNote('returned to the cart after declining; the question will be asked again at checkout.');
                }

                // -----
                // Tell the customer the option exists, without offering a way to reach it
                // from here.
                //
                // The interstitial is only reached through the Checkout button, because
                // that is the one route that passes through checkout_shipping. A customer
                // who leaves the cart another way never sees it -- most importantly via
                // PayPal Express Checkout, whose button is included directly into the cart
                // template and posts straight to PayPal. Multiship genuinely cannot apply
                // to such an order, since Express Checkout fixes a single delivery address
                // from the PayPal account, but without this notice the customer would
                // never learn the choice was available at all.
                //
                // Deliberately carries no link. An earlier version of this notice did, and
                // it let customers reach the address grid while skipping the explicit
                // Yes/No page; pointing at the Checkout button keeps one entry point.
                //
                if (!$_SESSION['multiship']->offerAvailable()) {
                    break;
                }

                if (!defined('SHIP_TO_MULTIPLE_CART_NOTICE')) {
                    $_SESSION['multiship']->debugNote('cart page: SHIP_TO_MULTIPLE_CART_NOTICE is not defined; the extra_definitions language file has not loaded.');
                    break;
                }

                if (empty($GLOBALS['messageStack']) || !is_object($GLOBALS['messageStack'])) {
                    $_SESSION['multiship']->debugNote('cart page: messageStack unavailable, cannot show the notice.');
                    break;
                }

                $notice = SHIP_TO_MULTIPLE_CART_NOTICE;
                if (defined('MODULE_PAYMENT_PAYPALWPP_STATUS') && MODULE_PAYMENT_PAYPALWPP_STATUS === 'True') {
                    $notice .= ' ' . SHIP_TO_MULTIPLE_CART_NOTICE_EC;
                }
                $GLOBALS['messageStack']->add('shopping_cart', $notice, 'caution');
                $_SESSION['multiship']->debugNote('cart page: notice added to the shopping_cart messageStack.');
                break;

            default:
                break;
        }
    }
}
