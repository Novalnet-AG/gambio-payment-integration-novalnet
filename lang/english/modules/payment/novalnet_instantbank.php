<?php

include_once(dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_INSTANTBANK_TEXT_TITLE', 'Novalnet Sofort');
define('MODULE_PAYMENT_NOVALNET_INSTANTBANK_TEXT_DESCRIPTION', 'The payment will be processed in the test mode therefore amount for this transaction will not be charged');
define('MODULE_PAYMENT_NOVALNET_INSTANTBANK_STATUS_TITLE', 'Enable payment method');
define('MODULE_PAYMENT_NOVALNET_INSTANTBANK_STATUS_DESC', 'Do you want to accept instantbank payment?');
define('MODULE_PAYMENT_NOVALNET_INSTANTBANK_ALLOWED_TITLE', 'Allowed zone(-s)');
define('MODULE_PAYMENT_NOVALNET_INSTANTBANK_ALLOWED_DESC', 'This payment method will be allowed for the mentioned zone(-s). Enter the zone(-s) in the following format E.g: DE, AT, CH. In case if the field is empty, all the zones will be allowed.');
