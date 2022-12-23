<?php
include_once (dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_SEPA_TEXT_TITLE', 'Novalnet Direct Debit SEPA ');
define('MODULE_PAYMENT_NOVALNET_SEPA_DESC','The amount will be debited from your account by Novalnet.<br><br>');
define('MODULE_PAYMENT_NOVALNET_SEPA_ALLOWED_TITLE','Allowed zone(-s)');
define('MODULE_PAYMENT_NOVALNET_SEPA_ALLOWED_DESC','This payment method will be allowed for the mentioned zone(-s). Enter the zone(-s) in the following format E.g: DE, AT, CH. In case if the field is empty, all the zones will be allowed.');
define('MODULE_PAYMENT_NOVALNET_SEPA_STATUS_TITLE','Enable payment method');
define('MODULE_PAYMENT_NOVALNET_SEPA_STATUS_DESC','Do you want to accept sepa payment?');
define('MODULE_PAYMENT_NOVALNET_SEPA_TEST_MODE_TITLE','Enable test mode');
define('MODULE_PAYMENT_NOVALNET_SEPA_TEST_MODE_DESC','The payment will be processed in the test mode therefore amount for this transaction will not be charged');
define('MODULE_PAYMENT_NOVALNET_SEPA_DUE_DATE_TITLE','Payment due date (in days)');
define('MODULE_PAYMENT_NOVALNET_SEPA_DUE_DATE_DESC','
Number of days after which the payment is debited (must be between 2 and 14 days)');
define('MODULE_PAYMENT_NOVALNET_SEPA_SORT_ORDER_TITLE','Display Sort Order');
define('MODULE_PAYMENT_NOVALNET_SEPA_SORT_ORDER_DESC','Display sort order; the lowest value is displayed first.');
define('MODULE_PAYMENT_NOVALNET_SEPA_ORDER_STATUS_TITLE','Completed order status');
define('MODULE_PAYMENT_NOVALNET_SEPA_ORDER_STATUS_DESC','Status to be used for successful orders');
define('MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_ZONE_TITLE','Payment zone');
define('MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_ZONE_DESC','When a zone is selected, this payment method will be enabled for that zone only.');
define('MODULE_PAYMENT_NOVALNET_SEPA_IBAN','IBAN');
define('MODULE_PAYMENT_NOVALNET_SEPA_ACCOUNT_HOLDER','Account Holder');
define('MODULE_PAYMENT_NOVALNET_SEPA_AUTHENTICATE_TITLE','Payment action');
define('MODULE_PAYMENT_NOVALNET_SEPA_AUTHENTICATE_DESC','Choose whether or not the payment should be charged immediately.');
define('MODULE_PAYMENT_NOVALNET_SEPA_MANUAL_CHECK_LIMIT_TITLE','Minimum transaction amount for authorization');
define('MODULE_PAYMENT_NOVALNET_SEPA_MANUAL_CHECK_LIMIT_DESC', 'In case the order amount exceeds the mentioned limit, the transaction will be set on-hold till your confirmation of the transaction. You can leave the field empty if you wish to process all the transactions as on-hold (in minimum unit of currency. E.g. enter 100 which is equal to 1.00).');
define('MODULE_PAYMENT_NOVALNET_SEPA_TOKENIZATION_TITLE','One click shopping');
define('MODULE_PAYMENT_NOVALNET_SEPA_TOKENIZATION_DESC','Payment details stored during the checkout process can be used for future payments');
define('MODULE_PAYMENT_NOVALNET_SEPA_VISIBILITY_BY_AMOUNT_TITLE','Minimum order amount.');
define('MODULE_PAYMENT_NOVALNET_SEPA_VISIBILITY_BY_AMOUNT_DESC','Minimum order amount to display the selected payment method (s) at during checkout (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)');
define('MODULE_PAYMENT_NOVALNET_SEPA_ENDCUSTOMER_INFO_TITLE','Notification for the buyer.');
define('MODULE_PAYMENT_NOVALNET_SEPA_ENDCUSTOMER_INFO_DESC','The entered text will be displayed on the checkout page.');
?>
