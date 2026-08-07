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
// -----
// Declared once and used for both the version and the readme path, so bumping the version
// cannot leave the button pointing at a directory that no longer exists. The version
// folder name and this value must always match.
//
$multiship_version = 'v3.0.0';

$multiship_readme_button = '';
if (defined('DIR_WS_CATALOG')) {
    $multiship_readme_button =
        '<br /><br /><a href="' . DIR_WS_CATALOG . 'zc_plugins/MultiShip/' . $multiship_version . '/docs/readme.html"'
        . ' target="_blank" rel="noopener" class="btn btn-primary" role="button">Read Me</a>';
}

// -----
// A GitHub button is deliberately not added yet. github_repo below is populated, but the
// Plugin Manager renders that field nowhere (see docs/multiship_core_requirements.md 2.4),
// and the repository's master branch still holds the pre-v3.0.0 plugin. Linking there now
// would send a store owner to code older than what they have installed. Add it here, the
// same way as the readme button, once the work is merged.
//

return [
    'pluginVersion' => $multiship_version,
    'pluginName' => 'Multiple Ship-To Addresses',
    'pluginDescription' => 'Allows a customer to ship the individual products in their cart to two or more different addresses, splitting the order into per-address sub-orders that can be tracked and status-updated independently in the admin.<br /><br />Built on the <em>Multiple Ship-To Addresses</em> plugin created by lat9 of Vinos de Frutas Tropicales, whose original work provides the order-splitting, per-address shipping-cost and destination-based tax handling that this plugin still relies on.' . $multiship_readme_button,
    'pluginAuthor' => 'My Zen Cart Host (dbltoe)',
    'pluginId' => 0, // ID from Zen Cart forum
    // -----
    // Dotted, because that is the only form the comparison can match.
    //
    // PluginManager::getPluginVersionsFromRepository() builds the value it looks for as
    //     $present_zc_version = 'v' . preg_replace('/[^0-9.]/', '', zen_get_zcversion());
    // and zen_get_zcversion() returns PROJECT_VERSION_MAJOR . '.' . PROJECT_VERSION_MINOR --
    // so a v2.2.2 store is looking for the string 'v2.2.2'. The dotless 'v222' this list used
    // to carry could never match it. Verified identical in v2.3 and in the 3.0.0-dev master,
    // so there is no version where the old form was right. Zen Cart's own bundled plugins
    // disagree with each other on this (SystemInspection dotless, ScanAdditionalImages
    // dotted); the dotted ones are the correct ones.
    //
    // Low severity, which is why it went unnoticed: the sole comparison drives the online
    // catalogue's "a newer version of this plugin exists for your Zen Cart" flag. It does not
    // gate installation, so a wrong value here silently costs store owners an upgrade notice
    // rather than breaking anything.
    //
    // v2.3.0 is listed although no such release exists yet -- tags stop at v2.2.2 and the 2.3
    // branch still declares itself 2.2.2, so a store tracking that branch is matched by
    // 'v2.2.2' today. It is here so the entry is already correct when 2.3.0 ships, which is
    // the branch this plugin was developed against.
    //
    // 3.0.0 is deliberately absent. The 3.0.0-dev master was audited seam by seam and nothing
    // structurally blocks this plugin, but it is a moving target and has never been run
    // against a 3.0.0 store. See docs/multiship_core_requirements.md.
    //
    'zcVersions' => ['v2.0.0', 'v2.0.1', 'v2.1.0', 'v2.2.0', 'v2.2.1', 'v2.2.2', 'v2.3.0'],
    'changelog' => '', // online URL (eg github release tag page, or changelog file there) or local filename only, ie: changelog.txt (in same dir as this manifest file)
    'github_repo' => 'https://github.com/dbltoe/multiship',
    'pluginGroups' => [],
];
