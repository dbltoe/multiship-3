<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart
// Original plugin Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// This file new in v3.0.0, Copyright (C) 2026 My Zen Cart Host (dbltoe)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
// Removes the single-delivery-address block from checkout_shipping while the order is
// going to several addresses.
//
// That block shows one address, states "Your order will be shipped to the address shown",
// and offered a Change Address button. All three are wrong for a multiship order. The
// button is already gone -- header_php_checkout_shipping_multiship.php clears
// $displayAddressEdit, which both core and ZCA Bootstrap honour -- but the heading, the
// address and the text are unguarded markup in the store's own template, which a plugin
// cannot reach. See docs/multiship_core_requirements.md 2.5.
//
// So this removes the nodes instead. Removing rather than hiding matters: display:none
// still leaves text a screen reader announces, which would be worse than showing it.
//
// This is a workaround and behaves like one:
//
// - It depends on the store's template using one of the container ids below. An unknown
//   template is left alone rather than half-edited.
// - With JavaScript off nothing is removed, and the customer sees the same wrong block
//   they see today. The messageStack notice added by multiship_observer stays in place
//   for exactly that reason -- it is the correct information either way, and this file
//   only removes the contradiction.
//
// It should be deleted once core provides a way to suppress that block.
//
if (empty($_SESSION['multiship'])
    || !$_SESSION['multiship']->isChosen()
    || !$_SESSION['multiship']->allItemsAssigned()
) {
    return;
}
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ZCA Bootstrap wraps heading, address, text and button in one card; core keeps the
    // heading and address separate, with the explanatory text as the next sibling.
    var containers = ['shippingInformation-card', 'checkoutShipto', 'checkoutShippingHeadingAddress'];

    for (var i = 0; i < containers.length; i++) {
        var el = document.getElementById(containers[i]);
        if (!el || !el.parentNode) {
            continue;
        }

        if (containers[i] === 'checkoutShipto') {
            var next = el.nextElementSibling;
            if (next && next.className && next.className.indexOf('floatingBox') !== -1) {
                next.parentNode.removeChild(next);
            }
        }

        el.parentNode.removeChild(el);
    }
});
</script>
