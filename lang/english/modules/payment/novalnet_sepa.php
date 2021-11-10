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
 * Script : novalnet_sepa.php
 *
 */
require_once (dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_SEPA_TEXT_TITLE', 'Direct Debit SEPA');
define('MODULE_PAYMENT_NOVALNET_SEPA_DESC','Your account will be debited upon the order submission');
define('MODULE_PAYMENT_NOVALNET_SEPA_ALLOWED_TITLE','Allowed zone(-s)');
define('MODULE_PAYMENT_NOVALNET_SEPA_ALLOWED_DESC','This payment method will be allowed for the mentioned zone(-s). Enter the zone(-s) in the following format E.g: DE, AT, CH. In case if the field is empty, all the zones will be allowed.');
define('MODULE_PAYMENT_NOVALNET_SEPA_ENABLE_MODULE_TITLE','Enable payment method');
define('MODULE_PAYMENT_NOVALNET_SEPA_ENABLE_MODULE_DESC','');
define('MODULE_PAYMENT_NOVALNET_SEPA_TEST_MODE_TITLE','Enable test mode');
define('MODULE_PAYMENT_NOVALNET_SEPA_TEST_MODE_DESC','The payment will be processed in the test mode therefore amount for this transaction will not be charged');
define('MODULE_PAYMENT_NOVALNET_SEPA_ENABLE_FRAUDMODULE_TITLE','Enable fraud prevention');
define('MODULE_PAYMENT_NOVALNET_SEPA_ENABLE_FRAUDMODULE_DESC','To authenticate the buyer for a transaction, the PIN will be automatically generated and sent to the buyer. This service is only available for customers from DE, AT, CH');
define('MODULE_PAYMENT_NOVALNET_SEPA_CALLBACK_LIMIT_TITLE','Minimum value of goods for the fraud module (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)');
define('MODULE_PAYMENT_NOVALNET_SEPA_CALLBACK_LIMIT_DESC','Enter the minimum value of goods from which the fraud module should be activated');
define('MODULE_PAYMENT_NOVALNET_SEPA_SHOP_TYPE_TITLE','Shopping type');
define('MODULE_PAYMENT_NOVALNET_SEPA_SHOP_TYPE_DESC','Select shopping type');
define('MODULE_PAYMENT_NOVALNET_SEPA_VISIBILITY_BY_AMOUNT_TITLE','Minimum value of goods (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)');
define('MODULE_PAYMENT_NOVALNET_SEPA_VISIBILITY_BY_AMOUNT_DESC','Enter the minimum value of goods from which the payment method is displayed to the customer during checkout');
define('MODULE_PAYMENT_NOVALNET_SEPA_ENDCUSTOMER_INFO_TITLE','Notification for the buyer');
define('MODULE_PAYMENT_NOVALNET_SEPA_ENDCUSTOMER_INFO_DESC','The entered text will be displayed on the checkout page');
define('MODULE_PAYMENT_NOVALNET_SEPA_SORT_ORDER_TITLE','Define a sorting order');
define('MODULE_PAYMENT_NOVALNET_SEPA_SORT_ORDER_DESC','This payment method will be sorted among others (in the ascending order) as per the given sort number.');
define('MODULE_PAYMENT_NOVALNET_SEPA_ORDER_STATUS_TITLE','Order Completion Status');
define('MODULE_PAYMENT_NOVALNET_SEPA_ORDER_STATUS_DESC','');
define('MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_ZONE_TITLE','Payment zone');
define('MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_ZONE_DESC','This payment method will be displayed for the mentioned zone(-s)');
define('MODULE_PAYMENT_NOVALNET_SEPA_TRANS_REFERENCE1_TITLE','Transaction reference 1');
define('MODULE_PAYMENT_NOVALNET_SEPA_TRANS_REFERENCE1_DESC','This reference will appear in your bank account statement');
define('MODULE_PAYMENT_NOVALNET_SEPA_TRANS_REFERENCE2_TITLE','Transaction reference 2');
define('MODULE_PAYMENT_NOVALNET_SEPA_TRANS_REFERENCE2_DESC','This reference will appear in your bank account statement');
define('MODULE_PAYMENT_NOVALNET_SEPA_ONE_CLICK','One click shopping');
define('MODULE_PAYMENT_NOVALNET_SEPA_ZERO_AMOUNT','Zero amount booking');
define('MODULE_PAYMENT_NOVALNET_SEPA_NEW_ACCOUNT', 'Enter new account details');
define('MODULE_PAYMENT_NOVALNET_SEPA_GIVEN_ACCOUNT','Given account details');
define('MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_REFILL_TITLE','Enable auto-fill for payment data');
define('MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_REFILL_DESC','For the registered users SEPA direct debit details will be filled automatically in the payment form');
define('MODULE_PAYMENT_NOVALNET_BANK_COUNTRY' ,'Bank country');
define('MODULE_PAYMENT_NOVALNET_ACCOUNT_OR_IBAN','IBAN or Account number');
define('MODULE_PAYMENT_NOVALNET_BANKCODE_OR_BIC','BIC or Bank code');
define('MODULE_PAYMENT_NOVALNET_SEPA_FORM_MANDATE_CONFIRM_TEXT', 'I hereby grant the SEPA direct debit mandate and confirm that the given IBAN and BIC are correct');
define('MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_DUE_DATE_TITLE','SEPA payment duration (in days)');
define('MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_DUE_DATE_DESC','Enter the number of days after which the payment should be processed (must be greater than 6 days)');
define('MODULE_PAYMENT_NOVALNET_SEPA_AUTO_FILL_TITLE','Enable auto-fill');
define('MODULE_PAYMENT_NOVALNET_SEPA_AUTO_FILL_DESC','The payment details will be filled automatically in the payment form during the checkout process');
define('MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_TITLE','<h2>Payment guarantee configuration</h2><h3>Basic requirements for Payment guarantee</h3>Allowed countries: AT, DE, CH <br>Allowed currency: EUR <br> Minimum amount of order >= 20,00 EUR <br>Maximum amount of order <= 5.000,00 EUR <br> Minimum age of end customer >= 18 Years<br> The billing address must be the same as the shipping address <br> Gift certificates/vouchers are not allowed<br><br>Enable payment guarantee');
define('MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_DESC','');
define('MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_ERROR','The payment cannot be processed, because the basic requirements haven’t been met.');
define('MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_MINIMUM_ORDER_AMOUNT_TITLE','Minimum order amount (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)');
define('MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_MINIMUM_ORDER_AMOUNT_DESC','This setting will override the default setting made in the minimum order amount. Note that amount should be in the range of 20,00 EUR - 5.000,00 EUR');
define('MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_MAXIMUM_ORDER_AMOUNT_TITLE','Maximum order amount (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)');
define('MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_MAXIMUM_ORDER_AMOUNT_DESC','This setting will override the default setting made in the maximum order amount. Note that amount should be greater than minimum order amount, but not more than 5.000,00 EUR');
define('MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_MINIMUM_AMOUNT_ERROR','The minimum amount should be at least 20,00 EUR but not more than 5.000,00 EUR');
define('MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_MAXIMUM_AMOUNT_ERROR','The maximum amount should be greater than minimum order amount, but not more than 5.000,00 EUR');
define('MODULE_PAYMENT_NOVALNET_SEPA_FORCE_TITLE','Force Non-guarantee payment');
define('MODULE_PAYMENT_NOVALNET_SEPA_FORCE_DESC','If the payment guarantee is activated (True), but the above mentioned requirements are not met, the payment should be processed as non-guarantee payment.');
define('MODULE_PAYMENT_NOVALNET_SEPA_BLOCK_TITLE','<b>SEPA Configuration</b>');
define('MODULE_PAYMENT_NOVALNET_SEPA_DUE_DATE_ERROR','SEPA Due date is not valid');
define('MODULE_PAYMENT_NOVALNET_SEPA_MANDATE_CONFIRM_ERROR','Please accept the SEPA direct debit mandate');
define('MODULE_PAYMENT_NOVALNET_SEPA_SELECT_COUNTRY','Please select the country');
define('MODULE_PAYMENT_NOVALNET_SELECT_PAYMENT_METHOD','Please select the payment method');
define('MODULE_PAYMENT_NOVALNET_SEPA_PUBLIC_TITLE', 'Direct Debit SEPA '. (((!defined('MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY')) || MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY == 'True')?'<a href="https://www.novalnet.com" target="_blank"/><img title="Direct Debit SEPA" alt="Direct Debit SEPA" src="'.DIR_WS_ICONS.'novalnet/novalnet_sepa.png"></a>':''));
