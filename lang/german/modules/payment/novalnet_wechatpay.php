<?php

require_once dirname(__FILE__).'/novalnet.php';
define('MODULE_PAYMENT_NOVALNET_WECHATPAY_TEXT_TITLE', 'Novalnet WeChat Pay');
define('MODULE_PAYMENT_NOVALNET_WECHATPAY_TEXT_DESCRIPTION', '');
define('MODULE_PAYMENT_NOVALNET_WECHATPAY_STATUS_TITLE', 'Zahlungsart aktivieren');
define('MODULE_PAYMENT_NOVALNET_WECHATPAY_STATUS_DESC', 'Möchten Sie wechat pay-Zahlungen akzeptieren?');
define('MODULE_PAYMENT_NOVALNET_WECHATPAY_TEST_MODE_TITLE', 'Testmodus aktivieren');
define('MODULE_PAYMENT_NOVALNET_WECHATPAY_TEST_MODE_DESC', 'Die Zahlung wird im Testmodus verarbeitet, daher wird der Betrag für diese Transaktion nicht berechnet.');
define('MODULE_PAYMENT_NOVALNET_WECHATPAY_SORT_ORDER_TITLE', 'Sortierreihenfolge anzeigen');
define('MODULE_PAYMENT_NOVALNET_WECHATPAY_SORT_ORDER_DESC', 'Sortierreihenfolge anzeigen; der niedrigste Wert wird zuerst angezeigt.');
define('MODULE_PAYMENT_NOVALNET_WECHATPAY_ORDER_STATUS_TITLE', 'Status für erfolgreichen Auftragsabschluss');
define('MODULE_PAYMENT_NOVALNET_WECHATPAY_ORDER_STATUS_DESC', 'Status, der für erfolgreiche Bestellungen zu verwenden ist');
define('MODULE_PAYMENT_NOVALNET_WECHATPAY_PAYMENT_ZONE_TITLE', 'Zahlungsbereich');
define('MODULE_PAYMENT_NOVALNET_WECHATPAY_PAYMENT_ZONE_DESC', 'Wenn eine Zone ausgewählt wird, wird diese Zahlungsmethode nur für diese Zone aktiviert.');
define('MODULE_PAYMENT_NOVALNET_WECHATPAY_VISIBILITY_BY_AMOUNT_TITLE', 'Mindestbestellbetrag');
define('MODULE_PAYMENT_NOVALNET_WECHATPAY_VISIBILITY_BY_AMOUNT_DESC', 'Mindestbestellsumme zur Anzeige der ausgewählten Zahlungsart(en) im Checkout (in der kleinsten Währungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR)');
define('MODULE_PAYMENT_NOVALNET_WECHATPAY_ENDCUSTOMER_INFO_TITLE', 'Benachrichtigung des Käufers.');
define('MODULE_PAYMENT_NOVALNET_WECHATPAY_ENDCUSTOMER_INFO_DESC', 'Der eingegebene Text wird auf der Kassenseite angezeigt.');
define('MODULE_PAYMENT_NOVALNET_WECHATPAY_TEXT_INFO', 'Sie werden zu Wechatpay weitergeleitet. Um eine erfolgreiche Zahlung zu gewährleisten, darf die Seite nicht geschlossen oder neu geladen werden, bis die Bezahlung abgeschlossen ist.');
