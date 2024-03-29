<?php
include_once (dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_SEPA_TEXT_TITLE', 'Novalnet SEPA-Lastschrift ');
define('MODULE_PAYMENT_NOVALNET_SEPA_ALLOWED_TITLE','Erlaubte Zone(n)');
define('MODULE_PAYMENT_NOVALNET_SEPA_ALLOWED_DESC','Diese Zahlungsmethode ist nur für die angegebene(n) Zone(n) zulässig. Geben Sie die Zone(n) in folgendem Format ein, z.B: DE, AT, CH. Wenn das Feld leer ist, werden alle Zonen zugelassen..');
define('MODULE_PAYMENT_NOVALNET_SEPA_STATUS_TITLE','Zahlungsart aktivieren');
define('MODULE_PAYMENT_NOVALNET_SEPA_STATUS_DESC','Möchten Sie Sepa-Zahlungen akzeptieren?');
define('MODULE_PAYMENT_NOVALNET_SEPA_TEST_MODE_TITLE','Testmodus aktivieren');
define('MODULE_PAYMENT_NOVALNET_SEPA_TEST_MODE_DESC','Die Zahlung wird im Testmodus durchgeführt, daher wird der Betrag für diese Transaktion nicht eingezogen.');
define('MODULE_PAYMENT_NOVALNET_SEPA_DUE_DATE_TITLE','Fälligkeitsdatum (in Tagen)');
define('MODULE_PAYMENT_NOVALNET_SEPA_DUE_DATE_DESC','Geben Sie die Anzahl der Tage ein, nach denen der Zahlungsbetrag eingezogen werden soll (muss zwischen 2 und 14 Tagen liegen) ');
define('MODULE_PAYMENT_NOVALNET_SEPA_SORT_ORDER_TITLE','Sortierreihenfolge anzeigen');
define('MODULE_PAYMENT_NOVALNET_SEPA_SORT_ORDER_DESC','Sortierreihenfolge anzeigen; der niedrigste Wert wird zuerst angezeigt.');
define('MODULE_PAYMENT_NOVALNET_SEPA_ORDER_STATUS_TITLE','Status für erfolgreichen Auftragsabschluss');
define('MODULE_PAYMENT_NOVALNET_SEPA_ORDER_STATUS_DESC','Status, der für erfolgreiche Bestellungen zu verwenden ist');
define('MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_ZONE_TITLE','Zahlungsbereich');
define('MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_ZONE_DESC','Wenn eine Zone ausgewählt wird, wird diese Zahlungsmethode nur für diese Zone aktiviert.');

define('MODULE_PAYMENT_NOVALNET_SEPA_ACCOUNT_HOLDER','Kontoinhaber');
define('MODULE_PAYMENT_NOVALNET_SEPA_AUTHENTICATE_TITLE','Aktion für vom Besteller autorisierte Zahlungen.');
define('MODULE_PAYMENT_NOVALNET_SEPA_AUTHENTICATE_DESC','Wählen Sie, ob die Zahlung sofort belastet werden soll oder nicht.');
define('MODULE_PAYMENT_NOVALNET_SEPA_MANUAL_CHECK_LIMIT_TITLE','Mindesttransaktionsbetrag für die Autorisierung');
define('MODULE_PAYMENT_NOVALNET_SEPA_MANUAL_CHECK_LIMIT_DESC', 'Übersteigt der Bestellbetrag das genannte Limit, wird die Transaktion, bis zu ihrer Bestätigung durch Sie, auf on hold gesetzt. Sie können das Feld leer lassen, wenn Sie möchten, dass alle Transaktionen als on hold behandelt werden.  (in der kleinsten Währungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR).');
define('MODULE_PAYMENT_NOVALNET_SEPA_TOKENIZATION_TITLE','Kauf mit einem Klick');
define('MODULE_PAYMENT_NOVALNET_SEPA_TOKENIZATION_DESC','Zahlungsdaten, die während des Bestellvorgangs gespeichert werden, können für zukünftige Zahlungen verwendet werden');
define('MODULE_PAYMENT_NOVALNET_SEPA_VISIBILITY_BY_AMOUNT_TITLE','Mindestbestellbetrag');
define('MODULE_PAYMENT_NOVALNET_SEPA_VISIBILITY_BY_AMOUNT_DESC','Mindestbestellsumme zur Anzeige der ausgewählten Zahlungsart(en) im Checkout (in der kleinsten Währungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR)');
define('MODULE_PAYMENT_NOVALNET_SEPA_ENDCUSTOMER_INFO_TITLE','Benachrichtigung des Käufers.');
define('MODULE_PAYMENT_NOVALNET_SEPA_ENDCUSTOMER_INFO_DESC','Der eingegebene Text wird auf der Kassenseite angezeigt.');
?>
