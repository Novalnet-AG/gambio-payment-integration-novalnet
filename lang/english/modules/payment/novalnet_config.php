<?php
require_once (dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_GLOBAL_CONFIG_TEXT_TITLE', 'Novalnet Global Configuration');
define('MODULE_PAYMENT_NOVALNET_GLOBAL_CONFIG_TEXT_DESC', '<span style="font-weight: bold; color:#878787;">
Please read the Installation Guide before you start and login to the <a href="https://admin.novalnet.de" target="_blank" style="text-decoration: underline; font-weight: bold; color:#0080c9;">Novalnet Admin Portal</a> using your merchant account. To get a merchant account, mail to <a style="font-weight: bold; color:#0080c9;"href="mailto:sales@novalnet.de">sales@novalnet.de</a> or call +49 (089) 923068320</span> <br><br>');
define('MODULE_PAYMENT_NOVALNET_SIGNATURE_TITLE', 'Product activation key');
define('MODULE_PAYMENT_NOVALNET_SIGNATURE_DESC', 'Get your Product activation key from the <a href="https://admin.novalnet.de" target="_blank" style="text-decoration: underline; font-weight: bold; color:#0080c9;">Novalnet Admin Portal</a> Project > Choose your project > API credentials >API Signature (Product activation key)');
define('MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY_TITLE', 'Payment access key');
define('MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY_DESC', 'Get your Payment access key from the <a href="https://admin.novalnet.de" target="_blank" style="text-decoration: underline; font-weight: bold; color:#0080c9;">Novalnet Admin Portal</a> Project > Choose your project > API credentials >Payment access key');
define('MODULE_PAYMENT_NOVALNET_TARIFF_ID_TITLE', 'Select Tariff ID');
define('MODULE_PAYMENT_NOVALNET_TARIFF_ID_DESC', 'Select a Tariff ID to match the preferred tariff plan you created at the Novalnet Admin Portal for this project');
define('MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_COMPLETE_TITLE', '<h2>Order status management for on-hold transactions</h2>Onhold order status');
define('MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_COMPLETE_DESC', 'Status to be used for on-hold orders until the transaction is confirmed or canceled');
define('MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED_TITLE', 'Canceled order status');
define('MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED_DESC', 'Status to be used when order is canceled or fully refunded');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_TEST_MODE_TITLE', '<h2>Notification / Webhook URL Setup</h2><br> Allow manual testing of the Notification / Webhook URL');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_TEST_MODE_DESC', 'Enable this to test the Novalnet Notification / Webhook URL manually. Disable this before setting your shop live to block unauthorized calls from external parties');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_URL_TITLE', 'Notification / Webhook URL');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_URL_DESC', 'You must configure the webhook endpoint in your <a href="https://admin.novalnet.de" target="_blank" style="text-decoration: underline; font-weight: bold; color:#0080c9;">Novalnet Admin Portal</a>. This will allow you to receive notifications about the transaction');
define('MODULE_PAYMENT_NOVALNET_CLIENT_KEY_TITLE', 'Client key');
define('MODULE_PAYMENT_NOVALNET_PROJECT_ID_TITLE', 'Project ID');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_TO_TITLE', '<script src="../ext/novalnet/js/novalnet_config.js" type="text/javascript"></script> <input type="button" id="webhook_url_button" style="font-weight: bold; color:#0080c9;" value="Configure"> <br> Send e-mail to');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_TO_DESC', 'Notification / Webhook URL execution messages will be sent to this e-mail');
?>
