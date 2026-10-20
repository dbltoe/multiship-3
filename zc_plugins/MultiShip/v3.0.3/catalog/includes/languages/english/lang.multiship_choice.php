<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart
// Original plugin Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// This file new in v3.0.0, Copyright (C) 2026 My Zen Cart Host (dbltoe)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
// Kept deliberately short. This page sits between the customer and their checkout, so
// every extra sentence is friction. The intent is to tell them what is now possible in
// one line, then get out of the way.
//
// %s in TEXT_MULTISHIP_CHOICE_INTRO is the store name, supplied by the template.
//
$define = [
    'NAVBAR_TITLE_MULTISHIP_CHOICE' => 'Delivery Addresses',

    'HEADING_TITLE_MULTISHIP_CHOICE' => 'Shopping for Just You?',

    'TEXT_MULTISHIP_CHOICE_INTRO' => '%s can split this order across as many addresses as you like &mdash; one to cousin Fred, two to Aunt Jane, three to the folks at the bakery &mdash; all shipped straight to them, with no repacking and no second trip to the post office.',

    'BUTTON_MULTISHIP_CHOICE_YES' => 'Send items to different addresses',
    'TEXT_MULTISHIP_CHOICE_YES_HELP' => 'Pick a delivery address for each item. You will need an account.',

    'BUTTON_MULTISHIP_CHOICE_NO' => 'Send everything to one address',
    'TEXT_MULTISHIP_CHOICE_NO_HELP' => 'Continue with the normal checkout.',

    'BUTTON_MULTISHIP_CHOICE_SHOP' => 'Let me add some more items',
    'TEXT_MULTISHIP_CHOICE_SHOP_HELP' => 'Back to shopping. We will ask again when you check out.',
];
return $define;
