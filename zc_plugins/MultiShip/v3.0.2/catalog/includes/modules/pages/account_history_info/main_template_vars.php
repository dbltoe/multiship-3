<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
// -----
// NOT the mechanism that selects the template on Zen Cart 2.x. See
// NOTIFY_MAIN_TEMPLATE_VARS_END in catalog/includes/classes/observers/class.multiship_observer.php,
// which is what actually does it.
//
// PageLoader::getBodyCode() looks for this file at
//     DIR_WS_MODULES . 'pages/' . $mainPage . '/main_template_vars.php'
// -- the core includes tree only. There is no plugin lookup for this filename, unlike the
// 'header_php' and 'jscript_' prefixes that listModulePagesFiles() scans, so a copy living
// inside zc_plugins is never found and this file never runs.
//
// Kept rather than deleted only because that has been confirmed against v2.3 and not against
// the v2.0.0 floor this plugin still declares. If it does run somewhere, it selects the same
// template the observer would and nothing conflicts. Delete it once the floor is v2.2.0+.
//
$tpl_page_body = (!empty($is_multiship_order)) ? '/tpl_account_history_info_multiship.php' : '/tpl_account_history_info_default.php';
require $template->get_template_dir($tpl_page_body, DIR_WS_TEMPLATE, $current_page_base, 'templates') . $tpl_page_body;
