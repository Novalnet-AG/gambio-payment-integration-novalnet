<?php
/**
 * Novalnet payment method module
 * This module is used for real time processing of
 * Novalnet transaction of customers.
 *
 * Copyright (c) Novalnet
 *
 * Released under the GNU General Public License
 * This free contribution made by request.
 * If you have found this script useful a small
 * recommendation as well as a comment on merchant form
 * would be greatly appreciated.
 *
 * Script : novalnet_invoice.php
 *
 */
require_once (dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_INVOICE_TEXT_TITLE', 'Novalnet Invoice');
define('MODULE_PAYMENT_NOVALNET_INV_PRE_DESC','Once you&#39;ve submitted the order, you will receive an e-mail with account details to make payment');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ALLOWED_TITLE','Allowed zone(-s)');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ALLOWED_DESC','This payment method will be allowed for the mentioned zone(-s). Enter the zone(-s) in the following format E.g: DE, AT, CH. In case if the field is empty, all the zones will be allowed.');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ENABLE_MODULE_TITLE','Enable payment method');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ENABLE_MODULE_DESC','');
define('MODULE_PAYMENT_NOVALNET_INVOICE_TEST_MODE_TITLE','Enable test mode');
define('MODULE_PAYMENT_NOVALNET_INVOICE_TEST_MODE_DESC','The payment will be processed in the test mode therefore amount for this transaction will not be charged');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ENABLE_FRAUDMODULE_TITLE','Enable fraud prevention');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ENABLE_FRAUDMODULE_DESC','To authenticate the buyer for a transaction, the PIN will be automatically generated and sent to the buyer. This service is only available for customers from DE, AT, CH');
define('MODULE_PAYMENT_NOVALNET_INVOICE_CALLBACK_LIMIT_TITLE','Minimum value of goods for the fraud module (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)');
define('MODULE_PAYMENT_NOVALNET_INVOICE_CALLBACK_LIMIT_DESC','Enter the minimum value of goods from which the fraud module should be activated');
define('MODULE_PAYMENT_NOVALNET_INVOICE_DUE_DATE_TITLE','Payment due date (in days)');
define('MODULE_PAYMENT_NOVALNET_INVOICE_DUE_DATE_DESC','Enter the number of days to transfer the payment amount to Novalnet (must be greater than 7 days). In case if the field is empty, 14 days will be set as due date by default');
define('MODULE_PAYMENT_NOVALNET_INVOICE_VISIBILITY_BY_AMOUNT_TITLE','Minimum value of goods (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)');
define('MODULE_PAYMENT_NOVALNET_INVOICE_VISIBILITY_BY_AMOUNT_DESC','Enter the minimum value of goods from which the payment method is displayed to the customer during checkout');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ENDCUSTOMER_INFO_TITLE','Notification for the buyer');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ENDCUSTOMER_INFO_DESC','The entered text will be displayed on the checkout page');
define('MODULE_PAYMENT_NOVALNET_INVOICE_SORT_ORDER_TITLE','Define a sorting order');
define('MODULE_PAYMENT_NOVALNET_INVOICE_SORT_ORDER_DESC','This payment method will be sorted among others (in the ascending order) as per the given sort number.');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ORDER_STATUS_TITLE','Order completion status');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ORDER_STATUS_DESC','');
define('MODULE_PAYMENT_NOVALNET_INVOICE_CALLBACK_ORDER_STATUS_TITLE','Callback order status');
define('MODULE_PAYMENT_NOVALNET_INVOICE_CALLBACK_ORDER_STATUS_DESC','');
define('MODULE_PAYMENT_NOVALNET_INVOICE_PAYMENT_ZONE_TITLE','Payment zone');
define('MODULE_PAYMENT_NOVALNET_INVOICE_PAYMENT_ZONE_DESC','This payment method will be displayed for the mentioned zone(-s)');
define('MODULE_PAYMENT_NOVALNET_INVOICE_TRANS_REFERENCE1_TITLE','Transaction reference 1');
define('MODULE_PAYMENT_NOVALNET_INVOICE_TRANS_REFERENCE1_DESC','This reference will appear in your bank account statement');
define('MODULE_PAYMENT_NOVALNET_INVOICE_TRANS_REFERENCE2_TITLE','Transaction reference 2');
define('MODULE_PAYMENT_NOVALNET_INVOICE_TRANS_REFERENCE2_DESC','This reference will appear in your bank account statement');
define('MODULE_PAYMENT_NOVALNET_INVOICE_PAYMENT_REFERENCE1_TITLE','Payment Reference 1 (Novalnet Invoice Reference)');
define('MODULE_PAYMENT_NOVALNET_INVOICE_PAYMENT_REFERENCE1_DESC','');
define('MODULE_PAYMENT_NOVALNET_INVOICE_PAYMENT_REFERENCE2_TITLE','Payment Reference 2 (TID)');
define('MODULE_PAYMENT_NOVALNET_INVOICE_PAYMENT_REFERENCE2_DESC','');
define('MODULE_PAYMENT_NOVALNET_INVOICE_PAYMENT_REFERENCE3_TITLE','Payment Reference 3 (Order No)');
define('MODULE_PAYMENT_NOVALNET_INVOICE_PAYMENT_REFERENCE3_DESC','');
define('MODULE_PAYMENT_NOVALNET_INVOICE_GUARANTEE_TITLE','<h2>Payment guarantee configuration</h2><h3>Basic requirements for Payment guarantee</h3>Allowed countries: AT, DE, CH <br>Allowed currency: EUR <br> Minimum amount of order >= 20,00 EUR <br>Maximum amount of order <= 5.000,00 EUR <br> Minimum age of end customer >= 18 Years<br> The billing address must be the same as the shipping address <br> Gift certificates/vouchers are not allowed<br><br>Enable payment guarantee');
define('MODULE_PAYMENT_NOVALNET_INVOICE_GUARANTEE_MINIMUM_ORDER_AMOUNT_TITLE','Minimum order amount (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)');
define('MODULE_PAYMENT_NOVALNET_INVOICE_GUARANTEE_MINIMUM_ORDER_AMOUNT_DESC','This setting will override the default setting made in the minimum order amount. Note that amount should be in the range of 20,00 EUR - 5.000,00 EUR');
define('MODULE_PAYMENT_NOVALNET_INVOICE_GUARANTEE_MAXIMUM_ORDER_AMOUNT_TITLE','Maximum order amount (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)');
define('MODULE_PAYMENT_NOVALNET_INVOICE_GUARANTEE_MAXIMUM_ORDER_AMOUNT_DESC','This setting will override the default setting made in the maximum order amount. Note that amount should be greater than minimum order amount, but not more than 5.000,00 EUR');
define('MODULE_PAYMENT_NOVALNET_INVOICE_GUARANTEE_DESC','');
define('MODULE_PAYMENT_NOVALNET_INVOICE_FORCE_TITLE','Force Non-guarantee payment');
define('MODULE_PAYMENT_NOVALNET_INVOICE_FORCE_DESC','If the payment guarantee is activated (True), but the above mentioned requirements are not met, the payment should be processed as non-guarantee payment.');
define('MODULE_PAYMENT_NOVALNET_INVOICE_BLOCK_TITLE','<b>Invoice Configuration</b>');
define('MODULE_PAYMENT_NOVALNET_INVOICE_DUE_DATE_ERROR','Please enter valid due date');
define('MODULE_PAYMENT_NOVALNET_INVOICE_GUARANTEE_ERROR','The payment cannot be processed, because the basic requirements haven’t been met.');
define('MODULE_PAYMENT_NOVALNET_INVOICE_GUARANTEE_MINIMUM_AMOUNT_ERROR','The minimum amount should be at least 20,00 EUR but not more than 5.000,00 EUR');
define('MODULE_PAYMENT_NOVALNET_INVOICE_GUARANTEE_MAXIMUM_AMOUNT_ERROR','The maximum amount should be greater than minimum order amount, but not more than 5.000,00 EUR');
define('MODULE_PAYMENT_NOVALNET_INVOICE_PUBLIC_TITLE', 'Invoice '.(((!defined('MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY')) || MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY == 'True') ? '<a href="http://www.novalnet.com" target="_blank"/><img title="Invoice" alt="Invoice" src="'.DIR_WS_ICONS.'novalnet/novalnet_invoice.png" width=3%></a>':''));
