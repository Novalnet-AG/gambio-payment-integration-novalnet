<?php
/**
 * Novalnet payment module
 * This script is used for extension process
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
include_once(DIR_FS_LANGUAGES . $_SESSION['language']."/modules/payment/novalnet.php");
include_once(DIR_FS_INC . 'xtc_format_price_order.inc.php');
include_once(DIR_FS_CATALOG . 'admin/includes/classes/order.php');

$request        = $_REQUEST;
$txn_details    = NovalnetHelper::getNovalnetTransDetails($request['oID']);
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
	$comments = '';
	$data['transaction'] = [
		'tid' => $txn_details['tid']
	];
	$custom_param = NovalnetHelper::getCustomData();
	$params = array_merge($data, $custom_param);
	$endpoint = (!empty($request['trans_status']) && $request['trans_status'] == 'CONFIRM') ? 'transaction_capture' : 'transaction_cancel';
	$response = NovalnetHelper::sendRequest($params, NovalnetHelper::get_action_endpoint($endpoint));
	$update_data = [
		'status' => $response['transaction']['status'],
	];
	if ($response['result']['status'] == 'SUCCESS') {
		if (in_array($order->info['payment_class'], array('novalnet_googlepay','novalnet_applepay'))) {
			$order_status = NovalnetHelper::getOrderStatus ($update_data['status'], $order->info['payment_class']);
		} else {
			$order_status = NovalnetHelper::getOrderStatus ($update_data['status'], $order->info['payment_method']);
		}
		$order_status = ($request['trans_status'] == 'CONFIRM') ? $order_status : MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED;
		if ($request['trans_status'] == 'CONFIRM') {
			$comments .= PHP_EOL . sprintf(MODULE_PAYMENT_NOVALNET_TRANS_CONFIRM_SUCCESSFUL_MESSAGE, date('d.m.Y', strtotime(date('d.m.Y'))), date('H:i:s')) . PHP_EOL;
			$comments .= NovalnetHelper::getTransactionDetails($response);
			if (in_array($response['transaction']['payment_type'], array( 'INSTALMENT_INVOICE','GUARANTEED_INVOICE', 'INVOICE', 'PREPAYMENT'))) {
				if (empty($response['transaction']['bank_details'])) {
					$response['transaction']['bank_details'] = $payment_details;
				}
				$comments .= NovalnetHelper::getBankDetails($response, $request['oID']);
				if (in_array($response['transaction']['payment_type'], array('GUARANTEED_INVOICE', 'INVOICE', 'PREPAYMENT'))) {
					$comments .= PHP_EOL. MODULE_PAYMENT_NOVALNET_PAYMENT_REFERENCE_TEXT .PHP_EOL;
					$comments .= sprintf(MODULE_PAYMENT_NOVALNET_PAYMENT_REFERENCE, 1, ('TID ' . $response['transaction']['tid'])) . PHP_EOL;
					$comments .= sprintf(MODULE_PAYMENT_NOVALNET_PAYMENT_REFERENCE, 2, ('BNR-' . MODULE_PAYMENT_NOVALNET_PROJECT_ID . '-' . $request['oID'])) . PHP_EOL;
				}
			}
			if (in_array($response['transaction']['payment_type'], array( 'INSTALMENT_INVOICE','INSTALMENT_DIRECT_DEBIT_SEPA'))) {
				$comments .= NovalnetHelper::formInstalmentPaymentReference($response);
				if (in_array($response['transaction']['status'],array('CONFIRMED', 'PENDING'))) {
					$total_amount = ($txn_details['amount'] < $response['transaction']['amount']) ? $response['transaction']['amount'] : $txn_details['amount'];
					$instalment_details = NovalnetHelper::storeInstalmentdetails($response, $total_amount);
					$update_data['instalment_cycle_details'] = $instalment_details;
				}
			}
			NovalnetHelper::sendOrderUpdateMail(array(
					'comments' => '<br>' . $comments,
					'order_no' => $request['oID'],
					'order' =>  $order,
			),array(
				'customer_id' => $customerId
			));
		} elseif ($request['trans_status'] == 'CANCEL') {
			$comments .= PHP_EOL.sprintf(MODULE_PAYMENT_NOVALNET_TRANS_DEACTIVATED_MESSAGE, date('d.m.Y', strtotime(date('d.m.Y'))), date('H:i:s'));
		}
		updateOrderStatus($request['oID'], $order_status, $comments);
		$messageStack->add_session($response['result']['status_text'], 'success');
	} else {
		 $messageStack->add_session($response['result']['status_text'], 'error');
	}
	if (!empty($request['oID'])) {
		xtc_db_perform('novalnet_transaction_detail', $update_data, 'update', 'order_no='.$request['oID']);
	}
	xtc_redirect(xtc_href_link(FILENAME_ORDERS, xtc_get_all_get_params(['action']) . 'action=edit' . '&oID=' . (int)$request['oID']));
} elseif ((!empty($request['nn_refund_confirm']) && ($request['refund_trans_amount'] != '' ) && $txn_details['status'] != 'Canceled')) { // To process refund process
	$refunded_amount = 0;
	$data['transaction'] = [
		'tid'    => (!empty($request['refund_tid'])) ? $request['refund_tid'] : $txn_details['tid'],
		'amount' => $request['refund_trans_amount'],
	];
	$data['custom'] = [
		'lang' => (isset($_SESSION['language_code'])) ? strtoupper($_SESSION['language_code']) : 'DE',
	];
	if (!empty($request['refund_reason'])){
		$data['transaction']['reason'] = $request['refund_reason'];
	}
	$response = NovalnetHelper::sendRequest($data, NovalnetHelper::get_action_endpoint('transaction_refund'));

	if ($response['result']['status'] == 'SUCCESS') {
		$refunded_amount = $response['transaction']['refund']['amount'];

		if (in_array($response['transaction']['payment_type'], array('INSTALMENT_INVOICE','INSTALMENT_DIRECT_DEBIT_SEPA'))) {
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
		$message = PHP_EOL. sprintf((MODULE_PAYMENT_NOVALNET_REFUND_PARENT_TID_MSG), $txn_details['tid'], xtc_format_price_order(($refunded_amount/100), 1, $txn_details['currency']));
		// Check for refund TID
		if (!empty($response['transaction']['refund']['tid'])) {
			$message .= PHP_EOL. sprintf((MODULE_PAYMENT_NOVALNET_REFUND_CHILD_TID_MSG), $response['transaction']['refund']['tid']);
		}
		if (!empty($request['oID'])) {
			xtc_db_perform('novalnet_transaction_detail', $update_data, 'update', 'order_no='.$request['oID']);
		}
		if (in_array($order->info['payment_class'], array('novalnet_googlepay','novalnet_applepay'))) {
			$order_status_value = ($update_data['refund_amount'] >= $txn_details['amount']) ? MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED : $order->info['orders_status'];
		} else {
			$order_status_value = ($update_data['refund_amount'] >= $txn_details['amount']) ? MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED : $order->info['orders_status'];
		}
		updateOrderStatus($request['oID'], $order_status_value, $message);
		$messageStack->add_session($response['result']['status_text'], 'success');
	} else {
		$messageStack->add_session($response['result']['status_text'], 'error');
	}
	xtc_redirect(xtc_href_link(FILENAME_ORDERS, xtc_get_all_get_params(['action']) . 'action=edit' . '&oID=' . (int)$request['oID']));
} else if (!empty($request['nn_book_confirm']) && !empty($request['book_amount'])) {	// To process zero amount booking transaction
	$merchant_data    = NovalnetHelper::getMerchantData();
	$customer_data    = NovalnetHelper::getCustomerData();
	$transaction_data = NovalnetHelper::getTransactionData();
	$custom_data	  = NovalnetHelper::getCustomData();
	$customer_data['customer']['billing']['country_code'] = $order->billing['country_iso_code_2'];
	$customer_data['customer']['delivery']['country_code'] = $order->delivery['country_iso_code_2'];
	$transaction_data['transaction']['payment_type'] = $txn_details['payment_type'];
	$data = array_merge($merchant_data, $customer_data, $transaction_data, $custom_data);
	$data['transaction']['amount'] = $request['book_amount'];
	$data['transaction']['payment_data']['token'] = $payment_details['token'];
	$response = NovalnetHelper::sendRequest($data, NovalnetHelper::get_action_endpoint('payment'));
	if ($response['result']['status'] == 'SUCCESS' ) {
		$order_status_value = xtc_db_fetch_array(xtc_db_query("SELECT orders_status from " . TABLE_ORDERS . " where orders_id = " . xtc_db_input($request['oID'])));
		$message =  PHP_EOL .PHP_EOL. sprintf(MODULE_PAYMENT_NOVALNET_TRANS_BOOKED_MESSAGE, xtc_format_price_order(($request['book_amount'] / 100), 1, $response['transaction']['currency']), $response['transaction']['tid']) . PHP_EOL;
		$update_data = [
					'amount' => $response['transaction']['amount'],
					'tid' 	 => $response['transaction']['tid'],
				];
		if (!empty($request['oID'])) {
			xtc_db_perform('novalnet_transaction_detail', $update_data, 'update', 'order_no='.$request['oID']);
		}
		updateOrderStatus($request['oID'], $order_status_value['orders_status'], $message);
		$messageStack->add_session($response['result']['status_text'], 'success');
	} else {
		$messageStack->add_session($response['result']['status_text'], 'error');
	}
	xtc_redirect(xtc_href_link(FILENAME_ORDERS, xtc_get_all_get_params(['action']) . 'action=edit' . '&oID=' . (int)$request['oID']));
} else if (!empty($request['nn_instalment_cancel'])) { // To process instalment cancel
	$data['instalment']['tid'] = $txn_details['tid'];
	$data['custom'] = [
		'lang' => (isset($_SESSION['language_code'])) ? strtoupper($_SESSION['language_code']) : 'DE',
	];
	$response = NovalnetHelper::sendRequest($data, NovalnetHelper::get_action_endpoint('instalment_cancel'));
	if ($response['result']['status'] == 'SUCCESS') {
		$update_data['status'] = 'DEACTIVATE';
		$message = PHP_EOL. sprintf((MODULE_PAYMENT_NOVALNET_REFUND_PARENT_TID_MSG), $txn_details['tid'], xtc_format_price_order(($response['transaction']['refund']['amount']/100), 1, $txn_details['currency']));
		if (!empty($response['transaction']['refund']['tid'])) {
			$message .= PHP_EOL. sprintf((MODULE_PAYMENT_NOVALNET_REFUND_CHILD_TID_MSG), $response['transaction']['refund']['tid']);
		}
		if (!empty($request['oID'])) {
			xtc_db_perform('novalnet_transaction_detail', $update_data, 'update', 'order_no='.$request['oID']);
		}
		$order_status_value = ($response['result']['status'] == 'SUCCESS') ? (MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED) : '';
		updateOrderStatus($request['oID'], $order_status_value, $message);
		$messageStack->add_session($response['result']['status_text'], 'success');
	} else {
		$messageStack->add_session($response['result']['status_text'], 'error');
	}
	xtc_redirect(xtc_href_link(FILENAME_ORDERS, xtc_get_all_get_params(['action']) . 'action=edit' . '&oID=' . (int)$request['oID']));
} else {
	xtc_redirect(xtc_href_link(FILENAME_ORDERS, xtc_get_all_get_params(['action']) . 'action=edit' . '&oID=' . (int)$request['oID']));
}
/**
* Update order status in the shop
*
* @param integer $order_id
* @param string $order_status
* @param string $message
*/
function updateOrderStatus($order_id, $order_status, $message) {
	$comments = xtc_db_fetch_array(xtc_db_query("SELECT comments FROM ". TABLE_ORDERS ." WHERE orders_id = '$order_id'"));
	xtc_db_perform(TABLE_ORDERS, array(
		'orders_status' => $order_status,
	), "update", "orders_id='$order_id'");
	xtc_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments, customer_id) values ('".xtc_db_input($order_id)."', '".xtc_db_input($order_status)."', '" .date('Y-m-d H:i:s') . "', '1', '".xtc_db_prepare_input($message)."', '0')");
}
?>
