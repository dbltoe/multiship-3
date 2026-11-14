<?php
// ---------------------------------------------------------------------------
// Part of the Multiple Shipping Addresses plugin for Zen Cart
//
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
//
// @license  https://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License v2.0
// ---------------------------------------------------------------------------

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
} 
$autoLoadConfig[200][] = array(
    'autoType'  => 'init_script',
    'loadFile'  => 'init_multiship.php'
);
$autoLoadConfig[199][] = array(
    'autoType'  => 'class',
    'loadFile'  => 'observers/class.multiship_admin_observer.php',
    'classPath' => DIR_WS_CLASSES
);
$autoLoadConfig[199][] = array(
    'autoType'   => 'classInstantiate',
    'className'  => 'multiship_observer',
    'objectName' => 'multiship'
);