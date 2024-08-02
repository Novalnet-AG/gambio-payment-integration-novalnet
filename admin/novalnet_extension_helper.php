<?php
/**
 * Novalnet payment module
 *
 * This script is used for process the extension actions
 *
 * @author     Novalnet AG
 * @copyright  Copyright (c) Novalnet
 * @license    https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link       https://www.novalnet.de
 *
 * Script : novalnet_extension_helper.php
 *
 */
require ('includes/application_top.php');
include_once(DIR_FS_CATALOG."ext/novalnet/NovalnetHelper.class.php");
include_once(DIR_FS_INC . 'xtc_format_price_order.inc.php');
include_once(DIR_FS_CATALOG . 'admin/includes/classes/order.php');
$novalnet_helper = new NovalnetHelper();
$request        = $_REQUEST;
$txn_details    = $novalnet_helper->getNovalnetTransDetails($request['oID']);
if (!empty($txn_details['payment_details'])) {
	$payment_details = json_decode($txn_details['payment_details'], true);
}
$order = new order($request['oID']);
$customerId = $order->customer['ID'];
if (empty($order->customer['ID'])) {
	$customerId = $order->customer['csID'];
}
//  To process on-hold transaction
if (isset($request['nn_manage_confirm']) && !empty($request['trans_status'])) {
	$order_status    = '';
	$data = [];
	$data['transaction'] = [
		'tid' => $txn_details['tid']
	];
	$data['custom'] = [
		'lang'			=> (isset($_SESSION['language_code'])) ? strtoupper($_SESSION['language_code']) : 'DE',
		'shop_invoked' 	=> 1
	];
	$endpoint = (!empty($request['trans_status']) && $request['trans_status'] == 'CONFIRM') ? 'transaction_capture' : 'transaction_cancel';
	$response = $novalnet_helper->sendRequest($data, $novalnet_helper->get_action_endpoint($endpoint));
	$update_data = [
		'status' => isset($response['transaction']['status']) ? $response['transaction']['status'] : '',
	];
	if ($response['result']['status'] == 'SUCCESS') {
		$order_status = $novalnet_helper->getOrderStatus ($update_data['status'], $order->info['payment_method']);
		if (in_array($order->info['payment_class'], array('novalnet_googlepay', 'novalnet_applepay'))) {
			$order_status = $novalnet_helper->getOrderStatus ($update_data['status'], $order->info['payment_class']);
		}
		$order_status_value = ($request['trans_status'] == 'CONFIRM') ? $order_status : MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED;
		
		$message = PHP_EOL.sprintf(MODULE_PAYMENT_NOVALNET_TRANS_DEACTIVATED_MESSAGE, date('d-m-Y', strtotime(date('d.m.Y'))), date('H:i:s')) . PHP_EOL;
		if ($request['trans_status'] == 'CONFIRM') {
			$message = PHP_EOL . PHP_EOL.sprintf(MODULE_PAYMENT_NOVALNET_TRANS_CONFIRM_SUCCESSFUL_MESSAGE, date('d-m-Y', strtotime(date('d.m.Y'))), date('H:i:s')) . PHP_EOL;
			$message .= $novalnet_helper->getTransactionDetails($response);
			if (in_array($response['transaction']['payment_type'], array( 'INSTALMENT_INVOICE', 'GUARANTEED_INVOICE', 'INVOICE', 'PREPAYMENT'))) {
				if (empty($response['transaction']['bank_details'])) {
					$response['transaction']['bank_details'] = $payment_details;
				}
				$message .= $novalnet_helper->getBankDetails($response, $request['oID']);
			}
			if (in_array($response['transaction']['payment_type'], array( 'INSTALMENT_INVOICE', 'INSTALMENT_DIRECT_DEBIT_SEPA'))) {
				$message .= $novalnet_helper->formInstalmentPaymentReference($response);
				if (in_array($response['transaction']['status'],array('CONFIRMED', 'PENDING'))) {
					$total_amount = ($txn_details['amount'] < $response['transaction']['amount']) ? $response['transaction']['amount'] : $txn_details['amount'];
					$instalment_details = $novalnet_helper->storeInstalmentdetails($response, $total_amount);
					$update_data['instalment_cycle_details'] = $instalment_details;
				}
			}
			$novalnet_helper->sendOrderUpdateMail(array(
					'comments' 	=> '<br>' . $message,
					'order_no' 	=> $request['oID'],
					'order' 	=>  $order,
			), array(
				'customer_id' 	=> $customerId
			)); 

		}
	}
} elseif ((!empty($request['nn_refund_confirm']) && ($request['refund_trans_amount'] != '' ) && $txn_details['status'] != 'Canceled')) { // To process refund process
	$refunded_amount = 0;
	$data['transaction'] = [
		'tid'    => (!empty($request['refund_tid'])) ? $request['refund_tid'] : $txn_details['tid'],
		'amount' => $request['refund_trans_amount'],
	];
	$data['custom'] = [
		'lang' 			=> (isset($_SESSION['language_code'])) ? strtoupper($_SESSION['language_code']) : 'DE',
		'shop_invoked' 	=> 1
	];
	if (!empty($request['refund_reason'])) {
		$data['transaction']['reason'] = $request['refund_reason'];
	}
	$response = $novalnet_helper->sendRequest($data, $novalnet_helper->get_action_endpoint('transaction_refund'));
	if ($response['result']['status'] == 'SUCCESS') {
		$refunded_amount = $response['transaction']['refund']['amount'];
		if (in_array($response['transaction']['payment_type'], array('INSTALMENT_INVOICE', 'INSTALMENT_DIRECT_DEBIT_SEPA'))) {
			$instalment_details = (!empty($txn_details['instalment_cycle_details'])) ? json_decode($txn_details['instalment_cycle_details'], true) : unserialize($txn_details['payment_details']);
			if(!empty($instalment_details)) {
				$cycle = $request['instalment_cycle'];
				$instalment_amount = (strpos((string)$instalment_details[$cycle]['instalment_cycle_amount'], '.')) ? $instalment_details[$cycle]['instalment_cycle_amount']*100 : $instalment_details[$cycle]['instalment_cycle_amount'];
				$instalment_amount = $instalment_amount - $refunded_amount;
				$instalment_details[$cycle]['instalment_cycle_amount'] = $instalment_amount;
				if($instalment_details[$cycle]['instalment_cycle_amount'] <= 0) {
					$instalment_details[$cycle]['status'] = 'Refunded';
				}
				$update_data = [
					'instalment_cycle_details' => json_encode($instalment_details),
				];
			}
		}
		$update_data['refund_amount'] = (!empty($txn_details['refund_amount'])) ? ($refunded_amount + $txn_details['refund_amount']) : $refunded_amount;
		$message = PHP_EOL . sprintf((MODULE_PAYMENT_NOVALNET_REFUND_PARENT_TID_MSG), $txn_details['tid'], xtc_format_price_order(($refunded_amount/100), 1, $txn_details['currency']));
		// Check for refund TID
		if (!empty($response['transaction']['refund']['tid'])) {
			$message .= PHP_EOL. sprintf((MODULE_PAYMENT_NOVALNET_REFUND_CHILD_TID_MSG), $response['transaction']['refund']['tid']);
		}
		$order_status_value = ($update_data['refund_amount'] >= $txn_details['amount']) ? MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED : $order->info['orders_status'];
		if (in_array($order->info['payment_class'], array('novalnet_googlepay','novalnet_applepay'))) {
			$order_status_value = ($update_data['refund_amount'] >= $txn_details['amount']) ? MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED : $order->info['orders_status'];
		}
	}
} else if (!empty($request['nn_book_confirm']) && !empty($request['book_amount'])) {	// To process zero amount booking transaction
	$merchant_data    = $novalnet_helper->getMerchantData();
	$customer_data    = $novalnet_helper->getCustomerData();
	$transaction_data = $novalnet_helper->getTransactionData();
	$custom_data['custom'] = [
		'lang' 			=> (isset($_SESSION['language_code'])) ? strtoupper($_SESSION['language_code']) : 'DE',
		'shop_invoked' 	=> 1
	];
	$transaction_data['transaction']['payment_type'] = $txn_details['payment_type'];
	$data = array_merge($merchant_data, $customer_data, $transaction_data, $custom_data);
	$data['transaction']['amount'] = $request['book_amount'];
	$data['transaction']['payment_data']['token'] = $payment_details['token'];
	$response = $novalnet_helper->sendRequest($data, $novalnet_helper->get_action_endpoint('payment'));
	if ($response['result']['status'] == 'SUCCESS' ) {
		$order_status = xtc_db_fetch_array(xtc_db_query("SELECT orders_status from " . TABLE_ORDERS . " where orders_id = " . xtc_db_input($request['oID'])));
		$order_status_value = $order_status['orders_status'];
		$message =  PHP_EOL . PHP_EOL . sprintf(MODULE_PAYMENT_NOVALNET_TRANS_BOOKED_MESSAGE, xtc_format_price_order(($request['book_amount'] / 100), 1, $response['transaction']['currency']), $response['transaction']['tid']) . PHP_EOL;
		$update_data = [
			'amount' => $response['transaction']['amount'],
			'tid' 	 => $response['transaction']['tid'],
		];
	}
} else if (!empty($request['nn_instacancel_allcycles']) || !empty($request['nn_instacancel_remaincycles'])) { // To process instalment cancel
	$data['instalment'] = [
		'tid' 		  => $txn_details['tid'],
		'cancel_type' => isset($request['nn_instacancel_allcycles']) ? 'CANCEL_ALL_CYCLES' : 'CANCEL_REMAINING_CYCLES'
    ];
	$data['custom'] = [
		'lang' => (isset($_SESSION['language_code'])) ? strtoupper($_SESSION['language_code']) : 'DE',
		'shop_invoked' 	=> 1
	];
	$response = $novalnet_helper->sendRequest($data, $novalnet_helper->get_action_endpoint('instalment_cancel'));
	$order_status = xtc_db_fetch_array(xtc_db_query("SELECT orders_status from " . TABLE_ORDERS . " where orders_id = " . xtc_db_input($request['oID'])));
	if ($response['result']['status'] == 'SUCCESS') {
		$instalment_details = !empty($txn_details['instalment_cycle_details']) ? json_decode($txn_details['instalment_cycle_details'], true) : [];
		foreach ($instalment_details as $cycle => $cycle_details) {
			$update_data['status'] = 'CONFIRMED';
			$message = PHP_EOL. sprintf((MODULE_PAYMENT_NOVALNET_INSTALMENT_CANCEL_REMAINING_CYCLES_TEXT), $txn_details['tid'], date('Y-m-d H:i:s'));
			if ($cycle_details['status'] == 'Pending') { // Cancel the instalment cycles if its pending
				$instalment_details[$cycle]['status'] = 'Canceled';
			}
			if (!empty($request['nn_instacancel_allcycles'])) { // If instalment all cycle cancel
				$update_data['status'] = 'DEACTIVATED';
				$message = PHP_EOL. sprintf((MODULE_PAYMENT_NOVALNET_INSTALMENT_CANCEL_ALLCYCLES_TEXT_FIRST), $txn_details['tid'], date('Y-m-d H:i:s'));
                if (isset($response['transaction']['refund']['amount']) && !empty($response['transaction']['refund']['amount'])) {
				$message = PHP_EOL. sprintf((MODULE_PAYMENT_NOVALNET_INSTALMENT_CANCEL_ALLCYCLES_TEXT), $txn_details['tid'], date('Y-m-d H:i:s'), xtc_format_price_order(($response['transaction']['refund']['amount'] / 100), 1, $response['transaction']['refund']['currency']));
				}
				if ($cycle_details['status'] == 'Paid') { // Refund the instalment cycles if its paid
					$instalment_details[$cycle]['status'] = 'Refunded';
				}
			}
		}
		$update_data = [
				'instalment_cycle_details' => !empty($instalment_details) ? json_encode($instalment_details) : '{}',
		];
		$order_status_value = (isset($request['nn_instacancel_allcycles'])) ? 99 : $order_status['orders_status'];
	}
}
if (isset($response['result']['status'])) {
	if ($response['result']['status'] == 'SUCCESS') {
		xtc_db_perform('novalnet_transaction_detail', $update_data, 'update', 'order_no='.$request['oID']);
		$messageStack->add_session($response['result']['status_text'], 'success');
	} else {
		$messageStack->add_session($response['result']['status_text'], 'error');
	}
}
if (isset($order_status_value) && isset($message)) {
	$novalnet_helper->updateStatus($request['oID'], $order_status_value, $message);
}
xtc_redirect(xtc_href_link(FILENAME_ORDERS, xtc_get_all_get_params(['action']) . 'action=edit' . '&oID=' . (int)$request['oID']));
?>
