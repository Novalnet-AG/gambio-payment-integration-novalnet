<?php
include_once (dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_INVOICE_TEXT_TITLE', 'Novalnet Kauf auf Rechnung');
define('MODULE_PAYMENT_NOVALNET_INVOICE_TEXT_DESCRIPTION', 'Der Kunde erhält die Ware vor der Bezahlung und erhält mit der Lieferung die Rechnung mit den Bestelldetails sowie einem festgelegtem Zahlungsziel');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ALLOWED_TITLE','Erlaubte Zone(n)');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ALLOWED_DESC','Diese Zahlungsmethode ist nur für die angegebene(n) Zone(n) zulässig. Geben Sie die Zone(n) in folgendem Format ein, z.B: DE, AT, CH. Wenn das Feld leer ist, werden alle Zonen zugelassen.');
define('MODULE_PAYMENT_NOVALNET_INVOICE_STATUS_TITLE','Zahlungsart aktivieren');
define('MODULE_PAYMENT_NOVALNET_INVOICE_STATUS_DESC','Möchten Sie die Zahlung auf Rechnung akzeptieren?');
define('MODULE_PAYMENT_NOVALNET_INVOICE_TEST_MODE_TITLE','Testmodus aktivieren');
define('MODULE_PAYMENT_NOVALNET_INVOICE_TEST_MODE_DESC','Die Zahlung wird im Testmodus durchgeführt, daher wird der Betrag für diese Transaktion nicht eingezogen.');
define('MODULE_PAYMENT_NOVALNET_INVOICE_DUE_DATE_TITLE','Fälligkeitsdatum (in Tagen)');
define('MODULE_PAYMENT_NOVALNET_INVOICE_DUE_DATE_DESC','Anzahl der Tage, die der Käufer Zeit hat, um den Betrag an Novalnet zu überweisen (muss mehr als 7 Tage betragen). Wenn Sie dieses Feld leer lassen, werden standardmäßig 14 Tage als Fälligkeitsdatum festgelegt.');
define('MODULE_PAYMENT_NOVALNET_INVOICE_SORT_ORDER_TITLE','Sortierreihenfolge anzeigen');
define('MODULE_PAYMENT_NOVALNET_INVOICE_SORT_ORDER_DESC','Sortierreihenfolge anzeigen; der niedrigste Wert wird zuerst angezeigt.');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ORDER_STATUS_TITLE','Status für erfolgreichen Auftragsabschluss');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ORDER_STATUS_DESC','Status, der für erfolgreiche Bestellungen zu verwenden ist');

define('MODULE_PAYMENT_NOVALNET_INVOICE_CALLBACK_ORDER_STATUS_TITLE','Callback / Webhook Bestellstatus');
define('MODULE_PAYMENT_NOVALNET_INVOICE_CALLBACK_ORDER_STATUS_DESC','Wählen Sie, welcher Status nach der erfolgreichen Ausführung des Novalnet-Callback-Skripts (ausgelöst bei erfolgreicher Zahlung) verwendet wird');
define('MODULE_PAYMENT_NOVALNET_INVOICE_PAYMENT_ZONE_TITLE','Zahlungsbereich');
define('MODULE_PAYMENT_NOVALNET_INVOICE_PAYMENT_ZONE_DESC','Wenn eine Zone ausgewählt wird, wird diese Zahlungsmethode nur für diese Zone aktiviert.');
define('MODULE_PAYMENT_NOVALNET_INVOICE_AUTHENTICATE_TITLE','Aktion für vom Besteller autorisierte Zahlungen');
define('MODULE_PAYMENT_NOVALNET_INVOICE_AUTHENTICATE_DESC','Wählen Sie, ob die Zahlung sofort belastet werden soll oder nicht. Authorize prüft die Zahlungsdetails und reserviert Mittel, um sie später zu erfassen, damit der Händler Zeit hat, über die Bestellung zu entscheiden.</br></br> Zahlung autorisieren');
define('MODULE_PAYMENT_NOVALNET_INVOICE_MANUAL_CHECK_LIMIT_TITLE','Mindesttransaktionsbetrag für die Autorisierung');
define('MODULE_PAYMENT_NOVALNET_INVOICE_MANUAL_CHECK_LIMIT_DESC', 'Übersteigt der Bestellbetrag das genannte Limit, wird die Transaktion, bis zu ihrer Bestätigung durch Sie, auf on hold gesetzt. Sie können das Feld leer lassen, wenn Sie möchten, dass alle Transaktionen als on hold behandelt werden.  (in der kleinsten Währungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR).');
define('MODULE_PAYMENT_NOVALNET_INVOICE_VISIBILITY_BY_AMOUNT_TITLE','Mindestbestellbetrag');
define('MODULE_PAYMENT_NOVALNET_INVOICE_VISIBILITY_BY_AMOUNT_DESC','Mindestbestellsumme zur Anzeige der ausgewählten Zahlungsart(en) im Checkout (in der kleinsten Währungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR)');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ENDCUSTOMER_INFO_TITLE','Benachrichtigung des Käufers');
define('MODULE_PAYMENT_NOVALNET_INVOICE_ENDCUSTOMER_INFO_DESC','Der eingegebene Text wird auf der Kassenseite angezeigt');
?>
