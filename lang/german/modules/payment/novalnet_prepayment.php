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
 * Script : novalnet_prepayment.php
 *
 */
require_once (dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_TEXT_TITLE', 'Vorauskasse');
define('MODULE_PAYMENT_NOVALNET_INV_PRE_DESC','Nachdem Sie die Bestellung abgeschickt haben, erhalten Sie eine Email mit den Bankdaten, um die Zahlung durchzuf&uuml;hren.');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_ALLOWED_TITLE','Zugelassene Gebiete');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_ALLOWED_DESC','Diese Zahlungsart wird nur f&uuml;r die aufgef&uuml;hrten Gebiete zugelassen. Geben Sie die Gebiete in folgendem Format ein, z.B. DE, AT, CH etc. Falls das Feld leer ist, sind alle Gebiete zugelassen.');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_ENABLE_MODULE_TITLE','Zahlungsart aktivieren');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_ENABLE_MODULE_DESC','');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_TEST_MODE_TITLE','Testmodus aktivieren');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_TEST_MODE_DESC','Die Zahlung wird im Testmodus durchgef&uuml;hrt, daher wird der Betrag f&uuml;r diese Transaktion nicht eingezogen');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_VISIBILITY_BY_AMOUNT_TITLE','Mindestwarenwert (in der kleinsten Währungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR)');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_VISIBILITY_BY_AMOUNT_DESC','Geben Sie den Mindestwarenwert ein, von dem ab die Zahlungsart f&uuml;r den Kunden beim Checkout angezeigt wird');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_ENDCUSTOMER_INFO_TITLE','Benachrichtigung des K&auml;ufers');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_ENDCUSTOMER_INFO_DESC','Der eingegebene Text wird auf der Checkout-Seite angezeigt');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_SORT_ORDER_TITLE','Geben Sie eine Sortierreihenfolge an');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_SORT_ORDER_DESC','Diese Zahlungsart wird unter anderen Zahlungsarten (in aufsteigender Richtung) anhand der angegebenen Nummer f&uuml;r die Sortierung eingeordnet');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_ORDER_STATUS_TITLE','Abschluss-Status der Bestellung');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_ORDER_STATUS_DESC','');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_CALLBACK_ORDER_STATUS_TITLE','Callback-Bestellstatus');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_CALLBACK_ORDER_STATUS_DESC','');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_PAYMENT_ZONE_TITLE','Zahlungsgebiet');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_PAYMENT_ZONE_DESC','Diese Zahlungsart wird f&uuml;r die angegebenen Gebiete angezeigt');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_TRANS_REFERENCE1_TITLE','1. Referenz zur Transaktion');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_TRANS_REFERENCE1_DESC','Diese Referenz wird auf Ihrem Kontoauszug erscheinen');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_TRANS_REFERENCE2_TITLE','2. Referenz zur Transaktion');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_TRANS_REFERENCE2_DESC','Diese Referenz wird auf Ihrem Kontoauszug erscheinen');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_PAYMENT_REFERENCE1_TITLE','Verwendungszweck 1: (Novalnet Rechnungsnummer)');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_PAYMENT_REFERENCE1_DESC','');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_PAYMENT_REFERENCE2_TITLE','Verwendungszweck 2: (TID)');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_PAYMENT_REFERENCE2_DESC','');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_PAYMENT_REFERENCE3_TITLE','Verwendungszweck 3: (Bestellnummer)');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_PAYMENT_REFERENCE3_DESC','');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_BLOCK_TITLE','<b>Vorauskasse Konfiguration</b>');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_PUBLIC_TITLE', 'Vorauskasse '.(((!defined('MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY')) || MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY == 'True') ? '<a href="https://www.novalnet.de" target="_blank"/><img title="Vorauskasse" alt="Vorauskasse" src="'.DIR_WS_ICONS.'novalnet/novalnet_prepayment.png" width=9%></a>':''));
