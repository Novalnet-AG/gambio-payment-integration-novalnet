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
 * Script : novalnet_invoice.php
 *
 */
require_once (dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_TEXT_TITLE', 'Ratenzahlung per Rechnung');
define('MODULE_PAYMENT_NOVALNET_INV_PRE_DESC','Sie erhalten eine E-Mail mit den Bankdaten von Novalnet, um die Zahlung abzuschließen');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_ALLOWED_TITLE','Zugelassene Gebiete');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_ALLOWED_DESC','Diese Zahlungsart wird nur f&uuml;r die aufgef&uuml;hrten Gebiete zugelassen. Geben Sie die Gebiete in folgendem Format ein, z.B. DE, AT, CH etc. Falls das Feld leer ist, sind alle Gebiete zugelassen.');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_STATUS_TITLE','Zahlungsart anzeigen');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_STATUS_DESC','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_TEST_MODE_TITLE','Testmodus aktivieren');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_TEST_MODE_DESC','Die Zahlung wird im Testmodus durchgef&uuml;hrt, daher wird der Betrag f&uuml;r diese Transaktion nicht eingezogen');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_ENDCUSTOMER_INFO_TITLE','Benachrichtigung des K&auml;ufers');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_ENDCUSTOMER_INFO_DESC','Der eingegebene Text wird auf der Checkout-Seite angezeigt');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_SORT_ORDER_TITLE','Anzeigereihenfolge');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_SORT_ORDER_DESC','Reihenfolge der Anzeige. Kleinste Ziffer wird zuerst angezeigt.');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_ORDER_STATUS_TITLE','Status für erfolgreichen Auftragsabschluss');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_ORDER_STATUS_DESC','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_PENDING_ORDER_STATUS_TITLE','Bestellstatus der ausstehenden Zahlung');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_PENDING_ORDER_STATUS_DESC','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_PAYMENT_ZONE_TITLE','Zahlungsgebiet');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_PAYMENT_ZONE_DESC','Wenn eine Zone ausgewählt ist, gilt die Zahlungsmethode nur für diese Zone.');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_GUARANTEE_TITLE','<h3>Voraussetzungen für die Ratenzahlung</h3>Erlaubte B2C-Länder: Deutschland, Österreich, Schweiz <br> Erlaubte B2B-Länder: Europäische Union <br> Zugelassene Währung: € <br> Mindestbetrag der Bestellung: 19.98 € <br> Bitte beachten Sie, dass der Betrag einer Rate mindestens 9.99 EUR betragen muss und Raten, die diese Kriterien nicht erfüllen, nicht im Ratenplan angezeigt werden <br>Mindestalter: 18 Jahre <br> Rechnungsadresse und Lieferadresse müssen übereinstimmen');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_GUARANTEE_DESC','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_BLOCK_TITLE','<b>Kauf auf Rechnung Konfiguration</b>');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_DUE_DATE_ERROR','Ungültiges Fälligkeitsdatum');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_GUARANTEE_ERROR','Die Zahlung kann nicht verarbeitet werden, weil die grundlegenden Anforderungen nicht erfüllt wurden.');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_GUARANTEE_MINIMUM_AMOUNT_ERROR','Der Mindestbetrag sollte bei mindestens 19,98 EUR');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_PUBLIC_TITLE', 'Kauf auf Rechnung  '.(((!defined('MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY')) || MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY == 'true') ? '<a href="https://www.novalnet.de" target="_blank"/><img title="Kauf auf Rechnung " alt="Kauf auf Rechnung " src="'.DIR_WS_ICONS.'payment/novalnet_invoice.png" width=8% ></a>':''));
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_MANUAL_CHECK_LIMIT_DESC', 'Mindesttransaktionsbetrag für die Autorisierung (in der kleinsten Währungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR)');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_MANUAL_CHECK_LIMIT_TITLE', 'Übersteigt der Bestellbetrag das genannte Limit, wird die Transaktion bis zu Ihrer Bestätigung auf On-Hold gesetzt. Alle Transaktionen werden als On-Hold Transaktionen angelegt, wenn Sie "Zahlung autorisieren" als Bearbeitungsmaßnahme hinterlegt und keinen Mindesttransaktionsbetrag konfiguriert haben.');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_CYCLE_TITLE','Anzahl der Raten');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_CYCLE_DESC','Wählen Sie die verschiedenen Anzahlen der Raten aus, die Sie erlauben wollen (Mehrfachnennung möglich).');

define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_VISIBILITY_BY_AMOUNT_TITLE','Mindestbestellsumme');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_VISIBILITY_BY_AMOUNT_DESC','Mindestbestellsumme zur Anzeige der ausgewählten Zahlungsart(en) im Checkout');

define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_ALLOW_B2B_TITLE','B2B-Kunden erlauben');
define('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_ALLOW_B2B_DESC','B2B-Kunden erlauben, Bestellungen aufzugeben');

