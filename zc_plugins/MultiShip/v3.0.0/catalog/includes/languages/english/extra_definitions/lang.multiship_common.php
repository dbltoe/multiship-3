<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
$define = [
    'SHIP_TO_MULTIPLE_ADDRESSES' => 'Ship to multiple addresses',
    'SHIPPING_TO_MULTIPLE_ADDRESSES' => 'Shipping to multiple addresses, see below.',

    'SHIP_TO_MULTIPLE_ADDRESSES_LINK' => 'If you are shipping to one address, now is the time to make sure the address above is correct.<br><br>Shipping to multiple addresses?  Click %s to identify which products ship to what address.',
    'SHIP_TO_MULTIPLE_ADDRESSES_ACTIVE' => 'Currently shipping to %1$u addresses.  Click %2$s to make any changes.',
    'SHIP_TO_MULTIPLE_HERE' => '* HERE *',   //-Used as the anchor text for the links (%s), inserted above.

    // -----
    // Shown on the shopping-cart page when the cart qualifies, the question has not yet
    // been put, AND the cart offers a way out that bypasses the interstitial.
    //
    // That last condition is the whole reason it exists. Customers who leave by the
    // Checkout button are asked properly a moment later; for them this only pre-empts a
    // page they are about to see. It is for the ones who leave another way -- PayPal
    // Express Checkout above all, whose button is included straight into the cart template
    // and posts to PayPal without ever reaching checkout_shipping.
    //
    // See NOTIFY_MULTISHIP_CART_NOTICE_NEEDED in the early observer, which decides that and
    // lets a store running some other instant checkout ask for the notice too.
    //
    // Deliberately carries NO link: the interstitial reached via Checkout is the single
    // entry point, and a link here would let a customer skip it.
    //
    'SHIP_TO_MULTIPLE_CART_NOTICE' => 'Sending these items to more than one address? Choose <strong>Checkout</strong> below and we will help you send each item where it needs to go.',

    // Appended to the cart notices below only when PayPal Express Checkout is enabled.
    'SHIP_TO_MULTIPLE_CART_NOTICE_EC' => 'PayPal Express sends the whole order to one address.',

    // -----
    // Shown on the cart page once the customer has chosen multiple addresses.
    //
    // Without it the cart goes silent after the choice is made: the offer disappears, as
    // it should, but nothing confirms the choice was kept. A customer returning to their
    // cart mid-flow has no way to tell whether multiship is still active, and reasonably
    // wonders where it went.
    //
    // %s is a link that turns multiship off and puts the original question back at Checkout.
    // It is here because a customer who returns to the cart to start over is not looking at
    // the address grid, which is the only other place that offer exists.
    'SHIP_TO_MULTIPLE_CART_ACTIVE' => 'This order is going to more than one address. Choose <strong>Checkout</strong> to set a delivery address for each item, or %s.',
    'SHIP_TO_MULTIPLE_CART_ACTIVE_DECLINE' => 'send everything to one address instead',

    // -----
    // Added to the 'login' messageStack when a not-yet-signed-in customer chooses to ship
    // to multiple addresses, since checkout_multiship requires an account.
    //
    // Without it the customer clicks "send items to different addresses" and lands on a
    // login page with no explanation of why. Deliberately says neither "above" nor
    // "below": Zen Cart's stock template and ZCA Bootstrap order the sign-in and
    // create-account sections differently, and ZCA autofocuses the sign-in field, which
    // scrolls its create-account section out of view.
    //
    // The wrapping span is a styling hook, kept deliberately.
    //
    // Zen Cart focuses the login email field two ways -- the autofocus attribute on the
    // input, and includes/modules/pages/login/on_load_main.js, whose entire contents are
    // document.loginForm.email_address.focus() -- so in split-login mode, where the
    // sign-in field sits below the create-account section, the page arrives scrolled past
    // this notice and past the create-account option it is pointing at.
    //
    // Handled by catalog/includes/modules/pages/login/jscript_multiship_login_focus.php,
    // which loads in the head and disables both causes before either fires. That file
    // scopes itself server-side on the multiship session, so the span is no longer what
    // gates it -- it is purely a styling hook now, and renaming it breaks nothing.
    'MULTISHIP_LOGIN_REQUIRED' => '<span class="multishipLoginNotice">Sending this order to more than one address needs an account, so each delivery can be tracked separately. Please sign in, or create an account, and we will bring you straight back.</span>',

    // -----
    // The multiship address ceiling, MODULE_MULTISHIP_MAX_ADDRESSES. %u is that number.
    //
    // Here rather than in lang.multiship_address.php because the limit is enforced by an
    // observer during core's address_book_process -- that is where the insert happens, and
    // therefore the only place the limit can actually be enforced rather than suggested.
    // A page-specific file would not be loaded there.
    //
    // Names the limit, not the addresses. They are ordinary address-book entries usable on
    // any order; only the ceiling belongs to this plugin, so "%u saved multiple shipping
    // addresses" would imply a kind of address that does not exist.
    //
    // Saying whose limit it is matters: a customer who has seen "a maximum of 5 address
    // book entries allowed" on their account page now meets a limit of 10, with no way to
    // know those are different rules.
    //
    // "multiple shipping" rather than the plugin's name, because no other customer-facing
    // string here uses that name -- the interstitial says "send items to different
    // addresses", the grid says "going to more than one address" -- and a refusal is the
    // worst moment to introduce a term the customer has never met.
    //
    'ERROR_MULTISHIP_ADDRESS_MAX' => 'You have reached the multiple shipping limit of %u delivery addresses. Remove one from your address book if you need to add another.',

    // -----
    // Shown on checkout_shipping to a customer who has chosen multiple addresses. This
    // is the route back to the address grid. It replaces a link that used to be rendered
    // by an override of tpl_checkout_shipping_default.php, which is no longer shipped;
    // delivering it through the messageStack keeps the plugin free of template files.
    //
    // The link text is title case and reads as a control rather than prose, because in
    // lowercase it looked like part of the sentence and customers did not recognise it as
    // the way forward. It stays descriptive rather than becoming "click here", so that it
    // still makes sense when read out of context in a screen reader's list of links.
    'MULTISHIP_RETURN_TO_ADDRESSES' => 'This order is going to more than one address. Choose a shipping method below, then click %s to set a delivery address for each item.',
    'MULTISHIP_RETURN_TO_ADDRESSES_LINK' => 'Continue to Addresses',

    // -----
    // Shown to a customer arriving back at the shipping step with every item addressed.
    //
    // Without it the page reads as though their work was lost and they are starting over:
    // it is headed Step 1, it is the page they already completed, and the message above --
    // which tells them to go and set addresses -- would be actively wrong.
    //
    // They are here because checkoutInitialize(), which recalculates shipping for the
    // addresses they just chose, runs only on this page. So the message says what the page
    // is for rather than apologising for it. %1$u is the number of addresses, %2$s a link
    // back to the grid for anyone who wants to change them.
    //
    // -----
    // Reassure, then branch, then give one instruction. An earlier draft said "check it
    // below", which left the customer to work out whether "it" was their addresses, the
    // shipping cost or the order -- at the moment they are already unsure why they are on
    // a page they thought they had finished.
    //
    // %1$u addresses, %2$s the link back to the grid.
    //
    // The button is described by position, not by name. An earlier version passed in
    // BUTTON_CONTINUE_ALT so the message would name the button the customer could see;
    // that constant is 'Continue', but core and ZCA both hand it to zen_image_submit() as
    // alt text and ZCA labels the button from elsewhere -- on a real store it reads
    // "Continue to Step 2". So the message named a control that was not there. Chasing
    // whatever label a template chose is not winnable across templates; "below" is.
    //
    'MULTISHIP_ADDRESSES_SET' => 'Your %1$u delivery addresses are saved. If you need to make changes to them, %2$s. Otherwise, verify your shipping method, then click <strong>Continue</strong> below.',
    'MULTISHIP_ADDRESSES_SET_LINK' => 'go back to your addresses',

    // -----
    // Replaces checkout_shipping's own heading while multiple addresses are in play.
    //
    // Core sets HEADING_TITLE to "Step 1 of 3 - Delivery Information" while its own
    // breadcrumb for the same page reads "Shipping Method". The breadcrumb is the accurate
    // one, and once this plugin removes the delivery-address block the heading describes
    // content that is no longer on the page at all.
    //
    // HEADING_TITLE is a constant and cannot be redefined, and overriding it through
    // extra_definitions would change it for single-address orders too, so it is swapped
    // client-side alongside the block removal.
    //
    // -----
    // "Confirm", not "Choose". The shipping method is already settled by the time a
    // multiship customer reaches this page -- checkout_multiship shows it to them
    // ("Current shipping method: ...") with its own link to change it, and the per-address
    // quoting has already run against it. Arriving at a page headed "Choose Your Shipping
    // Method" asks for a decision they have made, which reads as though the previous step
    // did not take.
    //
    'MULTISHIP_SHIPPING_HEADING' => 'Step 2 of 3 - Confirm Your Shipping',

    'TEXT_SHIPPING_TO' => 'Shipping to: ',

    'TEXT_GRAND_TOTAL' => 'Grand Total:',

    'MULTISHIP_MULTIPLE' => 'Multiple',
    'MULTISHIP_MULTIPLE_ADDRESSES' => 'Multiple Addresses',

    'ICON_MULTISHIP_NOSHIP' => 'multiship_noship.png',
    'ICON_MULTISHIP_NOSHIP_ALT' => 'Identifies that a selected ship-to address is not compatible with the currently-selected shipping method.',

    // -----
    // Note: this key was previously wrapped in a !defined() guard, since it duplicates a
    // storefront core definition.  That guard has no meaning in the array-based loader; the
    // value below matches the Zen Cart core default.
    //
    'WARNING_PRODUCT_QUANTITY_ADJUSTED' => 'Quantity has been adjusted to what is in stock. ',

    'MULTISHIP_PRODUCT_ADD_SHIP_PRIMARY' => 'The newly-added product <b>(%u x %s)</b> will ship to your <b>Primary</b> address.  You will have the opportunity to change this during the checkout process.',
    'MULTISHIP_PRODUCT_INCREASE_SHIP_PRIMARY' => 'Additional quantities of the product <b>(%s)</b> will ship to your <b>Primary</b> address.  You will have the opportunity to change this during the checkout process.',
    // -----
    // Reducing a quantity calls removeProduct(), which strips every address reference for
    // that product -- so the remaining ones are left unassigned, not on the primary
    // address. The other two messages above stay accurate because their code paths do
    // explicitly assign to the primary; this one never did.
    //
    'MULTISHIP_PRODUCT_DECREASE_SHIP_PRIMARY' => 'Changing the quantity of <b>(%s)</b> has cleared its delivery addresses. You will choose them again when you check out.',

    'ERROR_ADDRESS_NOT_VALID_FOR_SHIPPING' => 'That address selection is not supported by the currently-selected shipping method.',
    'MULTISHIP_CHOOSE_DIFFERENT_SHIPPING' => 'One or more of your additional shipping addresses cannot be used with the currently-selected shipping method. Either change your shipping method or click the link below to make changes to your additional shipping addresses.',
    'MULTISHIP_ICON_NO_SHIP' => '<i class="fa fa-exclamation-circle fa-lg"></i>',
];
return $define;
