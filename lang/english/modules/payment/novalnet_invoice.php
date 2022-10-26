<?php
include_once (dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_INVOICE_TEXT_TITLE', 'Novalnet Invoice');
define('MODULE_PAYMENT_NOVALNET_INVOICE_TEXT_DESCRIPTION', 'A payable credit note with the order details');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ALLOWED_TITLE','Allowed zone(-s)');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ALLOWED_DESC','This payment method will be allowed for the mentioned zone(-s). Enter the zone(-s) in the following format E.g: DE, AT, CH. In case if the field is empty, all the zones will be allowed.');
define('MODULE_PAYMENT_NOVALNET_INVOICE_STATUS_TITLE','Enable payment method');
define('MODULE_PAYMENT_NOVALNET_INVOICE_STATUS_DESC','Do you want to accept invoice payment?');
define('MODULE_PAYMENT_NOVALNET_INVOICE_VISIBILITY_BY_AMOUNT_TITLE','Minimum order amount');
define('MODULE_PAYMENT_NOVALNET_INVOICE_VISIBILITY_BY_AMOUNT_DESC','Minimum order amount to display the selected payment method (s) at during checkout (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)');
define('MODULE_PAYMENT_NOVALNET_INVOICE_TEST_MODE_TITLE','Enable test mode');
define('MODULE_PAYMENT_NOVALNET_INVOICE_TEST_MODE_DESC','The payment will be processed in the test mode therefore amount for this transaction will not be charged');
define('MODULE_PAYMENT_NOVALNET_INVOICE_DUE_DATE_TITLE','Payment due date (in days)');
define('MODULE_PAYMENT_NOVALNET_INVOICE_DUE_DATE_DESC','Number of days given to the buyer to transfer the amount to Novalnet (must be greater than 7 days). If this field is left blank, 14 days will be set as due date by default.');
define('MODULE_PAYMENT_NOVALNET_INVOICE_SORT_ORDER_TITLE','Display Sort Order');
define('MODULE_PAYMENT_NOVALNET_INVOICE_SORT_ORDER_DESC','Display sort order; the lowest value is displayed first.');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ORDER_STATUS_TITLE','Completed order status');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ORDER_STATUS_DESC','Status to be used for successful orders');
define('MODULE_PAYMENT_NOVALNET_INVOICE_CALLBACK_ORDER_STATUS_TITLE','Callback / webhook order status');
define('MODULE_PAYMENT_NOVALNET_INVOICE_CALLBACK_ORDER_STATUS_DESC','Status to be used when callback script is executed for payment received by Novalnet');
define('MODULE_PAYMENT_NOVALNET_INVOICE_PAYMENT_ZONE_TITLE','Payment zone');
define('MODULE_PAYMENT_NOVALNET_INVOICE_PAYMENT_ZONE_DESC','When a zone is selected, this payment method will be enabled for that zone only.');
define('MODULE_PAYMENT_NOVALNET_INVOICE_AUTHENTICATE_TITLE','Payment action');
define('MODULE_PAYMENT_NOVALNET_INVOICE_AUTHENTICATE_DESC','Choose whether or not the payment should be charged immediately. Authorize verifies payment details and reserves funds to capture it later, giving time for the merchant to decide on the order.</br></br>Authorize');
define('MODULE_PAYMENT_NOVALNET_INVOICE_MANUAL_CHECK_LIMIT_TITLE','Minimum transaction amount for authorization');
define('MODULE_PAYMENT_NOVALNET_INVOICE_MANUAL_CHECK_LIMIT_DESC', 'In case the order amount exceeds the mentioned limit, the transaction will be set on-hold till your confirmation of the transaction. You can leave the field empty if you wish to process all the transactions as on-hold (in minimum unit of currency. E.g. enter 100 which is equal to 1.00).');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ENDCUSTOMER_INFO_TITLE','Notification for the buyer');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ENDCUSTOMER_INFO_DESC','The entered text will be displayed on the checkout page');
?>
