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
 * Script : novalnet_config.php
 *
 */
require_once (dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_CONFIG_TEXT_TITLE', 'Novalnet Global Configuration (V_11.1.6)');
define('MODULE_PAYMENT_NOVALNET_CONFIG_TEXT_DESCRIPTION', '<span style="font-weight: bold; color:#878787;">For additional configurations login to <a href="https://admin.novalnet.de" target="_blank" style="text-decoration: underline; font-weight: bold; color:#0080c9;">Novalnet Merchant Administration portal</a>.<br/> To login to the Portal you need to have an account at Novalnet. If you don&#39;t have one yet, please contact <a style="font-weight: bold; color:#0080c9;"href="mailto:sales@novalnet.de">sales@novalnet.de</a> / tel. +49 (089) 923068320</span><br/><br/><span style="font-weight: bold; color:#878787;">To use the PayPal payment method please enter your PayPal API details in <a href="https://admin.novalnet.de" target="_blank" style="text-decoration: underline; font-weight: bold; color:#0080c9;">Novalnet Merchant Administration portal</a></span>');
define('MODULE_PAYMENT_NOVALNET_PUBLIC_KEY_TITLE', 'Product activation key');
define('MODULE_PAYMENT_NOVALNET_PUBLIC_KEY_DESC', 'Enter Novalnet Product activation key. To get the Product Activation Key, go to <a href="https://admin.novalnet.de" target="_blank" style="text-decoration: underline; font-weight: bold; color:#0080c9;">Novalnet Merchant Administration portal</a> - PROJECTS: Project Information - Shop Parameters: API Signature (Product activation key).');
define('MODULE_PAYMENT_NOVALNET_VENDOR_ID_TITLE', 'Merchant ID');
define('MODULE_PAYMENT_NOVALNET_VENDOR_ID_DESC', '');
define('MODULE_PAYMENT_NOVALNET_AUTHCODE_TITLE', 'Authentication code');
define('MODULE_PAYMENT_NOVALNET_AUTHCODE_DESC', '');
define('MODULE_PAYMENT_NOVALNET_PRODUCT_ID_TITLE', 'Project ID');
define('MODULE_PAYMENT_NOVALNET_PRODUCT_ID_DESC', '');
define('MODULE_PAYMENT_NOVALNET_TARIFF_ID_TITLE', 'Tariff ID');
define('MODULE_PAYMENT_NOVALNET_TARIFF_ID_DESC', 'Select Novalnet tariff ID');
define('MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY_TITLE', 'Payment access key');
define('MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY_DESC', '');
define('MODULE_PAYMENT_NOVALNET_TEST_ORDER_EMAIL_NOTIFICATION_TITLE', 'Enable E-mail notification for test transaction');
define('MODULE_PAYMENT_NOVALNET_TEST_ORDER_EMAIL_NOTIFICATION_DESC', 'You will receive email notifications about every test order in the web shop.');
define('MODULE_PAYMENT_NOVALNET_LAST_SUCCESSFULL_PAYMENT_SELECTION_TITLE', 'Enable default payment method');
define('MODULE_PAYMENT_NOVALNET_LAST_SUCCESSFULL_PAYMENT_SELECTION_DESC', 'For the registered users the last chosen payment method will be selected by default during the checkout');
define('MODULE_PAYMENT_NOVALNET_PROXY_TITLE', 'Proxy server');
define('MODULE_PAYMENT_NOVALNET_PROXY_DESC', 'Enter the IP address of your proxy server along with the port number in the following format IP Address : Port Number (if applicable)');
define('MODULE_PAYMENT_NOVALNET_GATEWAY_TIMEOUT_TITLE', 'Gateway Timeout (in seconds)');
define('MODULE_PAYMENT_NOVALNET_GATEWAY_TIMEOUT_DESC', 'In case the order processing time exceeds the gateway timeout, the order will not be placed');
define('MODULE_PAYMENT_NOVALNET_REFERRER_ID_TITLE', 'Referrer ID');
define('MODULE_PAYMENT_NOVALNET_REFERRER_ID_DESC', 'Enter the referrer ID of the person/company who recommended you Novalnet');
define('MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY_TITLE', '<h2>Logos display management </h2>You can activate or deactivate the logos display for the checkout page<br><br> Display payment method logo');
define('MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY_DESC', 'The payment method logo will be displayed on the checkout page');
define('MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_COMPLETE_TITLE', '<h2>Order status management for on-hold transaction(-s)</h2>Confirmation order status');
define('MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_COMPLETE_DESC', '');
define('MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED_TITLE', 'Cancellation order status');
define('MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED_DESC', '');
define('MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD_TITLE', '<h2>Dynamic subscription management</h2>Tariff period');
define('MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD_DESC', 'The period of the first subscription cycle (E.g: 1d/1m/1y)');
define('MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD2_AMOUNT_TITLE', 'Amount for the subsequent subscription cycle (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)');
define('MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD2_AMOUNT_DESC', 'The amount for the subsequent subscription cycle');
define('MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD2_TITLE', 'Period for subsequent subscription cycle');
define('MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD2_DESC', 'The period of the subsequent subscription cycle (E.g: 1d/1m/1y)');
define('MODULE_PAYMENT_NOVALNET_SUBSCRIPTION_CANCEL_TITLE', 'Cancellation status of subscription');
define('MODULE_PAYMENT_NOVALNET_SUBSCRIPTION_CANCEL_DESC', '');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_TEST_MODE_TITLE', '<h2>Merchant script management</h2>Deactivate IP address control (for test purpose only)');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_TEST_MODE_DESC', 'This option will allow performing a manual execution. Please disable this option before setting your shop to LIVE mode, to avoid unauthorized calls from external parties (excl. Novalnet).');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_SEND_TITLE', 'Enable E-mail notification for callback');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_SEND_DESC', '');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_TO_TITLE', 'E-mail address (To)');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_TO_DESC', 'E-mail address of the recipient');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_BCC_TITLE', 'E-mail address (Bcc)');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_BCC_DESC', 'E-mail address of the recipient for BCC');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_URL_TITLE', 'Notification URL ');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_URL_DESC', 'The notification URL is used to keep your database/system actual and synchronizes with the Novalnet transaction status.');
