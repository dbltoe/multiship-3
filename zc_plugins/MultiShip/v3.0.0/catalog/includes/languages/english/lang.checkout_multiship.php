<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
$define = [
    'NAVBAR_TITLE_1' => 'Checkout',
    'NAVBAR_TITLE_2' => 'Choose Multiple Shipping Addresses',

    'HEADING_TITLE' => 'Choose Shipping Address for Each Item',
    // -----
    // Stated, not asked. This only renders once every item has an address, so "All
    // finished?" was a question whose answer was always yes -- and "Return to Checkout"
    // described going back when the customer is going on.
    //
    // Distinct from the Update button beside it, which changes quantities: setting a row
    // to 0 removes that unit, setting it to 3 splits the row into three addressable ones.
    // Update changes what is in the order; this leaves the page.
    //
    'TEXT_CONTINUE_CHECKOUT' => 'Every item has a delivery address.',
    'TEXT_CONTINUE_CHECKOUT_LINK' => 'Continue with Checkout',

    // -----
    // Lets a customer who accepted the offer change their mind. Without it, accepting
    // is irreversible for the rest of the session.
    //
    'TEXT_DECLINE_MULTISHIP' => 'Changed your mind? %s to send this whole order to a single address.',
    'TEXT_DECLINE_MULTISHIP_LINK' => 'Use the Normal Checkout',

    'TEXT_CURRENT_SHIPPING_METHOD' => 'Your current shipping method: ',

    // -----
    // Link text on this page names its destination rather than saying "here".
    //
    // Screen-reader users commonly navigate by pulling up a list of every link on the
    // page, where the surrounding sentence is not read out. "HERE" and "here" tell such a
    // user nothing, and this page previously used both for two links that go to different
    // places. WCAG 2.4.4 (Link Purpose in Context) is the relevant criterion.
    //
    // Sentences are phrased so the link reads as a control rather than running on as
    // prose, which is also what makes it look clickable to everyone else.
    //
    'TEXT_SHIPPING_METHOD_CHANGE' => 'Not the one you want? %s.',
    'TEXT_SHIPPING_METHOD_CHANGE_LINK' => 'Change Shipping Method',

    'HEADING_ITEM' => 'Item',
    'HEADING_PRICE' => 'Price',
    'HEADING_QTY' => 'Qty.',
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

    // %u is how many items are still unanswered.
    'TEXT_MULTISHIP_ITEMS_UNASSIGNED' => 'Please choose a delivery address for every item. %u still need one, and you will not be able to continue until they do.',

    'TEXT_NEED_ANOTHER_ADDRESS' => 'Need another address? ',
    'TEXT_ENTER_NEW_ADDRESS' => 'Enter a new shipping address.',
    'TEXT_DELETE_ITEM' => 'If you changed any quantities, click ',

    'TEXT_MULTISHIP_INSTRUCTIONS' => 'You can delete an item by changing its quantity to 0 and clicking the "Update" button. To send a single item to multiple people, change the item\'s quantity to equal the number of people you\'re sending it to and then click the "Update" button.<br /><br /><strong>Notes:</strong><ul><li>If an icon appears next to a shipping address, that address is not supported by the currently-selected shipping method.</li><li>Any products that you have in your cart that don\'t require shipping (like gift certificates or downloadable products) are not displayed here.</li></ul>',

    'TEXT_QUANTITIES_CHANGED' => 'One or more product quantities have been changed, but not yet updated.  If you leave this page, those changes will not be saved.  To save those quantity changes, stay on the page and click the update button.',

    'ERROR_ADDRESS_INVALID_FOR_SHIPPING_METHOD' => 'One or more of the shipping addresses you previously chose cannot be used for the currently-selected shipping method; see the selections below marked with %1$s.<br /><br />Either modify the marked shipping addresses or click the link below to change the shipping method for your order.',
];
return $define;
