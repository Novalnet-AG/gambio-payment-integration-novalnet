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
 * Script : novalnet_przelewy24.php
 *
 */
require_once (dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_PRZELEWY24_TEXT_TITLE', 'Przelewy24');
define('MODULE_PAYMENT_NOVALNET_REDIRECT_DESC','Nach der erfolgreichen &Uuml;berpr&uuml;fung werden Sie auf die abgesicherte Novalnet-Bestellseite umgeleitet, um die Zahlung fortzusetzen.<br>Bitte schlie&szlig;en Sie den Browser nach der erfolgreichen Zahlung nicht, bis Sie zum Shop zur&uuml;ckgeleitet wurden');
define('MODULE_PAYMENT_NOVALNET_PRZELEWY24_ALLOWED_TITLE','Zugelassene Gebiete');
define('MODULE_PAYMENT_NOVALNET_PRZELEWY24_ALLOWED_DESC','Diese Zahlungsart wird nur f&uuml;r die aufgef&uuml;hrten Gebiete zugelassen. Geben Sie die Gebiete in folgendem Format ein, z.B. DE, AT, CH etc. Falls das Feld leer ist, sind alle Gebiete zugelassen.');
define('MODULE_PAYMENT_NOVALNET_PRZELEWY24_ENABLE_MODULE_TITLE','Zahlungsart aktivieren');
define('MODULE_PAYMENT_NOVALNET_PRZELEWY24_ENABLE_MODULE_DESC','');
define('MODULE_PAYMENT_NOVALNET_PRZELEWY24_TEST_MODE_TITLE','Testmodus aktivieren');
define('MODULE_PAYMENT_NOVALNET_PRZELEWY24_TEST_MODE_DESC','Die Zahlung wird im Testmodus durchgef&uuml;hrt, daher wird der Betrag f&uuml;r diese Transaktion nicht eingezogen');
define('MODULE_PAYMENT_NOVALNET_PRZELEWY24_VISIBILITY_BY_AMOUNT_TITLE','Mindestwarenwert (in der kleinsten Währungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR)');
define('MODULE_PAYMENT_NOVALNET_PRZELEWY24_VISIBILITY_BY_AMOUNT_DESC','Geben Sie den Mindestwarenwert ein, von dem ab die Zahlungsart f&uuml;r den Kunden beim Checkout angezeigt wird');
define('MODULE_PAYMENT_NOVALNET_PRZELEWY24_ENDCUSTOMER_INFO_TITLE','Benachrichtigung des K&auml;ufers');
define('MODULE_PAYMENT_NOVALNET_PRZELEWY24_ENDCUSTOMER_INFO_DESC','Der eingegebene Text wird auf der Checkout-Seite angezeigt');
define('MODULE_PAYMENT_NOVALNET_PRZELEWY24_SORT_ORDER_TITLE','Geben Sie eine Sortierreihenfolge an');
define('MODULE_PAYMENT_NOVALNET_PRZELEWY24_SORT_ORDER_DESC','Diese Zahlungsart wird unter anderen Zahlungsarten (in aufsteigender Richtung) anhand der angegebenen Nummer f&uuml;r die Sortierung eingeordnet');
define('MODULE_PAYMENT_NOVALNET_PRZELEWY24_PENDING_ORDER_STATUS_TITLE','Bestellstatus der ausstehenden Zahlung');
define('MODULE_PAYMENT_NOVALNET_PRZELEWY24_PENDING_ORDER_STATUS_DESC','');
define('MODULE_PAYMENT_NOVALNET_PRZELEWY24_ORDER_STATUS_TITLE','Abschluss-Status der Bestellung');
define('MODULE_PAYMENT_NOVALNET_PRZELEWY24_ORDER_STATUS_DESC','');
define('MODULE_PAYMENT_NOVALNET_PRZELEWY24_PAYMENT_ZONE_TITLE','Zahlungsgebiet');
define('MODULE_PAYMENT_NOVALNET_PRZELEWY24_PAYMENT_ZONE_DESC','Diese Zahlungsart wird f&uuml;r die angegebenen Gebiete angezeigt');
define('MODULE_PAYMENT_NOVALNET_PRZELEWY24_TRANS_REFERENCE1_TITLE','1. Referenz zur Transaktion');
define('MODULE_PAYMENT_NOVALNET_PRZELEWY24_TRANS_REFERENCE1_DESC','Diese Referenz wird auf Ihrem Kontoauszug erscheinen');
define('MODULE_PAYMENT_NOVALNET_PRZELEWY24_TRANS_REFERENCE2_TITLE','2. Referenz zur Transaktion');
define('MODULE_PAYMENT_NOVALNET_PRZELEWY24_TRANS_REFERENCE2_DESC','Diese Referenz wird auf Ihrem Kontoauszug erscheinen');
define('MODULE_PAYMENT_NOVALNET_PRZELEWY24_PUBLIC_TITLE', 'Przelewy24 '.(((!defined('MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY')) || MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY == 'True') ? '<a href="https://www.novalnet.de" target="_blank"/><img title="przelewy24" alt="przelewy24" src="'.DIR_WS_ICONS.'payment/novalnet_przelewy24.png" height="30px" width=16%></a>':''));
