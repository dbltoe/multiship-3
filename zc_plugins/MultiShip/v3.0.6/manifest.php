<?php
// -----
// Multiple Ship-To Addresses, encapsulated-plugin manifest.
//
// Original plugin Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// v3.0.0 encapsulated packaging Copyright (C) 2026 My Zen Cart Host (dbltoe)
// @license  https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
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
// Since v3.0.6 the button opens README.md through the plugin's own admin page
// (index.php?cmd=multiship_readme): behind the admin login, superusers only, in its own
// window. The guide is no longer a public file under zc_plugins. The button is shown only to
// superusers; the page enforces that too, because Zen Cart 2.0 and 2.1 keep the description
// they first saw (zencart/zencart#8031).
//
// -----
// The version folder name and this value must always match.
//
$multiship_version = 'v3.0.6';

// -----
// Declared once and used for both the button below and github_repo at the foot of this file,
// so the two cannot drift apart and leave the button pointing somewhere the manifest does not
// claim.
//
$multiship_github = 'https://github.com/dbltoe/multiship-3';

// -----
// The Zen Cart Plugin Library listing, and the number in it.
//
// The id in this URL is the same number as 'pluginId' below, and the two must not drift apart.
// PluginManager stores pluginId as plugin_control.zc_contrib_id and
// getPluginsAfterCheckingForNewVersionsOnline() posts those ids to zen-cart.com to ask whether
// a newer release exists. A wrong id, or the 0 this manifest carried until now, means store
// owners are never told an update is available -- silently, since nothing anywhere reports the
// lookup failing.
//
// 1823 is confirmed rather than inferred: /plugins/multiple-ship-to-addresses-vb1823 and
// /downloads.php?do=file&id=1823 both resolve to this plugin's library page.
//
$multiship_download = 'https://www.zen-cart.com/downloads.php?do=file&id=1823';

// -----
// The forum support thread, added before the first release rather than after it, deliberately.
//
// On v2.2 and later a later edit is picked up on the next Plugin Manager scan. On v2.0 and
// v2.1 it is not: those releases write plugin_control.description only on the INSERT that
// first creates the row, so whatever is here the first time a store scans this plugin is what
// that store shows for good. Adding the link after release would reach nobody on the older
// half of the supported range short of an uninstall and re-install.
//
$multiship_forum = 'https://www.zen-cart.com/threads/189154';

$multiship_readme_url = 'index.php?cmd=multiship_readme';
// -----
// Zen Cart 3.0.0 reads every manifest on every admin page, early (template resolver), before
// admin access is set up: zen_is_superuser() there is a fatal "Undefined constant
// SUPERUSER_PROFILE" that takes down the whole admin. Ask only once that exists.
//
$multiship_show_readme = true;
if (function_exists('zen_is_superuser') && defined('SUPERUSER_PROFILE')) {
    $multiship_show_readme = zen_is_superuser();
}

// -----
// The Settings button: this plugin's Configuration page, shown only once the plugin is
// installed (v3.0.5).
//
// The page's gID is assigned at install, so it differs from store to store and can't be
// written here; it's read from the admin page the installer registers (page key
// configMultiship). The link is relative ("index.php?cmd=configuration&gID=N") because
// Plugin Manager is an admin page, so it survives a domain or admin-folder rename.
//
// Zen Cart reads manifests only on the Plugin Manager page, but the lookup is still guarded
// so a manifest read anywhere else (no database, catalog side, CLI) just leaves the button
// out. On v2.2 and later this file is re-read on every Plugin Manager visit, so the button
// follows the install state by itself; on v2.0 and v2.1 the installer saves the re-read
// description at Install, Upgrade and Uninstall (refreshPluginDescription()).
//
$multiship_settings_url = '';
if (defined('IS_ADMIN_FLAG') && IS_ADMIN_FLAG === true && defined('TABLE_ADMIN_PAGES')
    && isset($GLOBALS['db']) && is_object($GLOBALS['db'])) {
    $multiship_page = $GLOBALS['db']->Execute(
        "SELECT main_page, page_params FROM " . TABLE_ADMIN_PAGES
        . " WHERE page_key = 'configMultiship' LIMIT 1"
    );
    if (!$multiship_page->EOF && defined($multiship_page->fields['main_page'])) {
        $multiship_settings_url = 'index.php?cmd=' . constant($multiship_page->fields['main_page'])
            . '&' . $multiship_page->fields['page_params'];
    }
}

// -----
// The source, for a store owner who wants to read it or report something against a line of it.
//
// Unguarded, unlike the Read Me button above, which only superusers are shown: this is an
// absolute URL that is correct wherever the file is parsed and for whoever reads it.
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
// -----
// One row of buttons, the layout dbltoe set for every free plugin on 2026-10-27:
//     ReadMe | Settings | Forum Thread | GitHub
// The tighter padding and 4px gaps keep all four on one line in Plugin Manager's side panel
// from a 1366px-wide window up, so the Author line stays above the fold. Settings opens in
// the same tab, being part of this admin; the others open a new one.
//
$multiship_button_gap = '4px';
$multiship_button_style = ' style="margin:0 ' . $multiship_button_gap . ' 0 0;padding-left:8px;padding-right:8px"';
$multiship_button = static function ($url, $label, $new_tab) use ($multiship_button_style) {
    return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"'
        . ($new_tab ? ' target="_blank" rel="noopener noreferrer"' : '')
        . ' class="btn btn-primary" role="button"' . $multiship_button_style . '>' . $label . '</a>';
};

// -----
// The support thread is a button in the row. It was a plain link on its own line until
// 2026-10-05, when dbltoe asked for a Support Thread button on every free plugin; it has
// been labeled "Forum Thread" since v3.0.5.
//
$multiship_links =
    '<div style="margin:10px 0 0;padding:0 0 0 ' . $multiship_button_gap . '">'
    . ($multiship_show_readme ? $multiship_button($multiship_readme_url, 'ReadMe', true) : '')
    . ($multiship_settings_url !== '' ? $multiship_button($multiship_settings_url, 'Settings', false) : '')
    . $multiship_button($multiship_forum, 'Forum Thread', true)
    . $multiship_button($multiship_github, 'GitHub', true)
    . '</div>';

// -----
// The row of buttons is appended to pluginDescription below.
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
        . $multiship_links,
    'pluginAuthor' => 'My Zen Cart Host (dbltoe)',
    // Stored as plugin_control.zc_contrib_id and sent to zen-cart.com to check for newer
    // releases. Must match the id in $multiship_download above; see the note there for what a
    // wrong value costs.
    'pluginId' => 1823,
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
    // v3.0.0 is listed since v3.0.4. v3.0.3 was run on the zclab rig's 3.0.0 site (the
    // master branch, PHP 8.3-8.5) on 2026-10-22: install and uninstall, the admin pages, and a
    // signed-in two-address order through the grid, payment and confirmation to a placed
    // order, its sub-orders, invoice and packing slip, all without an error logged.
    //
    'zcVersions' => ['v2.0.0', 'v2.0.1', 'v2.1.0', 'v2.2.0', 'v2.2.1', 'v2.2.2', 'v2.3.0', 'v3.0.0'],
    'changelog' => '', // online URL (eg github release tag page, or changelog file there) or local filename only, ie: changelog.txt (in same dir as this manifest file)
    // Public, and surfaced as a button in the plugin's description -- see the note above.
    // Declared at the top of this file so the field and the button cannot disagree.
    'github_repo' => $multiship_github,
    'pluginGroups' => [],
];
