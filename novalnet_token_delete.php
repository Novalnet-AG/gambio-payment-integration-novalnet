<?php
/**
 * Novalnet payment module
 *
 * This script is used for delting the token
 *
 * @author     Novalnet AG
 * @copyright  Copyright (c) Novalnet
 * @license    https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link       https://www.novalnet.de
 *
 * Script: novalnet_token_delete.php
 */
 
include ('includes/application_top.php');
$request = $_REQUEST;
if ($request['action'] == 'delete_token') {
	$data = [];
	$payment_details = xtc_db_fetch_array(xtc_db_query("SELECT payment_details, id, amount, order_no FROM novalnet_transaction_detail WHERE (status = 'CONFIRMED' or status = 'ON_HOLD' or status = 'PENDING') and id='" . $request['id'] . "'"));
	$payment_data = json_decode($payment_details['payment_details'], true);
	$cardDetails = "";
	if ($payment_details['amount'] <= 0 && !empty($payment_data['zero_amount_booking']) && $payment_data['zero_amount_booking'] == 1) {
		$cardDetails = array(
			'token' => $payment_data['token'],
			'zero_amount_booking' => $payment_data['zero_amount_booking']
		);
		$cardDetails = json_encode($cardDetails);
	}
	xtc_db_query("UPDATE novalnet_transaction_detail SET payment_details = '$cardDetails' WHERE (status = 'CONFIRMED' or status = 'ON_HOLD' or status = 'PENDING') and id='" . $request['id'] . "'");
	unset($_SESSION['saved_card_details']);
	$json_data = json_encode($data);
	echo $json_data;
	exit;
}
?>
