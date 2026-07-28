<?php
// ---------------------------------------------------------------------------
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
//
// Copyright (C) 2014-2017, Vinos de Frutas Tropicales (lat9)
//
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
// ---------------------------------------------------------------------------

// -----
// The One Page Checkout bypass must be attached *before* OPC's own observer is
// instantiated at checkpoint 97, since that observer calls checkEnabled() -- which
// issues NOTIFY_OPC_SET_DISABLED -- from its constructor. It deliberately depends
// on nothing but a session flag, as $_SESSION['multiship'] does not exist until 130.
//
$autoLoadConfig[90][] = array(
    'autoType' => 'class',
    'loadFile' => 'observers/class.multiship_opc_observer.php'
);
$autoLoadConfig[90][] = array(
    'autoType' => 'classInstantiate',
    'className' => 'multiship_opc_observer',
    'objectName' => 'multiship_opc_observer'
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