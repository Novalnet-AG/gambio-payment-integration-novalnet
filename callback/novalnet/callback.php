<?php
/**
 * Novalnet payment module
 *
 * This script is used for handle novalnet webhook event
 *
 * @author     Novalnet AG
 * @copyright  Copyright (c) Novalnet
 * @license    https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link       https://www.novalnet.de
 *
 * File: callback.php
 *
 */
chdir('../../');
include_once('includes/application_top.php');
include(DIR_FS_CATALOG . 'release_info.php');
require_once (DIR_FS_CATALOG . 'gm/inc/gm_save_order.inc.php');
include(DIR_FS_INC . 'xtc_format_price_order.inc.php');
require_once(DIR_FS_INC . 'xtc_php_mail.inc.php');
class NovalnetWebhooks {

	/**
	 * Mandatory Parameters.
	 *
	 * @var array
	 */
	protected $mandatory = [
		'event'       => [
			'type',
			'checksum',
			'tid',
		],
		'result'      => [
			'status',
		],
		'transaction' => [
			'tid',
			'payment_type',
			'status',
		],
	];

	/**
	 * Request parameters.
	 *
	 * @var array
	 */
	protected $event_data = [];

	/**
	 * Order reference values.
	 *
	 * @var array
	 */
	protected $order_details = [];

	/**
	 * Recived Event type.
	 *
	 * @var string
	 */
	protected $event_type;

	/**
	 * Recived Event TID.
	 *
	 * @var int
	 */
	protected $event_tid;

	/**
	 * Recived Event parent TID.
	 *
	 * @var int
	 */
	protected $parent_tid;

	/**
	 * Order language details.
	 *
	 * @var array
	 */
	protected $order_lang;

	/**
	 * Core Function : Constructor()
	 *
	 */
	function __construct() {
		try {
			$this->event_data = json_decode(file_get_contents('php://input'), true);
		} catch (Exception $e) {
			$this->displayMessage([ 'message' => 'Received data is not in the JSON format' . $e]);
		}
		$this->authenticateEventData();
		$this->event_tid  = !empty($this->event_data['event']['tid']) ? $this->event_data['event']['tid'] : '';
		$this->event_type = $this->event_data['event']['type'];
		$this->parent_tid = (!empty($this->event_data['event']['parent_tid'])) ? $this->event_data['event']['parent_tid'] :$this->event_data['event']['tid'];
		$this->order_details = $this->getOrderReference();
		// If the order in the Novalnet server to the order number in Novalnet database doesn't match, then there is an issue
		if (!empty($this->event_data['transaction']['order_no']) && !empty($this->order_details['shop_order'])
		&& (($this->event_data['transaction']['order_no']) != $this->order_details['shop_order'])) {
			$this->displayMessage(['message' => 'Order reference not matching for the order number ' . $this->order_details['shop_order']]);
		}
		// If both the order number from Novalnet and in shop is missing, then something is wrong
		if (!empty($this->event_data['transaction']['order_no']) && empty($this->order_details['shop_order'])) {
			$this->displayMessage(['message' => 'Order reference not found for the TID ' . $this->parent_tid]);
		}

		if (NovalnetHelper::is_success_status($this->event_data)) {
			switch($this->event_type) {
				case 'PAYMENT':
					$this->displayMessage(['message' => "The webhook notification received ('".$this->event_data['transaction']['payment_type']."') for the TID: '".$this->event_tid."'"]);
					break;
				case 'TRANSACTION_CAPTURE':
					$this->handleTransactionCapture();
					break;
				case 'TRANSACTION_CANCEL':
					$this->handleTransactionCancel();
					break;
				case 'TRANSACTION_REFUND':
				 $this->handleTransactionRefund();
					break;
				case 'CREDIT':
					$this->handleTransactionCredit();
					break;
				case 'CHARGEBACK':
					$this->handleChargeback();
					break;
				case 'INSTALMENT':
					$this->handleInstalment();
					break;
				case 'INSTALMENT_CANCEL':
					$this->handleInstalmentCancel();
					break;
				case 'TRANSACTION_UPDATE':
					$this->handleTransactionUpdate();
					break;
				case 'PAYMENT_REMINDER_1':
				case 'PAYMENT_REMINDER_2':
					$this->handlePaymentReminder();
					break;
				case 'SUBMISSION_TO_COLLECTION_AGENCY':
					$this->handleCollectionSubmission();
					break;
				default:
					$message = "The webhook notification has been received for the unhandled EVENT type('".$this->event_type."')";
					$this->displayMessage(['message' => $message]);
			}
		} else {
			$this->displayMessage(['message' => xtc_db_prepare_input($this->event_data['result']['status_text'])]);
		}
	}

	/**
	 * Authenticate server request
	 *
	 */
	function authenticateEventData() {
		$novalnet_host_name   = 'pay-nn.de';
		$request_received_ip = xtc_get_ip_address();
		$novalnet_host_ip  = gethostbyname($novalnet_host_name);
		if (!empty($novalnet_host_ip) && ! empty($request_received_ip)) {
			if ($novalnet_host_ip !== $request_received_ip && MODULE_PAYMENT_NOVALNET_CALLBACK_TEST_MODE == 'false') {
				$this->displayMessage(['message' => 'Unauthorised access from the IP ' . $request_received_ip]);
			}
		} else {
			$this->displayMessage([ 'message' => 'Unauthorised access from the IP. Host/recieved IP is empty' ]);
		}
		$this->validateEventData();
		$this->validateCheckSum();
	}

	/**
	 * Validate event_data
	 *
	 */
	function validateEventData() {
		if (!empty($this->event_data['custom']['shop_invoked'])) {
            $this->displayMessage(['message' => 'Process already handled in the shop.']);
        }
		foreach ($this->mandatory as $category => $parameters) {
			if (empty($this->event_data[ $category ])) {
				$this->displayMessage([ 'message' => "Required parameter category($category) not received" ]);
			} elseif (!empty($parameters)) {
				foreach ($parameters as $parameter) {
					if (empty($this->event_data[ $category ][ $parameter ])) {
						$this->displayMessage([ 'message' => "Required parameter($parameter) in the category($category) not received" ]);
					} elseif (in_array($parameter, [ 'tid', 'parent_tid' ], true) && ! preg_match('/^\d{17}$/', $this->event_data[ $category ][ $parameter ])) {
						$this->displayMessage([ 'message' => "Invalid TID received in the category($category) not received $parameter" ]);
					}
				}
			}
		}
	}

	/**
	 * Validate checksum
	 *
	 */
	function validateCheckSum() {
		if (!empty($this->event_data['event']['checksum']) && ! empty($this->event_data['event']['tid']) && ! empty($this->event_data['event']['type'])
		&& !empty($this->event_data['result']['status'])) {
			$token_string = $this->event_data['event']['tid'] . $this->event_data['event']['type'] . $this->event_data['result']['status'];
			if (isset($this->event_data['transaction']['amount'])) {
			  $token_string .= $this->event_data['transaction']['amount'];
			}
			if (isset($this->event_data['transaction']['currency'])) {
			  $token_string .= $this->event_data['transaction']['currency'];
			}
			if (!empty(MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY)) {
			  $token_string .= strrev(MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY);
			}
			$generated_checksum = hash('sha256', $token_string);
			if ($generated_checksum != $this->event_data['event']['checksum']) {
				$this->displayMessage([ 'message' =>'While notifying some data has been changed. The hash check failed']);
			}
		}
	}

	/*
	 * Get order reference from the novalnet_callback_history table
	 *
	 * @return array
	 */
	function getOrderReference() {
		$this->order_details = $this->getOrderDetails();
		// If the order is not found in Novalnet DB and found in core table, it is communication failure
		if (empty($this->order_details['nn_trans_details']) && !empty($this->order_details['shop_order'])) {
			if ($this->event_data['transaction']['payment_type'] == 'ONLINE_TRANSFER_CREDIT') {
				$this->event_data['transaction'] ['tid'] = $this->parent_tid;
				$this->handleCommunicationFailure($this->order_details['shop_order_details']);
				$this->order_details = $this->getOrderDetails();
			} elseif ($this->event_data ['event'] ['type'] == 'PAYMENT') {
				$this->handleCommunicationFailure($this->order_details['shop_order_details']);
				$this->order_details = $this->getOrderDetails();
			} else {
				$this->displayMessage([ 'message' => 'Order reference not found in the shop' ]);
			}
		}
		return $this->order_details;
	}

	/*
	 * Get order details from novalnet transaction table and order table
	 *
	 * @return array
	 */
	function getOrderDetails(){
		$order_details = [];
		$novalnet_order_details = xtc_db_fetch_array(xtc_db_query("SELECT * FROM novalnet_transaction_detail WHERE tid = '".$this->parent_tid."'"));
		if(!empty($novalnet_order_details['payment_id'])){
			$transaction_details  = xtc_db_fetch_array(xtc_db_query("SELECT payment_type,order_amount, order_no, sum(callback_amount) AS callback_total_amount  FROM novalnet_callback_history WHERE original_tid = " . $this->parent_tid)); // Get transaction details from Novalnet tables
			$novalnet_order_details['callback_amount'] = (!empty($novalnet_order_details['callback_amount'])) ? ($novalnet_order_details['callback_amount'] + $transaction_details['callback_total_amount']) : $transaction_details['callback_total_amount'];
		}
		$order_number = !empty($novalnet_order_details['order_no']) ? $novalnet_order_details['order_no'] : $this->event_data['transaction']['order_no'];
		if(empty($order_number)) {
			$this->displayMessage(array( 'message' => 'Order reference not found in the shop' ));
		}
		$shop_order_details = xtc_db_fetch_array(xtc_db_query("SELECT payment_method,payment_class,orders_id, customers_id,orders_status,comments,language FROM ".TABLE_ORDERS." WHERE orders_id = '".$order_number."'"));

		$this->order_lang = xtc_db_fetch_array(xtc_db_query("SELECT * FROM " . TABLE_LANGUAGES . " WHERE directory = '" . $shop_order_details['language'] ."'"));
		$this->include_required_files($this->order_lang);

		$order_details['nn_trans_details'] =  $novalnet_order_details;
		$order_details['shop_order'] =  $order_number;
		$order_details['shop_order_details'] =  $shop_order_details;
		return $order_details;
	}

	/*
	 * Update the communication failure
	 *
	 * @param $order_details
	 * @return array
	 */
	function handleCommunicationFailure($order_details) {
		if ($this->event_data['result']['status'] == 'SUCCESS') {
			$order = new order($order_details['orders_id']);
			if (in_array($order_details['payment_class'], array('novalnet_googlepay','novalnet_applepay'))) {
				$order->info['comments'] .= PHP_EOL. NovalnetHelper::updateTransactionDetails($order_details['payment_class'], $this->event_data);
				NovalnetHelper::updateOrderStatus($order_details['orders_id'], $order->info['comments'], $this->event_data, $order_details['payment_class']);
			} else {
				$order->info['comments'] .= PHP_EOL. NovalnetHelper::updateTransactionDetails($order_details['payment_class'], $this->event_data);
				NovalnetHelper::updateOrderStatus($order_details['orders_id'], $order->info['comments'], $this->event_data, $order_details['payment_method']);
			}
			$this->sendMailToCustomer(array(
				'order_no' 	  => $order_details['orders_id'],
				'language' 	  => $this->order_lang['language'],
				'language_id' => $this->order_lang['languages_id'],
				'comments' 	  => nl2br($order->info['comments']),
			), true);
			$this->displayMessage(['message' => 'Novalnet transaction details are updated']);
			return;
		} else {
			$status_text = NovalnetHelper::getServerResponse($this->event_data['result']);
			$novalnet_data = [
				'tid' => (!empty($this->parent_tid)) ? $this->parent_tid: '',
			];
			NovalnetHelper::updateTempOrderFail($order_details['orders_id'], $novalnet_data['tid'], $status_text);
			$this->displayMessage(['message' => xtc_db_prepare_input($this->event_data['result']['status_text'])]);
			return;
		}
	}

	/**
	 * Handle transaction capture
	 *
	 */
	function handleTransactionCapture() {
		if ($this->order_details['nn_trans_details']['status'] != $this->event_data['transaction']['status']) {
			$novalnet_update_data = [
				'status' => $this->event_data['transaction']['status'],
			];
			if (in_array($this->order_details['shop_order_details']['payment_class'], array('novalnet_googlepay','novalnet_applepay'))) {
				$order_status = NovalnetHelper::getOrderStatus ($this->event_data['transaction']['status'], $this->order_details['shop_order_details']['payment_class']);
			} else {
				$order_status = NovalnetHelper::getOrderStatus ($this->event_data['transaction']['status'], $this->order_details['shop_order_details']['payment_method']);
			}
			$comments = PHP_EOL.PHP_EOL.sprintf(MODULE_PAYMENT_NOVALNET_TRANS_CONFIRM_SUCCESSFUL_MESSAGE, gmdate('d-m-Y'), gmdate('H:i:s')).PHP_EOL;
			if (in_array($this->order_details['shop_order_details']['payment_method'], array('novalnet_instalment_sepa', 'novalnet_instalment_invoice'))) {
				$total_amount = ($this->order_details['nn_trans_details']['amount'] < $this->event_data['transaction']['amount']) ? $this->event_data['transaction']['amount'] : $this->order_details['nn_trans_details']['amount'];
				$novalnet_update_data['instalment_cycle_details'] = NovalnetHelper::storeInstalmentdetails($this->event_data, $total_amount);
			}
			$order_comment = $comments;
			$order_comment .= NovalnetHelper::getTransactionDetails($this->event_data);
			if (in_array($this->order_details['shop_order_details']['payment_method'], array('novalnet_invoice', 'novalnet_guarantee_invoice', 'novalnet_instalment_invoice'))) {
				if (empty($this->event_data ['transaction']['bank_details'])) {
					if (!empty($this->order_details['nn_trans_details']['payment_id'])) {
						if(!empty($this->order_details['nn_trans_details']['payment_ref'])) {
							$bank_data = unserialize($this->order_details['nn_trans_details']['payment_ref']);
							$bank_details = array(
								'account_holder' => $bank_data['invoice_account_holder'],
								'iban' 			 => $bank_data['invoice_iban'],
								'bic' 			 => $bank_data['invoice_bic'],
								'bank_name' 	 => $bank_data['invoice_bankname'],
								'bank_place' 	 => $bank_data['invoice_bankplace'],
							);
						} else {
							$bank_data = unserialize($this->order_details['nn_trans_details']['payment_details']);
							$bank_details = array(
								'account_holder' => $bank_data['account_holder'],
								'iban' 			 => $bank_data['bank_iban'],
								'bic' 			 => $bank_data['bank_bic'],
								'bank_name' 	 => $bank_data['bank_name'],
								'bank_place' 	 => $bank_data['bank_city'],
							);
						}
					} else {
						$bank_details = json_decode($this->order_details['nn_trans_details']['payment_details'], true);
					}
					$this->event_data ['transaction']['bank_details'] = $bank_details;
				}
				$order_comment .= NovalnetHelper::getBankDetails($this->event_data, $this->event_data['transaction']['order_no']);
			}
			if (in_array($this->order_details['shop_order_details']['payment_method'], array('novalnet_instalment_sepa', 'novalnet_instalment_invoice'))) {
				$order_comment .= NovalnetHelper::formInstalmentPaymentReference($this->event_data);
			}
			$this->updateOrderStatusHistory($this->event_data['transaction']['order_no'], $order_status, $order_comment);
			$this->sendMailToCustomer(array(
				'comments' 		=> '<br>' . $order_comment,
				'mail_subject' 	=> sprintf(MODULE_PAYMENT_NOVALNET_ORDER_CAPTURE_MAIL_SUBJECT, $this->order_details['shop_order']),
				'order_no' 		=> $this->order_details['shop_order'],
				'language' 		=> $this->order_lang['language'],
				'language_id' 	=> $this->order_lang['languages_id'],
			), true);
			$this->updateNovalnetTransaction($novalnet_update_data, "tid='{$this->parent_tid}'");
			$this->sendWebhookMail($comments);
			$this->displayMessage([ 'message' => xtc_db_prepare_input($comments)]);
		}
	}

	/**
	 * Handle transaction cancel
	 *
	 */
	function handleTransactionCancel() {
		$comments = PHP_EOL.sprintf(MODULE_PAYMENT_NOVALNET_TRANS_DEACTIVATED_MESSAGE, gmdate('d-m-Y'), gmdate('H:i:s'));
		$novalnet_update_data = [
			'status' => $this->event_data['transaction']['status'],
		];
		$this->updateNovalnetTransaction($novalnet_update_data, "tid='{$this->parent_tid}'");
		$this->updateOrderStatusHistory($this->event_data['transaction']['order_no'], MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED, $comments);
		$this->sendWebhookMail($comments);
		$this->displayMessage([ 'message' => xtc_db_prepare_input($comments)]);
	}


	/**
	 * Handle transaction refund
	 *
	 */
	function handleTransactionRefund() {
		if (!empty($this->event_data['transaction']['refund']['amount'])) {
			$order_status_id = '';
			$comments = PHP_EOL . sprintf(MODULE_PAYMENT_NOVALNET_REFUND_PARENT_TID_MSG, $this->parent_tid, xtc_format_price_order(($this->event_data['transaction']['refund']['amount']/100), 1, $this->event_data['transaction']['currency']));
			if (!empty($this->event_data['transaction']['refund']['tid'])) {
				$comments .= PHP_EOL.sprintf(MODULE_PAYMENT_NOVALNET_REFUND_CHILD_TID_MSG, $this->event_data['transaction']['refund']['tid']);
			}
			$refund_amount = $this->event_data['transaction']['refund']['amount'];
			$refunded_amount = $this->order_details['nn_trans_details']['refund_amount'] + $refund_amount;
			$novalnet_update_data = array(
				'refund_amount' => $refunded_amount,
				'status'        => $this->event_data['transaction']['status'],
			);
			if(!empty($this->order_details['nn_trans_details']['payment_id'])) {
				$callback_refund  = xtc_db_fetch_array(xtc_db_query("SELECT sum(callback_amount) AS callback_refund_total  FROM novalnet_callback_history WHERE payment_type IN ('PRZELEWY24_REFUND', 'RETURN_DEBIT_SEPA', 'CREDITCARD_BOOKBACK', 'PAYPAL_BOOKBACK', 'CREDITCARD_CHARGEBACK', 'REFUND_BY_BANK_TRANSFER_EU', 'REVERSAL','CASHPAYMENT_REFUND','GUARANTEED_SEPA_BOOKBACK','GUARANTEED_INVOICE_BOOKBACK','INSTALMENT_SEPA_BOOKBACK','INSTALMENT_INVOICE_BOOKBACK') AND original_tid = " . $this->parent_tid));
				$refunded_amount = (!empty($callback_refund['callback_refund_total'])) ? ($refunded_amount + $callback_refund['callback_refund_total']) : $refunded_amount;
			}
			if (in_array($this->event_data['transaction']['payment_type'], array('INSTALMENT_INVOICE','INSTALMENT_DIRECT_DEBIT_SEPA'))) {
				$instalment_details = (!empty($this->order_details['nn_trans_details']['instalment_cycle_details'])) ? json_decode($this->order_details['nn_trans_details']['instalment_cycle_details'], true) : unserialize($this->order_details['nn_trans_details']['payment_details']);
				if(!empty($instalment_details)) {
					foreach($instalment_details as $cycle => $cycle_details){
						if(!empty($cycle_details['reference_tid']) && ($cycle_details['reference_tid'] == $this->parent_tid)) {
							$instalment_amount = (strpos((string)$instalment_details[$cycle]['instalment_cycle_amount'], '.')) ? $instalment_details[$cycle]['instalment_cycle_amount']*100 : $instalment_details[$cycle]['instalment_cycle_amount'];
							$instalment_amount = $instalment_amount - $refund_amount;
							$instalment_details[$cycle]['instalment_cycle_amount'] = $instalment_amount;
							if($instalment_details[$cycle]['instalment_cycle_amount'] <= 0) {
								$instalment_details[$cycle]['status'] = 'Refunded';
							}
						}
					}
				}
				$novalnet_update_data['instalment_cycle_details'] =json_encode($instalment_details);
			}
			if ($refunded_amount >= $this->order_details['nn_trans_details']['amount']) {
				$order_status_id = MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED;
			}
			$this->updateNovalnetTransaction($novalnet_update_data, "tid='{$this->parent_tid}'");
			$this->updateOrderStatusHistory($this->order_details['shop_order'], $order_status_id, $comments);
			$this->sendWebhookMail($comments);
			$this->displayMessage([ 'message' => xtc_db_prepare_input($comments)]);
		}
	}

	/**
	 * Handle chargeback
	 *
	 */
	function handleTransactionCredit() {
		$update_comments = true;
		$order_status = '';
		$comments = PHP_EOL.sprintf(NOVALNET_WEBHOOK_CREDIT_NOTE, $this->parent_tid, xtc_format_price_order(($this->event_data['transaction']['amount']/100), 1, $this->event_data['transaction']['currency']), gmdate('d-m-Y H:i:s'), $this->event_tid);
		$order_status = NovalnetHelper::getOrderStatus($this->event_data['transaction']['status'], $this->order_details['shop_order_details']['payment_method']);
		if (in_array($this->event_data['transaction']['payment_type'], ['INVOICE_CREDIT', 'CASHPAYMENT_CREDIT', 'MULTIBANCO_CREDIT'])) {


			$paid_amount = (!empty($this->order_details['nn_trans_details']['refund_amount'])) ? ((int)$this->order_details['nn_trans_details']['refund_amount'] + (int)$this->order_details['nn_trans_details']['callback_amount']) : $this->order_details['nn_trans_details']['callback_amount'];
			if ($paid_amount < $this->order_details['nn_trans_details']['amount']) {
				$total_paid_amount = $paid_amount + $this->event_data['transaction']['amount'];
				$update_data = array(
					'callback_amount' => $total_paid_amount
				);
				if ($total_paid_amount >= $this->order_details['nn_trans_details']['amount']) {
					$order_status = constant('MODULE_PAYMENT_'. strtoupper($this->order_details['shop_order_details']['payment_method']) .'_CALLBACK_ORDER_STATUS');
					$update_data['status'] = $this->event_data['transaction']['status'];
				}
				$this->updateNovalnetTransaction($update_data, "tid='{$this->parent_tid}'");
			} else {
				$update_comments = false;
				$comments = sprintf(('Callback script executed already'), gmdate('d-m-Y'), gmdate('H:i:s'));
			}
		}

		if($update_comments) {
			$this->updateOrderStatusHistory($this->order_details['shop_order'], $order_status, $comments);
			$this->sendWebhookMail($comments);
		}
		$this->displayMessage([ 'message' => xtc_db_prepare_input($comments)]);
	}

	/**
	 * Handle chargeback
	 *
	 */
	function handleChargeback() {
		if (($this->order_details['nn_trans_details']['status'] == 'CONFIRMED' || in_array($this->order_details['nn_trans_details']['status'], NovalnetHelper::$statuses['CONFIRMED'])) && !empty($this->event_data ['transaction'] ['amount'])) {
			$comments =PHP_EOL.sprintf(NOVALNET_WEBHOOK_CHARGEBACK_NOTE , $this->parent_tid, xtc_format_price_order(($this->event_data['transaction']['amount']/100), 1, $this->event_data['transaction']['currency']), gmdate('d.m.Y'), gmdate('H:i:s'), $this->event_tid);
			$this->updateOrderStatusHistory($this->event_data['transaction']['order_no'], '', $comments);
			$this->sendWebhookMail($comments);
			$this->displayMessage([ 'message' => xtc_db_prepare_input($comments)]);
		}
	}

	/**
	 * Handle instalment
	 *
	 */
	function handleInstalment() {
		$comment = '';
		if ($this->event_data['transaction']['status'] == 'CONFIRMED' && !empty($this->event_data['instalment']['cycles_executed'])
		&& in_array($this->event_data['transaction']['payment_type'], array('INSTALMENT_INVOICE','INSTALMENT_DIRECT_DEBIT_SEPA'))) {
			$instalment_details = (!empty($this->order_details['nn_trans_details']['instalment_cycle_details'])) ? json_decode($this->order_details['nn_trans_details']['instalment_cycle_details'], true) : unserialize($this->order_details['nn_trans_details']['payment_details']);
			$instalment = $this->event_data['instalment'];
			$cycle_index = $instalment['cycles_executed'] - 1;
			if (!empty($instalment)) {
				$instalment_details[$cycle_index]['next_instalment_date'] = (!empty($instalment['next_cycle_date'])) ? $instalment['next_cycle_date'] : '-';
				if (!empty($this->event_data['transaction']['tid'])) {
					$instalment_details[$cycle_index]['reference_tid'] = $this->event_data['transaction']['tid'];
					$instalment_details[$cycle_index]['status'] = 'Paid';
					$instalment_details[$cycle_index]['paid_date'] = date('Y-m-d H:i:s');
				}
			}
			if ($this->event_data['transaction']['payment_type'] == 'INSTALMENT_INVOICE' && empty($this->event_data ['transaction']['bank_details'])) {
				if (!empty($this->order_details['nn_trans_details']['payment_id'])) {
					if(!empty($this->order_details['nn_trans_details']['payment_ref'])) {
						$bank_data = unserialize($this->order_details['nn_trans_details']['payment_ref']);
						$this->event_data ['transaction']['bank_details'] = array(
							'account_holder' => $bank_data['invoice_account_holder'],
							'iban' => $bank_data['invoice_iban'],
							'bic' => $bank_data['invoice_bic'],
							'bank_name' => $bank_data['invoice_bankname'],
							'bank_place' => $bank_data['invoice_bankplace'],
						);
					}
				} else {
					$this->event_data ['transaction']['bank_details'] = json_decode($this->order_details['nn_trans_details']['payment_details'], true);
				}
			}
			$comment = PHP_EOL.sprintf(NOVALNET_WEBHOOK_NEW_INSTALMENT_NOTE, $this->parent_tid, xtc_format_price_order(($this->event_data['instalment']['cycle_amount']/100), 1, $this->event_data['transaction']['currency']), gmdate('d-m-Y'), $this->event_tid);
			$this->updateNovalnetTransaction(array('instalment_cycle_details' => json_encode($instalment_details)), "tid='{$this->parent_tid}'");
			$this->updateOrderStatusHistory($this->event_data['transaction']['order_no'], '', $comment);
			$transaction_comment = NovalnetHelper::updateTransactionDetails($this->order_details['shop_order_details']['payment_method'], $this->event_data,false);
			$this->sendMailToCustomer(array(
				'comments' 		=> '<br>' . $transaction_comment,
				'order_no' 		=> $this->order_details['shop_order'],
				'language' 		=> $this->order_lang['language'],
				'language_id' 	=> $this->order_lang['languages_id'],
			), true);
			$this->updateOrderStatusHistory($this->event_data['transaction']['order_no'], '', $transaction_comment);
			$this->sendWebhookMail($comment);
			$this->displayMessage([ 'message' => xtc_db_prepare_input($comment)]);
		}
	}

	/**
	 * Handle instalment cancel
	 *
	 */
	function handleInstalmentCancel() {
		$comments = ''; $novalnet_update_data = [];
		$order_status = '';
		if ($this->event_data['transaction']['status'] == 'CONFIRMED') {
			$instalment_details = (!empty($this->order_details['nn_trans_details']['instalment_cycle_details'])) ? json_decode($this->order_details['nn_trans_details']['instalment_cycle_details'], true) : unserialize($this->order_details['nn_trans_details']['payment_details']);
            if (!empty($instalment_details)) {
				$comments = PHP_EOL.sprintf(MODULE_PAYMENT_NOVALNET_INSTALMENT_CANCEL_ALLCYCLES_TEXT, $this->parent_tid, gmdate('d.m.Y'), xtc_format_price_order(((isset($this->event_data['transaction']['refund']['amount']) ? $this->event_data['transaction']['refund']['amount']/100 : 0)), 1, ($this->event_data['transaction']['refund']['currency'])));

				foreach ($instalment_details as $key => $instalment_details_data) {
					if ($instalment_details_data['status'] == 'Pending') {
						$instalment_details[$key]['status'] = 'Canceled';
					}
					if ($this->event_data['instalment']['cancel_type'] == 'ALL_CYCLES' && $instalment_details_data['status'] == 'Paid') {
							$instalment_details[$key]['status'] = 'Refunded';
							$order_status = 99;
					}
				}
				if (isset($this->event_data['instalment']['cancel_type']) && $this->event_data['instalment']['cancel_type'] == 'REMAINING_CYCLES') {
					$comments = PHP_EOL.sprintf(MODULE_PAYMENT_NOVALNET_INSTALMENT_CANCEL_REMAINING_CYCLES_TEXT, $this->parent_tid, gmdate('d.m.Y'));
				}
			}
            $novalnet_update_data = [
                'instalment_cycle_details' => !empty($instalment_details) ? json_encode($instalment_details) : '{}',
                'status'                   => 'DEACTIVATED',
            ];

		}
		$this->updateNovalnetTransaction($novalnet_update_data, "tid='{$this->parent_tid}'");
		$this->updateOrderStatusHistory($this->event_data['transaction']['order_no'], $order_status, $comments);
		$this->sendWebhookMail($comments);
		$this->displayMessage(['message' => xtc_db_prepare_input($comments)]);
	}

	/**
	 * Handle transaction update
	 *
	 */
	function handleTransactionUpdate() {
		if (in_array( $this->event_data['transaction']['status'], array('PENDING', 'ON_HOLD', 'CONFIRMED', 'DEACTIVATED'))) {
			$novalnet_update_data = [
				'status' => $this->event_data ['transaction']['status'],
			];
			$order_status = ''; $transaction_comments = '';
			if ($this->event_data['transaction']['status'] == 'DEACTIVATED') {
				$comments = PHP_EOL.sprintf(MODULE_PAYMENT_NOVALNET_TRANS_DEACTIVATED_MESSAGE, gmdate('d.m.Y'), gmdate('H:i:s'));
				$order_status = MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED;
			} else {
				if (in_array($this->order_details['nn_trans_details']['status'], array('PENDING', 'ON_HOLD' ), true)
				|| in_array($this->order_details['nn_trans_details']['status'], NovalnetHelper::$statuses['PENDING'])
				|| in_array($this->order_details['nn_trans_details']['status'], NovalnetHelper::$statuses['ON_HOLD'])
				) {
					if ($this->event_data['transaction']['status'] == 'ON_HOLD') {
						$order_status = MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_COMPLETE;
					} elseif ($this->event_data['transaction']['status'] == 'CONFIRMED') {
						if (in_array( $this->event_data['transaction']['payment_type'], array('INSTALMENT_INVOICE','INSTALMENT_DIRECT_DEBIT_SEPA'))) {
							if (empty($this->order_details['nn_trans_details']['instalment_cycle_details'])) {
								$total_amount = ($this->order_details['nn_trans_details']['amount'] < $this->event_data['transaction']['amount']) ? $this->event_data['transaction']['amount'] : $this->order_details['nn_trans_details']['amount'];
								$novalnet_update_data['instalment_cycle_details'] = NovalnetHelper::storeInstalmentdetails($this->event_data, $total_amount);
							}
						}
						$order_status = constant('MODULE_PAYMENT_' . strtoupper($this->order_details['shop_order_details']['payment_method']) . '_ORDER_STATUS');
						$novalnet_update_data['callback_amount'] = $this->order_details['nn_trans_details']['amount'];
					}
					// Reform the transaction comments.
					if (in_array($this->event_data['transaction']['payment_type'], array( 'INSTALMENT_INVOICE','GUARANTEED_INVOICE', 'INVOICE', 'PREPAYMENT'))) {
						if (empty($this->event_data ['transaction']['bank_details'])) {
							if (!empty($this->order_details['nn_trans_details']['payment_id'])) {
								if(!empty($this->order_details['nn_trans_details']['payment_ref'])) {
									$bank_data = unserialize($this->order_details['nn_trans_details']['payment_ref']);
									$this->event_data ['transaction']['bank_details'] = array(
										'account_holder' => $bank_data['invoice_account_holder'],
										'iban' => $bank_data['invoice_iban'],
										'bic' => $bank_data['invoice_bic'],
										'bank_name' => $bank_data['invoice_bankname'],
										'bank_place' => $bank_data['invoice_bankplace'],
									);
								} else {
									$bank_data = unserialize($this->order_details['nn_trans_details']['payment_details']);
									$this->event_data ['transaction']['bank_details'] = array(
										'account_holder' => $bank_data['account_holder'],
										'iban' => $bank_data['bank_iban'],
										'bic' => $bank_data['bank_bic'],
										'bank_name' => $bank_data['bank_name'],
										'bank_place' => $bank_data['bank_city'],
									);
								}
							} else {
								$this->event_data ['transaction']['bank_details'] = json_decode($this->order_details['nn_trans_details']['payment_details'], true);
							}
						}
						$transaction_comments .= NovalnetHelper::getBankDetails($this->event_data, $this->event_data['transaction']['order_no']);
					}
					if ('CASHPAYMENT' === $this->event_data ['transaction']['payment_type']) {
						$this->event_data ['transaction']['nearest_stores'] = json_decode($this->order_details['nn_trans_details']['payment_details'], true);
						$transaction_comments .= NovalnetHelper::getNearestStoreDetails($this->event_data);
					}
					if (in_array($this->event_data['transaction']['payment_type'], array( 'INSTALMENT_INVOICE','INSTALMENT_DIRECT_DEBIT_SEPA'))) {
						$transaction_comments .= NovalnetHelper::formInstalmentPaymentReference($this->event_data);
					} else {
						if ((int)$this->event_data['transaction']['amount'] != (int)$this->order_details['nn_trans_details']['amount']) {
							$novalnet_update_data['amount'] = $this->event_data['transaction']['amount'];
							if('CONFIRMED' === $this->event_data['transaction']['status']) {
								$novalnet_update_data['callback_amount'] = $this->event_data['transaction']['amount'];
							}
						}
					}

					if (empty($this->event_data['instalment']['cycle_amount'])) {
						$amount = $this->event_data['transaction']['amount'];
					} else {
						$amount = $this->event_data['instalment']['cycle_amount'];
					}

					if (! empty($this->event_data['transaction']['due_date'])) {
						$comments = PHP_EOL.sprintf(NOVALNET_WEBHOOK_TRANSACTION_UPDATE_NOTE_DUE_DATE, $this->event_tid, xtc_format_price_order(($amount/100), 1, $this->event_data['transaction']['currency']), $this->event_data['transaction']['due_date']);
					} else {
						$comments = PHP_EOL.sprintf(NOVALNET_WEBHOOK_TRANSACTION_UPDATE_NOTE, $this->event_tid, xtc_format_price_order(($amount/100), 1, $this->event_data['transaction']['currency']));
					}
				}
			}
			$this->updateNovalnetTransaction($novalnet_update_data, "tid='{$this->parent_tid}'");
			$this->updateOrderStatusHistory($this->order_details['shop_order'], $order_status, $comments);
			if (!empty($transaction_comments)) {
				$this->updateOrderStatusHistory($this->order_details['shop_order'], $order_status, $transaction_comments);
				$this->sendMailToCustomer(array(
					'comments' => '<br>' . $comments . '<br>' . $transaction_comments,
					'order_no' => $this->order_details['shop_order'],
					'language' => $this->order_lang['language'],
					'language_id' => $this->order_lang['languages_id'],
				), true);
			}
			$this->sendWebhookMail($comments);
			$this->displayMessage([ 'message' => xtc_db_prepare_input($comments)]);
		}
	}

	/**
	 * Handle Payment Reminder
	 *
	 */
	function handlePaymentReminder() {
		$comments = PHP_EOL.sprintf(NOVALNET_PAYMENT_REMINDER_NOTE , explode('_', $this->event_type)[2]);
		$this->updateOrderStatusHistory($this->event_data['transaction']['order_no'], '', $comments);
		$this->sendWebhookMail($comments);
		$this->displayMessage([ 'message' => xtc_db_prepare_input($comments)]);
	}

	/**
	 * Handle Collection Agency Submission
	 *
	 */
	function handleCollectionSubmission() {
		$comments = PHP_EOL.sprintf(NOVALNET_COLLECTION_SUBMISSION_NOTE , $this->event_data['collection']['reference']);
		$this->updateOrderStatusHistory($this->event_data['transaction']['order_no'], '', $comments);
		$this->sendWebhookMail($comments);
		$this->displayMessage([ 'message' => xtc_db_prepare_input($comments)]);
	}

	/**
	 * Print the Webhook messages.
	 *
	 * @param $message
	 *
	 * @return void
	 */
	function displayMessage($message) {
		echo json_encode($message);
		exit;
	}

	/**
	 * Update the details in Shop order status table.
	 *
	 * @param $order_id
	 * @param $order_status_id
	 * @param $comments
	 */
	function updateOrderStatusHistory($order_id, $order_status_id = '', $comments = '') {
		$datas_need_to_update = [];
		if ($order_status_id == '') {
			$order_status_id = $this->order_details['shop_order_details']['orders_status'];
		}

		$datas_need_to_update['orders_status'] = $order_status_id;
		xtc_db_perform(TABLE_ORDERS, $datas_need_to_update, "update", "orders_id='$order_id'");

		$oh_data_array = array(
			'orders_id'         => $order_id,
			'orders_status_id'  => $order_status_id,
			'date_added'        => date('Y-m-d H:i:s'),
			'customer_notified' => 1,
			'comments'          => xtc_db_prepare_input($comments . PHP_EOL)
		);
		xtc_db_perform(TABLE_ORDERS_STATUS_HISTORY,$oh_data_array);
	}
	/**
	 * To Form recurring order mail
	 *
	 * @param $datas array
	 * @param $update_comment boolean
	 *
	 */
	function sendMailToCustomer($datas, $update_comment=false){
		// GET MAIL CONTENTS ARRAY
		$order = new order($datas['order_no']);
		if($update_comment){
			$order->info['comments'] = $datas['comments'];
		}
		// GET WITHDRAWAL
		MainFactory::create_object('ShopContentContentControl');
		$t_mail_attachment_array = array();
		// GET E-MAIL LOGO
		$t_mail_logo = '';
		$t_logo_mail = MainFactory::create_object('GMLogoManager', array("gm_logo_mail"));
		if($t_logo_mail->logo_use == '1')
		{
			$t_mail_logo = $t_logo_mail->get_logo();
		}

		$coo_send_order_content_view = MainFactory::create_object('SendOrderContentView');
		$coo_send_order_content_view->set_('order', $order);
		if($datas['order_no'] != ''){
			$coo_send_order_content_view->set_('order_id', $datas['order_no']);
		}
		if($datas['language'] != ''){
			$coo_send_order_content_view->set_('language', $datas['language']);
			$coo_send_order_content_view->set_('language_id', $datas['language_id']);
		}
		$coo_send_order_content_view->set_('mail_logo', $t_mail_logo);
		$t_mail_content_array = $coo_send_order_content_view->get_mail_content_array();
		// GET HTML MAIL CONTENT
		$t_content_mail = $t_mail_content_array['html'];
		// GET TXT MAIL CONTENT
		$t_txt_mail = $t_mail_content_array['txt'];

		if (extension_loaded('intl')) {
			global $gx_version;
			if ($gx_version <= 'v4.3.1.0') { // If less than or equal to 4.3.1.0  
				// Use LocalizedDate class for formatting
				$formatted_date = utf8_encode_wrapper((string)new LocalizedDate(DATE_FORMAT_LONG, $this->order_lang['code']));
			} else {
				// Use DateFormatter::formatAsFullDate method for formatting
				$formatted_date = utf8_encode_wrapper(DateFormatter::formatAsFullDate(new DateTime(), new LanguageCode(new StringType($this->order_lang['code']))));
			}
		} else {
			// If intl extension is not loaded, use strftime to format the date
			$formatted_date = utf8_encode_wrapper(strftime(DATE_FORMAT_LONG));
		}
		
		$order_subject = (!empty($datas['mail_subject'])) ? $datas['mail_subject'] : sprintf(MODULE_PAYMENT_NOVALNET_ORDER_MAIL_SUBJECT,$datas['order_no'],$formatted_date,'');

		// send mail to admin
		// BOF GM_MOD:
		if(SEND_EMAILS == 'true')
		{
			// get the sender mail adress. e.g. Host Europe has problems with the customer mail adress.
			$from_email_address = $order->customer['email_address'];
			if(SEND_EMAIL_BY_BILLING_ADRESS == 'SHOP_OWNER') {
				$email_to = EMAIL_FROM;
			}
			xtc_php_mail($from_email_address,
						$order->customer['firstname'].' '.$order->customer['lastname'],
						EMAIL_BILLING_ADDRESS,
						STORE_NAME,
						EMAIL_BILLING_FORWARDING_STRING,
						$order->customer['email_address'],
						$order->customer['firstname'].' '.$order->customer['lastname'],
						$t_mail_attachment_array,
						'',
						$order_subject,
						$t_content_mail,
						$t_txt_mail
			);
		}
		// send mail to customer
		// BOF GM_MOD:
		if (SEND_EMAILS == 'true')
		{
			xtc_php_mail(EMAIL_BILLING_ADDRESS,
						EMAIL_BILLING_NAME,
						$order->customer['email_address'],
						$order->customer['firstname'].' '.$order->customer['lastname'],
						'',
						EMAIL_BILLING_REPLY_ADDRESS,
						EMAIL_BILLING_REPLY_ADDRESS_NAME,
						$t_mail_attachment_array,
						'',
						$order_subject,
						$t_content_mail,
						$t_txt_mail
			);
		}
	}

	/*
	 * Update the transaction details in Novalnet table
	 *
	 * @param $data
	 * @param $parameters
	 */
	function updateNovalnetTransaction($data, $parameters = '') {
		if ($parameters == '') {
			return false;
		}
		xtc_db_perform('novalnet_transaction_detail', $data, 'update', $parameters);
	}

	/**
	 * Send notification mail to Merchant
	 *
	 * @param $comments
	 */
	function sendWebhookMail($comments) {
		$message = xtc_db_prepare_input($comments);
		$email = NovalnetHelper::validateEmail(MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_TO);
		// Assign email to address
		$email_to = !empty($email) ? $email : STORE_OWNER_EMAIL_ADDRESS;
		$order_subject = 'Novalnet Callback Script Access Report - '.STORE_NAME;
		// Send mail
		xtc_php_mail(EMAIL_FROM, STORE_NAME, $email_to, STORE_OWNER, '', '', '', '', '', $order_subject, $message, '');
	}

	/**
	 * Include language file and helper file.
	 */
	function include_required_files($lang_data) {
		// include language
		require_once (DIR_WS_CLASSES.'language.php');
		$lang = new language($lang_data['code']);
		// include helper file after language files.
		require_once(DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php');
	         new NovalnetHelper($lang->language['directory']);
		return;
	}
}
new NovalnetWebhooks();
?>
