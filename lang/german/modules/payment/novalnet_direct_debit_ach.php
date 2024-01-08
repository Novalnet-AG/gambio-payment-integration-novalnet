<?php
include_once (dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_TEXT_TITLE', 'Novalnet Lastschrift ACH');
define('MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ALLOWED_TITLE','Erlaubte Zone(n)');
define('MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ALLOWED_DESC','Diese Zahlungsmethode ist nur für die angegebene(n) Zone(n) zulässig. Geben Sie die Zone(n) in folgendem Format ein, z.B: DE, AT, CH. Wenn das Feld leer ist, werden alle Zonen zugelassen.');
define('MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_STATUS_TITLE','Aktivieren der Zahlungsmethode');
define('MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_STATUS_DESC','Möchten Sie ACH-Zahlungen per Lastschrift akzeptieren?');
define('MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_TEST_MODE_TITLE','Testmodus einschalten');
define('MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_TEST_MODE_DESC','Die Zahlung wird im Testmodus verarbeitet, daher wird der Betrag für diese Transaktion nicht berechnet.');
define('MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_SORT_ORDER_TITLE','Sortierreihenfolge anzeigen');
define('MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_SORT_ORDER_DESC','Sortierreihenfolge anzeigen; der niedrigste Wert wird zuerst angezeigt.');
define('MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ORDER_STATUS_TITLE','Status der abgeschlossenen Bestellung');
define('MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ORDER_STATUS_DESC','Status, der für erfolgreiche Bestellungen zu verwenden ist');
define('MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_PAYMENT_ZONE_TITLE','Zahlungsbereich');
define('MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_PAYMENT_ZONE_DESC','Wenn eine Zone ausgewählt wird, wird diese Zahlungsmethode nur für diese Zone aktiviert.');
define('MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_TOKENIZATION_TITLE','Einkaufen mit einem Klick');
define('MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_TOKENIZATION_DESC','Die während des Bezahlvorgangs gespeicherten Zahlungsdaten können für zukünftige Zahlungen verwendet werden.');
define('MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_VISIBILITY_BY_AMOUNT_TITLE','Mindestbestellwert.');
define('MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_VISIBILITY_BY_AMOUNT_DESC','Mindestbestellwert, bei dem die gewählte(n) Zahlungsmethode(n) während des Bezahlvorgangs angezeigt werden soll(en) (in der Mindestwährungseinheit. z.B. 100 eingeben, was 1,00 entspricht)');
define('MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ENDCUSTOMER_INFO_TITLE','Benachrichtigung des Käufers.');
define('MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ENDCUSTOMER_INFO_DESC','Der eingegebene Text wird auf der Kassenseite angezeigt.');
define('ACCOUNT_NO_ACH','Kontonummer');
define('ROUTING_NO_ACH','Routing-Nummer (ABA)');
?>
