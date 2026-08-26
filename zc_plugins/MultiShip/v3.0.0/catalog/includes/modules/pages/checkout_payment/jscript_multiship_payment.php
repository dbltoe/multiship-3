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
    var multishipPageClass = 'multishipPayment';
    function multishipMarkPage() {
        document.documentElement.classList.add(multishipPageClass);
    }
    multishipMarkPage();
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', multishipMarkPage);
    }
})();
</script>
<?php
// -----
// The rule lives here rather than in a checkout_payment.css, because that file could not
// reach this page.
//
// Page stylesheets are resolved by html_header_css_loader.php with
//     $template->get_template_dir('^' . $value . '.css', DIR_WS_TEMPLATE, $current_page_base, 'css')
// which returns *one* directory. The active template's css/ is step 3 of that lookup and a
// plugin's is step 4, so the two are mutually exclusive rather than additive: on any store
// whose template ships its own checkout_payment.css -- which ZCA Bootstrap does, since a
// payment page needs styling for its method list -- the plugin's file is never loaded at all.
// No error, no warning, just a button still floating right. dbltoe reported exactly that, and
// confirmed the markup was the .buttonRow.forward this was already written to target.
//
// The plugin's own pages are not exposed to this. No template ships CSS for a page it has
// never heard of, so checkout_multiship.css and its siblings always win at step 4. It is only
// the stylesheets named after *core* pages that can be shadowed.
//
// Emitting from here works because html_header.php loads the CSS at line 75 and the jscript_
// files at line 88, so this arrives after every stylesheet and wins on cascade order at equal
// specificity, without needing !important.
//
// Still scoped to the class above. The gate at the top of this file already means nothing is
// emitted for a customer who is not multishipping, so the scoping is belt and braces -- but it
// leaves a store owner something to target if they want the button back where it was.
//
?>
<style>
.multishipPayment .buttonRow.forward {
    float: none;
    clear: both;
    text-align: center;
    padding: 0.5em 0 1em;
}

.multishipPayment [id$="-btn-toolbar"] {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.75em;
    text-align: center;
    margin-top: 1.5em;
}
</style>
