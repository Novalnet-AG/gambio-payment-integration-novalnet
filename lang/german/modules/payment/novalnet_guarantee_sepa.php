<?php
include_once (dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_TEXT_TITLE', 'Novalnet SEPA-Lastschrift mit Zahlungsgarantie');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_ALLOWED_TITLE','Erlaubte Zone(n)');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_ALLOWED_DESC','Diese Zahlungsmethode ist nur für die angegebene(n) Zone(n) zulässig. Geben Sie die Zone(n) in folgendem Format ein, z.B: DE, AT, CH. Wenn das Feld leer ist, werden alle Zonen zugelassen.');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_STATUS_TITLE','Zahlungsart aktivieren');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_STATUS_DESC','Möchten Sie die Zahlung auf SEPA akzeptieren?');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_TEST_MODE_TITLE','Testmodus aktivieren');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_TEST_MODE_DESC','Die Zahlung wird im Testmodus durchgeführt, daher wird der Betrag für diese Transaktion nicht eingezogen.');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_SORT_ORDER_TITLE','Sortierreihenfolge anzeigen');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_SORT_ORDER_DESC','Sortierreihenfolge anzeigen; der niedrigste Wert wird zuerst angezeigt.');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_ORDER_STATUS_TITLE','Status für erfolgreichen Auftragsabschluss');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_ORDER_STATUS_DESC','Status, der für erfolgreiche Bestellungen zu verwenden ist');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_PAYMENT_ZONE_TITLE','Zahlungsbereich');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_PAYMENT_ZONE_DESC','Wenn eine Zone ausgewählt wird, wird diese Zahlungsmethode nur für diese Zone aktiviert.');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_TITLE','<h2> Einstellungen für die Zahlungsgarantie</h2><h3>Grundanforderungen:</h3>Erlaubte B2C-Länder: Deutschland, Österreich, Schweiz <br> Erlaubte B2B-Länder: Europäische Union <br>Erlaubte Währung: € <br> Mindestbestellwert: 9,99 € oder mehr <br>Altersgrenze: 18 Jahre oder mehr<br> Die Rechnungsadresse muss die gleiche sein wie die Lieferadresse');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_MINIMUM_ORDER_AMOUNT_TITLE',' Mindestbestellbetrag');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_MINIMUM_ORDER_AMOUNT_DESC','Mindestbestellsumme zur Anzeige der ausgewählten Zahlungsart(en) im Checkout (in der kleinsten Währungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR)');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_DESC','&nbsp;');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_FORCE_TITLE',' Zahlung ohne Zahlungsgarantie erzwingen');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_FORCE_DESC','Falls die Zahlungsgarantie zwar aktiviert ist, jedoch die Voraussetzungen für Zahlungsgarantie nicht erfüllt sind, wird die Zahlung ohne Zahlungsgarantie verarbeitet. Die Voraussetzungen finden Sie in der Installationsanleitung unter "Zahlungsgarantie aktivieren"');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_ALLOW_B2B_TITLE','B2B-Kunden erlauben');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_ALLOW_B2B_DESC','B2B-Kunden erlauben, Bestellungen aufzugeben');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_AUTHENTICATE_TITLE','Aktion für vom Besteller autorisierte Zahlungen.');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_AUTHENTICATE_DESC','Wählen Sie, ob die Zahlung sofort belastet werden soll oder nicht. Authorize prüft die Zahlungsdetails und reserviert Mittel, um sie später zu erfassen, damit der Händler Zeit hat, über die Bestellung zu entscheiden.</br></br> Zahlung autorisieren');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_MANUAL_CHECK_LIMIT_TITLE','Mindesttransaktionsbetrag für die Autorisierung');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_MANUAL_CHECK_LIMIT_DESC', 'Übersteigt der Bestellbetrag das genannte Limit, wird die Transaktion, bis zu ihrer Bestätigung durch Sie, auf on hold gesetzt. Sie können das Feld leer lassen, wenn Sie möchten, dass alle Transaktionen als on hold behandelt werden. (in der kleinsten Währungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR).');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_TOKENIZATION_TITLE','Kauf mit einem Klick');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_TOKENIZATION_DESC','Zahlungsdaten, die während des Bestellvorgangs gespeichert werden, können für zukünftige Zahlungen verwendet werden');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_SAVE_CARD_DETAILS', 'Ich möchte meine Kontodaten für spätere Einkäufe speichern');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_NEW_ACCOUNT_DETAILS', 'Neue Kontodaten hinzufügen');

define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_DUE_DATE_TITLE', 'Fälligkeitsdatum der Zahlung');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_DUE_DATE_DESC', 'Geben Sie die Anzahl der Tage ein, nach denen der Zahlungsbetrag eingezogen werden soll (muss zwischen 2 und 14 Tagen liegen) ');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_ENDCUSTOMER_INFO_TITLE','Benachrichtigung des Käufers');
define('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_ENDCUSTOMER_INFO_DESC','Der eingegebene Text wird auf der Kassenseite angezeigt');
?>
