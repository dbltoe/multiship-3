<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
class multiship_observer extends base 
{
    public function __construct() 
    {
        if (!empty($_SESSION['multiship']) && $_SESSION['multiship']->isEnabled()) {
            $this->attach(
                $this, array(
                    /* order.php class */
                    'NOTIFY_ORDER_CART_FINISHED',
                    'NOTIFY_ORDER_DURING_CREATE_ADDED_ORDER_HEADER', 
                    'NOTIFY_ORDER_DURING_CREATE_ADDED_ORDERTOTAL_LINE_ITEM', 
                    'NOTIFY_ORDER_DURING_CREATE_ADDED_PRODUCT_LINE_ITEM', 
                    'NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM', 
                    'NOTIFY_ORDER_INVOICE_CONTENT_READY_TO_SEND', 
                    'NOTIFY_ORDER_EMAIL_BEFORE_PRODUCTS', 
                    'NOTIFY_ORDER_PROCESSING_ONE_TIME_CHARGES_BEGIN',
                    
                    /* shopping_cart.php class */
                    'NOTIFIER_CART_REMOVE_START', 
                    'NOTIFIER_CART_UPDATE_QUANTITY_START', 
                    'NOTIFIER_CART_ADD_CART_START',
                    
                    /* /includes/modules/pages/address_book_process/header_php.php */
                    'NOTIFY_ADDRESS_BOOK_PROCESS_VALIDATION',
                    'NOTIFY_MODULE_ADDRESS_BOOK_ADDED_ADDRESS_BOOK_RECORD',

                    /* page header_php.php's */
                    'NOTIFY_HEADER_END_CHECKOUT_PROCESS',
                    'NOTIFY_HEADER_START_CHECKOUT_SHIPPING',
                    'NOTIFY_HEADER_START_CHECKOUT_PAYMENT',
                    
                    /* /includes/modules[/YOUR_TEMPLATE]/checkout_process.php */
                    'NOTIFY_CHECKOUT_PROCESS_AFTER_ORDER_TOTALS_PROCESS',
                    
                    /* /includes/modules/order_total/ot_shipping.php (zc156b+) */
                    'NOTIFY_OT_SHIPPING_TAX_CALCS',

                    // -----
                    // How the customer's order-history detail reaches the multiship template.
                    //
                    // includes/modules/pages/account_history_info/main_template_vars.php exists in
                    // this plugin to swap tpl_account_history_info_default for the multiship
                    // version, and has never been loaded. PageLoader::getBodyCode() tests
                    //     file_exists(DIR_WS_MODULES . 'pages/' . $mainPage . '/main_template_vars.php')
                    // which is the core includes tree only -- there is no plugin lookup for that
                    // filename, unlike the header_php_ and jscript_ prefixes that
                    // listModulePagesFiles() scans.
                    //
                    // So the page's header_php ran and built $multiship_info in full, and the
                    // default template then rendered without it. The customer saw the order but
                    // not the breakout.
                    //
                    // Core emits this notifier one line after setting $body_code, by reference,
                    // for exactly this purpose.
                    //
                    /* /includes/templates/*[/common]/main_template_vars.php */
                    'NOTIFY_MAIN_TEMPLATE_VARS_END',
                )
            );
        }
    }
  
    public function update(&$class, $eventID, $p1, &$p2, &$p3, &$p4, &$p5, &$p6, &$p7, &$p8, &$p9)
    {
        switch ($eventID) {
            // -----
            // Point account_history_info at the multiship template when the order being
            // viewed went to several addresses. $p2 is $body_code, by reference.
            //
            case 'NOTIFY_MAIN_TEMPLATE_VARS_END':
                $this->setMultishipHistoryTemplate($p2);
                break;

            // -----
            // A multiship customer who has just added an address belongs back at the
            // address grid, not at their account address book.
            //
            // Core redirects to FILENAME_ADDRESS_BOOK a few lines after issuing this
            // notifier (address_book_process line 253). Redirecting here pre-empts that,
            // which is the only way to change the destination -- the target in core is a
            // literal, so there is nothing to filter.
            //
            // Guarded on isChosen() so an ordinary address-book addition is untouched.
            //
            // -----
            // Enforce the multiship address ceiling where the insert actually happens.
            //
            // multiship_address checks the limit before drawing its form, but that form
            // posts to core's address_book_process -- so a back button, a second tab or a
            // bookmarked form reaches the insert without passing the check. The limit was
            // advisory rather than enforced, and an eleventh address went in against a
            // setting of ten.
            //
            // This notifier fires before the insert, which is where the decision belongs.
            // Redirecting here rather than setting the $error flag by reference is
            // deliberate: an error would fall through to core's own limit check further
            // down the page, which would send the customer to their address book with
            // "address book full" -- a different limit, and the wrong explanation.
            //
            // Adds only. Editing or deleting an existing address cannot increase the count,
            // and core excludes both from its own check for the same reason.
            //
            case 'NOTIFY_ADDRESS_BOOK_PROCESS_VALIDATION':
                if (!$_SESSION['multiship']->isChosen() || isset($_GET['edit']) || isset($_GET['delete'])) {
                    break;
                }

                $multiship_address_max = (defined('MODULE_MULTISHIP_MAX_ADDRESSES')) ? (int)MODULE_MULTISHIP_MAX_ADDRESSES : 10;
                if (count(zen_get_customer_address_book_entries($_SESSION['customer_id'])) < $multiship_address_max) {
                    break;
                }

                $_SESSION['multiship']->debugNote('address add refused: at the multiship limit of ' . $multiship_address_max . '.');
                $GLOBALS['messageStack']->add_session('multiship', sprintf(ERROR_MULTISHIP_ADDRESS_MAX, $multiship_address_max), 'caution');
                zen_redirect(zen_href_link(FILENAME_CHECKOUT_MULTISHIP, '', 'SSL'));
                break;

            case 'NOTIFY_MODULE_ADDRESS_BOOK_ADDED_ADDRESS_BOOK_RECORD':
                if ($_SESSION['multiship']->isChosen()) {
                    $_SESSION['multiship']->debugNote('address added during multiship; returning to the address grid.');
                    zen_redirect(zen_href_link(FILENAME_CHECKOUT_MULTISHIP, '', 'SSL'));
                }
                break;

            case 'NOTIFY_HEADER_START_CHECKOUT_SHIPPING':
                // -----
                // multiship_shipping_changed was set here, and read on checkout_payment to
                // send the customer back for a recalculation. Both ends are gone; see the
                // NOTIFY_HEADER_START_CHECKOUT_PAYMENT case below for why.
                //
                // -----
                // Restore the customer's route back to the address grid.
                //
                // checkout_multiship requires a shipping method, so a customer arriving
                // from the interstitial is bounced here to choose one. The link back used
                // to be rendered by an override of tpl_checkout_shipping_default.php,
                // which this plugin no longer ships, so without this they would be
                // stranded on the shipping page.
                //
                // Delivered as a message rather than by redirecting from checkout_payment:
                // isSelected() only becomes true once two or more *different* addresses
                // are assigned, so a redirect would trap any customer who opened the grid
                // and left everything going to one address.
                //
                // -----
                // Two different customers arrive here, and telling them the same thing
                // fails one of them.
                //
                // One is on the way *to* the grid and needs pointing at it. The other has
                // just finished there and is being sent back through a page headed Step 1
                // -- which reads as though their work was lost, especially if it repeats
                // "go and set your addresses". They are here because checkoutInitialize()
                // recalculates shipping for the addresses they chose, and this is the only
                // page it runs on, so the message tells them that rather than leaving them
                // to guess they are starting over.
                //
                if ($_SESSION['multiship']->isChosen() && $_SESSION['multiship']->allItemsAssigned()) {
                    $GLOBALS['messageStack']->add(
                        'checkout_shipping',
                        sprintf(
                            MULTISHIP_ADDRESSES_SET,
                            $_SESSION['multiship']->addressCount(),
                            '<a class="multishipActionLink" href="' . zen_href_link(FILENAME_CHECKOUT_MULTISHIP, '', 'SSL') . '">' . MULTISHIP_ADDRESSES_SET_LINK . '</a>'
                            // -----
                            // The button used to be named here, passed in as
                            // BUTTON_CONTINUE_ALT, so the message would always match what
                            // the customer could see. It did not: BUTTON_CONTINUE_ALT is
                            // 'Continue', and both core and ZCA pass it to
                            // zen_image_submit() as the *alt* text -- ZCA labels the button
                            // itself something else, and dbltoe's store renders it
                            // "Continue to Step 2". The message named a control that was
                            // not on the page.
                            //
                            // Tracking whatever a template chose to label its button is not
                            // winnable. Position is: the message now says "click Continue
                            // below" and points down the page instead.
                        ),
                        'caution'
                    );
                } elseif ($_SESSION['multiship']->isChosen()) {
                    $GLOBALS['messageStack']->add(
                        'checkout_shipping',
                        sprintf(
                            MULTISHIP_RETURN_TO_ADDRESSES,
                            // -----
                            // The class is a styling hook, not decoration. Customers were
                            // not recognising a plain inline link as the way forward, so a
                            // store can render it as a button from its own stylesheet --
                            // which is the only place that reliably loads, since plugin CSS
                            // sits behind the active template in the lookup order.
                            //
                            '<a class="multishipContinueLink" href="' . zen_href_link(FILENAME_CHECKOUT_MULTISHIP, '', 'SSL') . '">' . MULTISHIP_RETURN_TO_ADDRESSES_LINK . '</a>'
                        ),
                        'caution'
                    );
                }
                break;
            case 'NOTIFY_HEADER_START_CHECKOUT_PAYMENT':
                // -----
                // The bounce back to checkout_shipping is gone, and it was hurting the wrong
                // customers.
                //
                // It existed because checkout_shipping used to be where a multiship customer
                // chose their method: change it there and the per-address costs had to be
                // recalculated, so arriving at payment with multiship_shipping_changed set
                // sent them back one page to have that happen. The method moved onto the
                // address grid to get the flow down to three steps, and the grid recalculates
                // for itself, so nothing has needed this since.
                //
                // What kept it alive was the wrong guard at the other end.
                // jscript_checkout_shipping_multiship.php was gated on isEnabled(), which asks
                // whether multiship is *available* for this cart -- not whether the customer
                // chose it, and declineMultiship() does not change it. checkout_shipping now
                // renders only for customers who are NOT multishipping, so that script was
                // loading for exactly the people it was never meant for: it made core's
                // shipping page auto-submit on every method change, posted multiship_changed,
                // and then this redirect bounced them from payment back to shipping. One
                // visible detour through the store's ordinary checkout, caused entirely by a
                // plugin they had just declined.
                //
                // All three pieces removed together -- the script, the flag being set on
                // checkout_shipping, and this redirect. Any one of them would have stopped the
                // bounce; leaving the other two would have left machinery with nothing to do
                // and a trap for whoever read it next.
                //
                // Found by dbltoe during release-candidate review.
                //
                $_SESSION['multiship']->fixupSessionShippingCost();

                // -----
                // Say that the split survived, and that the address on this page is not part
                // of it.
                //
                // A customer who has just spent time sending seven items to five addresses
                // arrives at the paying step and is shown exactly one address, with text
                // inviting them to change it. Nothing on the page mentions their deliveries.
                // The address is the billing one and core's wording says so three times, but
                // at the moment of paying, one address where there were five reads as the
                // split having been lost.
                //
                // Verified before writing this: checkout_payment_address, where that button
                // leads, writes $_SESSION['billto'] and nothing else -- it never touches
                // sendto, the shipping method or any multiship state. So the fear is
                // unfounded and the answer is to say so, not to take the button away. It is
                // the customer's only route to correcting a billing address, and unlike the
                // Change Address button on checkout_shipping it destroys nothing.
                //
                if ($_SESSION['multiship']->isChosen()
                    && $_SESSION['multiship']->allItemsAssigned()
                    && defined('MULTISHIP_PAYMENT_BILLING_ONLY')
                    && !empty($GLOBALS['messageStack'])
                    && is_object($GLOBALS['messageStack'])
                ) {
                    $GLOBALS['messageStack']->add(
                        'checkout_payment',
                        sprintf(MULTISHIP_PAYMENT_BILLING_ONLY, $_SESSION['multiship']->addressCount()),
                        'caution'
                    );
                }
                break;
                
            // -----
            // Issued by /includes/modules/order_total/ot_shipping.php just prior to the shipping tax calculations.
            // If the order has multiple ship-to addresses, the session-based class will provide an update to those
            // calculations, based on possibly multiple shipping-tax rates.
            //
            // Parameters:
            //
            // $p2 ... (r/w) A reference to the boolean flag, set to true if the shipping-tax calculations should be overridden.
            // $p3 ... (r/w) A reference to the possibly-updated $shipping_tax value.
            // $p4 ... (r/w) A reference to the possibly-updated $shipping_tax_description
            //
            case 'NOTIFY_OT_SHIPPING_TAX_CALCS':
                $p2 = $_SESSION['multiship']->updateShippingTaxInfo($p3, $p4);
                break;
                
            // -----
            // Issued by /includes/classes/order.php at the end of its conversion of the information in the cart
            // to its order-placement format.  Gives us the opportunity to update the order's information to
            // capture any tax/total information for a multi-ship order.
            //
            case 'NOTIFY_ORDER_CART_FINISHED':
                $_SESSION['multiship']->updateOrdersTotalsAndTaxes($class);
                break;
                
            // -----
            // Issued by /includes/classes/order.php just after writing the overall order's information to the orders table.
            //
            // Parameters:
            // - $p1 ... (r/o) An associative array containing the $sql_data_array used to create the order's header.
            //
            case 'NOTIFY_ORDER_DURING_CREATE_ADDED_ORDER_HEADER':
                $_SESSION['multiship']->createOrderHeader($p1);
                break;
                
            // -----
            // Issued by /includes/classes/order.php, after the creation of each total for the order.
            //
            // Parameters:
            //
            // - $p1 ... (r/o) An associative array containing the $sql_data_array used to create that total's record.
            // - $p2 ... (r/w) A reference to the just-created order_totals record's id.
            //
            case 'NOTIFY_ORDER_DURING_CREATE_ADDED_ORDERTOTAL_LINE_ITEM':
                $_SESSION['multiship']->createOrderFixupTotal($p1, $p2);
                break;
                
            // -----
            // Issued by /includes/classes/order.php, after the creation of each product for the order.
            //
            // Parameters:
            //
            // - $p1 ... (r/o) An associative array containing the $sql_data_array used to create that product's record.
            // - $p2 ... (r/w) A reference to the just-created orders_products record's id.
            //
            case 'NOTIFY_ORDER_DURING_CREATE_ADDED_PRODUCT_LINE_ITEM':
                $_SESSION['multiship']->createOrderAddProducts($p1, $p2);
                break;
                
            // -----
            // Issued by /includes/classes/order.php, after the creation of each product-attribute addition for the order.
            //
            // Parameters:
            //
            // - $p1 ... (r/o) An associative array containing the $sql_data_array used to create that attribute's record.
            // - $p2 ... (r/w) A reference to the just-created orders_products_attributes record's id.
            //
            case 'NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM':
                $_SESSION['multiship']->createOrderAddAttributes($p1);
                break;

            case 'NOTIFY_HEADER_END_CHECKOUT_PROCESS':
                $_SESSION['multiship']->sessionCleanup();
                break;
                
            case 'NOTIFY_ORDER_INVOICE_CONTENT_READY_TO_SEND':
                $_SESSION['multiship']->fixupOrderEmail($class, $p1, $p2, $p3);
                break;
                
            case 'NOTIFY_ORDER_EMAIL_BEFORE_PRODUCTS':
                $_SESSION['multiship']->saveEmailHeader($p2, $p3);
                break;
                
            case 'NOTIFY_ORDER_PROCESSING_ONE_TIME_CHARGES_BEGIN':
                $_SESSION['multiship']->insertAttributesText($class);
                break;
                
            case 'NOTIFIER_CART_REMOVE_START':
                $_SESSION['multiship']->removeProduct($p2);
                break;
                
            case 'NOTIFIER_CART_UPDATE_QUANTITY_START':
                $_SESSION['multiship']->updateProduct($p2, $p3, $p4);
                break;
                
            case 'NOTIFIER_CART_ADD_CART_START':
                $_SESSION['multiship']->_checkAddProductMessage ($p2, $p3, $p4);
                break;
                
            case 'NOTIFY_CHECKOUT_PROCESS_AFTER_ORDER_TOTALS_PROCESS':
                $_SESSION['multiship']->adjustOrdersBaseTotals();
                break;
                
            default:
                break;
        }
    }

    // -----
    // Redirects account_history_info's body to the multiship template for an order that went
    // to several addresses, replacing the main_template_vars.php mechanism that a zc_plugin
    // cannot reach (see the note beside NOTIFY_MAIN_TEMPLATE_VARS_END above).
    //
    // $is_multiship_order and $multiship_info are set by this plugin's
    // header_php_account_history_info_multiship.php, which does load -- index.php gathers
    // 'header_php' files through listModulePagesFiles() well before the body code is chosen.
    //
    // Only the template chosen for this one page is affected; every other page, and a
    // single-address order on this page, falls through untouched.
    //
    protected function setMultishipHistoryTemplate(&$body_code)
    {
        global $current_page_base, $template, $is_multiship_order;

        if (empty($is_multiship_order) || $current_page_base !== FILENAME_ACCOUNT_HISTORY_INFO) {
            return;
        }

        // -----
        // Resolved rather than built by hand, so a store that has put its own copy of this
        // template in its active template directory keeps it: get_template_dir() finds the
        // template's copy at step 3 before this plugin's at step 4.
        //
        $tpl_page_body = '/tpl_account_history_info_multiship.php';
        $template_dir = $template->get_template_dir($tpl_page_body, DIR_WS_TEMPLATE, $current_page_base, 'templates');

        // -----
        // If it cannot be found, leave $body_code alone. The customer then sees the default
        // history page -- their order, without the per-address breakout -- which is what they
        // saw before this existed, rather than a fatal.
        //
        if (file_exists($template_dir . $tpl_page_body)) {
            $body_code = $template_dir . $tpl_page_body;
        }
    }
}
