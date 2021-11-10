<?php

/**
 * Novalnet payment module related file
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
require('includes/application_top.php');
require(DIR_WS_INCLUDES . 'header.php');
include_once(DIR_FS_LANGUAGES . $_SESSION['language'] . '/modules/payment/novalnet.php');
?>
 <div style="border: 1px solid #0080c9; background-color: #2196F3; text-align:center; padding: 13px; font-family: Arial, Verdana; font-size: 20px; margin:1px 0px 0px 0;"><?php
echo MODULE_PAYMENT_NOVALNET_MAP_PAGE_HEADER;
?></div>
<!-- To  redirect to Novalnet Admin Portal -->
       <iframe src="https://admin.novalnet.de" height="500px" width="100%" frameborder="0">Ihr Browser unterst&uuml;tzt keine Iframes.</iframe>

<?php
require(DIR_WS_INCLUDES . 'footer.php');
require(DIR_WS_INCLUDES . 'application_bottom.php');
?>
