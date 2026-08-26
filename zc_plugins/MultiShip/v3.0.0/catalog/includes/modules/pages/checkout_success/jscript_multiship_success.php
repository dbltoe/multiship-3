<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart
// Original plugin Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// This file new in v3.0.0, Copyright (C) 2026 My Zen Cart Host (dbltoe)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
// Puts the per-address breakdown on checkout_success, and corrects the delivery address that
// page states.
//
// checkout_success includes core's tpl_account_history_info_default.php, which prints
// $order->delivery from the order record -- one address, for an order that went to several.
// dbltoe found it showing the billing address as the delivery address, which is what that
// column holds once the real destinations have moved into orders_multiship.
//
// The same wall as everywhere else in this plugin: reaching that template by overriding it
// puts a plugin copy at step 4 of the template lookup, behind the active template at step 3,
// so on any store with a real template it is never read. lat9's version overrode
// tpl_account_history_info_default.php and carried a $current_page != FILENAME_CHECKOUT_SUCCESS
// guard precisely because checkout_success reuses it. Encapsulated, that override cannot
// land, so the page is corrected from here instead.
//
// Driven entirely by what header_php_checkout_success_multiship.php read back from the
// database. The session is gone by now -- checkout_process cleans it up before redirecting --
// which is also why this survives a refresh.
//
if (empty($multiship_info) || !is_array($multiship_info)) {
    multiship::writeDebugLog('checkout_success: nothing to render, the breakdown script is standing down.');
    return;
}

$multiship_breakdown_tpl = '/tpl_modules_multiship.php';
$multiship_breakdown_dir = $template->get_template_dir(
    $multiship_breakdown_tpl,
    DIR_WS_TEMPLATE,
    $current_page_base,
    'templates'
);
if (!file_exists($multiship_breakdown_dir . $multiship_breakdown_tpl)) {
    return;
}

ob_start();
include $multiship_breakdown_dir . $multiship_breakdown_tpl;
$multiship_breakdown_html = trim((string)ob_get_clean());

if ($multiship_breakdown_html === '') {
    multiship::writeDebugLog('checkout_success: the breakdown template rendered nothing.');
    return;
}

multiship::writeDebugLog('checkout_success: breakdown rendered, ' . strlen($multiship_breakdown_html) . ' bytes handed to the page.');

// -----
// A marker in the page source, only when debug is on.
//
// Earned its place while this page was doing nothing at all: it separates "the PHP never ran"
// from "the PHP ran and the DOM would not take it", and View Source answers that without
// needing access to the logs. Gated rather than removed, because that is a question worth
// being able to ask again on the next store whose template renames everything -- and gated
// rather than left in, because a customer's receipt is not the place for our scaffolding.
//
if (defined('MODULE_MULTISHIP_DEBUG') && MODULE_MULTISHIP_DEBUG == 'true') {
    echo '<!-- multiship: breakdown built for ' . count($multiship_info) . ' address(es), see the multiship log -->' . PHP_EOL;
}
?>
<?php
// -----
// The breakdown travels as inert markup in a <template>, not as a string for innerHTML.
//
// It used to be json_encode()d into a JavaScript variable and assigned with innerHTML. That
// worked and the content was safe -- it is this plugin's own server-rendered output, not
// anything a request supplied -- but innerHTML is a sink a security scanner is right to stop
// on, and Zen Cart's plugin review flagged it. Whoever reads this next should not have to
// re-derive that the string was trustworthy.
//
// A <template> is parsed by the browser as part of the document and its content is inert:
// nothing in it renders, no script in it runs, no image in it loads, until something clones
// it. Cloning yields a DocumentFragment that is inserted as nodes rather than parsed from
// text, so there is no HTML-from-string step anywhere in the path. <template> is valid in
// <head>, which is where this file's output lands.
//
// The markup is echoed raw here on purpose -- it is HTML and must stay HTML. What makes that
// safe is the escaping done where the values enter it, in tpl_modules_multiship.php.
//
?>
<template id="multishipSuccessBreakdownSource"><?php echo $multiship_breakdown_html; ?></template>
<script>
// -----
// Marks the document as a multiship success page, immediately.
//
// checkout_success.css is loaded by page name, so it arrives whether the order went to one
// address or five, and every layout rule in it is scoped to this class. Set in the head and
// on documentElement so the page does not paint once in the template's layout and again in
// ours.
//
// -----
// Set now, and set again once the document is parsed.
//
// A template may assign documentElement.className outright rather than appending to it, and
// if it does so after this script the class is simply gone. responsive_classic does exactly
// that: its html_header.php requires the jscript loader at line 108 -- which is what runs
// this file -- and then at line 135 executes
//     document.documentElement.className = 'no-fouc';
// wiping whatever was there. Every rule in this plugin keyed on the class below was dead on
// that template, which is why dbltoe still saw the store's own width on #checkoutBillto after
// an override that outranks it on specificity: the override was never matching anything.
//
// classList.add rather than += so this is idempotent and cannot clobber a class someone else
// has set, and DOMContentLoaded to get in after any such assignment. Nothing flashes: on the
// template that needs this the document is display:none until its own ready handler runs, and
// on templates that do not, the class set here at parse time is never disturbed.
//
(function () {
    var multishipPageClass = 'multishipSuccess';
    function multishipMarkPage() {
        document.documentElement.classList.add(multishipPageClass);
    }
    multishipMarkPage();
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', multishipMarkPage);
    }
})();

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var breakdownSource = document.getElementById('multishipSuccessBreakdownSource');
    if (breakdownSource === null) {
        return;
    }
    var multipleAddresses = <?php echo json_encode(MULTISHIP_MULTIPLE_ADDRESSES); ?>;
    var deliveryHeading = <?php echo json_encode(trim(rtrim(HEADING_DELIVERY_ADDRESS, ': '))); ?>;

    // -----
    // Find the delivery block without betting on one template's id.
    //
    // The first version of this file matched #myAccountShipInfo, which is what core uses, and
    // on dbltoe's ZCA store nothing happened -- the same trap as the confirmation page, where
    // ZCA calls its delivery block deliveryAddress-card and core calls it checkoutShipto.
    // Rather than collect ids one store at a time, fall back to the thing every template does
    // have in common: a heading that reads "Delivery Address", in whatever the store's
    // language is, because it comes from the same constant the template printed.
    //
    function findDeliveryBlock() {
        var known = document.getElementById('myAccountShipInfo') ||
                    document.getElementById('deliveryAddress-card');
        if (known !== null) {
            return known;
        }
        var headings = document.querySelectorAll('h1, h2, h3, h4, h5, .card-header, legend');
        for (var i = 0; i < headings.length; i++) {
            if (headings[i].textContent.trim().replace(/:$/, '') === deliveryHeading) {
                return headings[i].closest('div') || headings[i].parentNode;
            }
        }
        return null;
    }

    // The delivery address stated on this page is wrong for this order. Corrected in place
    // rather than removed: the heading and the shipping method beside it are both still right,
    // and the customer needs to be told there were several addresses, not left to infer it
    // from a gap. Overwritten rather than hidden -- display:none leaves text a screen reader
    // still announces, which is worse than showing it, because the customer relying on that
    // reader is the one least able to notice the contradiction.
    var deliveryBlock = findDeliveryBlock();
    if (deliveryBlock !== null) {
        var deliveryAddress = deliveryBlock.querySelector('address') ||
                              deliveryBlock.querySelector('.card-body');
        if (deliveryAddress !== null) {
            deliveryAddress.textContent = multipleAddresses;
        }
    }

    // -----
    // Somewhere to put it, in descending order of how well we know the page.
    //
    // After the billing box reads best -- who paid, then what went where. Failing that, after
    // whatever the delivery block turned out to be. Failing that, the end of the order-detail
    // container, and finally the page wrapper. The last two are not pretty, but a breakdown in
    // an awkward place beats a customer with no idea which parcel went to whom, and this page
    // is the receipt for an order that is already paid for.
    var anchor = document.getElementById('myAccountPaymentInfo') || deliveryBlock;
    var host = document.createElement('div');
    host.id = 'multishipSuccessBreakdown';
    host.appendChild(breakdownSource.content.cloneNode(true));

    if (anchor !== null && anchor.parentNode !== null) {
        anchor.parentNode.insertBefore(host, anchor.nextSibling);
        return;
    }

    var container = document.getElementById('accountHistInfo') ||
                    document.getElementById('checkoutSuccess');
    if (container !== null) {
        container.appendChild(host);
    }
});
</script>
