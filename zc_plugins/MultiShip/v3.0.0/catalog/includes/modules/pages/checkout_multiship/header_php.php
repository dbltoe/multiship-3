<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
$zco_notifier->notify('NOTIFY_HEADER_START_CHECKOUT_MULTISHIP');
require DIR_WS_MODULES . zen_get_module_directory('require_languages.php');

// -----
// If there's nothing (left) in the customer's cart, redirect them back to the shopping_cart page.
// If there's only one item left in the cart (or the multiship class isn't set), then this cart is not 
// a multiship candidate; redirect the customer back to the checkout_shipping page.
//
$cart_contents = $_SESSION['cart']->count_contents();
if ($cart_contents <= 0) {
    zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
} elseif ($cart_contents == 1 || !isset($_SESSION['multiship'])) {
    zen_redirect(zen_href_link(FILENAME_CHECKOUT_SHIPPING));
}

// if the customer is not logged on, redirect them to the login page
if (empty($_SESSION['customer_id'])) {
    // -----
    // Say why they are suddenly looking at a login page. They chose to ship to several
    // addresses and this is the first thing they see; without a word of explanation the
    // redirect reads as the site losing their place.
    //
    // add_session() rather than add(), since the message has to survive the redirect.
    //
    // -----
    // The login page carries a PayPal Express Checkout button too -- it and the shopping
    // cart are the only two templates that include tpl_ec_button.php. So this is the
    // second place a customer can silently leave multiship behind, and it is a page we
    // deliberately sent them to. Warn about it here for the same reason as on the cart.
    //
    $login_message = MULTISHIP_LOGIN_REQUIRED;
    if (defined('MODULE_PAYMENT_PAYPALWPP_STATUS') && MODULE_PAYMENT_PAYPALWPP_STATUS === 'True') {
        $login_message .= ' ' . SHIP_TO_MULTIPLE_CART_NOTICE_EC;
    }
    $messageStack->add_session('login', $login_message, 'caution');

    $_SESSION['navigation']->set_snapshot(array('mode' => 'SSL', 'page' => FILENAME_CHECKOUT_MULTISHIP));
    zen_redirect(zen_href_link(FILENAME_LOGIN, '', 'SSL'));
} else {
    // validate customer
    if (zen_get_customer_validate_session($_SESSION['customer_id']) == false) {
        $_SESSION['navigation']->set_snapshot();
        zen_redirect(zen_href_link(FILENAME_LOGIN, '', 'SSL'));
    }
}

// -----
// The customer has asked to go back to sending the whole order to one address. Clear
// the multiship intent and any partial address assignments, then hand them back to the
// store's normal checkout.
//
// This must run before the intent is recorded below, or declining would immediately
// re-assert it. On the *next* request the intent flag is gone, so One Page Checkout
// re-enables itself and takes the customer to its own checkout if the store runs it.
//
if (isset($_GET['action']) && $_GET['action'] === 'decline') {
    $_SESSION['multiship']->declineMultiship();
    zen_redirect(zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
}

// -----
// Reaching this point means the customer has a qualifying cart, is logged in and has
// asked to ship to multiple addresses. Record that intent now, *before* any of the
// redirects below.
//
// This matters because the next redirect can send the customer to checkout_shipping to
// pick a shipping method. That is a page One Page Checkout hijacks, so unless the intent
// flag is already set when the following request reaches autoLoadConfig[90], OPC will
// redirect them into one-page checkout and the multiship flow is lost.
//
$_SESSION['multiship']->chooseMultiship();

// if no shipping method has been selected, redirect the customer to the shipping method selection page
if (!isset($_SESSION['shipping'])) {
    $zco_notifier->notify('CHECKOUT_MULTISHIP_SHIPPING_NOT_SELECTED');
    zen_redirect(zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
}
if (isset($_SESSION['shipping']['id']) && $_SESSION['shipping']['id'] == 'free_free' && defined('MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER') && $_SESSION['cart']->get_content_type() != 'virtual' && $_SESSION['cart']->show_total() < MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER) {
    $zco_notifier->notify('CHECKOUT_MULTISHIP_FREE_SHIPPING_MISMATCH');
    zen_redirect(zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
}

// -----
// Addressing a corner-case scenario.  If a customer has entered some multiple ship-to addresses
// and subsequently changed the shipping-method so that one or more of those previously-entered
// addresses are no longer valid, we need a way to let the 'checkout_shipping' processing "know"
// that it should ignore those invalid addresses, from a 'multiship' standpoint, so that the 
// customer can make their change/correction.
//
if (isset($_GET['address_correction'])) {
    $ms_cart = $_SESSION['multiship']->getCart();
    foreach ($ms_cart as $address_id => $info) {
        if (isset($info['address-error'])) {
            $_SESSION['multiship_new_shipping'] = true;
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
        }
    }
}

// -----
// If the page's form has been posted, see what the customer wants to do.  The form can be posted either by
//
// - Changing one of the ship-to address selections (an onchange submission)
// - Clicking the "Update" button after (possibly) changing one or more of the item quantities.  The default
//   quantity for each item is '1', so if an item's quantity has been set to 0, the cart's quantity is reduced
//   by 1.  If the item's quantity is set to a positive, non-1 number, then the cart's quantity is increased
//   by that amount (less the '1').
//
if (isset($_POST['securityToken'])) {
    // -----
    // Quantity handling used to live here: a box on each row where 0 removed that unit and
    // 3 split it into three. It has been removed for v3.0.0.
    //
    // Every unit already has its own row, so a column of boxes all reading 1 explained
    // nothing, and its real behaviour was discoverable only by reading the instructions
    // above the grid. Quantities are the cart's business and customers already know to
    // change them there, so the page now links back rather than duplicating it.
    //
    // Removing it also removed the trickiest code on the page: it rewrote $_POST in place,
    // unsetting entries and appending others, which left the address and prid arrays with
    // gaps that everything downstream had to keep in step.
    //
    // -----
    // Check to see that the ship-to addresses currently selected are 'compatible' with
    // the currently-selected shipping method.
    //
    // -----
    // Rows the customer has not yet answered post an empty address, because the Send To
    // menu opens on a "please choose" prompt rather than defaulting to the primary
    // address. Those are dropped here, keeping address and prid aligned by index, so that
    // neither addressValidation() nor setMultiship() ever sees an empty address -- the
    // former would try to quote for it, the latter would use it as an array key.
    //
    // Note the quantity handling above can unset() entries, leaving gaps in the arrays.
    // foreach preserves the original keys, so $_POST['prid'][$i] still pairs correctly.
    //
    $multiship_chosen_addresses = [];
    $multiship_chosen_prids = [];
    foreach (($_POST['address'] ?? []) as $i => $posted_address) {
        if ($posted_address === '' || $posted_address === null) {
            continue;
        }
        $multiship_chosen_addresses[] = $posted_address;
        $multiship_chosen_prids[] = $_POST['prid'][$i];
    }

    if ($multiship_chosen_addresses === []) {
        $_SESSION['multiship']->sessionCleanup();
    } elseif (!$_SESSION['multiship']->addressValidation($multiship_chosen_addresses)) {
        $messageStack->add('multiship', ERROR_ADDRESS_NOT_VALID_FOR_SHIPPING, 'error');
    } else {
        // -----
        // Record the customer's multiship selection in the session variable.
        //
        $_SESSION['multiship']->setMultiship($multiship_chosen_addresses, $multiship_chosen_prids);
    }
}

$multiship_selected = $_SESSION['multiship']->isSelected();

// -----
// Build up address list input to create product-by-product drop-down address selection.
//
$addresses = $db->Execute(
    "SELECT address_book_id 
       FROM " . TABLE_ADDRESS_BOOK . " 
      WHERE customers_id = " . (int)$_SESSION['customer_id'] . "
      ORDER BY address_book_id ASC"
);
if ($addresses->EOF) {
    zen_redirect(zen_href_link(FILENAME_ADDRESS_BOOK, '', 'SSL'));
}
// -----
// A prompt sits at the top of every Send To menu and is what an unanswered row shows.
//
// Previously a row defaulted to the customer's primary address, so a customer who missed
// one silently shipped that item to themselves -- the failure is invisible until the
// parcel arrives at the wrong door. This mirrors how a product with required attributes
// refuses to be added until a choice is made: nothing is assumed on the customer's behalf.
//
// Its id is an empty string; the POST handling above drops those rows rather than treating
// the empty value as an address.
//
$multishipAddresses = array(
    array(
        'id' => '',
        'text' => TEXT_SELECT_ADDRESS_PROMPT,
    ),
);
while (!$addresses->EOF) {
    $multishipAddresses[] = array( 
        'id' => $addresses->fields['address_book_id'],
        'text' => str_replace("\n", ', ', zen_address_label($_SESSION['customer_id'], $addresses->fields['address_book_id']))
    );
    $addresses->MoveNext();
}

// -----
// Reassure a customer holding more addresses than the store's normal limit.
//
// The query above deliberately carries no LIMIT, so every address is offered here. Core's
// address book, however, counts against MAX_ADDRESS_BOOK_ENTRIES and will tell the same
// customer they have reached the maximum. Left unexplained that reads as though addresses
// have been lost, at precisely the moment they are about to depend on them.
//
if (defined('MAX_ADDRESS_BOOK_ENTRIES') && count($multishipAddresses) > (int)MAX_ADDRESS_BOOK_ENTRIES) {
    $messageStack->add(
        'multiship',
        sprintf(TEXT_MULTISHIP_OVER_ADDRESS_LIMIT, count($multishipAddresses), (int)MAX_ADDRESS_BOOK_ENTRIES),
        'caution'
    );
}

// -----
// Build up the products' list, one entry for each item currently in the cart, so each entry is associated with a quantity of 1
// for the specified product.
//
$products = $_SESSION['cart']->get_products();
$products_onetime_charges = false;
for ($i = 0, $productsArray = array(), $n = count($products); $i < $n; $i++) {
    $currentProduct = array( 
        'id' => $products[$i]['id'],
        'name' => $products[$i]['name'],
        'price' => $currencies->format($products[$i]['final_price']),
     );
    if ($products[$i]['onetime_charges'] != 0) {
        $products_onetime_charges = true;
        $currentProduct['price'] .= '<span class="onetime_charge">' . ONETIME_CHARGE_INDICATOR . '</span>';
    
    }
  
    if (isset($products[$i]['attributes']) && is_array($products[$i]['attributes'])) {
        $options_order_by = (PRODUCTS_OPTIONS_SORT_ORDER == '0') ? ' ORDER BY LPAD(po.products_options_sort_order,11,"0")' : ' ORDER BY po.products_options_name';
        $currentProduct['attributes'] = array();
        foreach ($products[$i]['attributes'] as $option => $value) {
            $attributes = 
                "SELECT po.products_options_name, pov.products_options_values_name
                   FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                        INNER JOIN " . TABLE_PRODUCTS_OPTIONS . " po
                            ON po.products_options_id = pa.options_id
                           AND po.language_id = :languageID
                        INNER JOIN " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov
                            ON pov.products_options_values_id = pa.options_values_id
                           AND pov.language_id = :languageID
                     WHERE pa.products_id = :productsID
                     AND pa.options_id = :optionsID
                     AND pa.options_values_id = :optionsValuesID$options_order_by";

            $attributes = $db->bindVars($attributes, ':productsID', $products[$i]['id'], 'integer');
            $attributes = $db->bindVars($attributes, ':optionsID', $option, 'integer');
            $attributes = $db->bindVars($attributes, ':optionsValuesID', $value, 'integer');
            $attributes = $db->bindVars($attributes, ':languageID', $_SESSION['languages_id'], 'integer');
            $attributes_values = $db->Execute($attributes);

            if ($value == PRODUCTS_OPTIONS_VALUES_TEXT_ID) {
                $attr_value = htmlspecialchars($products[$i]['attributes_values'][$option], ENT_COMPAT, CHARSET, TRUE);
            } else {
                $attr_value = $attributes_values->fields['products_options_values_name'];
            }

            $currentProduct['attributes'][] = array( 
                'name' => $attributes_values->fields['products_options_name'], 
                'value' => $attr_value
            );
            $zco_notifier->notify("CHECKOUT_MULTISHIP_ATTRIBUTES ($option => $value):", $attributes, $attributes_values, $currentProduct, $attr_value);
        }
    }
    for ($j = 0; $j < $products[$i]['quantity']; $j++) {
        $productsArray[] = $currentProduct;
    }
}

// -----
// Now, add the ship-to addresses to be associated with each product, keeping in mind that an instance of the
// product could have been either added or removed during prior processing.
//
$multiship_details = $_SESSION['multiship']->getCart();
$invalid_address_present = false;
$multiship_unassigned = 0;
for ($i = 0, $n = count($productsArray); $i < $n; $i++) {
    // -----
    // Unassigned, not "the primary address". A row only gains an address once the customer
    // has chosen one; anything still unanswered shows the prompt and is counted below so
    // the page can refuse to send them onward with items nobody has claimed.
    //
    $productsArray[$i]['sendto'] = '';
    $prid = $productsArray[$i]['id'];
    $productsArray[$i]['is_physical'] = $_SESSION['multiship']->cartItemIsPhysical($prid);
    foreach ($multiship_details as $address_id => &$currentProducts) {
        if (isset($currentProducts['address-error'])) {
            $invalid_address_present = true;
        }
        if (isset($currentProducts[$prid]) && $currentProducts[$prid] > 0) {
            $productsArray[$i]['sendto'] = $address_id;
            $currentProducts[$prid]--;
            break;
        }
    }
    unset($currentProducts);

    if ($productsArray[$i]['sendto'] === '') {
        $multiship_unassigned++;
    }
}

// -----
// $multiship_unassigned drives the template: while it is non-zero the route onward is
// replaced by a reminder in the same position, so nothing can be carried into checkout
// half-answered. Deliberately not pushed to the messageStack, which renders at the top of
// the page -- the customer is working at the bottom of a long grid, and a message they
// have already scrolled past is not a reminder.
//

$checkout_shipping_link = ($invalid_address_present) ? zen_href_link(FILENAME_CHECKOUT_MULTISHIP, 'address_correction', 'SSL') : zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL');

if ($invalid_address_present) {
    $messageStack->add('multiship', sprintf(ERROR_ADDRESS_INVALID_FOR_SHIPPING_METHOD, MULTISHIP_ICON_NO_SHIP), 'error');
}

$breadcrumb->add(NAVBAR_TITLE_1, zen_href_link(FILENAME_CHECKOUT_CONFIRMATION, '', 'SSL'));
$breadcrumb->add(NAVBAR_TITLE_2);

//$flag_disable_right = $flag_disable_left = true;

// This should be last line of the script:
$zco_notifier->notify('NOTIFY_HEADER_END_CHECKOUT_MULTISHIP');
