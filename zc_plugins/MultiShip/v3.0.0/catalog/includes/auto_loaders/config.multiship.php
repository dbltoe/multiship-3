<?php
// ---------------------------------------------------------------------------
// Part of the Multiple Shipping Addresses plugin for Zen Cart
//
// Copyright (C) 2014-2017, Vinos de Frutas Tropicales (lat9)
//
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
// ---------------------------------------------------------------------------

// -----
// Must be attached *before* One Page Checkout's own observer is instantiated at
// checkpoint 97, for two reasons: that observer issues NOTIFY_OPC_SET_DISABLED from its
// constructor, and it redirects NOTIFY_HEADER_START_CHECKOUT_SHIPPING to checkout_one.
// Zen Cart notifies observers in attach order, so attaching first is what lets the
// multiship interstitial be reached at all on an OPC store.
//
$autoLoadConfig[90][] = array(
    'autoType' => 'class',
    'loadFile' => 'observers/class.multiship_early_observer.php'
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
    'loadFile' => 'observers/class.multiship_observer.php'
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
    'loadFile' => 'class.multiship.php'
);
$autoLoadConfig[130][] = array(
    'autoType' => 'classInstantiate',
    'className' => 'multiship',
    'objectName' => 'multiship',
    'checkInstantiated' => true,
    'classSession' => true
);                                