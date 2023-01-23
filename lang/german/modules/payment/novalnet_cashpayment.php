<?php
include_once (dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_TEXT_TITLE', 'Novalnet Barzahlen/viacash');
define('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_TEXT_DESCRIPTION', 'Die Zahlung wird im Testmodus durchgeführt, daher wird der Betrag für diese Transaktion nicht eingezogen.');
define('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_ALLOWED_TITLE','Erlaubte Zone(n)');
define('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_ALLOWED_DESC','Diese Zahlungsmethode ist nur für die angegebene(n) Zone(n) zulässig. Geben Sie die Zone(n) in folgendem Format ein, z.B: DE, AT, CH. Wenn das Feld leer ist, werden alle Zonen zugelassen.');
define('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_STATUS_TITLE','Zahlungsart aktivieren');
define('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_STATUS_DESC','Möchten Sie Barzahlung akzeptieren?');
define('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_TEST_MODE_TITLE','Testmodus aktivieren');
define('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_TEST_MODE_DESC','Die Zahlung wird im Testmodus durchgeführt, daher wird der Betrag für diese Transaktion nicht eingezogen.');
define('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_SORT_ORDER_TITLE','Sortierreihenfolge anzeigen');
define('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_SORT_ORDER_DESC','Sortierreihenfolge anzeigen; der niedrigste Wert wird zuerst angezeigt.');
define('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_ORDER_STATUS_TITLE','Status für erfolgreichen Auftragsabschluss');
define('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_ORDER_STATUS_DESC','Status, der für erfolgreiche Bestellungen zu verwenden ist');
define('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_PAYMENT_ZONE_TITLE','Zahlungsbereich');
define('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_PAYMENT_ZONE_DESC','Wenn eine Zone ausgewählt wird, wird diese Zahlungsmethode nur für diese Zone aktiviert.');
define('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_DUE_DATE_TITLE','Beleg Verfallsdatum (in Tagen)');
define('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_DUE_DATE_DESC','Geben Sie die Anzahl der Tage ein, in denen Sie den Betrag in einer Filiale in Ihrer Nähe bezahlen müssen. Wenn das Feld leer ist, werden 14 Tage als Standard festgelegt.');
define('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_VISIBILITY_BY_AMOUNT_TITLE','Mindestbestellbetrag');
define('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_VISIBILITY_BY_AMOUNT_DESC','Mindestbestellsumme zur Anzeige der ausgewählten Zahlungsart(en) im Checkout (in der kleinsten Währungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR)');
define('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_CALLBACK_ORDER_STATUS_TITLE','Callback / Webhook Bestellstatus');
define('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_CALLBACK_ORDER_STATUS_DESC','Wählen Sie, welcher Status nach der erfolgreichen Ausführung des Novalnet-Callback-Skripts (ausgelöst bei erfolgreicher Zahlung) verwendet wird');
define('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_ENDCUSTOMER_INFO_TITLE','Benachrichtigung des Käufers.');
define('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_ENDCUSTOMER_INFO_DESC','Der eingegebene Text wird auf der Kassenseite angezeigt.');
?>
