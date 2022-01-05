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
 * Script : novalnet_prepayment.php
 *
 */
require_once (dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_TEXT_TITLE', 'Vorauskasse');
define('MODULE_PAYMENT_NOVALNET_INV_PRE_DESC','Sie erhalten eine E-Mail mit den Bankdaten von Novalnet, um die Zahlung abzuschließen');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_ALLOWED_TITLE','Zugelassene Gebiete');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_ALLOWED_DESC','Diese Zahlungsart wird nur f&uuml;r die aufgef&uuml;hrten Gebiete zugelassen. Geben Sie die Gebiete in folgendem Format ein, z.B. DE, AT, CH etc. Falls das Feld leer ist, sind alle Gebiete zugelassen.');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_STATUS_TITLE','Zahlungsart anzeigen');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_STATUS_DESC','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_TEST_MODE_TITLE','Testmodus aktivieren');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_TEST_MODE_DESC','Die Zahlung wird im Testmodus durchgef&uuml;hrt, daher wird der Betrag f&uuml;r diese Transaktion nicht eingezogen');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_VISIBILITY_BY_AMOUNT_TITLE','Mindestwarenwert (in der kleinsten Währungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR)');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_VISIBILITY_BY_AMOUNT_DESC','Geben Sie den Mindestwarenwert ein, von dem ab die Zahlungsart f&uuml;r den Kunden beim Checkout angezeigt wird');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_ENDCUSTOMER_INFO_TITLE','Benachrichtigung des K&auml;ufers');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_ENDCUSTOMER_INFO_DESC','Der eingegebene Text wird auf der Checkout-Seite angezeigt');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_SORT_ORDER_TITLE','Anzeigereihenfolge');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_SORT_ORDER_DESC','Reihenfolge der Anzeige. Kleinste Ziffer wird zuerst angezeigt.');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_ORDER_STATUS_TITLE','Status für erfolgreichen Auftragsabschluss');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_ORDER_STATUS_DESC','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_CALLBACK_ORDER_STATUS_TITLE','Callback / Webhook Bestellstatus');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_CALLBACK_ORDER_STATUS_DESC','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_PAYMENT_ZONE_TITLE','Zahlungsgebiet');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_PAYMENT_ZONE_DESC','Wenn eine Zone ausgewählt ist, gilt die Zahlungsmethode nur für diese Zone.');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_BLOCK_TITLE','<b>Vorauskasse Konfiguration</b>');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_PUBLIC_TITLE', 'Vorauskasse '.(((!defined('MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY')) || MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY == 'true') ? '<a href="https://www.novalnet.de" target="_blank"/><img title="Vorauskasse" alt="Vorauskasse" src="'.DIR_WS_ICONS.'payment/novalnet_prepayment.png" width=9%></a>':''));
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_DUE_DATE_TITLE','Fälligkeitsdatum (in Tagen)');
define('MODULE_PAYMENT_NOVALNET_PREPAYMENT_DUE_DATE_DESC','Anzahl der Tage, die der Käufer Zeit hat, um den Betrag an Novalnet zu überweisen (muss zwischen 7 und 28 Tagen liegen). Wenn Sie dieses Feld leer lassen, werden standardmäßig 14 Tage als Fälligkeitsdatum festgelegt.');
