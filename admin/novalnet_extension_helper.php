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
 * Script : novalnet_extension_helper.php
 *
 */
require ('includes/application_top.php');
include_once(DIR_FS_CATALOG.DIR_WS_INCLUDES . "external/novalnet/NovalnetHelper.class.php");
include_once(DIR_FS_LANGUAGES . $_SESSION['language']."/modules/payment/novalnet.php");
include_once(DIR_FS_INC . 'xtc_format_price_order.inc.php');
$request   = $_POST;
$datas     = NovalnetHelper::getNovalnetTransDetails($_POST['oID']);
$client_ip = xtc_get_ip_address();
$request['remote_ip'] = NovalnetHelper::getIpAddress($client_ip);
	if (!empty($request['nn_refund_confirm']) && $request['refund_trans_amount'] != '') { // To process refund process
            $refund_params = array(
                'vendor' 		=> $datas['vendor'],
                'product' 		=> $datas['product'],
                'key' 			=> $datas['payment_id'],
                'tariff' 		=> $datas['tariff_id'],
                'auth_code' 	=> $datas['auth_code'],
                'refund_request'=> '1',
                'remote_ip'     => $request['remote_ip'],
                'tid' 			=> $datas['tid'],
                'refund_param' 	=> $request['refund_trans_amount']
            );
            if (!empty($request['refund_ref'])) { // Assigning refund ref tid
                $refund_params['refund_ref'] = $request['refund_ref'];
            }
            // Send the request to Novalnet server
            $response = NovalnetHelper::doPaymentCurlCall('https://payport.novalnet.de/paygate.jsp', $refund_params);
            parse_str($response, $data);
            $order_status            = '';
            $param['gateway_status'] = $data['tid_status'];
            if ($data['status'] == 100) { // Payment success
                $message .= PHP_EOL . sprintf(utf8_encode(MODULE_PAYMENT_NOVALNET_REFUND_PARENT_TID_MSG), $datas['tid'], xtc_format_price_order(($request['refund_trans_amount'] / 100), 1, $datas['currency']));
                $newtid = !empty($data['tid']) ? $data['tid'] : (!empty($data['paypal_refund_tid']) ? $data['paypal_refund_tid'] : '');
                if (!empty($newtid)) { // Get new tid for refund process
                    $message .= sprintf(utf8_encode(MODULE_PAYMENT_NOVALNET_REFUND_CHILD_TID_MSG), $newtid);
                }
                $message .= PHP_EOL;
                $order_status       = xtc_db_fetch_array(xtc_db_query("SELECT orders_status from " . TABLE_ORDERS . " where orders_id = " . xtc_db_input($request['oID'])));
                $order_status_value = $order_status['orders_status'];
                $param['refund_amount'] = ($datas['refund_amount']+$request['refund_trans_amount']);
                // Transaction details update the shop Novalnet table
                xtc_db_perform('novalnet_transaction_detail', $param, "update", "tid='" . $datas['tid'] . "'");
                // Update the void status
                if ($param['gateway_status'] != 100) { // Process for onhold
                    $order_status_value = MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED;
                    xtc_db_perform(TABLE_ORDERS, array(
                        'orders_status' => $order_status_value
                    ), 'update', 'orders_id="' . $request['oID'] . '"');
                }
                // Update the order status in shop
                updateOrderStatus($request['oID'], $order_status_value, utf8_decode($message), true,true);
            }
			$response = http_build_query($data);
            // Redirect to shop
            xtc_redirect(xtc_href_link(FILENAME_ORDERS, 'action=edit&'.$response.'&oID=' . (int)$request['oID']));
    } else if (!empty($request['new_amount']) && isset($request['nn_amount_update_confirm'])) { // To process amount update field
			$orderInfo_details = unserialize($datas['payment_details']);
			$due_date = $orderInfo_details['due_date'];
			if (!empty($request['amount_change_year']) && !empty($request['amount_change_month']) && !empty($request['amount_change_day'])) {
				$due_date = $request['amount_change_year'].'-'.$request['amount_change_month'].'-'.$request['amount_change_day'];
			}
            $amount_change_request = array(
                'vendor' 			=> $datas['vendor'],
                'product' 			=> $datas['product'],
                'key' 				=> $datas['payment_id'],
                'tariff' 			=> $datas['tariff_id'],
                'auth_code' 		=> $datas['auth_code'],
                'edit_status' 		=> '1',
                'remote_ip'         => $request['remote_ip'],
                'tid' 				=> $datas['tid'],
                'status' 			=> 100,
                'update_inv_amount' => '1',
                'amount' 			=> $request['new_amount']
            );
            if (in_array($datas['payment_id'], array(27,59)) && !empty($due_date)) { // Due_date added for payment invoice ,prepayment only
                $amount_change_request['due_date'] = date('Y-m-d', strtotime($due_date));
            }
			// Send the request to Novalnet server
            $response = NovalnetHelper::doPaymentCurlCall('https://payport.novalnet.de/paygate.jsp', $amount_change_request);
            parse_str($response, $data);
            if ($data['status'] == 100) { // Amount update success in shop
                $message                 = PHP_EOL . sprintf(utf8_encode(MODULE_PAYMENT_NOVALNET_TRANS_UPDATED_MESSAGE), xtc_format_price_order(($amount_change_request['amount'] / 100), 1, $datas['currency']), date(DATE_FORMAT, strtotime($due_date)), date('H:i:s')) . PHP_EOL;
                $orderInfo               = xtc_db_fetch_array(xtc_db_query("SELECT orders_status from " . TABLE_ORDERS . " where orders_id = " . xtc_db_input($request['oID'])));
                $param                   = array();
                $param =  array (
						'gateway_status'=> $data['tid_status'],
						'amount'        => $amount_change_request['amount']);
                if ($datas['payment_id'] == 37) { // Allow only sepa payment
					$message                 = PHP_EOL . sprintf(utf8_encode(MODULE_PAYMENT_NOVALNET_SEPA_TRANS_AMOUNT_UPDATED_MESSAGE), xtc_format_price_order(($amount_change_request['amount'] / 100), 1, $datas['currency']), date(DATE_FORMAT, strtotime(date('Y-m-d'))), date('H:i:s')) . PHP_EOL;
					
					$callback_param['order_amount'] =$callback_param['callback_amount'] = $amount_change_request['amount'];
					xtc_db_perform('novalnet_callback_history', $callback_param, "update", "original_tid='" . $datas['tid'] . "'");
                }
                if (in_array($datas['payment_id'],array(27,59))) { // Allowed only payment id 27 and 59
                    $orderInfo_comments = NovalnetHelper::transactionCommentsForm($datas,$datas['payment_type']);
                    $transaction_comments     = '';
                    if($datas['payment_id'] == 27 ) {
						$test_mode_text .= (($datas['test_mode'] == 1) ? PHP_EOL . MODULE_PAYMENT_NOVALNET_TEST_ORDER_MESSAGE . PHP_EOL : '');
						// To form Novalnet transaction comments
						$novalnetPaymentReference = NovalnetHelper::formInvoicePrepaymentPaymentReference($datas['payment_ref'], $datas['payment_type'], $datas);
						list($transaction_Details, $bank_details) = NovalnetHelper::formInvoicePrepaymentComments(array(
							'invoice_account'   => $orderInfo_details['account_holder'],
							'invoice_bankname'  => $orderInfo_details['bank_name'],
							'invoice_bankplace' => $orderInfo_details['bank_city'],
							'amount' 			=> sprintf("%.2f", ($amount_change_request['amount'] / 100)),
							'currency' 			=> $orderInfo_details['currency'],
							'tid' 				=> $orderInfo_details['tid'],
							'invoice_iban' 		=> $orderInfo_details['bank_iban'],
							'invoice_bic' 		=> $orderInfo_details['bank_bic'],
							'due_date' 			=> $amount_change_request['due_date']
						), $test_mode_text);
						$transaction_comments .= $orderInfo_comments . $transaction_Details . $novalnetPaymentReference;
						$param['payment_details'] = serialize($bank_details); // To get bank details
						$orderInfo['comments'] = $transaction_comments;
					} else {
						$orderInfo_comments .= MODULE_PAYMENT_NOVALNET_TRANS_SLIP_EXPIRY_DATE.date(DATE_FORMAT,strtotime($amount_change_request['due_date'])).PHP_EOL;
						$orderInfo_comments .= PHP_EOL.MODULE_PAYMENT_NOVALNET_NEAREST_STORE_DETAILS.PHP_EOL;
						$nearest_store =  NovalnetHelper::getNearestStore($orderInfo_details,'nearest_store');
						$cashpayment_slip_details = array_merge($nearest_store,array('due_date'=> $amount_change_request['due_date']));
						$param['payment_details'] = serialize($cashpayment_slip_details);
						$i = 0;
						foreach ($nearest_store as $key => $values){
							$i++;
							$country_name = xtc_db_fetch_array(xtc_db_query("select countries_name from " . TABLE_COUNTRIES . " where countries_iso_code_2 = '" . $nearest_store['nearest_store_country_'.$i] . "'"));
							if(!empty($nearest_store['nearest_store_title_'.$i])) {
								$orderInfo_comments .= PHP_EOL . $nearest_store['nearest_store_title_'.$i].PHP_EOL;
							}
							if (!empty($nearest_store['nearest_store_street_'.$i])) {
								$orderInfo_comments .= $nearest_store['nearest_store_street_'.$i].PHP_EOL;
							}
							if(!empty($nearest_store['nearest_store_city_'.$i])) {
								$orderInfo_comments .= $nearest_store['nearest_store_city_'.$i].PHP_EOL;
							}
							if(!empty($nearest_store['nearest_store_zipcode_'.$i])) {
								$orderInfo_comments .= $nearest_store['nearest_store_zipcode_'.$i].PHP_EOL;
							}
							if(!empty($nearest_store['nearest_store_country_'.$i])) {
								$orderInfo_comments .= $country_name['countries_name'].PHP_EOL;
							}
						}         
						$transaction_comments     = $orderInfo['comments'] = $orderInfo_comments;
					}
					$call_amount['order_amount'] = $amount_change_request['amount'];
					xtc_db_perform('novalnet_callback_history',$call_amount , "update", "original_tid='" . $datas['tid'] . "'");
					xtc_db_perform(TABLE_ORDERS, $orderInfo, 'update', 'orders_id="' . $request['oID'] . '"');
				}	
                // Transaction details update the shop Novalnet table
                xtc_db_perform('novalnet_transaction_detail', $param, "update", "tid='" . $datas['tid'] . "'");
                // Update the order status in shop
                updateOrderStatus($request['oID'], $orderInfo['orders_status'], utf8_decode($message) . $transaction_comments, true, true);
            }
            $response = http_build_query($data);
            // Redirect to shop
            xtc_redirect(xtc_href_link(FILENAME_ORDERS, 'action=edit&'.$response.'&oID=' . (int)$request['oID']));
	} else if (isset($request['nn_manage_confirm']) && !empty($request['trans_status'])) { //  To process on-hold transaction
		// Send the request to Novalnet server
			$response      = NovalnetHelper::doPaymentCurlCall('https://payport.novalnet.de/paygate.jsp',  array(
                'vendor' 		=> $datas['vendor'],
                'product' 		=> $datas['product'],
                'key' 			=> $datas['payment_id'],
                'tariff' 		=> $datas['tariff_id'],
                'auth_code' 	=> $datas['auth_code'],
                'edit_status' 	=> '1',
                'remote_ip'     => $request['remote_ip'],
                'tid' 			=> $datas['tid'],
                'status' 		=> $request['trans_status'] //Status 100 or 103
            ));
            
            parse_str($response, $data);
            if ($data['status'] == 100 || ($data['status'] == 90 && $datas['payment_type'] == 'novalnet_paypal' )) { // Payment success
                $param = array(
		    'gateway_status'  => $data['tid_status'],
		);
				if($datas['payment_type'] == 'novalnet_paypal') {
					if(MODULE_PAYMENT_NOVALNET_PAYPAL_SHOP_TYPE == 'ONECLICK' && !empty($data['paypal_transaction_id'])) {
						$param['payment_details'] = serialize(array(
											'paypal_transaction_tid'   => $data['paypal_transaction_id'],
											'novalnet_transaction_tid' => $datas['tid']));
					}
					$amout_update['callback_amount'] = $data['tid_status'] == 90 ? 0 : $datas['amount'];
					xtc_db_perform('novalnet_callback_history', $amout_update, "update", "original_tid='" . $datas['tid'] . "'");
				}
                $order_status            = ($request['trans_status'] == 100) ? MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_COMPLETE : MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED;
                // Transaction details update the shop novalnet table
                xtc_db_perform('novalnet_transaction_detail', $param, "update", "tid='" . $datas['tid'] . "'");
                // Update the order status in shop
                xtc_db_perform(TABLE_ORDERS, array(
                    'orders_status' => $order_status
                ), 'update', 'orders_id="' . $request['oID'] . '"');
                $message = ($request['trans_status'] == 100) ? updateOrderStatus($request['oID'], $order_status, PHP_EOL . sprintf(MODULE_PAYMENT_NOVALNET_TRANS_CONFIRM_SUCCESSFUL_MESSAGE, date(DATE_FORMAT, strtotime(date('d.m.Y'))), date('H:i:s')) . PHP_EOL, true,true) : updateOrderStatus($request['oID'], $order_status, PHP_EOL . sprintf(MODULE_PAYMENT_NOVALNET_TRANS_DEACTIVATED_MESSAGE, date(DATE_FORMAT, strtotime(date('d.m.Y'))), date('H:i:s')) . PHP_EOL, true,true);
            }
            $response = http_build_query($data);
            // Redirect to shop
            xtc_redirect(xtc_href_link(FILENAME_ORDERS, 'action=edit&'.$response.'&oID=' . (int)$request['oID']));
	} else  if (!empty($request['nn_book_confirm']) && !empty($request['book_amount'])) { // To process zero amount booking transaction
		    $urlparams                = unserialize($datas['payment_details']);
            $urlparams['amount']      = trim($request['book_amount']);
            $urlparams['order_no']    = $request['oID'];
            $urlparams['remote_ip']   = $request['remote_ip'];
            $urlparams['payment_ref'] = $datas['tid'];
            if (!empty($urlparams['sepa_due_date'])) { // Assigning Due date param sepa Payment only
                $sepa_duedate              = ((trim(MODULE_PAYMENT_NOVALNET_SEPA_DUE_DATE) != '' && trim(MODULE_PAYMENT_NOVALNET_SEPA_DUE_DATE) >= 7) ? trim(MODULE_PAYMENT_NOVALNET_SEPA_DUE_DATE) : 7);
                $urlparams['sepa_due_date'] = (date('Y-m-d', strtotime('+' . $sepa_duedate . ' days')));
            }
            // Send the request to Novalnet server
            $response = NovalnetHelper::doPaymentCurlCall('https://payport.novalnet.de/paygate.jsp', $urlparams);
            parse_str($response, $data);
            if ($data['status'] == 100 || ( $data['status'] == 90 && $datas['payment_type'] == 'novalnet_paypal' )) { // Zero amount booking process success
                $orderInfo = xtc_db_fetch_array(xtc_db_query("SELECT orders_status from " . TABLE_ORDERS . " where orders_id = " . xtc_db_input($request['oID'])));
                $test_mode_msg = (isset($data['test_mode']) && $data['test_mode'] == 1) ? PHP_EOL . MODULE_PAYMENT_NOVALNET_TEST_ORDER_MESSAGE . PHP_EOL : '';
                $message = PHP_EOL . MODULE_PAYMENT_NOVALNET_TRANSACTION_DETAILS . PHP_EOL . MODULE_PAYMENT_NOVALNET_TRANSACTION_ID . $data['tid'] . $test_mode_msg;
				$message .=  PHP_EOL . sprintf(MODULE_PAYMENT_NOVALNET_TRANS_BOOKED_MESSAGE, xtc_format_price_order(($request['book_amount'] / 100), 1, $datas['currency']), $data['tid']) . PHP_EOL;
                $param['tid'] = $data['tid'];
                xtc_db_perform('novalnet_subscription_detail', $param, "update", "order_no='" . xtc_db_input($request['oID']) . "'");
                $callback_param['original_tid'] = $data['tid'];
                $callback_param['callback_amount']= ( $data['status'] == 100 ) ? $urlparams['amount'] : 0 ;
                $callback_param['order_amount'] = $urlparams['amount'];
                xtc_db_perform('novalnet_callback_history', $callback_param, "update", "order_no='" . xtc_db_input($request['oID']) . "'");
                $param =  array(
								'amount'          => $urlparams['amount'],
								'gateway_status'  => $data['tid_status'],
								'tid'  		      => $data['tid'],
				);
				updateOrderStatus($request['oID'], $orderInfo['orders_status'], $message, true,true);
				// Transaction details update the shop Novalnet table
                xtc_db_perform('novalnet_transaction_detail', $param, "update", "order_no='" . xtc_db_input($request['oID']) . "'");
            }
			$response = http_build_query($data);
            // Redirect to shop
            xtc_redirect(xtc_href_link(FILENAME_ORDERS, 'action=edit&'.$response.'&oID=' . (int)$request['oID']));
	} else if (isset($request['nn_subs_confirm']) && !empty($request['subscribe_termination_reason'])) { // To process  subscription cancel
		dosubscriptionCancel($request,$datas);
	}

    /**
     * Update order status in the shop
     *
     * @param integer $order_id
     * @param string $orders_status_id
     * @param string $message
     * @param boolean $insert_status_history
     * @param boolean $customer_notified
     */
    function updateOrderStatus($order_id, $orders_status_id, $message, $insert_status_history, $customer_notified) {
		if ($insert_status_history) { // Insert record into shop table
			$customer_notified_status = (($customer_notified) ? 1 : 0);
			xtc_db_perform(TABLE_ORDERS, array(
            'orders_status' => $orders_status_id,
            'comments' 		=> $message
			), "update", "orders_id='$order_id'");
            xtc_db_query("INSERT INTO " . TABLE_ORDERS_STATUS_HISTORY . " SET orders_id =  '$order_id',orders_status_id = '$orders_status_id', date_added = NOW(), customer_notified = '$customer_notified_status', comments = '$message'");
        }
    }

    /**
     * To process the subscription cancel
     *
     * @param array $request
     * @param array $datas
     */
    function dosubscriptionCancel($request, $datas) {
        $cancelSubscription = dosubscriptionStop(array(
                'tid' 				 => $datas['tid'],
                'payment_id' 		 => $datas['payment_id'],
                'termination_reason' => $request['subscribe_termination_reason'],
                'vendor' 			 => $datas['vendor'],
                'tariff_id' 		 => $datas['tariff_id'],
                'auth_code' 		 => $datas['auth_code'],
                'product' 			 => $datas['product'],
                'order_id' 			 => $request['oID'],
                'remote_ip'      	 => $request['remote_ip'],
            ));
            $page = array('message' => $cancelSubscription);
            // Redirect to shop
            xtc_redirect(xtc_href_link(FILENAME_ORDERS, 'page=1&oID=' . (int)$request['oID']).'&action=edit&'.http_build_query($page));
    }

    /**
     * Perform subscription cancellation
     *
     * @param array $request
     * @return string
     */
    function dosubscriptionStop($request) {
		$parent_tid = xtc_db_fetch_array(xtc_db_query("SELECT  tid FROM novalnet_subscription_detail WHERE order_no='". xtc_db_input($request['order_id']) ."' "));
   	    // Send the request to Novalnet server
        $response = NovalnetHelper::doPaymentCurlCall('https://payport.novalnet.de/paygate.jsp', array(
            'vendor' 		 => $request['vendor'],
            'product' 		 => $request['product'],
            'key' 			 => $request['payment_id'],
            'tariff'		 => $request['tariff_id'],
            'auth_code'		 => $request['auth_code'],
            'cancel_sub'	 => '1',
            'remote_ip'      => $request['remote_ip'],
            'tid'			 => $parent_tid['tid'],
            'cancel_reason'  => $request['termination_reason']
        ));
        parse_str($response, $data);
        $params['gateway_status'] = $data['tid_status'];
        if ($data['status'] == 100) { // Success of subscription cancellation
            // Update gateway status in Novalnet table
			xtc_db_perform('novalnet_transaction_detail', $params, "update", "tid=" . $parent_tid['tid']);
            $params = array(
                'termination_reason' => $request['termination_reason'],
                'termination_at'     => date('Y-m-d H:i:s')
            );
            // Update subscription termination details in Novalnet subscription table
            xtc_db_perform('novalnet_subscription_detail', $params, "update", "tid=" . $parent_tid['tid']);
            $message = PHP_EOL . MODULE_PAYMENT_NOVALNET_SUBS_CANCEL_REASON_MESSAGE . $request['termination_reason'] . PHP_EOL;
            $subscription_orders = xtc_db_query("SELECT  order_no FROM novalnet_subscription_detail WHERE tid='". xtc_db_input($parent_tid['tid']) ."' ");
            // Update the order status in shop
            while ($row = xtc_db_fetch_array($subscription_orders)) {
				updateOrderStatus($row['order_no'], MODULE_PAYMENT_NOVALNET_SUBSCRIPTION_CANCEL, $message, true, true);
            }
            return $message;
        } else { // Failure of subscription cancellation
			$response = http_build_query($data);
            // Redirect to shop
            xtc_redirect(xtc_href_link(FILENAME_ORDERS, 'action=edit&'.$response.'&oID=' . (int)$request['order_id']));
        }
    }
