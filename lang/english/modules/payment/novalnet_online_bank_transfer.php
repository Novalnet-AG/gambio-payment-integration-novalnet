<?php
include_once (dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_ONLINE_BANK_TRANSFER_TEXT_TITLE', 'Novalnet Online bank transfer');
define('MODULE_PAYMENT_NOVALNET_ONLINE_BANK_TRANSFER_TEXT_DESCRIPTION', 'The payment will be processed in the test mode therefore amount for this transaction will not be charged');
define('MODULE_PAYMENT_NOVALNET_ONLINE_BANK_TRANSFER_ALLOWED_TITLE','Allowed zone(-s)');
define('MODULE_PAYMENT_NOVALNET_ONLINE_BANK_TRANSFER_ALLOWED_DESC','This payment method will be allowed for the mentioned zone(-s). Enter the zone(-s) in the following format E.g: DE, AT, CH. In case if the field is empty, all the zones will be allowed.');
define('MODULE_PAYMENT_NOVALNET_ONLINE_BANK_TRANSFER_STATUS_TITLE','Enable payment method');
define('MODULE_PAYMENT_NOVALNET_ONLINE_BANK_TRANSFER_STATUS_DESC','Do you want to accept online bank transfer payment?');
define('MODULE_PAYMENT_NOVALNET_ONLINE_BANK_TRANSFER_TEST_MODE_TITLE','Enable test mode');
define('MODULE_PAYMENT_NOVALNET_ONLINE_BANK_TRANSFER_TEST_MODE_DESC','The payment will be processed in the test mode therefore amount for this transaction will not be charged');
define('MODULE_PAYMENT_NOVALNET_ONLINE_BANK_TRANSFER_SORT_ORDER_TITLE','Display Sort Order');
define('MODULE_PAYMENT_NOVALNET_ONLINE_BANK_TRANSFER_SORT_ORDER_DESC','Display sort order; the lowest value is displayed first.');
define('MODULE_PAYMENT_NOVALNET_ONLINE_BANK_TRANSFER_ORDER_STATUS_TITLE','Completed order status');
define('MODULE_PAYMENT_NOVALNET_ONLINE_BANK_TRANSFER_ORDER_STATUS_DESC','Status to be used for successful orders');
define('MODULE_PAYMENT_NOVALNET_ONLINE_BANK_TRANSFER_PAYMENT_ZONE_TITLE','Payment zone');
define('MODULE_PAYMENT_NOVALNET_ONLINE_BANK_TRANSFER_PAYMENT_ZONE_DESC','When a zone is selected, this payment method will be enabled for that zone only.');
define('MODULE_PAYMENT_NOVALNET_ONLINE_BANK_TRANSFER_VISIBILITY_BY_AMOUNT_TITLE','Minimum order amount.');
define('MODULE_PAYMENT_NOVALNET_ONLINE_BANK_TRANSFER_VISIBILITY_BY_AMOUNT_DESC','Minimum order amount to display the selected payment method (s) at during checkout (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)');
define('MODULE_PAYMENT_NOVALNET_ONLINE_BANK_TRANSFER_ENDCUSTOMER_INFO_TITLE','Notification for the buyer.');
define('MODULE_PAYMENT_NOVALNET_ONLINE_BANK_TRANSFER_ENDCUSTOMER_INFO_DESC','The entered text will be displayed on the checkout page.');
?>
