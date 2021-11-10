<?php
/**
 * Novalnet payment method module
 * This module is used for real time processing of
 * Novalnet transaction of customers.
 *
 * Copyright (c) Novalnet
 *
 * Released under the GNU General Public License
 * This free contribution made by request.
 * If you have found this script useful a small
 * recommendation as well as a comment on merchant form
 * would be greatly appreciated.
 *
 * Script : novalnet_cc.php
 *
 */
require_once (dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_CC_TEXT_TITLE', 'Novalnet Credit Card');
define('MODULE_PAYMENT_NOVALNET_CC_TEXT_DESCRIPTION','The amount will be debited from your credit card once the order is submitted');
define('MODULE_PAYMENT_NOVALNET_CC_REDIRECTION_TEXT_DESCRIPTION','After the successful verification, you will be redirected to Novalnet secure order page to proceed with the payment<br>Please don&#39;t close the browser after successful payment, until you have been redirected back to the Shop');
define('MODULE_PAYMENT_NOVALNET_CC_ALLOWED_TITLE','Allowed zone(-s)');
define('MODULE_PAYMENT_NOVALNET_CC_ALLOWED_DESC','This payment method will be allowed for the mentioned zone(-s). Enter the zone(-s) in the following format E.g: DE, AT, CH. In case if the field is empty, all the zones will be allowed.');
define('MODULE_PAYMENT_NOVALNET_CC_ENABLE_MODULE_TITLE','Enable payment method');
define('MODULE_PAYMENT_NOVALNET_CC_ENABLE_MODULE_DESC','');
define('MODULE_PAYMENT_NOVALNET_CC_TEST_MODE_TITLE','Enable test mode');
define('MODULE_PAYMENT_NOVALNET_CC_TEST_MODE_DESC','The payment will be processed in the test mode therefore amount for this transaction will not be charged');
define('MODULE_PAYMENT_NOVALNET_CC_3D_SECURE_TITLE','Enable 3D secure');
define('MODULE_PAYMENT_NOVALNET_CC_3D_SECURE_DESC','The 3D-Secure will be activated for credit cards. The issuing bank prompts the buyer for a password what, in turn, help to prevent a fraudulent payment. It can be used by the issuing bank as evidence that the buyer is indeed their card holder. This is intended to help decrease a risk of charge-back.');
define('MODULE_PAYMENT_NOVALNET_CC_AMEX_ACCEPT_TITLE','Display AMEX logo');
define('MODULE_PAYMENT_NOVALNET_CC_AMEX_ACCEPT_DESC','Display AMEX logo in checkout page');
define('MODULE_PAYMENT_NOVALNET_CC_MAESTRO_ACCEPT_TITLE','Display Maestro logo');
define('MODULE_PAYMENT_NOVALNET_CC_MAESTRO_ACCEPT_DESC','Display Maestro logo in checkout page');
define('MODULE_PAYMENT_NOVALNET_CC_CARTASI_ACCEPT_TITLE','Display CartaSi logo');
define('MODULE_PAYMENT_NOVALNET_CC_CARTASI_ACCEPT_DESC','Display CartaSi logo in checkout page');
define('MODULE_PAYMENT_NOVALNET_CC_SHOP_TYPE_TITLE','Shopping type');
define('MODULE_PAYMENT_NOVALNET_CC_SHOP_TYPE_DESC','Select shopping type');
define('MODULE_PAYMENT_NOVALNET_CC_VISIBILITY_BY_AMOUNT_TITLE','Minimum value of goods (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)');
define('MODULE_PAYMENT_NOVALNET_CC_VISIBILITY_BY_AMOUNT_DESC','Enter the minimum value of goods from which the payment method is displayed to the customer during checkout');
define('MODULE_PAYMENT_NOVALNET_CC_ENDCUSTOMER_INFO_TITLE','Notification for the buyer');
define('MODULE_PAYMENT_NOVALNET_CC_ENDCUSTOMER_INFO_DESC','The entered text will be displayed on the checkout page');
define('MODULE_PAYMENT_NOVALNET_CC_SORT_ORDER_TITLE','Define a sorting order');
define('MODULE_PAYMENT_NOVALNET_CC_SORT_ORDER_DESC','This payment method will be sorted among others (in the ascending order) as per the given sort number.');
define('MODULE_PAYMENT_NOVALNET_CC_ORDER_STATUS_TITLE','Order Completion Status');
define('MODULE_PAYMENT_NOVALNET_CC_ORDER_STATUS_DESC','');
define('MODULE_PAYMENT_NOVALNET_CC_PAYMENT_ZONE_TITLE','Payment zone');
define('MODULE_PAYMENT_NOVALNET_CC_PAYMENT_ZONE_DESC','This payment method will be displayed for the mentioned zone(-s)');
define('MODULE_PAYMENT_NOVALNET_CC_TRANS_REFERENCE1_TITLE','Transaction reference 1');
define('MODULE_PAYMENT_NOVALNET_CC_TRANS_REFERENCE1_DESC','This reference will appear in your bank account statement');
define('MODULE_PAYMENT_NOVALNET_CC_TRANS_REFERENCE2_TITLE','Transaction reference 2');
define('MODULE_PAYMENT_NOVALNET_CC_TRANS_REFERENCE2_DESC','This reference will appear in your bank account statement');
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
define('MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_HOLDER_TITLE','<h2>Form appearance</h2>CSS settings for Credit Card fields<br><br>'.MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_HOLDER.'<br><br><span style="font-weight:normal;">Label</span>');
define('MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_HOLDER_DESC','');
define('MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_HOLDER_INPUT_TITLE','<span style="font-weight:normal;">Input field</span>');
define('MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_HOLDER_INPUT_DESC','');
define('MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_NUMBER_TITLE',MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_NO.'<br><br><span style="font-weight:normal;">Label</span>');
define('MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_NUMBER_DESC','');
define('MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_NUMBER_INPUT_TITLE','<span style="font-weight:normal;">Input field</span>');
define('MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_NUMBER_INPUT_DESC','');
define('MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_EXPIRY_DATE_TITLE',MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_VALID_DATE.'<br><br><span style="font-weight:normal;">Label</span>');
define('MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_EXPIRY_DATE_DESC','');
define('MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_EXPIRY_DATE_INPUT_TITLE','<span style="font-weight:normal;">Input field</span>');
define('MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_EXPIRY_DATE_INPUT_DESC','');
define('MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_CVC_TITLE',MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_CVC.'<br><br><span style="font-weight:normal;">Label</span>');
define('MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_CVC_DESC','');
define('MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_CVC_INPUT_TITLE','<span style="font-weight:normal;">Input field</span>');
define('MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_CVC_INPUT_DESC','');
define('MODULE_PAYMENT_NOVALNET_CC_CSS_STANDARD_STYLE_TITLE','CSS settings for Credit Card iframe<br><br><span style="font-weight:normal;">Label</span>');
define('MODULE_PAYMENT_NOVALNET_CC_CSS_STANDARD_STYLE_DESC','');
define('MODULE_PAYMENT_NOVALNET_CC_CSS_STANDARD_STYLE_INPUT_TITLE','<span style="font-weight:normal;">Input</span>');
define('MODULE_PAYMENT_NOVALNET_CC_CSS_STANDARD_STYLE_INPUT_DESC','');
define('MODULE_PAYMENT_NOVALNET_CC_CSS_TEXT_TITLE','<span style="font-weight:normal;">CSS Text</span>');
define('MODULE_PAYMENT_NOVALNET_CC_CSS_TEXT_DESC','');
define('MODULE_PAYMENT_NOVALNET_CC_PUBLIC_TITLE', 'Credit Card '. (((!defined('MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY')) || MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY == 'True')?'<a href="http://www.novalnet.com" target="_blank"/><img title="Credit Card" alt="Credit Card" src="'.DIR_WS_ICONS.'novalnet/novalnet_cc_visa.png" width=23% height=51%></a>':'').
(((!defined('MODULE_PAYMENT_NOVALNET_CC_AMEX_ACCEPT')) || MODULE_PAYMENT_NOVALNET_CC_AMEX_ACCEPT == 'True' && ((!defined('MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY')) || MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY == 'True'))?'<a href="http://www.novalnet.com" target="_blank"/><img title="Credit Card" alt="Credit Card" src="'.DIR_WS_ICONS.'novalnet/novalnet_cc_amex.png" width=7% height=51%></a>':'').' '.
(((!defined('MODULE_PAYMENT_NOVALNET_CC_MAESTRO_ACCEPT')) || MODULE_PAYMENT_NOVALNET_CC_MAESTRO_ACCEPT == 'True' && ((!defined('MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY')) || MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY == 'True'))?'<a href="http://www.novalnet.com" target="_blank"/><img title="Credit Card" alt="Credit Card" src="'.DIR_WS_ICONS.'novalnet/novalnet_cc_maestro.png" width=10% height=51%></a>':'').
(((!defined('MODULE_PAYMENT_NOVALNET_CC_CARTASI_ACCEPT')) || MODULE_PAYMENT_NOVALNET_CC_CARTASI_ACCEPT == 'True' && ((!defined('MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY')) || MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY == 'True'))?'<a href="http://www.novalnet.com" target="_blank"/><img title="Credit Card" alt="Credit Card" src="'.DIR_WS_ICONS.'novalnet/novalnet_cc_cartasi.png" width=10%></a>':''));
