<?php
// ---------------------------------------------------------------------------
// Part of the Multiple Shipping Addresses plugin for Zen Cart
//
// Copyright (C) 2014-2017, Vinos de Frutas Tropicales (lat9)
//
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
// ---------------------------------------------------------------------------

// -----
// Every 'class' entry below names its classPath, DIR_WS_CLASSES, even though that is the
// default. On Zen Cart 2.0.x the default is not usable by a plugin.
//
// 2.0's InitSystem::processAutoTypeClass() starts a plugin entry from the FULL path,
// DIR_FS_CATALOG . DIR_WS_CLASSES, and hands it to FileSystem::getRelativeDir() to strip
// the catalog prefix. That method's isCatalogDir() test is inverted -- it answers true only
// when DIR_FS_CATALOG is NOT in the path -- so the absolute path comes back unchanged and
// the plugin file is looked for at zc_plugins/MultiShip/<ver>/catalog/F:/store/includes/...
// It is never found, nothing is included, and the classInstantiate entry that follows
// fatals on every storefront page: "Class multiship_early_observer not found".
//
// A relative classPath is returned by getRelativeDir() as it is, so the lookup lands in the
// plugin's own includes/classes/. 2.1 and later fall back to DIR_WS_CLASSES themselves
// ($entry['classPath'] ?? DIR_WS_CLASSES), so naming it changes nothing there. The admin
// auto-loader has always named it, which is why the admin side never had this problem.
//
// -----
// Must be attached *before* One Page Checkout's own observer is instantiated at
// checkpoint 97, for two reasons: that observer issues NOTIFY_OPC_SET_DISABLED from its
// constructor, and it redirects NOTIFY_HEADER_START_CHECKOUT_SHIPPING to checkout_one.
// Zen Cart notifies observers in attach order, so attaching first is what lets the
// multiship interstitial be reached at all on an OPC store.
//
$autoLoadConfig[90][] = array(
    'autoType' => 'class',
    'loadFile' => 'observers/class.multiship_early_observer.php',
    'classPath' => DIR_WS_CLASSES
);
$autoLoadConfig[90][] = array(
    'autoType' => 'classInstantiate',
    'className' => 'multiship_early_observer',
    'objectName' => 'multiship_early_observer'
);

// -----
// Needs to be instantiated before the init_cart_handler (at checkpoint 140).
//
$autoLoadConfig[131][] = array(
    'autoType' => 'class',
    'loadFile' => 'observers/class.multiship_observer.php',
    'classPath' => DIR_WS_CLASSES
);
$autoLoadConfig[131][] = array(
    'autoType' => 'classInstantiate',
    'className' => 'multiship_observer',
    'objectName'=>'multiship_observer'
);

// -----
// Needs to be instantiated after the messageStack but before the multiship_observer, since
// the observer calls functions in this class.
//
$autoLoadConfig[0][] = array(
    'autoType' => 'class',
    'loadFile' => 'class.multiship.php',
    'classPath' => DIR_WS_CLASSES
);
$autoLoadConfig[130][] = array(
    'autoType' => 'classInstantiate',
    'className' => 'multiship',
    'objectName' => 'multiship',
    'checkInstantiated' => true,
    'classSession' => true
);                                