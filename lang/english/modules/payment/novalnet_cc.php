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
 * Script : novalnet_cc.php
 *
 */
require_once (dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_CC_TEXT_TITLE', 'Credit/Debit Cards');
define('MODULE_PAYMENT_NOVALNET_CC_TEXT_DESCRIPTION','Your credit/debit card will be charged immediately after the order is completed');
define('MODULE_PAYMENT_NOVALNET_REDIRECT_DESC','Your credit/debit card will be charged immediately after the order is completed
');
define('MODULE_PAYMENT_NOVALNET_CC_REDIRECTION_TEXT_DESCRIPTION','Your credit/debit card will be charged immediately after the order is completed
');
define('MODULE_PAYMENT_NOVALNET_CC_ALLOWED_TITLE','Allowed zone(-s)');
define('MODULE_PAYMENT_NOVALNET_CC_ALLOWED_DESC','This payment method will be allowed for the mentioned zone(-s). Enter the zone(-s) in the following format E.g: DE, AT, CH. In case if the field is empty, all the zones will be allowed.');
define('MODULE_PAYMENT_NOVALNET_CC_STATUS_TITLE','Display payment method');
define('MODULE_PAYMENT_NOVALNET_CC_STATUS_DESC','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_CC_TEST_MODE_TITLE','Enable test mode');
define('MODULE_PAYMENT_NOVALNET_CC_TEST_MODE_DESC','The payment will be processed in the test mode therefore amount for this transaction will not be charged');
define('MODULE_PAYMENT_NOVALNET_CC_MANUAL_CHECK_LIMIT_DESC', 'Minimum transaction limit for authorization (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)');
define('MODULE_PAYMENT_NOVALNET_CC_3D_SECURE_FORCE_TITLE','Enforce 3D secure payment outside EU');
define('MODULE_PAYMENT_NOVALNET_CC_3D_SECURE_FORCE_DESC','By enabling this option, all payments from cards issued outside the EU will be authenticated via 3DS 2.0 SCA.');
define('MODULE_PAYMENT_NOVALNET_CC_MANUAL_CHECK_LIMIT_TITLE', 'In case the order amount exceeds the mentioned limit, the transaction will be set on-hold till your confirmation of the transaction. Configure Authorize as payment action and leave the field empty if you wish to process all the transactions as on-hold.');
define('MODULE_PAYMENT_NOVALNET_CC_AMEX_ACCEPT_TITLE','Display AMEX logo');
define('MODULE_PAYMENT_NOVALNET_CC_AMEX_ACCEPT_DESC','Display AMEX logo at the checkout page');
define('MODULE_PAYMENT_NOVALNET_CC_MAESTRO_ACCEPT_TITLE','Display Maestro logo');
define('MODULE_PAYMENT_NOVALNET_CC_MAESTRO_ACCEPT_DESC','Display Maestro logo at the checkout page');
define('MODULE_PAYMENT_NOVALNET_CC_SHOP_TYPE_TITLE','Shopping type');
define('MODULE_PAYMENT_NOVALNET_CC_SHOP_TYPE_DESC','Select shopping type');
define('MODULE_PAYMENT_NOVALNET_CC_VISIBILITY_BY_AMOUNT_TITLE','Minimum value of goods (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)');
define('MODULE_PAYMENT_NOVALNET_CC_VISIBILITY_BY_AMOUNT_DESC','Enter the minimum value of goods from which the payment method is displayed to the customer during checkout');
define('MODULE_PAYMENT_NOVALNET_CC_ENDCUSTOMER_INFO_TITLE','Notification for the buyer');
define('MODULE_PAYMENT_NOVALNET_CC_ENDCUSTOMER_INFO_DESC','The entered text will be displayed on the checkout page');
define('MODULE_PAYMENT_NOVALNET_CC_SORT_ORDER_TITLE','Display Sort Order');
define('MODULE_PAYMENT_NOVALNET_CC_SORT_ORDER_DESC','Display sort order; the lowest value is displayed first.');
define('MODULE_PAYMENT_NOVALNET_CC_ORDER_STATUS_TITLE','Completed order status');
define('MODULE_PAYMENT_NOVALNET_CC_ORDER_STATUS_DESC','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_CC_PAYMENT_ZONE_TITLE','Payment zone');
define('MODULE_PAYMENT_NOVALNET_CC_PAYMENT_ZONE_DESC','When a zone is selected, this payment method will be enabled for that zone only.');
define('MODULE_PAYMENT_NOVALNET_CC_ONE_CLICK','One click shopping');
define('MODULE_PAYMENT_NOVALNET_CC_ZERO_AMOUNT','Zero amount booking');
define('MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_TYPE','Credit Card type');
define('MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_HOLDER','Card holder name');
define('MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_NO','Card number');
define('MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_VALID_DATE','Expiry date');
define('MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_CVC','CVC/CVV/CID');
define('MODULE_PAYMENT_NOVALNET_CC_IFRAME_CVC_HINT','what is this?');
define('MODULE_PAYMENT_NOVALNET_CC_IFRAME_ERROR','Your credit card details are invalid');
define('MODULE_PAYMENT_NOVALNET_CC_IFRAME_HOLDER_INPUT_TEXT','Name on card');
define('MODULE_PAYMENT_NOVALNET_CC_IFRAME_NUMBER_INPUT_TEXT','XXXX XXXX XXXX XXXX');
define('MODULE_PAYMENT_NOVALNET_CC_IFRAME_EXPIRYDATE_INPUT_TEXT','MM / YYYY');
define('MODULE_PAYMENT_NOVALNET_CC_IFRAME_CVC_INPUT_TEXT','XXX');
define('MODULE_PAYMENT_NOVALNET_VALID_CC_DETAILS', 'Your credit card details are invalid');
define('MODULE_PAYMENT_NOVALNET_CC_NEW_ACCOUNT', 'Enter new card details');
define('MODULE_PAYMENT_NOVALNET_CC_GIVEN_ACCOUNT','Given card details');
define('MODULE_PAYMENT_NOVALNET_CC_CSS_STANDARD_STYLE_TITLE','Custom CSS settings<br><br><span style="font-weight:normal;">Label</span>');
define('MODULE_PAYMENT_NOVALNET_CC_CSS_STANDARD_STYLE_DESC','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_CC_CSS_STANDARD_STYLE_INPUT_TITLE','<span style="font-weight:normal;">Input</span>');
define('MODULE_PAYMENT_NOVALNET_CC_CSS_STANDARD_STYLE_INPUT_DESC','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_CC_CSS_TEXT_TITLE','<span style="font-weight:normal;">CSS Text</span>');
define('MODULE_PAYMENT_NOVALNET_CC_CSS_TEXT_DESC','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_CC_PUBLIC_TITLE', 'Credit Card  '. (((!defined('MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY')) || MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY == 'true') && (((!defined('MODULE_PAYMENT_NOVALNET_CC_AMEX_ACCEPT')) || MODULE_PAYMENT_NOVALNET_CC_AMEX_ACCEPT == 'false') && ((!defined('MODULE_PAYMENT_NOVALNET_CC_MAESTRO_ACCEPT')) || MODULE_PAYMENT_NOVALNET_CC_MAESTRO_ACCEPT == 'false')) ?'<a href="https://www.novalnet.de" target="_blank"/><img title="Credit Card" alt="Credit Card" src="'.DIR_WS_ICONS.'payment/novalnet_cc.png" height="30px"></a>':'').(((!defined('MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY')) || MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY == 'true') && (((!defined('MODULE_PAYMENT_NOVALNET_CC_AMEX_ACCEPT')) || MODULE_PAYMENT_NOVALNET_CC_AMEX_ACCEPT == 'true') &&  ((!defined('MODULE_PAYMENT_NOVALNET_CC_MAESTRO_ACCEPT')) || MODULE_PAYMENT_NOVALNET_CC_MAESTRO_ACCEPT == 'false')) ?'<a href="https://www.novalnet.de" target="_blank"/><img title="Credit Card" alt="Credit Card" src="'.DIR_WS_ICONS.'payment/novalnet_visa_master_amex.png" height="30px"></a>':'').(((!defined('MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY')) || MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY == 'true') && (((!defined('MODULE_PAYMENT_NOVALNET_CC_MAESTRO_ACCEPT')) || MODULE_PAYMENT_NOVALNET_CC_MAESTRO_ACCEPT == 'true') && ((!defined('MODULE_PAYMENT_NOVALNET_CC_AMEX_ACCEPT')) || MODULE_PAYMENT_NOVALNET_CC_AMEX_ACCEPT == 'false')) ?'<a href="https://www.novalnet.de" target="_blank"/><img title="Credit Card" alt="Credit Card" src="'.DIR_WS_ICONS.'payment/novalnet_visa_master_maestro.png" height="30px"></a>':'').(((!defined('MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY')) || MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY == 'true') && (((!defined('MODULE_PAYMENT_NOVALNET_CC_MAESTRO_ACCEPT')) || MODULE_PAYMENT_NOVALNET_CC_MAESTRO_ACCEPT == 'true') && ((!defined('MODULE_PAYMENT_NOVALNET_CC_AMEX_ACCEPT')) || MODULE_PAYMENT_NOVALNET_CC_AMEX_ACCEPT == 'true'))?'<a href="https://www.novalnet.de" target="_blank"/><img title="Credit Card" alt="Credit Card" src="'.DIR_WS_ICONS.'payment/novalnet_visa_master_amex_maestro.png" height="30px"></a>':''));
define('MODULE_PAYMENT_NOVALNET_CC_SHOP_TYPE_CLICK_TITLE','Shopping type');
define('MODULE_PAYMENT_NOVALNET_CC_SHOP_TYPE_CLICK_DESC','One click shopping <br><br> Payment details stored during the checkout process can be used for future payments');
define('MODULE_PAYMENT_NOVALNET_CC_ZERO_AMOUNT_TITLE','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_CC_ZERO_AMOUNT_DESC','Zero amount booking');
