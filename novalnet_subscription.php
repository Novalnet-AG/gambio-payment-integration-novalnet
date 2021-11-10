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
 * Script : novalnet_subscription.php
 */
ob_start();
include_once('includes/application_top.php');
include_once(DIR_WS_INCLUDES . "external/novalnet/NovalnetHelper.class.php");
include_once(DIR_WS_LANGUAGES . $_SESSION['language'] . '/modules/payment/novalnet.php');
$request = array_map('trim', $_REQUEST);
if (!empty($request['novalnet_subscription_update'])) {
		$client_ip      = xtc_get_ip_address();
        $remote_ip      = NovalnetHelper::getIpAddress($client_ip);
    	$order_id   = (int)$request['order_id'];
		$parent_tid = xtc_db_fetch_array(xtc_db_query('select tid from novalnet_subscription_detail where order_no = '.$order_id));
		$parent_tid = (int)$parent_tid['tid'];
        $datas      = xtc_db_fetch_array(xtc_db_query('select vendor, product, tariff_id, auth_code, payment_id from novalnet_transaction_detail where tid = '.$parent_tid));
        doCancelSubscription(array(
			'vendor'             => $datas['vendor'],
			'product'            => $datas['product'],
			'key'                => $datas['payment_id'],
            'tariff'             => $datas['tariff_id'],
            'auth_code'          => $datas['auth_code'],
            'cancel_sub'	     => '1',
            'remote_ip'	     	 => $remote_ip,
            'tid'                => $parent_tid,
            'cancel_reason'      => $request['novalnet_subscribe_termination_reason']),$order_id);
    xtc_redirect(xtc_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'page=1&order_id=' . $order_id, 'SSL'));
}
/**
 * Perform subscription cancellation
 *
 * @param array $subscription_params
 * @param integer $order_id
 * @return string
 */
 function doCancelSubscription($subscription_params,$order_id) {
		// Send the request to Novalnet server
        $response = NovalnetHelper::doPaymentCurlCall('https://payport.novalnet.de/paygate.jsp', $subscription_params);
        parse_str($response, $data);
        $params['gateway_status'] = $data['tid_status'];
        if ($data['status'] == 100) { // Success of subscription cancellation
            // Update gateway status in Novalnet table
			xtc_db_perform('novalnet_transaction_detail', $params, "update", "tid=" . $subscription_params['tid']);
            $params = array(
                'termination_reason' => $subscription_params['cancel_reason'],
                'termination_at'     => date('Y-m-d H:i:s')
            );
            // Update subscription termination details in Novalnet subscription table
            xtc_db_perform('novalnet_subscription_detail', $params, "update", "tid=" . $subscription_params['tid']);
            $message = PHP_EOL . MODULE_PAYMENT_NOVALNET_SUBS_CANCEL_REASON_MESSAGE . $subscription_params['cancel_reason'] . PHP_EOL;
            $subscription_orders = xtc_db_query("SELECT  order_no FROM novalnet_subscription_detail WHERE tid='". xtc_db_input($subscription_params['tid']) ."' ");
            // Update the order status in shop
            while ($row = xtc_db_fetch_array($subscription_orders)) {
				 xtc_db_perform(TABLE_ORDERS, array(
					'orders_status'  => MODULE_PAYMENT_NOVALNET_SUBSCRIPTION_CANCEL,
				), 'update', 'orders_id="' . $row['order_no'] . '"');

                xtc_db_query("INSERT INTO " . TABLE_ORDERS_STATUS_HISTORY . " SET orders_id =".(int)$row['order_no'].",orders_status_id = '".MODULE_PAYMENT_NOVALNET_SUBSCRIPTION_CANCEL."', date_added = NOW(), customer_notified = '1', comments = '".$message."'");
            }
        } else { // Failure of subscription cancellation
			xtc_redirect(xtc_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'page=1&order_id=' . $order_id, 'SSL'));
        }
    }
/**
 * To process Novalnet subscription cancel process
 *
 * @param integer $order_id
 * return void
 */
function displaySubscriptionForm($order_id)
{
	$order_id = (int)$order_id;
    $transaction_info = xtc_db_fetch_array(xtc_db_query("SELECT gateway_status FROM novalnet_transaction_detail WHERE order_no=" . $order_id));
    $subscription_info = xtc_db_fetch_array(xtc_db_query("SELECT subs_id, termination_reason FROM novalnet_subscription_detail WHERE order_no=" . $order_id));
    $subscripton_option          = '';
    if (!empty($transaction_info) && $transaction_info['gateway_status'] != 103 && !empty($subscription_info) && !empty($subscription_info['subs_id']) && empty($subscription_info['termination_reason'])) {
        $subs_termination_reason = array(
            MODULE_PAYMENT_NOVALNET_SUBS_REASON_1,
            MODULE_PAYMENT_NOVALNET_SUBS_REASON_2,
            MODULE_PAYMENT_NOVALNET_SUBS_REASON_3,
            MODULE_PAYMENT_NOVALNET_SUBS_REASON_4,
            MODULE_PAYMENT_NOVALNET_SUBS_REASON_5,
            MODULE_PAYMENT_NOVALNET_SUBS_REASON_6,
            MODULE_PAYMENT_NOVALNET_SUBS_REASON_7,
            MODULE_PAYMENT_NOVALNET_SUBS_REASON_8,
            MODULE_PAYMENT_NOVALNET_SUBS_REASON_9,
            MODULE_PAYMENT_NOVALNET_SUBS_REASON_10,
            MODULE_PAYMENT_NOVALNET_SUBS_REASON_11
        );
        $subscripton_option  = "<div><h2>" . MODULE_PAYMENT_NOVALNET_SUBS_CANCEL_TITLE . ":</h2></div><br><span id='novalnet_error' style='color:red'></span>";
        $subscripton_option .= "<p>" . MODULE_PAYMENT_NOVALNET_SUBS_SELECT_REASON . "</p><script>
            function validate_subscription_form_front() {
            	if (document.getElementById('novalnet_subscribe_termination_reason').value =='') {
					document.getElementById('novalnet_error').innerHTML=document.getElementsByName('nn_subs_error')[0].value;
					return false;
				}
				if (!confirm(document.getElementsByName('nn_sub_cancel')[0].value)) {
					return false;
				}
			}
			function remove_subs_error() {
				document.getElementById('novalnet_error').innerHTML='';
			}
            </script>".
        xtc_draw_form('novalnet_subscriptionstop',DIR_WS_CATALOG . "novalnet_subscription.php").
        xtc_draw_hidden_field('order_id', $order_id, 'id="order_id"').
        xtc_draw_hidden_field('nn_sub_cancel', MODULE_PAYMENT_NOVALNET_PAYMENT_CANCEL_SUBSCRIPTION, 'id="nn_sub_cancel"').
        xtc_draw_hidden_field('nn_subs_error', MODULE_PAYMENT_NOVALNET_SUBS_CANCEL_REASON_TITLE, 'id="nn_subs_error"').
        xtc_draw_hidden_field('current_url', $_SERVER['REQUEST_URI'], 'id="current_url"')."
        <select name='novalnet_subscribe_termination_reason' id='novalnet_subscribe_termination_reason' onclick= 'return remove_subs_error()'><option value=''>" . MODULE_PAYMENT_NOVALNET_SELECT_STATUS_OPTION . "</option>";
        foreach ($subs_termination_reason as $values) {
            $subscripton_option .= "<option value='$values'>$values</option>";
        }
        $subscripton_option .= "</select> &nbsp;&nbsp;<input type='submit' name='novalnet_subscription_update' onclick='return validate_subscription_form_front()' value='" . MODULE_PAYMENT_NOVALNET_CONFIRM_TEXT . "' /></form>";
    }
    return $subscripton_option;
}
