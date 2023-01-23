<?php
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_TEXT_TITLE', 'Novalnet Applepay');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_STATUS_TITLE' , 'Enable payment method');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_BLOCK_TITLE' , 'Applepay configuration');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_STATUS_DESC' , 'Do you want to accept Applepay payment?');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_TEST_MODE_TITLE' , 'Enable test mode');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_TEST_MODE_DESC' , 'The payment will be processed in the test mode therefore amount for this transaction will not be charged');
define('MODULE_PAYMENT_NOVALNET_PAYMENT_MODE', 'Test order');
define('MODULE_PAYMENT_NOVALNET_TRANSACTION_ID', 'Novalnet transaction ID: ');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_SORT_ORDER_TITLE','Display Sort Order');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_SORT_ORDER_DESC','Display sort order; the lowest value is displayed first.');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_ORDER_STATUS_TITLE','Completed order Status');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_ORDER_STATUS_DESC','Status to be used for successful orders');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_AUTHENTICATE_TITLE','Payment action');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_AUTHENTICATE_DESC','Choose whether or not the payment should be charged immediately. Authorize verifies payment details and reserves funds to capture it later, giving time for the merchant to decide on the order.</br></br>Authorize');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_MANUAL_CHECK_LIMIT_TITLE','Minimum transaction amount for authorization');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_MANUAL_CHECK_LIMIT_DESC','In case the order amount exceeds the mentioned limit, the transaction will be set on-hold till your confirmation of the transaction. You can leave the field empty if you wish to process all the transactions as on-hold (in minimum unit of currency. E.g. enter 100 which is equal to 1.00).');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_TYPE_TITLE','<h3>Button Design</h3> <h5>Style for Apple pay button</h5> Button Type');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_THEME_TITLE','Button Theme');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_HEIGHT_TITLE','Button Height');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_HEIGHT_DESC','Range from 30 to 64 pixels');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_CORNER_RADIUS_TITLE','Button Corner Radius');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_CORNER_RADIUS_DESC','Range from 1 to 10 pixels');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_DISPLAY_TITLE','Display the Apple Pay Button on');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_DISPLAY_DESC','The selected pages will display the Apple pay button to pay instantly as an express checkout button');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_ALLOWED_TITLE','Allowed zone(-s)');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_ALLOWED_DESC','This payment method will be allowed for the mentioned zone(-s). Enter the zone(-s) in the following format E.g: DE, AT, CH. In case if the field is empty, all the zones will be allowed.');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_PAYMENT_ZONE_TITLE','Payment zone');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_PAYMENT_ZONE_DESC','If a zone is selected, only enable this payment method for that zone.');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_ENDCUSTOMER_INFO_TITLE','Notification for the buyer');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_ENDCUSTOMER_INFO_DESC','The entered text will be displayed on the checkout page');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_TEXT_INFO','Amount will be booked from your card after successful authentication');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_PUBLIC_TITLE', xtc_image(DIR_WS_ICONS.'novalnet/novalnet_applepay.png', 'Applepay'));
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_VISIBILITY_BY_AMOUNT_TITLE','Minimum order amount');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_VISIBILITY_BY_AMOUNT_DESC','Minimum order amount to display the selected payment method (s) at during checkout (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)');
define('ERROR_CHECKOUT_SHIPPING_NO_MODULE','No shipping');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_BUSINESS_NAME_TITLE','Business name');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_BUSINESS_NAME_DESC','The business name is rendered in the Apple Pay payment sheet, and this text will appear as PAY "BUSINESS NAME" so that the customer knows where he is paying to');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_VISIBILITY_BY_AMOUNT_TITLE','Minimum order amount');
define('MODULE_PAYMENT_NOVALNET_APPLEPAY_VISIBILITY_BY_AMOUNT_DESC','Minimum order amount to display the selected payment method (s) at during checkout (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)');
?>
