<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
$define = [
    'NAVBAR_TITLE_1' => 'Checkout',
    'NAVBAR_TITLE_2' => 'Shipping Method and Addresses',

    // -----
    // Names both questions, because the page now asks both.
    //
    // It read "Choose a Shipping Address for Each Item" when addresses were all it did. The
    // shipping method moved onto this page to get the flow down to three steps, and a
    // heading that mentions only half of what is on screen tells a customer the other half
    // is something they have wandered into by mistake.
    //
    'HEADING_TITLE' => 'Choose a Shipping Method and Addresses',
    // -----
    // Stated, not asked. This only renders once every item has an address, so "All
    // finished?" was a question whose answer was always yes -- and "Return to Checkout"
    // described going back when the customer is going on.
    //
    // Distinct from the Update button beside it, which changes quantities: setting a row
    // to 0 removes that unit, setting it to 3 splits the row into three addressable ones.
    // Update changes what is in the order; this leaves the page.
    //
    'TEXT_CONTINUE_CHECKOUT_LINK' => 'Continue with Checkout',

    // -----
    // Lets a customer who accepted the offer change their mind. Without it, accepting
    // is irreversible for the rest of the session.
    //
    'TEXT_DECLINE_MULTISHIP' => 'Changed your mind? %s to send this whole order to a single address.',
    'TEXT_DECLINE_MULTISHIP_LINK' => 'Use the Normal Checkout',

    // -----
    // Heading over the shipping-method choice, which now lives on this page.
    //
    // TEXT_CURRENT_SHIPPING_METHOD, TEXT_SHIPPING_METHOD_CHANGE and
    // TEXT_SHIPPING_METHOD_CHANGE_LINK are gone with the block they described -- this page
    // used to state the method and link back to checkout_shipping to change it, which is
    // what made the flow five pages long.
    //
    'TEXT_MULTISHIP_SHIPPING_HEADING' => 'How should this order travel?',

    // -----
    // Raised when a stored method is no longer on offer -- a zone restriction or a disabled
    // module can retire one between visits.
    //
    // Core has its own wording for this, but it lives in lang.checkout_shipping.php, which
    // is not loaded on this page. Referencing it here would be a fatal on the one request
    // it was needed, so this plugin carries its own.
    //
    'ERROR_PLEASE_RESELECT_SHIPPING_METHOD' => 'The shipping method you chose is no longer available for this order. Please choose another below.',

    'HEADING_ITEM' => 'Item',
    'HEADING_PRICE' => 'Price',
    'HEADING_SENDTO' => 'Send To:',

    'TEXT_OPTION_DIVIDER' => ': ',
    'ONETIME_CHARGE_INDICATOR' => '*',
    'TEXT_ONETIME_CHARGES_APPLY' => 'One-time charges apply.',
    // -----
    // Shown when the customer holds more addresses than MAX_ADDRESS_BOOK_ENTRIES.
    //
    // Multiship lists every address the customer has -- its query carries no limit -- but
    // core's address book counts against that setting and will tell the customer they have
    // reached the maximum. Without a word here, that reads as "some of my addresses have
    // been lost", exactly when they are about to rely on them.
    //
    // %1$u is how many they hold, %2$u the store's normal limit.
    //
    'TEXT_MULTISHIP_OVER_ADDRESS_LIMIT' => 'You have %1$u delivery addresses saved. This store normally keeps %2$u, so your account page may say you have reached the maximum &mdash; nothing has been lost. Every address is saved and all of them can be used here.',

    // -----
    // The first entry in every Send To menu, and what an unanswered row shows.
    //
    // A row used to default to the customer's primary address, so a missed item shipped
    // to them silently. Nothing is assumed now: the customer chooses for every item, the
    // same way a product with required attributes will not be added until answered.
    //
    'TEXT_SELECT_ADDRESS_PROMPT' => 'Please choose an address for this item',

    // -----
    // Shown where the Continue button will appear, so the space reads as "not finished
    // yet" rather than sitting empty. %u is how many items are still unanswered.
    //
    'TEXT_MULTISHIP_ITEMS_UNASSIGNED' => 'Choose a delivery address for every item &mdash; your own, or the person you are sending it to. %u still to go.',

    // -----
    // Occupies the Continue position while an item is assigned to an address the chosen
    // shipping method cannot reach. Names the marker rather than the address, because the
    // marker is what the customer has to go and find; %s is that marker.
    //
    // Distinct from TEXT_MULTISHIP_ITEMS_UNASSIGNED above, which covers rows nobody has
    // claimed yet. Both block the way onward, for different reasons, and saying which is
    // which is the difference between a customer fixing it and a customer stuck.
    //
    'TEXT_MULTISHIP_ADDRESS_UNREACHABLE' => 'One or more items are going to an address your chosen shipping method is not available for &mdash; look for %s beside the address above. Either choose a different address for those items, or pick another shipping method.',

    'TEXT_NEED_ANOTHER_ADDRESS' => 'Need another address? ',
    'TEXT_ENTER_NEW_ADDRESS' => 'Enter a new shipping address.',
    'TEXT_DELETE_ITEM' => 'If you changed any quantities, click ',

    // -----
    // Rewritten for v3.0.0. The original explained a quantity box that has been removed:
    // each unit now has its own row, and quantities are changed in the cart where customers
    // already expect to change them. What remains is what the page cannot tell you itself.
    //
    'TEXT_MULTISHIP_INSTRUCTIONS' => 'Every item is listed separately, so you can send each one wherever you like.<br /><br /><strong>Two things worth knowing:</strong><ul><li>If a warning icon appears beside an address, the shipping method you chose is not available for it. Pick another address, or change the shipping method.</li><li>Anything that does not need shipping, such as a gift certificate or a download, is not listed here.</li></ul>',

    // -----
    // Quantities and extra items are the cart's business, not this page's. %1$s and %2$s
    // are links to the cart and to the storefront.
    //
    // -----
    // Says nothing about changing your mind, and that is deliberate.
    //
    // This used to open "Sending more than one of something, or changed your mind about an
    // item?" -- and the decline link a few lines below opens "Changed your mind?". Two links
    // on the same page, the same words, opposite consequences: this one goes to the cart and
    // keeps multiship, that one ends multiship altogether. dbltoe took this one expecting
    // the other and reported being stuck in multiship, which is exactly what the wording
    // promised him.
    //
    // Now says what it is for -- quantities and items -- and leaves "changed your mind" to
    // the link that actually acts on it.
    //
    'TEXT_MULTISHIP_CHANGE_QUANTITIES' => 'Need a different quantity, or want to add or remove something? %1$s to make changes, or %2$s to keep shopping. Your delivery addresses are kept.',
    'TEXT_MULTISHIP_CHANGE_QUANTITIES_CART' => 'Return to Your Cart',
    'TEXT_MULTISHIP_CHANGE_QUANTITIES_SHOP' => 'browse the store',

    // The submit that saves the menu selections. Named for what it does, and the only way
    // to save them without JavaScript, since the menus otherwise submit on change.
    'BUTTON_MULTISHIP_SAVE_ADDRESSES' => 'Save Addresses',

    // -----
    // Shown inside <noscript>, beneath the Save Addresses button, so only a customer without
    // JavaScript ever reads it. Everyone else has their choices saved as they make them and
    // never sees the button at all.
    //
    // An instruction, not an explanation. This customer has no other way to record anything,
    // so what they need is what to do -- not to be told that other people's browsers behave
    // differently, which they can neither see nor act on.
    //
    'TEXT_MULTISHIP_SAVE_ADDRESSES_NOTE' => 'Choose an address for every item above, then press Save Addresses to record your choices.',

    'TEXT_QUANTITIES_CHANGED' => 'One or more product quantities have been changed, but not yet updated.  If you leave this page, those changes will not be saved.  To save those quantity changes, stay on the page and click the update button.',

    // -----
    // Says the method is unavailable, not that the address is undeliverable.
    //
    // The plugin cannot tell those apart. A missing quote means the chosen module returned
    // nothing for that sub-order, and a zone restriction is only one reason. Free shipping is
    // another and a common one: freeoptions and freeshipper qualify on the cart's total,
    // weight or item count, and checkoutInitialize() quotes each address against its own
    // share -- so a $60 cart that qualified whole can fail a $50 threshold twice once it is
    // split, with nothing wrong with either address. Telling that customer their address
    // cannot be delivered to sends them to change something that was never the problem.
    //
    // "above" replaces "click the link below to change the shipping method". That link went
    // when the method moved onto this page in v3.0.0; the choices are now in the fieldset
    // above this message, and the instruction had been pointing at nothing since.
    //
    'ERROR_ADDRESS_INVALID_FOR_SHIPPING_METHOD' => 'The shipping method you chose is not available for one or more of these addresses &mdash; look for %1$s beside them below. Either choose a different address for those items, or pick another shipping method above.',
];
return $define;
