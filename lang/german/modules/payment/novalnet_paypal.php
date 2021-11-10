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
 * Script : novalnet_paypal.php
 *
 */
require_once (dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_TEXT_TITLE', 'PayPal');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_REDIRECTION_TEXT_DESCRIPTION','Nach der erfolgreichen &Uuml;berpr&uuml;fung werden Sie auf die abgesicherte Novalnet-Bestellseite umgeleitet, um die Zahlung fortzusetzen.');
define('MODULE_PAYMENT_NOVALNET_REDIRECT_DESC','Nach der erfolgreichen &Uuml;berpr&uuml;fung werden Sie auf die abgesicherte Novalnet-Bestellseite umgeleitet, um die Zahlung fortzusetzen.<br>Bitte schlie&szlig;en Sie den Browser nach der erfolgreichen Zahlung nicht, bis Sie zum Shop zur&uuml;ckgeleitet wurden');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_REDIRECTION_BROWSER_TEXT_DESCRIPTION','Bitte schlie&szlig;en Sie den Browser nach der erfolgreichen Zahlung nicht, bis Sie zum Shop zur&uuml;ckgeleitet wurden');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_ALLOWED_TITLE','Zugelassene Gebiete');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_ALLOWED_DESC','Diese Zahlungsart wird nur f&uuml;r die aufgef&uuml;hrten Gebiete zugelassen. Geben Sie die Gebiete in folgendem Format ein, z.B. DE, AT, CH etc. Falls das Feld leer ist, sind alle Gebiete zugelassen.');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_ENABLE_MODULE_TITLE','Zahlungsart aktivieren');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_ENABLE_MODULE_DESC','');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_TEST_MODE_TITLE','Testmodus aktivieren');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_TEST_MODE_DESC','Die Zahlung wird im Testmodus durchgef&uuml;hrt, daher wird der Betrag f&uuml;r diese Transaktion nicht eingezogen');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_VISIBILITY_BY_AMOUNT_TITLE','Mindestwarenwert (in der kleinsten Währungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR)');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_VISIBILITY_BY_AMOUNT_DESC','Geben Sie den Mindestwarenwert ein, von dem ab die Zahlungsart f&uuml;r den Kunden beim Checkout angezeigt wird');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_MANUAL_CHECK_LIMIT_TITLE', 'Limit f&uuml;r onhold-Buchungen setzen (in der kleinsten Währungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR)');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_MANUAL_CHECK_LIMIT_DESC', 'Falls der Bestellbetrag das angegebene Limit &uuml;bersteigt, wird die Transaktion ausgesetzt, bis Sie diese selbst best&auml;tigen. (Um diese Option zu verwenden, m&uuml;ssen Sie die Option Billing Agreement (Zahlungsvereinbarung) in Ihrem PayPal-Konto aktiviert haben. Kontaktieren Sie dazu bitte Ihren Kundenbetreuer bei PayPal.)');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_ENDCUSTOMER_INFO_TITLE','Benachrichtigung des K&auml;ufers');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_ENDCUSTOMER_INFO_DESC','Der eingegebene Text wird auf der Checkout-Seite angezeigt');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_SORT_ORDER_TITLE','Geben Sie eine Sortierreihenfolge an');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_SORT_ORDER_DESC','Diese Zahlungsart wird unter anderen Zahlungsarten (in aufsteigender Richtung) anhand der angegebenen Nummer f&uuml;r die Sortierung eingeordnet');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_PENDING_ORDER_STATUS_TITLE','Bestellstatus der ausstehenden Zahlung');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_PENDING_ORDER_STATUS_DESC','');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_ORDER_STATUS_TITLE','Abschluss-Status der Bestellung');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_ORDER_STATUS_DESC','');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_ONE_CLICK_TEXT_DESCRIPTION','Sobald die Bestellung abgeschickt wurde, wird die Zahlung bei Novalnet als Referenztransaktion verarbeitet.');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_SHOP_TYPE_TITLE','Einkaufstyp');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_SHOP_TYPE_DESC','Einkaufstyp ausw&auml;hlen <br><span style="color:red">Um diese Option zu verwenden, müssen Sie die Option Billing Agreement (Zahlungsvereinbarung) in Ihrem PayPal-Konto aktiviert haben. Kontaktieren Sie dazu bitte Ihren Kundenbetreuer bei PayPal.</span> ');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_NOTIFICATION_TO_MERCHANT','');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_NEW_ACCOUNT', 'Mit neuen PayPal-Kontodetails fortfahren');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_GIVEN_ACCOUNT','Angegebene PayPal-Kontodetails');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_TRANSACTION_TID','PayPal Transaktions-ID');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_ORDER_TRANSACTION_TID','Novalnet Transaktions-ID');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_ONE_CLICK','Kauf mit einem Klick');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_ZERO_AMOUNT','Transaktionen mit Betrag 0');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_PAYMENT_ZONE_TITLE','Zahlungsgebiet');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_PAYMENT_ZONE_DESC','Diese Zahlungsart wird f&uuml;r die angegebenen Gebiete angezeigt');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_TRANS_REFERENCE1_TITLE','1. Referenz zur Transaktion');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_TRANS_REFERENCE1_DESC','Diese Referenz wird auf Ihrem Kontoauszug erscheinen');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_TRANS_REFERENCE2_TITLE','2. Referenz zur Transaktion');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_TRANS_REFERENCE2_DESC','Diese Referenz wird auf Ihrem Kontoauszug erscheinen');
define('MODULE_PAYMENT_NOVALNET_PAYPAL_PUBLIC_TITLE', 'PayPal '.(((!defined('MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY')) || MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY == 'True') ? '<a href="https://www.novalnet.de" target="_blank"/><img title="PayPal" alt="PayPal" src="'.DIR_WS_ICONS.'payment/novalnet_paypal.png" height="30px"></a>':''));
