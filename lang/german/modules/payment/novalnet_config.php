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
define('MODULE_PAYMENT_NOVALNET_CONFIG_TEXT_TITLE', 'Novalnet Haupteinstellungen (V_11.3.0)');
define('MODULE_PAYMENT_NOVALNET_CONFIG_TEXT_DESCRIPTION','<span style="font-weight: bold; color:#878787;"> Bevor Sie beginnen, lesen Sie bitte die Installationsanleitung und melden Sie sich mit Ihrem Händlerkonto im <a href="https://admin.novalnet.de" target="_blank" style="text-decoration: underline; font-weight: bold; color:#0080c9;">Novalnet Admin-Portal</a> an. Um ein Händlerkonto zu erhalten, senden Sie bitte eine E-Mail an <a style="font-weight: bold; color:#0080c9" href="mailto:sales@novalnet.de">sales@novalnet.de</a> oder rufen Sie uns unter +49 89 923068320 an</span><br><br>');
define('MODULE_PAYMENT_NOVALNET_PUBLIC_KEY_TITLE', 'Aktivierungsschl&uuml;ssel des Produkts');
define('MODULE_PAYMENT_NOVALNET_PUBLIC_KEY_DESC', 'Ihren Produktaktivierungsschlüssel finden Sie im <a href="https://admin.novalnet.de" target="_blank" style="text-decoration: underline; font-weight: bold; color:#0080c9;">Novalnet Admin-Portal</a>: PROJEKT > Wählen Sie Ihr Projekt > Shop-Parameter > API-Signatur (Produktaktivierungsschlüssel)');
define('MODULE_PAYMENT_NOVALNET_VENDOR_ID_TITLE', 'H&auml;ndler-ID');
define('MODULE_PAYMENT_NOVALNET_VENDOR_ID_DESC', '&nbsp;');
define('MODULE_PAYMENT_NOVALNET_AUTHCODE_TITLE', 'Authentifizierungscode');
define('MODULE_PAYMENT_NOVALNET_AUTHCODE_DESC', '&nbsp;');
define('MODULE_PAYMENT_NOVALNET_PRODUCT_ID_TITLE', 'Projekt-ID');
define('MODULE_PAYMENT_NOVALNET_PRODUCT_ID_DESC', '&nbsp;');
define('MODULE_PAYMENT_NOVALNET_TARIFF_ID_TITLE', 'Auswahl der Tarif-ID');
define('MODULE_PAYMENT_NOVALNET_TARIFF_ID_DESC', 'Wählen Sie eine Tarif-ID, die dem bevorzugten Tarifplan entspricht, den Sie im <a href="https://admin.novalnet.de" target="_blank" style="text-decoration: underline; font-weight: bold; color:#0080c9;">Novalnet Admin-Portal</a> für dieses Projekt erstellt haben');
define('MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY_TITLE', 'Zahlungs-Zugriffsschl&uuml;ssel');
define('MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY_DESC', '&nbsp;');
define('MODULE_PAYMENT_NOVALNET_CLIENT_KEY_TITLE', 'Schlüsselkunde');
define('MODULE_PAYMENT_NOVALNET_CLIENT_KEY_DESC', '&nbsp;');
define('MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY_TITLE', 'Zahlungslogo anzeigen');
define('MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY_DESC', 'Das Logo der Zahlungsart wird auf der Checkout-Seite angezeigt');
define('MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_COMPLETE_TITLE', '<h2>Verwaltung des Bestellstatus f&uuml;r ausgesetzte Zahlungen</h2>On-hold-Bestellstatus');
define('MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_COMPLETE_DESC', '&nbsp;');
define('MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED_TITLE', 'Status für stornierte Bestellungen');
define('MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED_DESC', '&nbsp;');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_TEST_MODE_TITLE', '<h2>Benachrichtigungs- / Webhook-URL festlegen</h2>');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_TEST_MODE_DESC', '&nbsp;');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_SEND_TITLE', 'Email-Benachrichtigung f&uuml;r Callback aktivieren');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_SEND_DESC', '&nbsp;');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_TO_TITLE', 'E-Mails senden an');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_TO_DESC', 'E-Mail-Benachrichtigungen werden an diese E-Mail-Adresse gesendet');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_URL_TITLE', 'Benachrichtigungs- / Webhook-URL');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_URL_DESC', 'Sie müssen die folgende Webhook-URL im <a href="https://admin.novalnet.de" target="_blank" style="text-decoration: underline; font-weight: bold; color:#0080c9;">Novalnet Admin-Portal</a> hinzufügen. Dadurch können Sie Benachrichtigungen über den Transaktionsstatus erhalten.
');
