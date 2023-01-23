<?php
require_once (dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_GLOBAL_CONFIG_TEXT_TITLE', 'Novalnet Haupteinstellungen');
define('MODULE_PAYMENT_NOVALNET_GLOBAL_CONFIG_TEXT_DESC','<span style="font-weight: bold; color:#878787;"> Bevor Sie beginnen, lesen Sie bitte die Installationsanleitung und melden Sie sich mit Ihrem Händlerkonto im <a href="https://admin.novalnet.de" target="_blank" style="text-decoration: underline; font-weight: bold; color:#0080c9;">Novalnet Admin-Portal</a> an. Um ein Händlerkonto zu erhalten, senden Sie bitte eine E-Mail an <a style="font-weight: bold; color:#0080c9" href="mailto:sales@novalnet.de">sales@novalnet.de</a> oder rufen Sie uns unter +49 89 923068320 an</span><br><br>');
define('MODULE_PAYMENT_NOVALNET_SIGNATURE_TITLE', 'Aktivierungsschlüssel des Produkts');
define('MODULE_PAYMENT_NOVALNET_SIGNATURE_DESC', 'Ihren Produktaktivierungsschlüssel finden Sie im <a href="https://admin.novalnet.de" target="_blank" style="text-decoration: underline; font-weight: bold; color:#0080c9;">Novalnet Admin-Portal</a> Projekt > Wählen Sie Ihr Projekt > API-Anmeldeinformationen > API-Signatur (Produktaktivierungsschlüssel)');
define('MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY_TITLE', 'Zahlungs-Zugriffsschlüssel');
define('MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY_DESC', 'Ihren Paymentzugriffsschlüssel finden Sie im <a href="https://admin.novalnet.de" target="_blank" style="text-decoration: underline; font-weight: bold; color:#0080c9;">Novalnet Admin-Portal</a> Projekt > Wählen Sie Ihr Projekt > API-Anmeldeinformationen > Paymentzugriffsschlüssel');
define('MODULE_PAYMENT_NOVALNET_TARIFF_ID_TITLE', 'Tarif-ID auswählen');
define('MODULE_PAYMENT_NOVALNET_TARIFF_ID_DESC', 'Wählen Sie eine Tarif-ID, die dem bevorzugten Tarifplan entspricht, den Sie im Novalnet Admin-Portal für dieses Projekt erstellt haben');
define('MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_COMPLETE_TITLE', '<h2>Verwaltung des Bestellstatus für ausgesetzte Zahlungen</h2>On-hold-Bestellstatus');
define('MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_COMPLETE_DESC', 'Wählen Sie, welcher Status für On-hold-Bestellungen verwendet wird, solange diese nicht bestätigt oder storniert worden sind');
define('MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED_TITLE', 'Status für stornierte Bestellungen');
define('MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED_DESC', 'Wählen Sie, welcher Status für stornierte oder voll erstattete Bestellungen verwendet wird');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_TEST_MODE_TITLE', '<h2>Benachrichtigungs- / Webhook-URL festlegen</h2><br> Manuelles Testen der Benachrichtigungs / Webhook-URL erlauben');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_TEST_MODE_DESC', 'Aktivieren Sie diese Option, um die Novalnet-Benachrichtigungs-/Webhook-URL manuell zu testen. Deaktivieren Sie die Option, bevor Sie Ihren Shop liveschalten, um unautorisierte Zugriffe von Dritten zu blockieren');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_URL_TITLE', 'Benachrichtigung / Webhook-URL im Novalnet-Verwaltungsportal');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_URL_DESC', 'Sie müssen die folgende Webhook-URL im <a href="https://admin.novalnet.de" target="_blank" style="text-decoration: underline; font-weight: bold; color:#0080c9;">Novalnet Admin-Portal</a> hinzufügen. Dadurch können Sie Benachrichtigungen über den Transaktionsstatus erhalten');
define('MODULE_PAYMENT_NOVALNET_CLIENT_KEY_TITLE', 'Client-Schlüssel');
define('MODULE_PAYMENT_NOVALNET_PROJECT_ID_TITLE', 'Projekt-ID');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_TO_TITLE', '<script src="../ext/novalnet/js/novalnet_config.js" type="text/javascript"></script> <input type="button" id="webhook_url_button" style="font-weight: bold; color:#0080c9;" value="Konfigurieren"> <br> E-Mails senden an');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_TO_DESC', 'E-Mail-Benachrichtigungen werden an diese E-Mail-Adresse gesendet');
?>
