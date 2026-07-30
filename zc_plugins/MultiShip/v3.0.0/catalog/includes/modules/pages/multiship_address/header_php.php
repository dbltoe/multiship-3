<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart
// Original plugin Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// This file new in v3.0.0, Copyright (C) 2026 My Zen Cart Host (dbltoe)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
// Lets a multiship customer add a delivery address once the store's address-book limit
// has been reached.
//
// Why this page exists: core's address_book page hides its "Add Address" button and shows
// "A maximum of N address book entries allowed" as soon as the count reaches
// MAX_ADDRESS_BOOK_ENTRIES. Both live in tpl_address_book_default.php, a core page
// template that the active store template overrides -- so a plugin cannot reach either.
// A customer part-way through addressing a multiship order simply hits a dead end.
//
// Why it does NOT reimplement the address form or its validation:
//
// In address_book_process/header_php.php the POST is handled first -- validated at
// lines 69-170, inserted at line 222, redirected at line 253 -- and the limit is only
// checked afterwards, at line 332, guarding the branch that renders a *blank* add form.
// A valid POST therefore inserts and redirects before the limit is ever consulted.
//
// So this page renders the store's own address form and posts it to core. Core validates,
// applies its own zone matching, fires NOTIFY_ADDRESS_BOOK_PROCESS_VALIDATION for other
// plugins, and performs the insert. Nothing is duplicated and nothing can drift.
//
// The dependency is on that ordering. If core ever moves the limit check ahead of the
// POST handling, this page stops being able to add addresses -- it would fail closed,
// redirecting to the address book rather than doing anything unsafe, but it would stop
// working. Worth raising upstream rather than relying on quietly.
//
// Known rough edge: a *failed* validation does not redirect, so core falls through to the
// limit check and sends the customer to the address book with "address book full" rather
// than showing the field error. Wrong message, but not destructive.
//
$zco_notifier->notify('NOTIFY_HEADER_START_MULTISHIP_ADDRESS');

require DIR_WS_MODULES . zen_get_module_directory('require_languages.php');

if (empty($_SESSION['customer_id'])) {
    $_SESSION['navigation']->set_snapshot(array('mode' => 'SSL', 'page' => FILENAME_MULTISHIP_ADDRESS));
    zen_redirect(zen_href_link(FILENAME_LOGIN, '', 'SSL'));
}

if (!isset($_SESSION['multiship'])) {
    zen_redirect(zen_href_link(FILENAME_ADDRESS_BOOK, '', 'SSL'));
}

// -----
// Only reachable while multiship is actually in play. Otherwise the store's own limit
// stands, and the customer belongs on the normal address book page.
//
if (!$_SESSION['multiship']->isChosen()) {
    zen_redirect(zen_href_link(FILENAME_ADDRESS_BOOK, '', 'SSL'));
}

// -----
// The plugin's own ceiling, so "unlimited" is never the answer. Deliberately separate
// from MAX_ADDRESS_BOOK_ENTRIES: that setting governs what an ordinary customer may keep,
// this one governs how far a multiship order may spread.
//
$multiship_address_max = (defined('MODULE_MULTISHIP_MAX_ADDRESSES')) ? (int)MODULE_MULTISHIP_MAX_ADDRESSES : 10;
$multiship_address_count = count(zen_get_customer_address_book_entries($_SESSION['customer_id']));
if ($multiship_address_count >= $multiship_address_max) {
    $messageStack->add_session('multiship', sprintf(ERROR_MULTISHIP_ADDRESS_MAX, $multiship_address_max), 'caution');
    zen_redirect(zen_href_link(FILENAME_CHECKOUT_MULTISHIP, '', 'SSL'));
}

// -----
// Set up exactly the variables the store's tpl_modules_address_book_details.php expects,
// mirroring the "add a new entry" branch of address_book_process (its lines 296-330).
//
$process = false;
$zone_name = '';
$entry_state_has_zones = false;
$error_state_input = false;
$state = '';
$zone_id = 0;
$error = false;

$entry_query =
    "SELECT entry_country_id
       FROM " . TABLE_ADDRESS_BOOK . " a, " . TABLE_CUSTOMERS . " c
      WHERE a.customers_id = :customersID
        AND a.customers_id = c.customers_id
        AND a.address_book_id = c.customers_default_address_id";
$entry_query = $db->bindVars($entry_query, ':customersID', $_SESSION['customer_id'], 'integer');
$entry = $db->Execute($entry_query);

$entry->fields['entry_gender'] = 'm';
$entry->fields['entry_firstname'] = '';
$entry->fields['entry_lastname'] = '';
$entry->fields['entry_company'] = '';
$entry->fields['entry_street_address'] = '';
$entry->fields['entry_suburb'] = '';
$entry->fields['entry_city'] = '';
$entry->fields['entry_state'] = '';
$entry->fields['entry_zone_id'] = 0;
$entry->fields['entry_postcode'] = '';

$selected_country = $entry->fields['entry_country_id'] ?? SHOW_CREATE_ACCOUNT_DEFAULT_COUNTRY;
$entry->fields['entry_country_id'] = $selected_country;

$flag_show_pulldown_states = (ACCOUNT_STATE_DRAW_INITIAL_DROPDOWN === 'true');
$state_field_label = ($flag_show_pulldown_states) ? '' : ENTRY_STATE;

// -----
// Both crumbs come from this page's own language file. An earlier version borrowed
// NAVBAR_TITLE_1 from address_book_process, which is page-specific and therefore not
// defined here -- a fatal on a page that had never been loaded. Only constants from
// lang.english.php, or from this plugin's own files, are safe to use on a mod-owned page.
//
$breadcrumb->add(NAVBAR_TITLE_MULTISHIP_ADDRESSES, zen_href_link(FILENAME_CHECKOUT_MULTISHIP, '', 'SSL'));
$breadcrumb->add(NAVBAR_TITLE_MULTISHIP_ADDRESS);

$zco_notifier->notify('NOTIFY_HEADER_END_MULTISHIP_ADDRESS');
