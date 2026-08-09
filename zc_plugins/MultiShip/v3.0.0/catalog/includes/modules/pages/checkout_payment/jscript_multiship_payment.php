<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart
// Original plugin Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// This file new in v3.0.0, Copyright (C) 2026 My Zen Cart Host (dbltoe)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
// Marks checkout_payment as belonging to a multiship checkout, so checkout_payment.css can
// centre its closing button the way the pages either side of it do.
//
// This is the one step of the three that is entirely core's page. Steps one and three --
// the address grid and the confirmation -- both close with their controls centred, and
// dbltoe found the odd one out: "checkout_payment button is on the right."
//
// Scoped rather than applied outright, and that distinction matters more here than anywhere
// else in this plugin. checkout_payment is not a multiship page; it is the payment page every
// customer of the store sees. Restyling it for all of them because some of them are shipping
// to several addresses would be this plugin redecorating somebody else's checkout. With the
// class, a customer who never chose multiship sees the page exactly as their store's template
// draws it, and only a customer already two steps into a multiship checkout gets the layout
// that matches the steps around it.
//
if (empty($_SESSION['multiship']) || !$_SESSION['multiship']->isSelected()) {
    return;
}
?>
<script>
// documentElement rather than body, and here in the head rather than on DOMContentLoaded, so
// the button is drawn centred once instead of being drawn right and then moved.
document.documentElement.className += ' multishipPayment';
</script>
