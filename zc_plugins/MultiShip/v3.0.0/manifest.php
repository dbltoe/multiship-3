<?php
// -----
// Multiple Ship-To Addresses, encapsulated-plugin manifest.
//
// Original plugin Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// v3.0.0 encapsulated packaging Copyright (C) 2026 My Zen Cart Host (dbltoe)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
return [
    'pluginVersion' => 'v3.0.0',
    'pluginName' => 'Multiple Ship-To Addresses',
    'pluginDescription' => 'Allows a customer to ship the individual products in their cart to two or more different addresses, splitting the order into per-address sub-orders that can be tracked and status-updated independently in the admin.<br /><br />Built on the <em>Multiple Ship-To Addresses</em> plugin created by lat9 of Vinos de Frutas Tropicales, whose original work provides the order-splitting, per-address shipping-cost and destination-based tax handling that this plugin still relies on.',
    'pluginAuthor' => 'My Zen Cart Host (dbltoe)',
    'pluginId' => 0, // ID from Zen Cart forum
    'zcVersions' => ['v200', 'v201', 'v210', 'v220', 'v221', 'v222', 'v230'],
    'changelog' => '', // online URL (eg github release tag page, or changelog file there) or local filename only, ie: changelog.txt (in same dir as this manifest file)
    'github_repo' => 'https://github.com/dbltoe/multiship',
    'pluginGroups' => [],
];
