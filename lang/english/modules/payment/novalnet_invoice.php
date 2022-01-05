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
 * Script : novalnet_invoice.php
 *
 */
require_once (dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_INVOICE_TEXT_TITLE', 'Invoice');
define('MODULE_PAYMENT_NOVALNET_INV_PRE_DESC','You will receive an e-mail with the Novalnet account details to complete the payment.');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ALLOWED_TITLE','Allowed zone(-s)');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ALLOWED_DESC','This payment method will be allowed for the mentioned zone(-s). Enter the zone(-s) in the following format E.g: DE, AT, CH. In case if the field is empty, all the zones will be allowed.');
define('MODULE_PAYMENT_NOVALNET_INVOICE_STATUS_TITLE','Display payment method');
define('MODULE_PAYMENT_NOVALNET_INVOICE_STATUS_DESC','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_INVOICE_TEST_MODE_TITLE','Enable test mode');
define('MODULE_PAYMENT_NOVALNET_INVOICE_TEST_MODE_DESC','The payment will be processed in the test mode therefore amount for this transaction will not be charged');
define('MODULE_PAYMENT_NOVALNET_INVOICE_MANUAL_CHECK_LIMIT_DESC', 'Minimum transaction limit for authorization (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)');
define('MODULE_PAYMENT_NOVALNET_INVOICE_MANUAL_CHECK_LIMIT_TITLE', 'In case the order amount exceeds the mentioned limit, the transaction will be set on-hold till your confirmation of the transaction. Configure Authorize as payment action and leave the field empty if you wish to process all the transactions as on-hold.');
define('MODULE_PAYMENT_NOVALNET_INVOICE_DUE_DATE_TITLE','Payment due date (in days)');
define('MODULE_PAYMENT_NOVALNET_INVOICE_DUE_DATE_DESC','
Number of days given to the buyer to transfer the amount to Novalnet (must be greater than 7 days). If this field is left blank, 14 days will be set as due date by default');
define('MODULE_PAYMENT_NOVALNET_INVOICE_VISIBILITY_BY_AMOUNT_TITLE','Minimum value of goods (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)');
define('MODULE_PAYMENT_NOVALNET_INVOICE_VISIBILITY_BY_AMOUNT_DESC','Enter the minimum value of goods from which the payment method is displayed to the customer during checkout');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ENDCUSTOMER_INFO_TITLE','Notification for the buyer');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ENDCUSTOMER_INFO_DESC','The entered text will be displayed on the checkout page');
define('MODULE_PAYMENT_NOVALNET_INVOICE_SORT_ORDER_TITLE','Display Sort Order');
define('MODULE_PAYMENT_NOVALNET_INVOICE_SORT_ORDER_DESC','Display sort order; the lowest value is displayed first.');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ORDER_STATUS_TITLE','Completed order status');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ORDER_STATUS_DESC','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_INVOICE_CALLBACK_ORDER_STATUS_TITLE','Callback / Webhook order status');
define('MODULE_PAYMENT_NOVALNET_INVOICE_CALLBACK_ORDER_STATUS_DESC','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_INVOICE_PAYMENT_ZONE_TITLE','Payment zone');
define('MODULE_PAYMENT_NOVALNET_INVOICE_PAYMENT_ZONE_DESC','When a zone is selected, this payment method will be enabled for that zone only.');
define('MODULE_PAYMENT_NOVALNET_INVOICE_GUARANTEE_TITLE','<h2>Payment guarantee configuration</h2><h3>Basic requirements:</h3>Allowed B2C countries: Germany, Austria, Switzerland <br> Allowed B2B countries: European Union <br>Allowed currency: € <br> Minimum order amount: 9,99 € or more <br>Age limit: 18 years or more<br> The billing address must be the same as the shipping address  <br><br>Enable payment guarantee');
define('MODULE_PAYMENT_NOVALNET_INVOICE_GUARANTEE_MINIMUM_ORDER_AMOUNT_TITLE','Minimum order amount (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)');
define('MODULE_PAYMENT_NOVALNET_INVOICE_GUARANTEE_MINIMUM_ORDER_AMOUNT_DESC','Minimum order amount to display the selected payment method (s) at during checkout');
define('MODULE_PAYMENT_NOVALNET_INVOICE_GUARANTEE_DESC','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_INVOICE_FORCE_TITLE','Force Non-guarantee payment');
define('MODULE_PAYMENT_NOVALNET_INVOICE_FORCE_DESC','Even if payment guarantee is enabled, payments will still be processed as non-guarantee payments if the payment guarantee requirements are not met. Review the requirements under "Enable Payment Guarantee" in the Installation Guide.');
define('MODULE_PAYMENT_NOVALNET_INVOICE_BLOCK_TITLE','<b>Invoice Configuration</b>');
define('MODULE_PAYMENT_NOVALNET_INVOICE_DUE_DATE_ERROR','Invalid due date');
define('MODULE_PAYMENT_NOVALNET_INVOICE_GUARANTEE_ERROR','The payment cannot be processed, because the basic requirements haven’t been met.');
define('MODULE_PAYMENT_NOVALNET_INVOICE_PENDING_ORDER_STATUS_TITLE','Order status for the pending payment');
define('MODULE_PAYMENT_NOVALNET_INVOICE_PENDING_ORDER_STATUS_DESC','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_INVOICE_GUARANTEE_MINIMUM_AMOUNT_ERROR','The minimum amount should be at least 9,99 EUR');
define('MODULE_PAYMENT_NOVALNET_INVOICE_PUBLIC_TITLE', 'Invoice '.(((!defined('MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY')) || MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY == 'true') ? '<a href="https://www.novalnet.com" target="_blank"/><img title="Invoice" alt="Invoice" src="'.DIR_WS_ICONS.'payment/novalnet_invoice.png" width=8%></a>':''));

define('MODULE_PAYMENT_NOVALNET_INVOICE_ALLOW_B2B_TITLE','Allow B2B Customers');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ALLOW_B2B_DESC','Allow B2B customers to place order');

