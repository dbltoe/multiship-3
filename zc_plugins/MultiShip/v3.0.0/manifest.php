<?php
// -----
// Multiple Ship-To Addresses, encapsulated-plugin manifest.
//
// Original plugin Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// v3.0.0 encapsulated packaging Copyright (C) 2026 My Zen Cart Host (dbltoe)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//

// -----
// A "Read Me" button for the Plugin Manager listing.
//
// Plugin Manager builds its buttons from hardcoded setBoxContent() calls in
// PluginManagerController (lines 75-121) with no notifier around them, so a plugin cannot
// add one directly. But pluginDescription is echoed into the same box unescaped -- line 67
// of that class -- so a link carrying Bootstrap's button classes renders alongside the
// genuine buttons.
//
// The readme is reachable over the web despite living under zc_plugins: that directory's
// .htaccess denies everything, then re-allows a whitelist which includes html, css and js.
// So readme.html and its stylesheets serve, while the plugin's PHP stays blocked.
//
// Guarded on DIR_WS_CATALOG being defined. The manifest is included rather than parsed, so
// it must not fatal if it is ever read outside a full storefront/admin context; without
// the constant the button is simply omitted.
//
$multiship_readme_button = '';
if (defined('DIR_WS_CATALOG')) {
    $multiship_readme_button =
        '<br /><br /><a href="' . DIR_WS_CATALOG . 'zc_plugins/MultiShip/v3.0.0/docs/readme.html"'
        . ' target="_blank" rel="noopener" class="btn btn-primary" role="button">Read Me</a>';
}

return [
    'pluginVersion' => 'v3.0.0',
    'pluginName' => 'Multiple Ship-To Addresses',
    'pluginDescription' => 'Allows a customer to ship the individual products in their cart to two or more different addresses, splitting the order into per-address sub-orders that can be tracked and status-updated independently in the admin.<br /><br />Built on the <em>Multiple Ship-To Addresses</em> plugin created by lat9 of Vinos de Frutas Tropicales, whose original work provides the order-splitting, per-address shipping-cost and destination-based tax handling that this plugin still relies on.' . $multiship_readme_button,
    'pluginAuthor' => 'My Zen Cart Host (dbltoe)',
    'pluginId' => 0, // ID from Zen Cart forum
    'zcVersions' => ['v200', 'v201', 'v210', 'v220', 'v221', 'v222', 'v230'],
    'changelog' => '', // online URL (eg github release tag page, or changelog file there) or local filename only, ie: changelog.txt (in same dir as this manifest file)
    'github_repo' => 'https://github.com/dbltoe/multiship',
    'pluginGroups' => [],
];
