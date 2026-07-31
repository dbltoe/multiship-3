<?php
// ---------------------------------------------------------------------------
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
//
// Copyright (C) 2014-2017, Vinos de Frutas Tropicales (lat9)
//
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
// ---------------------------------------------------------------------------

if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
  die('Illegal Access');
}

class multiship_observer extends base
{
    protected $eventID = '';
    protected $processed_order;

    public function __construct ()
    {
        $this->attach(
            $this, 
            array(
                //-Issued by /includes/classes/order.php
                'NOTIFY_ORDER_AFTER_QUERY',
                
                //-Issued by /admin/includes/classes/order.php (pre-zc156) and on admin for zc156 (albeit deprecated)
                'ORDER_QUERY_ADMIN_COMPLETE',
                
                //-Issued by /admin/orders.php
                'NOTIFY_ADMIN_ORDERS_MENU_LEGEND',
                'NOTIFY_ADMIN_ORDERS_SHOW_ORDER_DIFFERENCE',
                'NOTIFY_ADMIN_ORDERS_UPDATE_ORDER_START',       //-Added by multiship!
                'NOTIFY_ADMIN_ORDERS_EDIT_BEGIN',
                'NOTIFY_ADMIN_ORDERS_EXTRA_STATUS_INPUTS',      //-Added by multiship!

                // -----
                // How the per-address product breakdown reaches the order-detail page in v3.0.0.
                //
                // lat9's version had a patched core orders.php include
                // includes/modules/multiship_orders_products.php directly. An encapsulated plugin
                // ships no core files, so that module had no way in and the admin showed who the
                // order was going to without ever showing what each person was to receive.
                //
                // Core emits this notifier immediately below the purchased-products table, with
                // by-reference content it then echoes -- exactly the position the patch occupied.
                //
                'NOTIFY_ADMIN_ORDERS_CONTENT_UNDER_PRODUCTS',


                //-Issued by /admin/includes/functions/general.php::zen_remove_order
                'NOTIFIER_ADMIN_ZEN_REMOVE_ORDER'
            )
        );
    }
  
    public function update(&$class, $eventID, $p1, &$p2, &$p3, &$p4, &$p5) 
    {
        global $db;
        $this->eventID = $eventID;
        $order_query_admin = false;

        switch ($eventID) {
            // -----
            // Enabling zc155/zc156 interoperability, the zc155 admin order-class issues **only** this
            // event while the zc156 version brings in the storefront version of the class which issues
            // this event (deprecated) _after_ issuing the event that follows.
            //
            // If the NOTIFY_ORDER_AFTER_QUERY event has been processed, there's nothing to do here.  If
            // it hasn't (i.e. zc155), then this clause sets a flag to let the zc156 event "know" that
            // the orders_id has been gathered.
            //
            case 'ORDER_QUERY_ADMIN_COMPLETE':
                if (isset($this->processed_order)) {
                    break;
                }
                $order_query_admin = true;
                if (empty($p1['orders_id'])) {
                    $this->logError('Invalid notification parameters: ' . json_encode($p1));
                }
                $orders_id = (int)$p1['orders_id'];
            case 'NOTIFY_ORDER_AFTER_QUERY':            //-Fall through from above processing
                if (!$order_query_admin) {
                    if (empty($p2)) {
                        $this->logError('Invalid notification parameters: ' . json_encode($p2));
                    }
                    $orders_id = (int)$p2;
                }
                $this->processed_order = true;
        
                $multiship_orders = $db->Execute(
                    "SELECT orders_multiship_id, delivery_name as name, delivery_company as company, delivery_street_address as street_address, delivery_suburb as suburb, 
                            delivery_city as city, delivery_postcode as postcode, delivery_state as state, delivery_country as country, delivery_address_format_id as address_format_id, 
                            orders_status, content_type 
                       FROM " . TABLE_ORDERS_MULTISHIP . " 
                      WHERE orders_id = $orders_id"
                );
                // -----
                // Carried inside $order->info rather than as $order->multiship_info.
                //
                // The order class declares its properties (public array $info = []), and PHP 8.2
                // deprecates creating one that was never declared -- "Creation of dynamic property
                // order::$multiship_info is deprecated". A plugin cannot add a property to a core
                // class, but $info is already an array we are permitted to extend, and
                // is_multiship_order below has always lived there. Same storage, no deprecation,
                // and it removes a dependency on core never declaring that name itself.
                //
                $class->info['is_multiship_order'] = !$multiship_orders->EOF;
                $class->info['multiship_info'] = array();
                while (!$multiship_orders->EOF) {
                    $multiship_id = $multiship_orders->fields['orders_multiship_id'];
                    unset($multiship_orders->fields['orders_multiship_id']);
                    $class->info['multiship_info'][$multiship_id]['info'] = $multiship_orders->fields;

                    $multiship_totals = $db->Execute(
                        "SELECT title, text, value, class
                           FROM " . TABLE_ORDERS_MULTISHIP_TOTAL . "
                          WHERE orders_multiship_id = $multiship_id
                       ORDER BY sort_order"
                    );
                    $class->info['multiship_info'][$multiship_id]['totals'] = array();
                    while (!$multiship_totals->EOF) {
                        $class->info['multiship_info'][$multiship_id]['totals'][] = $multiship_totals->fields;
                        $multiship_totals->MoveNext();
                    }
                    unset ($multiship_totals);
          
                    $multiship_orders->MoveNext();
                }
                unset ($multiship_orders);
        
                $orders_products = $db->Execute(
                    "SELECT orders_multiship_id 
                       FROM " . TABLE_ORDERS_PRODUCTS . " 
                      WHERE orders_id = $orders_id 
                   ORDER BY orders_products_id"
                );
                if ($orders_products->RecordCount() != count($class->products)) {
                    $this->logError('orders_products count mismatch, current: ' . $orders_products->RecordCount() . ', in order: ' . count($class->products));
                }
                $i = 0;
                while (!$orders_products->EOF) {
                    $class->products[$i]['orders_multiship_id'] = $orders_products->fields['orders_multiship_id'];
                    $i++;
                    $orders_products->MoveNext();
                }
                unset($orders_products);
                break;
      
            case 'NOTIFIER_ADMIN_ZEN_REMOVE_ORDER':
                if (!isset($p2) || ((int)$p2) <= 0) {
                    $this->logError("Missing or invalid orders_id in notification params array ($p2).");
                }
                $orders_id = (int)$p2;
                $db->Execute("DELETE FROM " . TABLE_ORDERS_MULTISHIP . " WHERE orders_id = $orders_id");
                $db->Execute("DELETE FROM " . TABLE_ORDERS_MULTISHIP_TOTAL . " WHERE orders_id = $orders_id");
                break;

            case 'NOTIFY_ADMIN_ORDERS_MENU_LEGEND':
                $p2 .= ' ' . zen_image(DIR_WS_IMAGES . 'icon_status_blue.gif', TEXT_MULTISHIP_ORDER, 10, 10) . ' ' . TEXT_MULTISHIP_ORDER;
                break;
                
            case 'NOTIFY_ADMIN_ORDERS_SHOW_ORDER_DIFFERENCE':
                if ($this->isMultiShipOrder($p2['orders_id'])) {
                    $p3 .= zen_image(DIR_WS_IMAGES . 'icon_status_blue.gif', TEXT_MULTISHIP_ORDER, 10, 10) . '&nbsp;';
                }
                break;
                
            case 'NOTIFY_ADMIN_ORDERS_UPDATE_ORDER_START':
                $this->updateMultiShipOrders((int)$p1);
                break;
                
            case 'NOTIFY_ADMIN_ORDERS_EDIT_BEGIN':
                if ($this->isMultiShipOrder($p1)) {
                }
                break;
                
            case 'NOTIFY_ADMIN_ORDERS_EXTRA_STATUS_INPUTS':
                $this->addMultiShipStatusFields($p1, $p2);
                break;

            case 'NOTIFY_ADMIN_ORDERS_CONTENT_UNDER_PRODUCTS':
                $p2 .= $this->getMultiShipProductsContent((int)($p1['oID'] ?? 0));
                break;
      
            default:
                break;
        }
        $this->eventID = '';
    }
  
    public function isMultiShipOrder($order_id) 
    {
        $check = $GLOBALS['db']->Execute(
            "SELECT orders_multiship_id 
               FROM " . TABLE_ORDERS_MULTISHIP . " 
              WHERE orders_id = " . (int)$order_id . " 
              LIMIT 1"
        );
        return !$check->EOF;
    }
    
    protected function updateMultiShipOrders($oID)
    {
        $suborder_changed = false;

        if (isset($_POST['multiship_status']) && is_array($_POST['multiship_status']) && is_array($_POST['multiship_current_status'])) {
            foreach ($_POST['multiship_status'] as $multiship_id => $multiship_status) {
                $multiship_id = (int)$multiship_id;
                $multiship_status = (int)$multiship_status;
                $current_status = (isset($_POST['multiship_current_status'][$multiship_id])) ? (int)$_POST['multiship_current_status'][$multiship_id] : false;
                if ($current_status !== false && $multiship_status != $current_status) {
                    if ($GLOBALS['comments'] != '') {
                        $GLOBALS['comments'] .= "\n";
                    }
                    $GLOBALS['comments'] .= sprintf(MULTISHIP_SUBORDER_STATUS_CHANGED, zen_db_prepare_input($_POST['multiship_shipping_name'][$multiship_id]), $GLOBALS['orders_status_array'][$current_status], $GLOBALS['orders_status_array'][$multiship_status]);

                    $GLOBALS['db']->Execute(
                        "UPDATE " . TABLE_ORDERS_MULTISHIP . "
                            SET orders_status = $multiship_status,
                                last_modified = now()
                          WHERE orders_multiship_id = $multiship_id
                            AND orders_id = $oID
                          LIMIT 1"
                    );
                    $suborder_changed = true;
                }
            }
        }

        // -----
        // Make sure a sub-order change is actually recorded.
        //
        // The UPDATEs above have already run by the time core decides whether anything
        // happened, and that decision does not consider them. zen_update_orders_history()
        // proceeds only when
        //     ($orders_new_status != -1 && $orders_current_status != $orders_new_status) || !empty($email_message)
        // (functions_osh_update.php:87), and $email_message is populated only when
        // $email_include_message is true -- which orders.php:266 takes from the "append
        // comments to email" checkbox. Appending to $GLOBALS['comments'] therefore counts for
        // nothing on its own.
        //
        // So an admin who changed only recipients' statuses, leaving the order's own status
        // alone, got "Warning: Nothing to change. The order was not updated." while the
        // sub-orders had in fact been changed: no orders_status_history row, no record of who
        // changed what or when, and a message stating the opposite of what happened.
        //
        // Setting this makes the comment count, so core writes the history row it would
        // otherwise skip and reports success.
        //
        // It does have one visible consequence, and it is deliberate. If the admin ticked
        // "notify customer" but not "append comments", the email will now carry the
        // sub-order lines. That is the customer being told their delivery's status changed,
        // which is the point of changing it -- and with "notify customer" unticked no email
        // is sent either way, since that is gated separately on $_POST['notify'].
        //
        if ($suborder_changed) {
            $GLOBALS['email_include_message'] = true;
        }
    }
    
    protected function addMultiShipStatusFields($order, &$extra_status_fields)
    {
        if (!empty($order->info['is_multiship_order'])) {
            $orders_statuses = $this->getOrderStatusPulldownList();
            foreach ($order->info['multiship_info'] as $multiship_id => $multiship_info) {
                $hidden_fields = zen_draw_hidden_field("multiship_current_status[$multiship_id]", $multiship_info['info']['orders_status']);
                $hidden_fields .= zen_draw_hidden_field("multiship_shipping_name[$multiship_id]", $multiship_info['info']['name']);
                $extra_status_fields[] = array(
                    'label' => array(
                        'text' => sprintf(MULTISHIP_SUBORDER_STATUS, '<em>' . $multiship_info['info']['name'] . '</em>'),
                        'parms' =>  'style="font-weight: 700;"'
                    ),
                    'input' => zen_draw_pull_down_menu("multiship_status[$multiship_id]", $orders_statuses, $multiship_info['info']['orders_status'], 'class="form-control"') . $hidden_fields
                );
            }
        }
    }

    // -----
    // Renders the per-address product breakdown for the order-detail page.
    //
    // includes/modules/multiship_orders_products.php echoes its markup rather than returning
    // it -- it was written to be included inline by a patched orders.php -- so it is buffered
    // here instead of being rewritten. Keeping it echo-based also keeps it identical in shape
    // to invoice_multiship.php and packingslip_multiship.php, which are still included the
    // ordinary way by this plugin's own admin pages.
    //
    // The module reads $order, $oID, $db and $currencies from its enclosing scope. $oID
    // arrives with the notification; the rest are admin globals, established well before the
    // products table this content sits beneath is rendered.
    //
    // Path is relative to this file rather than DIR_WS_MODULES: the module lives inside the
    // plugin, and nothing in the admin resolves arbitrary plugin includes by name.
    //
    protected function getMultiShipProductsContent(int $oID): string
    {
        $order = $GLOBALS['order'] ?? null;
        if ($oID <= 0 || !is_object($order) || empty($order->info['multiship_info'])) {
            return '';
        }

        $module = __DIR__ . '/../../modules/multiship_orders_products.php';
        if (!file_exists($module)) {
            return '';
        }

        $db = $GLOBALS['db'];
        $currencies = $GLOBALS['currencies'];

        ob_start();
        include $module;
        return (string)ob_get_clean();
    }

    // -----
    // The order-status list in the shape zen_draw_pull_down_menu() wants -- an array of
    // ['id' => .., 'text' => ..].
    //
    // This used to read $GLOBALS['orders_statuses']. Zen Cart 2.x builds that list in
    // zen_getOrdersStatuses(), and orders.php unpacks only the 'orders_status_array' member
    // (an id => name map, the wrong shape) while leaving 'orders_statuses' behind --
    // invoice.php and packingslip.php unpack it, orders.php does not. So the global was
    // never set on the one page this runs on, and the pull-down was handed null: an
    // undefined-variable warning followed by "foreach() argument must be of type
    // array|object" from inside html_output.php, once per sub-order.
    //
    // Asking for the list directly removes the dependency on which admin page happens to
    // have unpacked what. The fallback covers a v2.0.0 store predating the helper.
    //
    protected function getOrderStatusPulldownList(): array
    {
        if (function_exists('zen_getOrdersStatuses')) {
            $statuses = zen_getOrdersStatuses();
            if (!empty($statuses['orders_statuses'])) {
                return $statuses['orders_statuses'];
            }
        }

        $orders_statuses = [];
        $status_query = $GLOBALS['db']->Execute(
            "SELECT orders_status_id, orders_status_name
               FROM " . TABLE_ORDERS_STATUS . "
              WHERE language_id = " . (int)$_SESSION['languages_id'] . "
           ORDER BY orders_status_id"
        );
        foreach ($status_query as $status) {
            $orders_statuses[] = [
                'id' => $status['orders_status_id'],
                'text' => $status['orders_status_name'] . ' [' . $status['orders_status_id'] . ']',
            ];
        }
        return $orders_statuses;
    }


    protected function logError($message) 
    {
        $event_info = ($this->eventID !== '') ? (' (' . $this->eventID . ')') : '';
        throw new \RuntimeException($event_info . ': ' . $message);
    }
}