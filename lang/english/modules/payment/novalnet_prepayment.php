<?php
/**
 * Novalnet payment method module
 * This module is used for real time processing of
 * Novalnet transaction of customers.
 *
 * @category   PHP
 * @package    NovalnetGateway
 * @author     Novalnet AG
 * @copyright  Copyright by Novalnet
 * @license    https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * Script : novalnet_prepayment.php
 *
 */
require_once (dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_TEXT_TITLE', 'Prepayment');
define('MODULE_PAYMENT_NOVALNET_INV_PRE_DESC','You will receive an e-mail with the Novalnet account details to complete the payment.');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_ALLOWED_TITLE','Allowed zone(-s)');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_ALLOWED_DESC','This payment method will be allowed for the mentioned zone(-s). Enter the zone(-s) in the following format E.g: DE, AT, CH. In case if the field is empty, all the zones will be allowed.');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_STATUS_TITLE','Display payment method');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_STATUS_DESC','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_TEST_MODE_TITLE','Enable test mode');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_TEST_MODE_DESC','The payment will be processed in the test mode therefore amount for this transaction will not be charged');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_VISIBILITY_BY_AMOUNT_TITLE','Minimum value of goods (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_VISIBILITY_BY_AMOUNT_DESC','Enter the minimum value of goods from which the payment method is displayed to the customer during checkout');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_ENDCUSTOMER_INFO_TITLE','Notification for the buyer');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_ENDCUSTOMER_INFO_DESC','The entered text will be displayed on the checkout page');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_SORT_ORDER_TITLE','Display Sort Order');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_SORT_ORDER_DESC','Display sort order; the lowest value is displayed first.');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_ORDER_STATUS_TITLE','Completed order status');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_ORDER_STATUS_DESC','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_CALLBACK_ORDER_STATUS_TITLE','Callback / Webhook order status');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_CALLBACK_ORDER_STATUS_DESC','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_PAYMENT_ZONE_TITLE','Payment zone');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_PAYMENT_ZONE_DESC','When a zone is selected, this payment method will be enabled for that zone only.');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_BLOCK_TITLE','<b>Prepayment Configuration</b>');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_PUBLIC_TITLE', 'Prepayment '.(((!defined('MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY')) || MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY == 'true') ? '<a href="https://www.novalnet.com" target="_blank"/><img title="Prepayment" alt="Prepayment" src="'.DIR_WS_ICONS.'payment/novalnet_prepayment.png" width=9%></a>':''));
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_DUE_DATE_TITLE','Payment due date (in days)');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_DUE_DATE_DESC','Number of days given to the buyer to transfer the amount to Novalnet (must be between 7 and 28 days). If this field is left blank, 14 days will be set as due date by default.');
