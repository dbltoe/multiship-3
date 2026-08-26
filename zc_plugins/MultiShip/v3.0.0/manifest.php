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

// -----
// Declared once and used for both the button below and github_repo at the foot of this file,
// so the two cannot drift apart and leave the button pointing somewhere the manifest does not
// claim.
//
$multiship_github = 'https://github.com/dbltoe/multiship-3';

$multiship_readme_button = '';
if (defined('DIR_WS_CATALOG')) {
    $multiship_readme_button =
        '<br /><br /><a href="' . DIR_WS_CATALOG . 'zc_plugins/MultiShip/' . $multiship_version . '/docs/readme.html"'
        . ' target="_blank" rel="noopener" class="btn btn-primary" role="button">Read Me</a>';
}

// -----
// The source, for a store owner who wants to read it or report something against a line of it.
//
// Unguarded, unlike the Read Me button above: that one builds a path from DIR_WS_CATALOG and
// has to be omitted if the manifest is ever read outside a storefront or admin context, while
// this is an absolute URL that is correct wherever the file is parsed.
//
// btn btn-primary, because that is what Plugin Manager itself uses. Every button core builds
// in PluginManagerController -- Install, Upgrade, Disable -- carries exactly
//     class="btn btn-primary" role="button"
// so matching it is the only way to look like the buttons beside it.
//
// This started as btn-secondary, on the reasoning that the source should not compete with the
// documentation. It rendered as a plain link: btn-secondary is Bootstrap 4's name and this
// admin does not define it, so the class contributed nothing and the styling fell back to an
// anchor. dbltoe spotted it immediately -- "the GitHub is a link rather than a button like
// ReadMe and Install". Deciding which of two buttons should be quieter is not worth having one
// of them not look like a button at all.
//
$multiship_github_button =
    '&nbsp;<a href="' . $multiship_github . '"'
    . ' target="_blank" rel="noopener" class="btn btn-primary" role="button">GitHub</a>';

// -----
// Cindy's credit, given its own line rather than buried in the description.
//
// It was a sentence inside the description prose, which is where a store owner skims past it.
// This is the pattern dbltoe uses for Email Address Exporter, where DrByte, Swifty8078,
// Ooba_Scott and swguy are acknowledged the same way: a quiet italic line of its own, after
// the buttons and immediately above the Author line that Plugin Manager renders next. It reads
// as what it is -- whose work this was built on -- rather than as a claim about features.
//
// The description no longer repeats it. Said twice in one panel it starts to look like
// disclaiming rather than crediting.
//
// Named for what her code still does, not merely that it existed. The order splitting, the
// per-address quoting and the destination-based tax handling in this version are hers; v3.0.0
// encapsulated and rebuilt around them, and that is a different contribution from having
// worked out how to do it in the first place.
//
$multiship_credits =
    '<div style="margin:8px 0 0;"><em>'
    . 'Originally created by lat9 of Vinos de Frutas Tropicales, whose order splitting, '
    . 'per-address shipping costs and destination-based tax handling this version still '
    . 'relies on.'
    . '</em></div>';

// -----
// Both buttons are appended to pluginDescription below.
//
// This comment used to explain why a GitHub button must never be added: the repository was
// private, so a link would have given every store owner a 404. dbltoe has made it public, so
// the objection is gone and the button is here.
//
// The mechanism is the same one the Read Me button uses. Plugin Manager builds its own buttons
// from hardcoded setBoxContent() calls in PluginManagerController with no notifier anywhere
// near them, so a plugin cannot add one properly -- but pluginDescription is echoed into the
// same box unescaped, so a link carrying Bootstrap's button classes renders alongside the
// genuine ones.
//
// If the repository is ever made private again, this button has to go with it. A link to a
// private repository is a 404 for everyone who is not a collaborator, which is worse than no
// link at all.
//

return [
    'pluginVersion' => $multiship_version,
    'pluginName' => 'Multiple Ship-To Addresses',
    'pluginDescription' =>
        'Allows a customer to ship the individual products in their cart to two or more '
        . 'different addresses, splitting the order into per-address sub-orders that can be '
        . 'tracked and status-updated independently in the admin.'
        . $multiship_readme_button
        . $multiship_github_button
        . $multiship_credits,
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
    // catalog's "a newer version of this plugin exists for your Zen Cart" flag. It does not
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
    // Public, and surfaced as a button in the plugin's description -- see the note above.
    // Declared at the top of this file so the field and the button cannot disagree.
    'github_repo' => $multiship_github,
    'pluginGroups' => [],
];
