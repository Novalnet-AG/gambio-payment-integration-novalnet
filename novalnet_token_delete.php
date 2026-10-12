<?php
/**
 * Novalnet payment module
 *
 * This script is used for delting the token
 *
 * @author    Novalnet AG
 * @copyright Copyright (c) Novalnet
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link      https://www.novalnet.de
 *
 * Script: novalnet_token_delete.php
 */

require 'includes/application_top.php';
$request = $_REQUEST;
if ($request['action'] == 'delete_token') {
    xtc_db_query("UPDATE novalnet_transaction_detail SET payment_details = NULL WHERE (status = 'CONFIRMED' or status = 'ON_HOLD' or status = 'PENDING') and id='" . $request['id'] . "'");
    $json_data = json_encode(['success' => true]);
    echo $json_data;
    exit;
}
