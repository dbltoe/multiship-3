<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
if (empty($_SESSION['multiship']) || !$_SESSION['multiship']->isEnabled()) {
    return;
}
// -----
// Choosing a shipping method submits the form so the multiship costs can be recalculated
// per address, and the reload lands the customer back at the top of the page. On a store
// with several carriers the radio list is well down the page, so they have to find their
// way back to it to see what they just chose -- and the shipping list is exactly what they
// were looking at.
//
// Same remedy as the address grid: remember where they were, put them back. See
// pages/checkout_multiship/jscript_multiship_grid.php, which does the same for that page.
//
?>
<script><!--
jQuery(document).ready(function() {
    var multishipScrollKey = 'multishipShippingPosition';

    jQuery($('<input>').attr({
        type: 'hidden',
        id: 'multiship-change',
        name: 'multiship_changed',
        value: '0'
    }).appendTo("form[name='checkout_address']"));

    jQuery('input[name=shipping]').on('change', function() {
        jQuery('#multiship-change').val('1');
        try {
            window.sessionStorage.setItem(multishipScrollKey, String(window.pageYOffset || 0));
        } catch (e) {
            /* Private browsing can refuse sessionStorage; losing the position is not worth
               stopping the submit for. */
        }
        jQuery("form[name='checkout_address']").submit();
    });

    try {
        var multishipSaved = window.sessionStorage.getItem(multishipScrollKey);
        if (multishipSaved !== null) {
            window.sessionStorage.removeItem(multishipScrollKey);
            /* Focus the method they chose, so a keyboard user is not returned to the top of
               the document on every change. preventScroll, or focusing would undo the
               restore below. */
            var multishipChosen = document.querySelector('input[name=shipping]:checked');
            if (multishipChosen !== null && typeof multishipChosen.focus === 'function') {
                multishipChosen.focus({ preventScroll: true });
            }
            window.scrollTo(0, parseInt(multishipSaved, 10) || 0);
        }
    } catch (e) {
        /* As above. */
    }
});
//--></script>
