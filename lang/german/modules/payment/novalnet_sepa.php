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
 * Script : novalnet_sepa.php
 *
 */
require_once (dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_SEPA_TEXT_TITLE', 'Lastschrift SEPA');
define('MODULE_PAYMENT_NOVALNET_SEPA_DESC','Ihr Konto wird nach Abschicken der Bestellung belastet');
define('MODULE_PAYMENT_NOVALNET_SEPA_ALLOWED_TITLE','Zugelassene Gebiete');
define('MODULE_PAYMENT_NOVALNET_SEPA_ALLOWED_DESC','Diese Zahlungsart wird nur f&uuml;r die aufgef&uuml;hrten Gebiete zugelassen. Geben Sie die Gebiete in folgendem Format ein, z.B. DE, AT, CH etc. Falls das Feld leer ist, sind alle Gebiete zugelassen.');
define('MODULE_PAYMENT_NOVALNET_SEPA_ENABLE_MODULE_TITLE','Zahlungsart aktivieren');
define('MODULE_PAYMENT_NOVALNET_SEPA_ENABLE_MODULE_DESC','');
define('MODULE_PAYMENT_NOVALNET_SEPA_TEST_MODE_TITLE','Testmodus aktivieren');
define('MODULE_PAYMENT_NOVALNET_SEPA_TEST_MODE_DESC','Die Zahlung wird im Testmodus durchgef&uuml;hrt, daher wird der Betrag f&uuml;r diese Transaktion nicht eingezogen');
define('MODULE_PAYMENT_NOVALNET_SEPA_ENABLE_FRAUDMODULE_TITLE','Betrugspr&uuml;fung aktivieren');
define('MODULE_PAYMENT_NOVALNET_SEPA_ENABLE_FRAUDMODULE_DESC','Um den K&auml;ufer einer Transaktion zu authentifizieren, werden die PIN automatisch generiert und an den K&auml;ufer geschickt. Dieser Dienst wird nur f&uuml;r Kunden aus DE, AT und CH angeboten');
define('MODULE_PAYMENT_NOVALNET_SEPA_CALLBACK_LIMIT_TITLE','Mindestwarenwert f&uuml;r Betrugspr&uuml;fungsmodul (in der kleinsten Währungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR)');
define('MODULE_PAYMENT_NOVALNET_SEPA_CALLBACK_LIMIT_DESC','Geben Sie den Mindestwarenwert ein, von dem ab das Betrugspr&uuml;fungsmodul aktiviert sein soll');
define('MODULE_PAYMENT_NOVALNET_SEPA_SHOP_TYPE_TITLE','Einkaufstyp');
define('MODULE_PAYMENT_NOVALNET_SEPA_SHOP_TYPE_DESC','Einkaufstyp ausw&auml;hlen ');
define('MODULE_PAYMENT_NOVALNET_SEPA_VISIBILITY_BY_AMOUNT_TITLE','Mindestwarenwert (in der kleinsten Währungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR)');
define('MODULE_PAYMENT_NOVALNET_SEPA_VISIBILITY_BY_AMOUNT_DESC','Geben Sie den Mindestwarenwert ein, von dem ab die Zahlungsart f&uuml;r den Kunden beim Checkout angezeigt wird');
define('MODULE_PAYMENT_NOVALNET_SEPA_ENDCUSTOMER_INFO_TITLE','Benachrichtigung des K&auml;ufers');
define('MODULE_PAYMENT_NOVALNET_SEPA_ENDCUSTOMER_INFO_DESC','Der eingegebene Text wird auf der Checkout-Seite angezeigt');
define('MODULE_PAYMENT_NOVALNET_SEPA_SORT_ORDER_TITLE','Geben Sie eine Sortierreihenfolge an');
define('MODULE_PAYMENT_NOVALNET_SEPA_SORT_ORDER_DESC','Diese Zahlungsart wird unter anderen Zahlungsarten (in aufsteigender Richtung) anhand der angegebenen Nummer f&uuml;r die Sortierung eingeordnet');
define('MODULE_PAYMENT_NOVALNET_SEPA_ORDER_STATUS_TITLE','Abschluss-Status der Bestellung');
define('MODULE_PAYMENT_NOVALNET_SEPA_ORDER_STATUS_DESC','');
define('MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_ZONE_TITLE','Zahlungsgebiet');
define('MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_ZONE_DESC','Diese Zahlungsart wird f&uuml;r die angegebenen Gebiete angezeigt');
define('MODULE_PAYMENT_NOVALNET_SEPA_TRANS_REFERENCE1_TITLE','1. Referenz zur Transaktion');
define('MODULE_PAYMENT_NOVALNET_SEPA_TRANS_REFERENCE1_DESC','Diese Referenz wird auf Ihrem Kontoauszug erscheinen');
define('MODULE_PAYMENT_NOVALNET_SEPA_TRANS_REFERENCE2_TITLE','2. Referenz zur Transaktion');
define('MODULE_PAYMENT_NOVALNET_SEPA_TRANS_REFERENCE2_DESC','Diese Referenz wird auf Ihrem Kontoauszug erscheinen');
define('MODULE_PAYMENT_NOVALNET_SEPA_ONE_CLICK','Kauf mit einem Klick');
define('MODULE_PAYMENT_NOVALNET_SEPA_ZERO_AMOUNT','Transaktionen mit Betrag 0');
define('MODULE_PAYMENT_NOVALNET_SEPA_NEW_ACCOUNT', 'Neue Kontodaten eingeben');
define('MODULE_PAYMENT_NOVALNET_SEPA_GIVEN_ACCOUNT','Eingegebene Kontodaten');
define('MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_REFILL_TITLE','Automatisches Eintragen der Zahlungsdaten aktivieren');
define('MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_REFILL_DESC','F&uuml;r registrierte Benutzer werden die SEPA-Lastschriftdetails automatisch in das Zahlungsformular eingetragen.');
define('MODULE_PAYMENT_NOVALNET_BANK_COUNTRY' ,'Land der Bank');
define('MODULE_PAYMENT_NOVALNET_ACCOUNT_OR_IBAN','IBAN oder Kontonummer');
define('MODULE_PAYMENT_NOVALNET_BANKCODE_OR_BIC','BIC oder Bankleitzahl');
define('MODULE_PAYMENT_NOVALNET_SEPA_FORM_MANDATE_CONFIRM_TEXT', 'Hiermit erteile ich das SEPA-Lastschriftmandat und best&auml;tige, dass die angegebene IBAN und BIC korrekt sind.');
define('MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_DUE_DATE_TITLE','Abstand (in Tagen) bis zum SEPA-Einzugsdatum');
define('MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_DUE_DATE_DESC','Geben Sie die Anzahl der Tage ein, nach denen die Zahlung verarbeitet werden soll (muss gr&ouml;&szlig;er als 6 Tage sein).');
define('MODULE_PAYMENT_NOVALNET_SEPA_AUTO_FILL_TITLE','Automatisches Eintragen aktivieren');
define('MODULE_PAYMENT_NOVALNET_SEPA_AUTO_FILL_DESC','Die Zahlungsdetails werden automatisch w&auml;hrend des Checkout-Vorgangs in das Zahlungsformular eingetragen.');
define('MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_TITLE','<h2>Einstellungen für die Zahlungsgarantie</h2><h3>Grundanforderungen für die Zahlungsgarantie</h3>Zugelassene Staaten: AT, DE, CH <br>Zugelassene Währung: EUR <br> Mindestbetrag der Bestellung >= 20,00 EUR <br>Maximalbetrag der Bestellung <= 5.000,00 EUR <br> Mindestalter des Endkunden >= 18 Jahre <br> Rechnungsadresse und Lieferadresse müssen übereinstimmen <br> Geschenkgutscheine / Coupons sind nicht erlaubt<br><br>Zahlungsgarantie aktivieren');
define('MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_DESC','');
define('MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_MINIMUM_ORDER_AMOUNT_TITLE','Mindestbestellbetrag (in der kleinsten Währungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR)');
define('MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_MINIMUM_ORDER_AMOUNT_DESC','Diese Einstellung wird den Standardwert für den Mindestbestellbetrag überschreiben. Beachten Sie, dass der Betrag im Bereich von 20,00 EUR – 5.000,00 EUR liegen muss.');
define('MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_MAXIMUM_ORDER_AMOUNT_TITLE','Maximaler Bestellbetrag (in der kleinsten Währungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR)');
define('MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_MAXIMUM_ORDER_AMOUNT_DESC','Diese Einstellung wird den Standardwert für den Maximal-Bestellbetrag überschreiben. Beachten Sie, dass dieser Betrag größer als der Mindestbestellbetrag sein sollte, jedoch nicht höher als 5.000,00 EUR.');
define('MODULE_PAYMENT_NOVALNET_SEPA_FORCE_TITLE','Zahlung ohne Zahlungsgarantie erzwingen');
define('MODULE_PAYMENT_NOVALNET_SEPA_FORCE_DESC','Falls die Zahlungsgarantie aktiviert ist (wahr), die oben genannten Anforderungen jedoch nicht erfüllt werden, soll die Zahlung ohne Zahlungsgarantie verarbeitet werden.');
define('MODULE_PAYMENT_NOVALNET_SEPA_BLOCK_TITLE','<b>SEPA Konfiguration</b>');
define('MODULE_PAYMENT_NOVALNET_SEPA_DUE_DATE_ERROR','SEPA F&auml;lligkeitsdatum Ung&uuml;ltiger');
define('MODULE_PAYMENT_NOVALNET_SEPA_MANDATE_CONFIRM_ERROR','Akzeptieren Sie bitte das SEPA-Lastschriftmandat');
define('MODULE_PAYMENT_NOVALNET_SEPA_SELECT_COUNTRY','Wählen Sie bitte ein Land aus');
define('MODULE_PAYMENT_NOVALNET_SELECT_PAYMENT_METHOD','Wählen Sie bitte die Zahlungsart aus');
define('MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_ERROR','Die Zahlung kann nicht verarbeitet werden, weil die grundlegenden Anforderungen nicht erfüllt wurden.');
define('MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_MINIMUM_AMOUNT_ERROR','Der Mindestbetrag sollte bei mindestens 20,00 EUR liegen, jedoch nicht mehr als 5.000,00 EUR');
define('MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_MAXIMUM_AMOUNT_ERROR','Der Maximalbetrag sollte größer sein als der Mindestbestellbetrag, jedoch nicht höher als 5.000,00 EUR');
define('MODULE_PAYMENT_NOVALNET_SEPA_PUBLIC_TITLE', 'Lastschrift SEPA '. (((!defined('MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY')) || MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY == 'True')?'<a href="https://www.novalnet.de" target="_blank"/><img title="Lastschrift SEPA" alt="Lastschrift SEPA" src="'.DIR_WS_ICONS.'novalnet/novalnet_sepa.png"></a>':''));
