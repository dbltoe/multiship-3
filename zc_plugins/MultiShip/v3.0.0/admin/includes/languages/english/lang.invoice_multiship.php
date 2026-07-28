<?php
//
// +----------------------------------------------------------------------+
// |zen-cart Open Source E-commerce                                       |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 The zen-cart developers                           |
// |                                                                      |
// | http://www.zen-cart.com/index.php                                    |
// |                                                                      |
// | Portions Copyright (c) 2003 osCommerce                               |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the GPL license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.zen-cart.com/license/2_0.txt.                             |
// | If you did not receive a copy of the zen-cart license and are unable |
// | to obtain it through the world-wide-web, please send a note to       |
// | license@zen-cart.com so we can mail you a copy immediately.          |
// +----------------------------------------------------------------------+
//  $Id: invoice.php 5961 2007-03-03 17:17:39Z ajeh $
//
$define = [
    'TABLE_HEADING_COMMENTS' => 'Comments',
    'TABLE_HEADING_CUSTOMER_NOTIFIED' => 'Customer Notified',
    'TABLE_HEADING_DATE_ADDED' => 'Date Added',
    'TABLE_HEADING_STATUS' => 'Status',

    'TABLE_HEADING_PRODUCTS_MODEL' => 'Model',
    'TABLE_HEADING_PRODUCTS' => 'Products',
    'TABLE_HEADING_TAX' => 'Tax',
    'TABLE_HEADING_TOTAL' => 'Total',
    'TABLE_HEADING_PRICE_EXCLUDING_TAX' => 'Price (ex)',
    'TABLE_HEADING_PRICE_INCLUDING_TAX' => 'Price (inc)',
    'TABLE_HEADING_TOTAL_EXCLUDING_TAX' => 'Total (ex)',
    'TABLE_HEADING_TOTAL_INCLUDING_TAX' => 'Total (inc)',

    'ENTRY_CUSTOMER' => 'CUSTOMER:',

    'ENTRY_SOLD_TO' => 'SOLD TO:',
    'ENTRY_SHIP_TO' => 'SHIP TO:',
    'ENTRY_PAYMENT_METHOD' => 'Payment Method:',
    'ENTRY_SUB_TOTAL' => 'Sub-Total:',
    'ENTRY_TAX' => 'Tax:',
    'ENTRY_SHIPPING' => 'Shipping:',
    'ENTRY_TOTAL' => 'Total:',
    'ENTRY_DATE_PURCHASED' => 'Date Ordered:',

    'ENTRY_ORDER_ID' => 'Invoice No. ',
    'TEXT_INFO_ATTRIBUTE_FREE' => '&nbsp;-&nbsp;FREE',
];
return $define;
