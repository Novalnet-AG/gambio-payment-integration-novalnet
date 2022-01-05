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
 * Script : novalnet_paypal.php
 *
 */
require_once (dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_TEXT_TITLE', 'PayPal');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_REDIRECTION_TEXT_DESCRIPTION','Sie werden zu PayPal weitergeleitet. Um eine erfolgreiche Zahlung zu gewährleisten, darf die Seite nicht geschlossen oder neu geladen werden, bis die Bezahlung abgeschlossen ist');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_REDIRECT_DESC','Sie werden zu eps weitergeleitet. Um eine erfolgreiche Zahlung zu gewährleisten, darf die Seite nicht geschlossen oder neu geladen werden, bis die Bezahlung abgeschlossen ist');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_REDIRECTION_BROWSER_TEXT_DESCRIPTION','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_ALLOWED_TITLE','Zugelassene Gebiete');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_ALLOWED_DESC','Diese Zahlungsart wird nur f&uuml;r die aufgef&uuml;hrten Gebiete zugelassen. Geben Sie die Gebiete in folgendem Format ein, z.B. DE, AT, CH etc. Falls das Feld leer ist, sind alle Gebiete zugelassen.');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_STATUS_TITLE','Zahlungsart anzeigen');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_STATUS_DESC','Um PayPal-Transaktionen zu akzeptieren, konfigurieren Sie Ihre PayPal-API-Informationen im <a href="https://admin.novalnet.de" target="_blank" style="text-decoration: underline; font-weight: bold; color:#0080c9;">Novalnet Admin-Portal</a> > PROJEKT > Wählen Sie Ihr Projekt > Zahlungsmethoden > Paypal > Konfigurieren');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_TEST_MODE_TITLE','Testmodus aktivieren');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_TEST_MODE_DESC','Die Zahlung wird im Testmodus durchgef&uuml;hrt, daher wird der Betrag f&uuml;r diese Transaktion nicht eingezogen');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_VISIBILITY_BY_AMOUNT_TITLE','Mindestwarenwert (in der kleinsten Währungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR)');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_VISIBILITY_BY_AMOUNT_DESC','Geben Sie den Mindestwarenwert ein, von dem ab die Zahlungsart f&uuml;r den Kunden beim Checkout angezeigt wird');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_MANUAL_CHECK_LIMIT_DESC', 'Mindesttransaktionsbetrag für die Autorisierung
 (in der kleinsten Währungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR)');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_MANUAL_CHECK_LIMIT_TITLE', 'Übersteigt der Bestellbetrag das genannte Limit, wird die Transaktion bis zu Ihrer Bestätigung auf On-Hold gesetzt. Alle Transaktionen werden als On-Hold Transaktionen angelegt, wenn Sie "Zahlung autorisieren" als Bearbeitungsmaßnahme hinterlegt und keinen Mindesttransaktionsbetrag konfiguriert haben.');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_ENDCUSTOMER_INFO_TITLE','Benachrichtigung des K&auml;ufers');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_ENDCUSTOMER_INFO_DESC','Der eingegebene Text wird auf der Checkout-Seite angezeigt');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_SORT_ORDER_TITLE','Anzeigereihenfolge');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_SORT_ORDER_DESC','Reihenfolge der Anzeige. Kleinste Ziffer wird zuerst angezeigt.');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_PENDING_ORDER_STATUS_TITLE','Bestellstatus der ausstehenden Zahlung');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_PENDING_ORDER_STATUS_DESC','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_ORDER_STATUS_TITLE','Status für erfolgreichen Auftragsabschluss');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_ORDER_STATUS_DESC','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_ONE_CLICK_TEXT_DESCRIPTION','Sobald die Bestellung abgeschickt wurde, wird die Zahlung bei Novalnet als Referenztransaktion verarbeitet.');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_SHOP_TYPE_TITLE','Einkaufstyp');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_SHOP_TYPE_DESC','Einkaufstyp ausw&auml;hlen <br><span style="color:red">Um diese Option zu verwenden, müssen Sie die Option Billing Agreement (Zahlungsvereinbarung) in Ihrem PayPal-Konto aktiviert haben. Kontaktieren Sie dazu bitte Ihren Kundenbetreuer bei PayPal.</span> ');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_NOTIFICATION_TO_MERCHANT','');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_NEW_ACCOUNT', 'Mit neuen PayPal-Kontodetails fortfahren');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_GIVEN_ACCOUNT','Angegebene PayPal-Kontodetails');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_TRANSACTION_TID','PayPal Transaktions-ID');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_ORDER_TRANSACTION_TID','Novalnet-Transaktions-ID');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_ONE_CLICK','Kauf mit einem Klick');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_ZERO_AMOUNT','Transaktionen mit Betrag 0');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_PAYMENT_ZONE_TITLE','Zahlungsgebiet');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_PAYMENT_ZONE_DESC','Wenn eine Zone ausgewählt ist, gilt die Zahlungsmethode nur für diese Zone.');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_PUBLIC_TITLE', 'PayPal ');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_SHOP_TYPE_CLICK_TITLE','Einkaufstyp');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_SHOP_TYPE_CLICK_DESC','Kauf mit einem Klick <br><br> Um diese Option zu verwenden, müssen Sie die Option Billing Agreement (Zahlungsvereinbarung) in Ihrem PayPal-Konto aktiviert haben. Kontaktieren Sie dazu bitte Ihren Kundenbetreuer bei PayPal.');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_ZERO_AMOUNT_TITLE','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_ZERO_AMOUNT_DESC','Transaktionen mit Betrag 0');
