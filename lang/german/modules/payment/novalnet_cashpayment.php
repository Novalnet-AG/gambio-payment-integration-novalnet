<?php

include_once(dirname(__FILE__).'/novalnet.php');
define('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_TEXT_TITLE', 'Novalnet Barzahlen/viacash');
define('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_TEXT_DESCRIPTION', 'Die Zahlung wird im Testmodus durchgeführt, daher wird der Betrag für diese Transaktion nicht eingezogen.');
define('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_STATUS_TITLE', 'Zahlungsart aktivieren');
define('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_STATUS_DESC', 'Möchten Sie Barzahlung akzeptieren?');
define('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_ALLOWED_TITLE', 'Erlaubte Zone(n)');
define('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_ALLOWED_DESC', 'Diese Zahlungsmethode ist nur für die angegebene(n) Zone(n) zulässig. Geben Sie die Zone(n) in folgendem Format ein, z.B: DE, AT, CH. Wenn das Feld leer ist, werden alle Zonen zugelassen.');
