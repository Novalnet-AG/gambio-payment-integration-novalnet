<?php
include_once (dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_ALIPAY_TEXT_TITLE', 'Novalnet Alipay');
define('MODULE_PAYMENT_NOVALNET_ALIPAY_TEXT_DESCRIPTION', 'Die Zahlung wird im Testmodus durchgeführt, daher wird der Betrag für diese Transaktion nicht eingezogen.');
define('MODULE_PAYMENT_NOVALNET_ALIPAY_ALLOWED_TITLE','Erlaubte Zone(n)');
define('MODULE_PAYMENT_NOVALNET_ALIPAY_ALLOWED_DESC','Diese Zahlungsmethode ist nur für die angegebene(n) Zone(n) zulässig. Geben Sie die Zone(n) in folgendem Format ein, z.B: DE, AT, CH. Wenn das Feld leer ist, werden alle Zonen zugelassen.');
define('MODULE_PAYMENT_NOVALNET_ALIPAY_STATUS_TITLE','Zahlungsart aktivieren');
define('MODULE_PAYMENT_NOVALNET_ALIPAY_STATUS_DESC','Möchten Sie alipay-Zahlungen akzeptieren?');
define('MODULE_PAYMENT_NOVALNET_ALIPAY_TEST_MODE_TITLE','Testmodus aktivieren');
define('MODULE_PAYMENT_NOVALNET_ALIPAY_TEST_MODE_DESC','Die Zahlung wird im Testmodus durchgeführt, daher wird der Betrag für diese Transaktion nicht eingezogen.');
define('MODULE_PAYMENT_NOVALNET_ALIPAY_SORT_ORDER_TITLE','Sortierreihenfolge anzeigen');
define('MODULE_PAYMENT_NOVALNET_ALIPAY_SORT_ORDER_DESC','Sortierreihenfolge anzeigen; der niedrigste Wert wird zuerst angezeigt.');
define('MODULE_PAYMENT_NOVALNET_ALIPAY_ORDER_STATUS_TITLE','Status für erfolgreichen Auftragsabschluss');
define('MODULE_PAYMENT_NOVALNET_ALIPAY_ORDER_STATUS_DESC','Wählen Sie, welcher Status für erfolgreich abgeschlossene Bestellungen verwendet wird');
define('MODULE_PAYMENT_NOVALNET_ALIPAY_PAYMENT_ZONE_TITLE','Zahlungsbereich');
define('MODULE_PAYMENT_NOVALNET_ALIPAY_PAYMENT_ZONE_DESC','Wenn eine Zone ausgewählt wird, wird diese Zahlungsmethode nur für diese Zone aktiviert.');
define('MODULE_PAYMENT_NOVALNET_ALIPAY_VISIBILITY_BY_AMOUNT_TITLE','Mindestbestellbetrag');
define('MODULE_PAYMENT_NOVALNET_ALIPAY_VISIBILITY_BY_AMOUNT_DESC','Mindestbestellsumme zur Anzeige der ausgewählten Zahlungsart(en) im Checkout  (in der kleinsten Währungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR)');
define('MODULE_PAYMENT_NOVALNET_ALIPAY_ENDCUSTOMER_INFO_TITLE','Benachrichtigung des Käufers.');
define('MODULE_PAYMENT_NOVALNET_ALIPAY_ENDCUSTOMER_INFO_DESC','Der eingegebene Text wird auf der Kassenseite angezeigt.');
?>

