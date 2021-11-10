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
 * Script : novalnet.php
 *
 */
define('MODULE_PAYMENT_NOVALNET_TRUE', 'Wahr');
define('MODULE_PAYMENT_NOVALNET_FALSE', 'Falsch');
define('MODULE_PAYMENT_NOVALNET_CONFIG_BLOCK_TITLE','<b>Novalnet Haupteinstellungen</b> (V_11.1.1)');
define('MODULE_PAYMENT_NOVALNET_SELECT','-- Ausw&auml;hlen -- ');
define('MODULE_PAYMENT_NOVALNET_NO_SCRIPT','Aktivieren Sie bitte JavaScript in Ihrem Browser, um die Zahlung fortzusetzen');
define('MODULE_PAYMENT_NOVALNET_OPTION_NONE','Keiner');
define('MODULE_PAYMENT_NOVALNET_FRAUD_OPTIONCALLBACK','PIN-by-Callback');
define('MODULE_PAYMENT_NOVALNET_FRAUD_OPTIONSMS', 'PIN-by-SMS');
define('MODULE_PAYMENT_NOVALNET_FRAUDMODULE_CALLBACK_INPUT_TITLE','Telefonnummer');
define('MODULE_PAYMENT_NOVALNET_FRAUDMODULE_SMS_INPUT_TITLE','Mobiltelefonnummer');
define('MODULE_PAYMENT_NOVALNET_FRAUDMODULE_PIN_INFO' ,'In Kürze erhalten Sie einen Telefonanruf mit der PIN zu Ihrer Transaktion, um die Zahlung abzuschließen');
define('MODULE_PAYMENT_NOVALNET_FRAUDMODULE_SMS_PIN_INFO','In Kürze erhalten Sie eine SMS mit der PIN zu Ihrer Transaktion, um die Zahlung abzuschließen.');
define('MODULE_PAYMENT_NOVALNET_PAYMENT_REFUND_CONFIRM','Sind Sie sicher, dass Sie den Betrag zurückerstatten möchten?');
define('MODULE_PAYMENT_NOVALNET_PAYMENT_ZERO_AMOUNT_BOOK_CONFIRM','Sind Sie sich sicher, dass Sie den Bestellbetrag buchen wollen?');
define('MODULE_PAYMENT_NOVALNET_PAYMENT_CAPTURE_CONFIRM','Sind Sie sicher, dass Sie die Zahlung einziehen möchten?');
define('MODULE_PAYMENT_NOVALNET_PAYMENT_CANCEL_SUBSCRIPTION','Sind Sie sicher, dass Sie das Abonnement kündigen wollen?');
define('MODULE_PAYMENT_NOVALNET_PAYMENT_VOID_CONFIRM','Sind Sie sicher, dass Sie die Zahlung stornieren wollen?');
define('MODULE_PAYMENT_NOVALNET_FRAUDMODULE_PIN_REQUEST_DESC', 'PIN zu Ihrer Transaktion');
define('MODULE_PAYMENT_NOVALNET_FRAUDMODULE_NEW_PIN', '&nbsp; PIN vergessen?');
define('MODULE_PAYMENT_NOVALNET_FRAUDMODULE_PIN_EMPTY', 'PIN eingeben');
define('MODULE_PAYMENT_NOVALNET_FRAUDMODULE_PIN_NOTVALID', 'Die von Ihnen eingegebene PIN ist falsch');
define('MODULE_PAYMENT_NOVALNET_AMOUNT_CHANGE_ERROR', 'Der Bestellbetrag hat sich geändert, setzen Sie bitte die neue Bestellung fort');
define('MODULE_PAYMENT_NOVALNET_CONFIG_BLOCK_FUNC_ERROR','Erw&auml;hnt PHP-Paket (e) in diesem Server nicht verf&uuml;gbar ist. Bitte aktivieren Sie sie.<br/>');
define('MODULE_PAYMENT_NOVALNET_FRAUDMODULE_TELEPHONE_ERROR','Geben Sie bitte Ihre Telefonnummer ein');
define('MODULE_PAYMENT_NOVALNET_FRAUDMODULE_SMS_ERROR', 'Geben Sie bitte Ihre Mobiltelefonnummer ein.');
define('MODULE_PAYMENT_NOVALNET_TRANSACTION_DETAILS', 'Novalnet-Transaktionsdetails');
define('MODULE_PAYMENT_NOVALNET_TRANSACTION_ID','Novalnet Transaktions-ID: ');
define('MODULE_PAYMENT_NOVALNET_TEST_ORDER_MESSAGE','Testbestellung');
define('MODULE_PAYMENT_NOVALNET_IBAN','IBAN');
define('MODULE_PAYMENT_GUARANTEE_FIELD','Ihr Geburtsdatum');
define('MODULE_PAYMENT_NOVALNET_ACCOUNT_HOLDER','Kontoinhaber');
define('MODULE_PAYMENT_NOVALNET_TEST_MODE_MSG','<span style="color:red;">Die Zahlung wird im Testmodus durchgef&uuml;hrt, daher wird der Betrag f&uuml;r diese Transaktion nicht eingezogen.</span>');
define('MODULE_PAYMENT_NOVALNET_VALID_DUEDATE_MESSAGE','Das Datum sollte in der Zukunft liegen.');
define('MODULE_PAYMENT_GUARANTEE_PAYMENT_DOB_ERROR_MESSAGE','Geben Sie ein gültiges Geburtsdatum ein');
define('MODULE_PAYMENT_NOVALNET_AMOUNT_ERROR_MESSAGE','Ungültiger Betrag');
define('MODULE_PAYMENT_NOVALNET_VALID_MERCHANT_CREDENTIALS_ERROR','F&uuml;llen Sie bitte alle Pflichtfelder aus.');
define('MODULE_PAYMENT_NOVALNET_PAYMENT_REFERENCE_ERROR','Wählen Sie mindestens einen Verwendungszweck aus');
define('MODULE_PAYMENT_NOVALNET_VALID_ACCOUNT_CREDENTIALS_ERROR','Ihre Kontodaten sind ungültig.');
define('MODULE_PAYMENT_NOVALNET_TRANS_CONFIRM_TITLE','Ablauf der Buchung steuern');
define('MODULE_PAYMENT_NOVALNET_SELECT_STATUS_TEXT', 'Wählen Sie bitte einen Status aus');
define('MODULE_PAYMENT_NOVALNET_TRANS_CONFIRM_SUCCESSFUL_MESSAGE','Die Buchung wurde am %s um %s Uhr bestätigt.');
define('MODULE_PAYMENT_NOVALNET_TRANS_DEACTIVATED_MESSAGE','Die Transaktion wurde am %s um %s Uhr storniert.');
define('MODULE_PAYMENT_NOVALNET_REFUND_AMT_TITLE','Geben Sie bitte den erstatteten Betrag ein');
define('MODULE_PAYMENT_NOVALNET_REFUND_TITLE','Ablauf der R&uuml;ckerstattung');
define('MODULE_PAYMENT_NOVALNET_REFUND_PARENT_TID_MSG','Die Rückerstattung wurde für die TID: %s mit dem Betrag %s durchgeführt.');
define('MODULE_PAYMENT_NOVALNET_REFUND_CHILD_TID_MSG',' Ihre neue TID für den erstatteten Betrag: %s');
define('MODULE_PAYMENT_NOVALNET_AMOUNT_EX',' (in der kleinsten Währungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR)');
define('MODULE_PAYMENT_NOVALNET_AMOUNT', 'Betrag: ');
define('MODULE_PAYMENT_NOVALNET_CONFIRM_TEXT','Best&auml;tigen');
define('MODULE_PAYMENT_NOVALNET_BACK_TEXT', 'Zur&uuml;ck');
define('MODULE_PAYMENT_NOVALNET_UPDATE_TEXT','&Auml;ndern');
define('MODULE_PAYMENT_NOVALNET_CANCEL_TEXT','Stornieren');
define('MODULE_PAYMENT_NOVALNET_SELECT_STATUS_OPTION', '--Ausw&auml;hlen--');
define('MODULE_PAYMENT_NOVALNET_SUBS_CANCEL_TITLE','Stornierung von Abonnements');
define('MODULE_PAYMENT_NOVALNET_SUBS_SELECT_REASON','W&auml;hlen Sie bitte den Grund aus');
define('MODULE_PAYMENT_NOVALNET_SUBS_CANCEL_REASON_MESSAGE','Das Abonnement wurde gekündigt wegen: ');
define('MODULE_PAYMENT_NOVALNET_SUBS_CANCEL_REASON_TITLE', 'Wählen Sie bitte den Grund für die Abonnementskündigung aus.');
define('MODULE_PAYMENT_NOVALNET_SUBS_REASON_1','Angebot zu teuer');
define('MODULE_PAYMENT_NOVALNET_SUBS_REASON_2','Betrug');
define('MODULE_PAYMENT_NOVALNET_SUBS_REASON_3','(Ehe-)Partner hat Einspruch eingelegt');
define('MODULE_PAYMENT_NOVALNET_SUBS_REASON_4', 'Finanzielle Schwierigkeiten');
define('MODULE_PAYMENT_NOVALNET_SUBS_REASON_5','Inhalt entsprach nicht meinen Vorstellungen');
define('MODULE_PAYMENT_NOVALNET_SUBS_REASON_6','Inhalte nicht ausreichend');
define('MODULE_PAYMENT_NOVALNET_SUBS_REASON_7','Nur an Probezugang interessiert');
define('MODULE_PAYMENT_NOVALNET_SUBS_REASON_8','Seite zu langsam');
define('MODULE_PAYMENT_NOVALNET_SUBS_REASON_9','Zufriedener Kunde');
define('MODULE_PAYMENT_NOVALNET_SUBS_REASON_10','Zugangsprobleme');
define('MODULE_PAYMENT_NOVALNET_SUBS_REASON_11','Sonstige');
define('MODULE_PAYMENT_NOVALNET_BOOK_TITLE','Transaktion durchf&uuml;hren');
define('MODULE_PAYMENT_NOVALNET_BOOK_AMT_TITLE','Buchungsbetrag der Transaktion');
define('MODULE_PAYMENT_NOVALNET_TRANS_BOOKED_MESSAGE','Ihre Bestellung wurde mit einem Betrag von %s gebucht. Ihre neue TID für den gebuchten Betrag: %s');
define('MODULE_PAYMENT_NOVALNET_REFUND_PAYMENTTYPE_TITLE','W&auml;hlen Sie die Option R&uuml;ckerstattung aus.');
define('MODULE_PAYMENT_NOVALNET_PAYMENTTYPE_NONE', 'Keiner');
define('MODULE_PAYMENT_NOVALNET_SEPA_TEXT','Lastschrift SEPA');
define('MODULE_PAYMENT_NOVALNET_BIC', 'BIC');
define('MODULE_PAYMENT_NOVALNET_INV_PRE_ACCOUNT_HOLDER','Kontoinhaber: NOVALNET AG');
define('MODULE_PAYMENT_NOVALNET_DUE_DATE','Fälligkeitsdatum: ');
define('MODULE_PAYMENT_NOVALNET_MAP_PAGE_HEADER','Loggen Sie sich hier mit Ihren Novalnet H&auml;ndler-Zugangsdaten ein.Um neue Zahlungsarten zu aktivieren, kontaktieren Sie bitte <a href="mailto:support@novalnet.de">support@novalnet.de</a>');
define('MODULE_PAYMENT_NOVALNET_TRANSACTION_ERROR','Die Zahlung war nicht erfolgreich. Ein Fehler trat auf.');
define('MODULE_PAYMENT_NOVALNET_REFUND_REFERENCE_TEXT', 'Referenz f&uuml;r die R&uuml;ckerstattung');
define('MODULE_PAYMENT_NOVALNET_TRANSACTION_REDIRECT_ERROR', 'Während der Umleitung wurden einige Daten geändert. Die Überprüfung des Hashes schlug fehl.');
define('MODULE_PAYMENT_NOVALNET_BANK','Bank: ');
define('MODULE_PAYMENT_NOVALNET_INVPRE_REF','Verwendungszweck: ');
define('MODULE_PAYMENT_NOVALNET_INVPRE_REF_MULTI','%s.Verwendungszweck: ');
define('MODULE_PAYMENT_NOVALNET_PAYMENT_SINGLE_TEXT','Bitte verwenden Sie nun der unten angegebenen Verwendungszweck für die Überweisung, da nur so Ihr Geldeingang zugeordnet werden kann:');
define('MODULE_PAYMENT_NOVALNET_PAYMENT_MULTI_TEXT','Bitte verwenden Sie einen der unten angegebenen Verwendungszwecke für die Überweisung, da nur so Ihr Geldeingang zugeordnet werden kann:');
define('MODULE_PAYMENT_NOVALNET_ORDER_NUMBER',' Bestellnummer ');
define('MODULE_PAYMENT_NOVALNET_INVOICE_COMMENTS_PARAGRAPH', 'Überweisen Sie bitte den Betrag an die unten aufgeführte Bankverbindung unseres Zahlungsdienstleisters Novalnet.');
define('MODULE_PAYMENT_NOVALNET_FRAUDMODULE_TITLE', 'Betrugspr&uuml;fung aktivieren');
define('MODULE_PAYMENT_NOVALNET_FRAUDMODULE_DESC', 'Um den K&auml;ufer einer Transaktion zu authentifizieren, werden die PIN automatisch generiert und an den K&auml;ufer geschickt. Dieser Dienst wird nur f&uuml;r Kunden aus DE, AT und CH angeboten');
define('MODULE_PAYMENT_GUARANTEE_PAYMENT_AGE_ERROR_MESSAGE','Sie müssen mindestens 18 Jahre alt sein');
define('MODULE_PAYMENT_GUARANTEE_PAYMENT_DOB_EMPTY_ERROR_MESSAGE','Geben Sie bitte Ihr Geburtsdatum ein');
define('MODULE_PAYMENT_GUARANTEE_PAYMENT_INVAILD_DOB_ERROR_MESSAGE','Ungültiges Datumsformat');
define('MODULE_PAYMENT_NOVALNET_TRANS_DUE_DATE_TITLE','F&auml;lligkeitsdatum der Transaktion');
define('MODULE_PAYMENT_NOVALNET_TRANS_AMOUNT_TITLE', 'Transaktionsbetrag');
define('MODULE_PAYMENT_NOVALNET_AMOUNT_CHANGE_DUE_DATE_TITLE' ,'Betrag / F&auml;lligkeitsdatum &auml;ndern');
define('MODULE_PAYMENT_NOVALNET_AMOUNT_CHANGE_TITLE','Betrag &auml;ndern');
define('MODULE_PAYMENT_NOVALNET_ORDER_AMT_UPDATE_TEXT','Sind Sie sich sicher, dass Sie den Bestellbetrag ändern wollen?');
define('MODULE_PAYMENT_NOVALNET_ORDER_AMT_DATE_UPDATE_TEXT','Sind Sie sich sicher, dass Sie den Betrag / das Fälligkeitsdatum der Bestellung ändern wollen?');
define('MODULE_PAYMENT_NOVALNET_VAILD_SUBSCRIPTION_PERIOD_ERROR','Geben Sie bitte eine gültige Abonnementsperiode ein');
define('MODULE_PAYMENT_NOVALNET_TRANS_UPDATED_MESSAGE','Der Betrag der Transaktion ( %s ) wurde am %s um %s Uhr erfolgreich geändert');
define('MODULE_PAYMENT_INVOICE_DUE_DATE_INVAILD','Ungültiges Fälligkeitsdatum');
define('MODULE_PAYMENT_INVOICE_CREDIT_COMMENTS','Novalnet-Callback-Skript erfolgreich ausgeführt für die TID: %s mit dem Betrag %s am %s um %s Uhr. Bitte suchen Sie nach der bezahlten Transaktion in unserer Novalnet-Händleradministration mit der TID: %s');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_CHARGEBACK_COMMENTS' ,'Novalnet-Callback-Nachricht erhalten: Chargeback erfolgreich importiert für die TID: %s Betrag: %s am %s um %s. TID der Folgebuchung: %s');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_BOOKBACK_COMMENTS' ,'Novalnet-Callback-Meldung erhalten: Rückerstattung / Bookback erfolgreich ausgeführt für die TID: %s Betrag: %s on %s & %s. TID der Folgebuchung: %s');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_UPDATE_COMMENTS' ,'Novalnet-Callback-Skript erfolgreich ausgeführt für die TID: %s mit dem Betrag %s am %s um %s Uhr.');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_CHARGING_DATE_COMMENTS' ,'Nächstes Belastungsdatum: %s ');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_REFERENCE_TID_COMMENTS',' Verwendungszweck TID: %s');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_SUBS_STOP_COMMENTS','Nachricht vom Novalnet-Callback-Skript erhalten: Das Abonnement wurde für die TID: %s am %s um %s Uhr eingestellt.');
define('MODULE_PAYMENT_NOVALNET_CALLBACK_RECURRING_COMMENTS','Novalnet-Callback-Skript erfolgreich ausgeführt für die TID: %s mit dem Betrag %s am , um %s %s Uhr.Bitte suchen Sie nach der bezahlten Transaktion in unserer Novalnet-Händleradministration mit der TID: %s.');
define('MODULE_PAYMENT_NOVALNET_PRZELEWY_CANCEL_ORDER_MESSAGE' ,'Die Transaktion wurde storniert. Grund: %s');
define('MODULE_PAYMENT_NOVALNET_TEST_TRANSACTION_EMAIL_HEADING' ,'Benachrichtigung zu Novalnet-Testbestellung-%s');
define('MODULE_PAYMENT_NOVALNET_TEST_TRANSACTION_EMAIL_CONTENT' ,'Sehr geehrte Kundin, <br/> <p> &emsp;wir möchten Sie darüber informieren, dass eine Testbestellung %s kürzlich in Ihrem Shop durchgeführt wurde. Stellen Sie bitte sicher, dass für Ihr Projekt im Novalnet-Administrationsportal der Live-Modus gesetzt wurde und Zahlungen über Novalnet in Ihrem Shopsystem aktiviert sind. Ignorieren Sie bitte diese E-Mail, falls die Bestellung von Ihnen zu Testzwecken durchgeführt wurde. </p> <br/>Mit freundlichen Grüßen <br/> Novalnet AG');
