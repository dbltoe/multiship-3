<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
// The interstitial that asks whether this order ships to more than one address.
//
// It is a plain page with a form: no JavaScript, no overlay, and no template changes,
// so it works with scripting disabled and on any store template. The customer is
// redirected here by multiship_early_observer on entry to checkout, but only when the
// cart qualifies and they have not already been asked.
//
// Deliberately does *not* require a login. NOTIFY_HEADER_START_CHECKOUT_SHIPPING fires
// before core's login check, so the question is put before registration: answering
// "yes" then sends the customer to checkout_multiship, which requires an account and
// handles the login/registration round-trip itself.
//
$zco_notifier->notify('NOTIFY_HEADER_START_MULTISHIP_CHOICE');

require DIR_WS_MODULES . zen_get_module_directory('require_languages.php');

if ($_SESSION['cart']->count_contents() <= 0) {
    zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
}

if (!isset($_SESSION['multiship'])) {
    zen_redirect(zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
}

// -----
// Record the customer's answer. Either branch marks the question as asked, so it is
// put at most once per session.
//
if (isset($_POST['multiship_choice'])) {
    if ($_POST['multiship_choice'] === 'yes') {
        $_SESSION['multiship']->chooseMultiship();
        zen_redirect(zen_href_link(FILENAME_CHECKOUT_MULTISHIP, '', 'SSL'));
    }

    $_SESSION['multiship']->declineMultiship();
    zen_redirect(zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
}

// -----
// Guard against reaching this page directly when there is nothing to ask about, for
// example via a bookmark, or after the cart dropped below the multiship threshold.
//
if (!$_SESSION['multiship']->offerAvailable()) {
    zen_redirect(zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
}

$breadcrumb->add(NAVBAR_TITLE_MULTISHIP_CHOICE);

$zco_notifier->notify('NOTIFY_HEADER_END_MULTISHIP_CHOICE');
