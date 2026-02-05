<?php

require_once dirname(__FILE__).'/novalnet.php';
define('MODULE_PAYMENT_NOVALNET_MBWAY_TEXT_TITLE', 'Novalnet MB Way');
define('MODULE_PAYMENT_NOVALNET_MBWAY_TEXT_DESCRIPTION', 'The payment will be processed in the test mode therefore amount for this transaction will not be charged');
define('MODULE_PAYMENT_NOVALNET_MBWAY_STATUS_TITLE', 'Enable payment method');
define('MODULE_PAYMENT_NOVALNET_MBWAY_STATUS_DESC', 'Do you want to accept mbway payment?');
define('MODULE_PAYMENT_NOVALNET_MBWAY_TEST_MODE_TITLE', 'Enable test mode');
define('MODULE_PAYMENT_NOVALNET_MBWAY_TEST_MODE_DESC', 'The payment will be processed in the test mode therefore amount for this transaction will not be charged');
define('MODULE_PAYMENT_NOVALNET_MBWAY_SORT_ORDER_TITLE', 'Display Sort Order');
define('MODULE_PAYMENT_NOVALNET_MBWAY_SORT_ORDER_DESC', 'Display sort order; the lowest value is displayed first.');
define('MODULE_PAYMENT_NOVALNET_MBWAY_ORDER_STATUS_TITLE', 'Completed order status');
define('MODULE_PAYMENT_NOVALNET_MBWAY_ORDER_STATUS_DESC', 'Status to be used for successful orders');
define('MODULE_PAYMENT_NOVALNET_MBWAY_PAYMENT_ZONE_TITLE', 'Payment zone');
define('MODULE_PAYMENT_NOVALNET_MBWAY_PAYMENT_ZONE_DESC', 'When a zone is selected, this payment method will be enabled for that zone only.');
define('MODULE_PAYMENT_NOVALNET_MBWAY_VISIBILITY_BY_AMOUNT_TITLE', 'Minimum order amount');
define('MODULE_PAYMENT_NOVALNET_MBWAY_VISIBILITY_BY_AMOUNT_DESC', 'Minimum order amount to display the selected payment method (s) at during checkout (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)');
define('MODULE_PAYMENT_NOVALNET_MBWAY_ENDCUSTOMER_INFO_TITLE', 'Notification for the buyer');
define('MODULE_PAYMENT_NOVALNET_MBWAY_ENDCUSTOMER_INFO_DESC', 'The entered text will be displayed on the checkout page.');
define('MODULE_PAYMENT_NOVALNET_MBWAY_TEXT_INFO', 'After completing your order, a payment request notification will be sent to your mobile device. You can enter the PIN and authorises the payment.');
define('MODULE_PAYMENT_NOVALNET_MBWAY_MOBILE_NO', 'Enter Mobile Number *');
define('MODULE_PAYMENT_NOVALNET_MBWAY_VALID_MOBILENO_CREDENTIALS_ERROR', 'Mobile number is required!');
