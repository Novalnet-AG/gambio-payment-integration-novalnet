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
 * Script : novalnet_sepa.php
 *
 */
require_once (dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_SEPA_TEXT_TITLE', 'Direct Debit SEPA');
define('MODULE_PAYMENT_NOVALNET_SEPA_DESC','The amount will be debited from your account by Novalnet');
define('MODULE_PAYMENT_NOVALNET_SEPA_ALLOWED_TITLE','Allowed zone(-s)');
define('MODULE_PAYMENT_NOVALNET_SEPA_ALLOWED_DESC','This payment method will be allowed for the mentioned zone(-s). Enter the zone(-s) in the following format E.g: DE, AT, CH. In case if the field is empty, all the zones will be allowed.');
define('MODULE_PAYMENT_NOVALNET_SEPA_STATUS_TITLE','Display payment method');
define('MODULE_PAYMENT_NOVALNET_SEPA_STATUS_DESC','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_SEPA_TEST_MODE_TITLE','Enable test mode');
define('MODULE_PAYMENT_NOVALNET_SEPA_TEST_MODE_DESC','The payment will be processed in the test mode therefore amount for this transaction will not be charged');
define('MODULE_PAYMENT_NOVALNET_SEPA_SHOP_TYPE_TITLE','Shopping type');
define('MODULE_PAYMENT_NOVALNET_SEPA_SHOP_TYPE_DESC','Select shopping type');
define('MODULE_PAYMENT_NOVALNET_SEPA_VISIBILITY_BY_AMOUNT_TITLE','Minimum value of goods (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)');
define('MODULE_PAYMENT_NOVALNET_SEPA_VISIBILITY_BY_AMOUNT_DESC','Enter the minimum value of goods from which the payment method is displayed to the customer during checkout');
define('MODULE_PAYMENT_NOVALNET_SEPA_ENDCUSTOMER_INFO_TITLE','Notification for the buyer');
define('MODULE_PAYMENT_NOVALNET_SEPA_ENDCUSTOMER_INFO_DESC','The entered text will be displayed on the checkout page');
define('MODULE_PAYMENT_NOVALNET_SEPA_SORT_ORDER_TITLE','Display Sort Order');
define('MODULE_PAYMENT_NOVALNET_SEPA_SORT_ORDER_DESC','Display sort order; the lowest value is displayed first.');
define('MODULE_PAYMENT_NOVALNET_SEPA_MANUAL_CHECK_LIMIT_DESC', 'Minimum transaction limit for authorization (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)');
define('MODULE_PAYMENT_NOVALNET_SEPA_MANUAL_CHECK_LIMIT_TITLE', 'In case the order amount exceeds the mentioned limit, the transaction will be set on-hold till your confirmation of the transaction. Configure Authorize as payment action and leave the field empty if you wish to process all the transactions as on-hold.');
define('MODULE_PAYMENT_NOVALNET_SEPA_ORDER_STATUS_TITLE','Completed order status');
define('MODULE_PAYMENT_NOVALNET_SEPA_ORDER_STATUS_DESC','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_ZONE_TITLE','Payment zone');
define('MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_ZONE_DESC','When a zone is selected, this payment method will be enabled for that zone only.');
define('MODULE_PAYMENT_NOVALNET_SEPA_ONE_CLICK','One click shopping');
define('MODULE_PAYMENT_NOVALNET_SEPA_ZERO_AMOUNT','Zero amount booking');
define('MODULE_PAYMENT_NOVALNET_SEPA_NEW_ACCOUNT', 'Enter new account details');
define('MODULE_PAYMENT_NOVALNET_SEPA_GIVEN_ACCOUNT','Given account details');
define('MODULE_PAYMENT_NOVALNET_BANK_COUNTRY' ,'Bank country');
define('MODULE_PAYMENT_NOVALNET_ACCOUNT_OR_IBAN','IBAN');
define('MODULE_PAYMENT_NOVALNET_BANKCODE_OR_BIC','BIC or Bank code');
define('MODULE_PAYMENT_NOVALNET_SEPA_FORM_MANDATE_CONFIRM_TEXT', 'I hereby grant the mandate for the SEPA direct debit (electronic transmission) and confirm that the given bank details are correct!');
define('MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_DUE_DATE_TITLE','Payment due date (in days)');
define('MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_DUE_DATE_DESC','Number of days after which the payment is debited (must be between 2 and 14 days)');
define('MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_TITLE','<h2>Payment guarantee configuration</h2><h3>Basic requirements:</h3>Allowed B2C countries: Germany, Austria, Switzerland <br> Allowed B2B countries: European Union <br>Allowed currency: € <br> Minimum order amount: 9,99 € or more <br>Age limit: 18 years or more<br> The billing address must be the same as the shipping address  <br><br>Enable payment guarantee');
define('MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_DESC','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_ERROR','The payment cannot be processed, because the basic requirements haven’t been met.');
define('MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_MINIMUM_ORDER_AMOUNT_TITLE','Minimum order amount (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)');
define('MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_MINIMUM_ORDER_AMOUNT_DESC','Minimum order amount to display the selected payment method (s) at during checkout');
define('MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_MINIMUM_AMOUNT_ERROR','The minimum amount should be at least 9,99 EUR');
define('MODULE_PAYMENT_NOVALNET_SEPA_PENDING_ORDER_STATUS_TITLE','Order status for the pending payment');
define('MODULE_PAYMENT_NOVALNET_SEPA_PENDING_ORDER_STATUS_DESC', '&nbsp;');
define('MODULE_PAYMENT_NOVALNET_SEPA_FORCE_TITLE','Force Non-guarantee payment');
define('MODULE_PAYMENT_NOVALNET_SEPA_FORCE_DESC','Even if payment guarantee is enabled, payments will still be processed as non-guarantee payments if the payment guarantee requirements are not met. Review the requirements under "Enable Payment Guarantee" in the Installation Guide.');
define('MODULE_PAYMENT_NOVALNET_SEPA_BLOCK_TITLE','<b>SEPA Configuration</b>');
define('MODULE_PAYMENT_NOVALNET_SEPA_DUE_DATE_ERROR','Invalid due date');
define('MODULE_PAYMENT_NOVALNET_SEPA_MANDATE_CONFIRM_ERROR','Please accept the SEPA direct debit mandate');
define('MODULE_PAYMENT_NOVALNET_SEPA_SELECT_COUNTRY','Please select the country');
define('MODULE_PAYMENT_NOVALNET_SELECT_PAYMENT_METHOD','Please select the payment method');
define('MODULE_PAYMENT_NOVALNET_SEPA_PUBLIC_TITLE', 'Direct Debit SEPA '. (((!defined('MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY')) || MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY == 'true')?'<a href="https://www.novalnet.com" target="_blank"/><img title="Direct Debit SEPA" alt="Direct Debit SEPA" src="'.DIR_WS_ICONS.'payment/novalnet_sepa.png" height="30px"></a>':''));
define('MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_PAYMENT_PENDING_TEXT','Your order is under verification and we will soon update you with the order status. Please note that this may take upto 24 hours.');
define('MODULE_PAYMENT_NOVALNET_SEPA_ABOUT_MANDATE_TEXT','I authorise (A) Novalnet AG to send instructions to my bank to debit my account and (B) my bank to debit my account in accordance with the instructions from Novalnet AG.<br><br><strong style="text-align:center">Creditor identifier: DE53ZZZ00000004253</strong><br><br><strong>Note: </strong>You are entitled to a refund from your bank under the terms and conditions of your agreement with bank. A refund must be claimed within 8 weeks starting from the date on which your account was debited.');
define('MODULE_PAYMENT_NOVALNET_SEPA_SHOP_TYPE_CLICK_TITLE','Shopping type');
define('MODULE_PAYMENT_NOVALNET_SEPA_SHOP_TYPE_CLICK_DESC','One click shopping <br><br> Payment details stored during the checkout process can be used for future payments');
define('MODULE_PAYMENT_NOVALNET_SEPA_ZERO_AMOUNT_TITLE','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_SEPA_ZERO_AMOUNT_DESC','Zero amount booking');

define('MODULE_PAYMENT_NOVALNET_SEPA_ALLOW_B2B_TITLE','Allow B2B Customers');
define('MODULE_PAYMENT_NOVALNET_SEPA_ALLOW_B2B_DESC','Allow B2B customers to place order');

