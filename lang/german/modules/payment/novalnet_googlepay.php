<?php
require_once (dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_TEXT_TITLE', 'Novalnet Googlepay');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_STATUS_TITLE', 'Aktivieren der Zahlungsmethode');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BLOCK_TITLE', 'Googlepay-Konfiguration');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_STATUS_DESC', 'Möchten Sie Googlepay-Zahlungen akzeptieren?');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_TEST_MODE_TITLE', 'Testmodus aktivieren');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_TEST_MODE_DESC', 'Die Zahlung wird im Testmodus verarbeitet, daher wird der Betrag für diese Transaktion nicht berechnet.');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ORDER_STATUS_TITLE','Status für erfolgreichen Auftragsabschluss');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ORDER_STATUS_DESC','Status, der für erfolgreiche Bestellungen zu verwenden ist');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_SORT_ORDER_TITLE','Sortierreihenfolge anzeigen');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_SORT_ORDER_DESC','Sortierreihenfolge anzeigen; der niedrigste Wert wird zuerst angezeigt.');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_AUTHENTICATE_TITLE','Aktion für vom Besteller autorisierte Zahlungen');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_AUTHENTICATE_DESC','Wählen Sie, ob die Zahlung sofort belastet werden soll oder nicht. Authorize prüft die Zahlungsdetails und reserviert Mittel, um sie später zu erfassen, damit der Händler Zeit hat, über die Bestellung zu entscheiden.</br></br> Zahlung autorisieren');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_MANUAL_CHECK_LIMIT_TITLE','Mindesttransaktionsbetrag für die Autorisierung');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_MANUAL_CHECK_LIMIT_DESC','Übersteigt der Bestellbetrag das genannte Limit, wird die Transaktion, bis zu ihrer Bestätigung durch Sie, auf on hold gesetzt. Sie können das Feld leer lassen, wenn Sie möchten, dass alle Transaktionen als on hold behandelt werden.  (in der kleinsten Währungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR).');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_TYPE_TITLE','<h3>Button-Design</h3> <h5>Stil für Google Pay schaltfläche</h5> Button-Typ');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_THEME_TITLE','Button-Farbe');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_HEIGHT_TITLE','Button-Höhe');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_HEIGHT_DESC','zwischen 30 und 64 Pixel');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_DISPLAY_TITLE','Google-Pay-Button anzeigen auf');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_DISPLAY_DESC','Auf den ausgewählten Seiten wird die Google-Pay-Schaltfläche zur sofortigen Bezahlung als Express-Checkout-Schaltfläche angezeigt <br><br>');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ALLOWED_TITLE','Erlaubte Zone(n)');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ALLOWED_DESC','Diese Zahlungsmethode ist nur für die angegebene(n) Zone(n) zulässig. Geben Sie die Zone(n) in folgendem Format ein, z.B: DE, AT, CH. Wenn das Feld leer ist, werden alle Zonen zugelassen.');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_PAYMENT_ZONE_TITLE','Zahlungsbereich');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_PAYMENT_ZONE_DESC','Wenn eine Zone ausgewählt ist, aktivieren Sie diese Zahlungsmethode nur für diese Zone.');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_TEXT_INFO','Der Betrag wird nach erfolgreicher Authentifizierung von Ihrer Karte abgebucht');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_PUBLIC_TITLE', xtc_image(DIR_WS_ICONS.'novalnet/novalnet_googlepay.png', "Googlepay "));
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_VISIBILITY_BY_AMOUNT_TITLE','Mindestbestellbetrag');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_VISIBILITY_BY_AMOUNT_DESC','Mindestbestellsumme zur Anzeige der ausgewählten Zahlungsart(en) im Checkout (in der kleinsten Währungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR)');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ENDCUSTOMER_INFO_TITLE','Benachrichtigung des Käufers');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ENDCUSTOMER_INFO_DESC','Der eingegebene Text wird auf der Kassenseite angezeigt');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUSINESS_NAME_TITLE','Name des Geschäfts');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUSINESS_NAME_DESC','Der Name des Geschäfts wird in den Zahlungsbeleg von Google Pay eingefügt und der Text wird als PAY Name des Geschäfts angezeigt, so dass der Endkunde weiß, an wen er zahlt.');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ENFORCE_3D_SECCURE_TITLE','3D-Secure-Zahlungen außerhalb der EU erzwingen');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ENFORCE_3D_SECCURE_DESC','Wenn Sie diese Option aktivieren, werden alle Zahlungen mit Karten, die außerhalb der EU ausgegeben wurden, mit der starken Kundenauthentifizierung (Strong Customer Authentication, SCA) von 3D-Secure 2.0 authentifiziert.');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_MERCHANT_ID_TITLE','Google-Händler-ID');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_MERCHANT_ID_DESC','Beachten Sie bitte, dass die Händler-ID von Google für die Ausführung dieser Zahlungsart in der Live-Umgebung benötigt wird. Die Händler-ID wird nach der Registrierung bei <a href=https://pay.google.com/business/console/ target=_blank>Google Pay und der Wallet-Konsole</a> vergeben. Siehe auch: <a href=https://developers.google.com/pay/api/web/guides/test-and-deploy/request-prod-access target=_blank>Anfrage für Produktiv-Zugang stellen</a>, falls Sie mehr Informationen zum Genehmigungsverfahren benötigen und dazu, wie Sie eine Google Händler-ID erhalten. Die Registrierung beinhaltet auch, dass Sie Ihre Anbindung mit ausreichenden Screenshots einreichen, deshalb sammeln Sie diese Informationen, indem Sie die Zahlungsmethode im Testmodus aktivieren. Um die Validierung dieses Feldes zu überspringen, während Sie die Konfiguration speichern, verwenden Sie diese Test-ID, BCR2DN4XXXTN7FSI , zum Testen und Einreichen Ihrer Anbindung bei Google.');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_DISPLAY_CARTPAGE','Warenkorb');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_DISPLAY_PRODUCTPAGE','Produktseite');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_DISPLAY_CHECKOUTPAGE','Checkout-Seite');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_PLAIN','Einfach');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUY','Kaufen');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_DONATE','Spenden');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BOOK','Buchen');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_CHECKOUT','Checkout');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ORDER','Bestellen');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_SUBSCRIBE','Abonnieren');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_PAY','Bezahlen');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ESTIMATEDTOTAL_LABEL', 'Geschätzter Gesamtbetrag (Keine Angebote angewendet)');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_TOTAL_LABEL', 'Insgesamt');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_INCL_TAX_LABEL', 'Inkl Steuer');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_EXCL_TAX_LABEL', 'Exkl Steuer');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_DISCOUNT_AND_GIFT_VOUCHER_LABEL', 'Rabatt');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_SHIPPING_LABEL', 'Versand');
define('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_PAGES_TEXT', 'Buttton-Anzeigeseiten');
?>
