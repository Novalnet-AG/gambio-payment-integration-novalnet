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
define('MODULE_PAYMENT_NOVALNET_CONFIG_TEXT_TITLE', 'Novalnet Global Configuration (V_11.3.0)');
define('MODULE_PAYMENT_NOVALNET_CONFIG_TEXT_DESCRIPTION', '<span style="font-weight: bold; color:#878787;">
Please read the Installation Guide before you start and login to the <a href="https://admin.novalnet.de" target="_blank" style="text-decoration: underline; font-weight: bold; color:#0080c9;">Novalnet Admin Portal</a> using your merchant account. To get a merchant account, mail to <a style="font-weight: bold; color:#0080c9;"href="mailto:sales@novalnet.de">sales@novalnet.de</a> or call +49 (089) 923068320</span> <br><br>');
define('MODULE_PAYMENT_NOVALNET_PUBLIC_KEY_TITLE', 'Product activation key');
define('MODULE_PAYMENT_NOVALNET_PUBLIC_KEY_DESC', '
Get your Product activation key from the <a href="https://admin.novalnet.de" target="_blank" style="text-decoration: underline; font-weight: bold; color:#0080c9;">Novalnet Admin Portal</a> : PROJECT > Choose your project > Shop Parameters > API Signature (Product activation key)');
define('MODULE_PAYMENT_NOVALNET_VENDOR_ID_TITLE', 'Merchant ID');
define('MODULE_PAYMENT_NOVALNET_VENDOR_ID_DESC', '&nbsp;');
define('MODULE_PAYMENT_NOVALNET_AUTHCODE_TITLE', 'Authentication code');
define('MODULE_PAYMENT_NOVALNET_AUTHCODE_DESC', '&nbsp;');
define('MODULE_PAYMENT_NOVALNET_PRODUCT_ID_TITLE', 'Project ID');
define('MODULE_PAYMENT_NOVALNET_PRODUCT_ID_DESC', '&nbsp;');
define('MODULE_PAYMENT_NOVALNET_TARIFF_ID_TITLE', 'Select Tariff ID');
define('MODULE_PAYMENT_NOVALNET_TARIFF_ID_DESC', 'Select a Tariff ID to match the preferred tariff plan you created at the <a href="https://admin.novalnet.de" target="_blank" style="text-decoration: underline; font-weight: bold; color:#0080c9;">Novalnet Admin Portal</a> for this project');
define('MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY_TITLE', 'Payment access key');
define('MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY_DESC', '&nbsp;');
define('MODULE_PAYMENT_NOVALNET_CLIENT_KEY_TITLE', 'Client key');
define('MODULE_PAYMENT_NOVALNET_CLIENT_KEY_DESC', '&nbsp;');
define('MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY_TITLE', 'Display payment logo');
define('MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY_DESC', 'The payment method logo(s) will be displayed on the checkout page');
define('MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_COMPLETE_TITLE', '<h2>Order status management for on-hold transactions</h2>Onhold order status');
define('MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_COMPLETE_DESC', '&nbsp;');
define('MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED_TITLE', 'Canceled order status');
define('MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED_DESC', '&nbsp;');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_TEST_MODE_TITLE', '<h2>Notification / Webhook URL Setup</h2>');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_TEST_MODE_DESC', '&nbsp;');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_SEND_TITLE', 'Enable E-mail notification for callback');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_SEND_DESC', '&nbsp;');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_TO_TITLE', 'Send e-mail to');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_TO_DESC', 'Notification / Webhook URL execution messages will be sent to this e-mail');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_URL_TITLE', 'Notification / Webhook URL');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_URL_DESC', 'You must add the following webhook endpoint to your <a href="https://admin.novalnet.de" target="_blank" style="text-decoration: underline; font-weight: bold; color:#0080c9;">Novalnet Admin Portal</a> . This will allow you to receive notifications about the transaction status.');
