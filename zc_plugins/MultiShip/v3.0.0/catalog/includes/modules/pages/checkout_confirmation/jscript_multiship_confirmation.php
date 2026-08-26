<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart
// Original plugin Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// This file new in v3.0.0, Copyright (C) 2026 My Zen Cart Host (dbltoe)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
// Puts the per-address breakdown on the confirmation page, in place of the single delivery
// address that page would otherwise show.
//
// This is the last page before the customer commits, and until now it showed one address --
// their default one -- for an order going to five. Not merely unhelpful: it stated something
// untrue at the moment of paying, and gave no way to check the work just done.
//
// tpl_modules_multiship.php has always rendered exactly what is wanted here -- each
// recipient, their items, their shipping, their totals -- from $multiship_info and
// $multiship_grand_total, both set by header_php_checkout_confirmation_multiship.php. It has
// simply never had anywhere to render. lat9's version reached the page through an override
// of tpl_checkout_confirmation_default.php; a plugin's copy of that resolves at step 4 of
// the template lookup, behind the active template at step 3, so on any store with a real
// template it is never read. Same wall as the admin product breakdown and the account
// order history, and the same answer: render it ourselves and place it.
//
// Buffered rather than rewritten to return a string, so the template stays usable by
// anything that can include it normally, and stays identical in shape to its siblings.
//
if (empty($_SESSION['multiship']) || !$_SESSION['multiship']->isSelected()) {
    return;
}
if (empty($multiship_info) || !is_array($multiship_info)) {
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
    return;
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
<template id="multishipBreakdownSource"><?php echo $multiship_breakdown_html; ?></template>
<script>
// -----
// Marks the document as a multiship confirmation, immediately.
//
// checkout_confirmation.css is loaded by page name, so it arrives on every confirmation
// page whether the order is going to one address or five. The layout rules in it are only
// right for the second kind -- a store's ordinary single-address checkout should be left
// exactly as its template drew it -- so every one of them is scoped to this class.
//
// Set here in the head rather than in the DOMContentLoaded handler below, and on
// documentElement rather than body: both exist by now, and waiting would let the page paint
// once in the template's layout and again in ours.
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
    var multishipPageClass = 'multishipConfirmation';
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

    var breakdownSource = document.getElementById('multishipBreakdownSource');
    if (breakdownSource === null) {
        return;
    }

    // ZCA Bootstrap wraps the delivery address in a card; core wraps it in a plain div.
    // Both are stable ids. An unrecognized template is left alone rather than half-edited --
    // the messageStack notice added by multiship_observer still tells that customer the
    // order is going to several addresses, which is true either way.
    var slot = document.getElementById('deliveryAddress-card') ||
               document.getElementById('checkoutShipto');
    if (slot === null || slot.parentNode === null) {
        return;
    }

    // Replaced, not hidden. The single address is wrong for this order, and display:none
    // leaves text a screen reader still announces -- worse than showing it, because the
    // customer relying on that reader is the one least able to notice the contradiction.
    var host = document.createElement('div');
    host.id = 'multishipConfirmationBreakdown';
    host.appendChild(breakdownSource.content.cloneNode(true));
    slot.parentNode.replaceChild(host, slot);
});
</script>
