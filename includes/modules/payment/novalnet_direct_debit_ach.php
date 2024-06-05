<?php
/**
 * Novalnet payment module
 * This module is used for real time processing of
 * Novalnet transaction of customers.
 *
 * @author     Novalnet AG
 * @copyright  Copyright (c) Novalnet
 * @license    https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link       https://www.novalnet.de
 *
 * Script : novalnet_direct_debit_ach.php
 *
 */
require_once(DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php');

class novalnet_direct_debit_ach {
	var $code, $title, $enabled, $sort_order, $test_mode, $info;

	/**
	 * Core Function : Constructor()
	 *
	 */
	function __construct() {
		global $order;
		$this->code       		= 'novalnet_direct_debit_ach';
		$this->title      		= ((defined('MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_TEXT_TITLE')) ? MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_TEXT_TITLE : '');
		$this->info       		= (defined('MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ENDCUSTOMER_INFO')) ? trim(strip_tags(MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ENDCUSTOMER_INFO)) : '';
		$this->sort_order 		= (defined('MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_SORT_ORDER') && MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_SORT_ORDER != '') ? trim(MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_SORT_ORDER) : 0;
		$this->enabled    		= ((defined('MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_STATUS') && MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_STATUS == 'true') ? true : false);
		$this->test_mode  		= ((defined('MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_TEST_MODE') && MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_TEST_MODE == 'true') ? true : false);
		if (is_object($order)) {
			$this->update_status();
		}
	}

	/**
	 * Core Function : update_status()
	 *
	 * check if zone is allowed to see module
	 */
	function update_status() {
		global $order;
		if (($this->enabled == true) && ((int) MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_PAYMENT_ZONE > 0)) {
			$check_flag = false;
			$check_query = xtc_db_query("select zone_id from ".TABLE_ZONES_TO_GEO_ZONES." where geo_zone_id = '".MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_PAYMENT_ZONE."' and zone_country_id = '".$order->delivery['country']['id']."' order by zone_id");
			while ($check = xtc_db_fetch_array($check_query)) {
				if ($check['zone_id'] < 1) {
					$check_flag = true;
					break;
				}
				elseif ($check['zone_id'] == $order->delivery['zone_id']) {
					$check_flag = true;
					break;
				}
			}
			if ($check_flag == false) {
				$this->enabled = false;
			}
		}
		return false;
	}

	/**
	 * Core Function : javascript_validation ()
	 *
	 * Javascript validation takes place
	 * @return boolean
	 */
	function javascript_validation() {
		return false;
	}

	/**
	 * Core Function : selection ()
	 *
	 * Display checkout form in chekout payment page
	 * @return array
	 */
		function selection() {
			global $order;
            if (NovalnetHelper::checkMerchantCredentials() || strpos(MODULE_PAYMENT_INSTALLED, 'novalnet_config') === false
            || !NovalnetHelper::hidePaymentVisibility($this->code)) {
                if (!empty($_SESSION['payment']) && $_SESSION['payment'] == $this->code) {
                    unset($_SESSION['payment']);
                }
                return false;
            }
			$selection = [
					'id'          => $this->code,
					'module'      => $this->title,
					'description' =>'<link rel="stylesheet" type="text/css" href="ext/novalnet/css/novalnet.min.css">'.'<script type="text/javascript" src="' . DIR_WS_CATALOG . 'ext/novalnet/js/novalnet_sepa.min.js"  integrity="sha384-0jd2jLGYkXr/YZXdZ2BKsmiq5yRxFfusF7mIpYmAptX8JLuG75eKGcbEeQRgLbhs"></script>'.'<script type=text/javascript src="' . DIR_WS_CATALOG . 'ext/novalnet/js/novalnet.min.js"   integrity="sha384-N+PgkQChyIKyAHpnkAP8N9pNMLqiNdCYOM7yVXIU16ZFUzR1OKVozFXfkKBQlXvo"></script>'. NovalnetHelper::showPaymentDescription($this->code) . $this->info,
			];

		   $payment_details = xtc_db_query("SELECT payment_details, id FROM novalnet_transaction_detail WHERE customer_id='" . xtc_db_input($_SESSION['customer_id']) . "' AND payment_details !='' AND payment_type ='DIRECT_DEBIT_ACH' AND status ='CONFIRMED' ORDER BY id DESC LIMIT 10");
		   $saved_card_details = [];
		   $count = 0;
		   while ($payment_detail = xtc_db_fetch_array($payment_details)) {
			   $decode_details = json_decode($payment_detail['payment_details'], true);
			   if (empty($decode_details['account_number']) && empty($decode_details['routing_number']) ) {
				   continue;
			   }
			   $decode_details['id'] = $payment_detail['id'];
			   $saved_card_details[$count] = $decode_details;
			   $count++;
		   };
		   unset($_SESSION['saved_card_details']);
		   if (defined('MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_TOKENIZATION') && MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_TOKENIZATION == 'true') {
			   if (!empty($saved_card_details)) {
				   $payment_data = [];
				   foreach ($saved_card_details as $key => $value) {
					   if (empty($value['account_number']) || in_array($value['account_number'], $payment_data)) {
						   continue;
					   }
					   if (empty($value)) {
						   continue;
					   }
					   if(!empty($_SESSION['saved_card_details']) || !empty($saved_card_details)){
						   unset($_SESSION['saved_card_details']);
						   $_SESSION['saved_card_details'] = $saved_card_details;
					   }
					   if ($key == 0){ $checked = "checked"; } else {$checked = "";}
					   $oneclick  = (!empty($saved_card_details)) ? '<nobr><input name = "novalnet_direct_debit_ach" type="radio" class ="novalnet_direct_debit_ach_saved_acc" value="'.$value['token'].'" '.$checked.' />'.sprintf((ACCOUNT_NO_ACH." ".'%s'),$value['account_number'])." " .sprintf((ROUTING_NO_ACH." ".'%s'),$value['routing_number']) .'&nbsp;&nbsp;<a href="javascript:void(0);" id="'.$value['id'].'" class="token" >'.(defined('MODULE_PAYMENT_NOVALNET_REMOVE_DUPLICATE_TOKEN') ? MODULE_PAYMENT_NOVALNET_REMOVE_DUPLICATE_TOKEN : '').'</a></nobr>' : '';
					   if(!empty($value['account_number'])){
						   $selection['fields'][] = [ 'title' =>   $oneclick ];
					   }
					   array_push($payment_data, $value['account_number']);
				   }
				   $selection['fields'][] = [ 'title' => '<nobr><input type="radio" name = "novalnet_direct_debit_ach"  id="novalnet_direct_debit_ach_new" value="new" />'.MODULE_PAYMENT_NOVALNET_SEPA_NEW_ACCOUNT_DETAILS.'</nobr>'];
			   }
			$selection['fields'][] = [
				'title' =>'<div class="novalnet-group" >'. MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ACCOUNT_HOLDER.'<br>'. xtc_draw_input_field('novalnet_ach_account_holder', $order->customer['firstname'] . ' ' . $order->customer['lastname'], 'id="novalnet_ach_account_holder" autocomplete="off" placeholder ="'.MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ACCOUNT_HOLDER.'" style="width:170%;" ').'</div>',
				'field' => '',

				];
			$selection['fields'][] = [
				'title' =>'<div class="novalnet-group" >'. MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ACCOUNT_NO.'<br>'. xtc_draw_input_field('novalnet_ach_account_no', '', 'id="novalnet_ach_account_no" autocomplete="off" placeholder ="'.MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ACCOUNT_NO.'" style="width:170%;" oninput="validateNumericInput(this)" ').'</div>',
				'field' => '',

				];
				$selection['fields'][] = [
					'title' => '<div class="novalnet-group" >'.'<nobr>'. MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ROUTING_NO.'</nobr>'.'<br>'. xtc_draw_input_field('novalnet_ach_routing_no', '', 'id="novalnet_ach_routing_no" autocomplete="off" placeholder ="'.MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ROUTING_NO.'" style="width:170%;" oninput="validateNumericInput(this)" ').'</div>',
					'field' => '',

					];

			   $selection['fields'][] = [
				   'title' =>'<div class="novalnet-group" >'. '<nobr>'.xtc_draw_checkbox_field('nn_ach_checkbox', 1 , false, 'id="novalnet_direct_debit_ach_onclick"')." ".MODULE_PAYMENT_NOVALNET_SEPA_SAVE_CARD_DETAILS.'</nobr>'.'</div>',
			   ];
		   } else {
				$selection['fields'][] = [
					'title' =>'<div class="novalnet-group" >'. MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ACCOUNT_HOLDER.'<br>'. xtc_draw_input_field('novalnet_ach_account_holder', $order->customer['firstname'] . ' ' . $order->customer['lastname'], 'id="novalnet_ach_account_holder" autocomplete="off" placeholder ="'.MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ACCOUNT_HOLDER.'" style="width:170%;"').'</div>',
					'field' => '',
				];
				$selection['fields'][] = [
					'title' => '<div class="novalnet-group" >'.MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ACCOUNT_NO.'<br>'. xtc_draw_input_field('novalnet_ach_account_no', '', 'id="novalnet_ach_account_no" autocomplete="off" placeholder ="'.MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ACCOUNT_NO.'" style="width:170%;" oninput="validateNumericInput(this)" ').'</div>',
					'field' => '',
				];
				$selection['fields'][] = [
					'title' =>'<div class="novalnet-group" >'.'<nobr>'. MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ROUTING_NO.'</nobr>'.'<br>'. xtc_draw_input_field('novalnet_ach_routing_no', '', 'id="novalnet_ach_routing_no" autocomplete="off" placeholder ="'.MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ROUTING_NO.'" style="width:170%;" oninput="validateNumericInput(this)" ').'</div>',
					'field' => '',
				];
			}
			return $selection;
		}


	/**
	 * Core Function : pre_confirmation_check ()
	 *
	 * Perform validations for post values
	 * @return boolean
	 */
	function pre_confirmation_check() {
		$_SESSION['novalnet_direct_debit_ach'] = $_REQUEST['novalnet_direct_debit_ach'];
		if(!empty($_SESSION['saved_card_details']) && $_SESSION['novalnet_direct_debit_ach'] != 'new' ) {
			$_SESSION['novalnet_direct_debit_ach_token'] = $_REQUEST['novalnet_direct_debit_ach'];
		}
		else if (!empty($_REQUEST['novalnet_ach_account_no']) && !empty($_REQUEST['novalnet_ach_routing_no'])) {
			$_SESSION['novalnet_ach_account_holder'] = $_REQUEST['novalnet_ach_account_holder'];
			$_SESSION['novalnet_ach_account_no']     = $_REQUEST['novalnet_ach_account_no'];
            $_SESSION['novalnet_ach_routing_no']     = $_REQUEST['novalnet_ach_routing_no'];
			if(!empty($_REQUEST['nn_ach_checkbox'])) {
				$_SESSION['novalnet_direct_debit_ach_create_token'] = $_REQUEST['nn_ach_checkbox'];
			}
		}
		else {
			xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code . '&error=' .MODULE_PAYMENT_NOVALNET_ACH_VALID_ACCOUNT_CREDENTIALS_ERROR, 'SSL', true, false));
		}
	}

	/**
	 * Core Function : confirmation ()
	 *
	 * Displays confirmation page
	 */
	function confirmation() {
        global $order;
		if($_SESSION['customers_status']['customers_status_show_price_tax'] === '0') {
			$_SESSION['novalnet']['payment_amount'] = ($order->info['total'] + (round($order->info['tax'], 2)));
		} else {
			$_SESSION['novalnet']['payment_amount'] = $order->info['total'];
		}
	}

	/**
	 * Core Function : process_button ()
	 *
	 * Payments redirects from shop to payment site (Note : if the payment is redirect)
	 */
	function process_button() {
		return false;
	}

	/**
	 * Core Function : before_process ()
	 *
	 * Send params to Novalnet server (Note : if the payment uses curl request)
	 */
	function before_process() {

		global $order;
		$merchant_data = NovalnetHelper::getMerchantData();
		$customer_data = NovalnetHelper::getCustomerData();
		$custom_data = NovalnetHelper::getCustomData();
		$transaction_data = NovalnetHelper::getTransactionData();
		$transaction_data['transaction']['payment_type'] = NovalnetHelper::getPaymentName($this->code);

		if (!empty($_SESSION['saved_card_details'])  && $_SESSION['novalnet_direct_debit_ach'] != 'new') {
			NovalnetHelper::getToeknizationDetails($this->code, $transaction_data);
		} else {
			if ($_SESSION['novalnet_direct_debit_ach'] == 'new' || !empty($_SESSION['novalnet_direct_debit_ach_create_token'])){
				NovalnetHelper::getToeknizationDetails($this->code, $transaction_data);
			}
			$transaction_data['transaction']['payment_data'] = [
				"account_holder" => $_SESSION['novalnet_ach_account_holder'],
				"account_number" => $_SESSION['novalnet_ach_account_no'],
				"routing_number" => $_SESSION['novalnet_ach_routing_no'] ,
			];
		}
		$params = array_merge($merchant_data, $customer_data, $transaction_data, $custom_data);
		if (!empty($params['transaction']['create_token'])) {
			$params['custom']['input1'] = 'store_payment_data';
			$params['custom']['inputval1'] = 1;
		}
		if (defined('MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_AUTHENTICATE') &&  MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_AUTHENTICATE == 'true') {
			$params['transaction']['amount'] = 0;
			$params['transaction']['create_token'] = 1;
		}
		$response = NovalnetHelper::sendRequest($params, NovalnetHelper::get_action_endpoint('payment'));
		if ($response['result']['status'] == 'SUCCESS') { // Success
			$_SESSION['response'] = $response;
		} else { // Failure
			$error = !empty($response['result']['status_text']) ? $response['result']['status_text'] : '';
			xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code . '&error=' . urlencode($error), 'SSL', true, false));
		}
		$order->info['comments'] .= NovalnetHelper::updateTransactionDetails($this->code, $_SESSION['response']);
		unset($_SESSION['novalnet']['payment_amount'], $_SESSION['novalnet_ach_account_no'],$_SESSION['novalnet_ach_account_holder'], $_SESSION['novalnet_ach_routing_no'], $_SESSION['novalnet_direct_debit_ach_create_token'],$_SESSION['novalnet_direct_debit_ach'],$_SESSION['novalnet_direct_debit_ach_token']);
	}


	 /**
	 * Core Function : after_process ()
	 *
	 * Send params to Novalnet server (Note : if the payment uses curl request)
	 */
	 function after_process() {
        global $order, $insert_id;
        NovalnetHelper::updateOrderStatus($insert_id, $order->info['comments'], $_SESSION['response'], $this->code);
        NovalnetHelper::sendTransactionUpdate($insert_id);
	}

	/**
	 * Core Function : get_error ()
	 *
	 * Show validation / error message
	 * @return array
	 */
	function get_error() {
		if ($_GET['error']) {
			$error = [
				'title' => $this->code,
				'error' => stripslashes(urldecode($_GET['error']))
			];
			return $error;
		}
	}

	/**
	 * Core Function : check ()
	 *
	 * Checks for payment installation status
	 * @return boolean
	 */
	function check() {
		if (!isset ($this->_check)) {
		$check_query = xtc_db_query("select `value` from `gx_configurations` where `key` = 'configuration/MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_STATUS'");
		$this->_check = xtc_db_num_rows($check_query);
		}
		return $this->_check;
	}

	/**
	 * Core Function : install ()
	 *
	 * Payment module installation
	 */
	function install() {
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_STATUS','false',  '1', 'switcher',now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_TEST_MODE','false',  '2', 'switcher', now())");
        xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ALLOWED', '',  '3',  now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_PAYMENT_ZONE', '0',  '3', 'geo-zone',now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_VISIBILITY_BY_AMOUNT', '',  '4',  now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_TOKENIZATION', 'true' , '5', 'switcher' , now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_AUTHENTICATE','false', '6' ,'switcher', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ORDER_STATUS', '2',  '7', 'order-status', now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`,  `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_SORT_ORDER', '4', '8',  now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`,  `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ENDCUSTOMER_INFO', '',  '9',  now())");
	}

	/**
	 * Core Function : remove ()
	 *
	 * Payment module uninstallation
	 */
	function remove() {
		xtc_db_query("delete from `gx_configurations` where `key` in ('" . implode("', '", $this->keys()) . "')");
	}

	/**
	 * Core Function : keys ()
	 *
	 * @return array keys to display in payment configuration (Backend)
	 */
	function keys() {

		$lang = (isset($_SESSION['language_code'])) ? strtoupper($_SESSION['language_code']) : 'DE';
		echo '<input type="hidden" name="lang_code" id="lang_code" value= "'.$lang.'" />';
		return array(
			'configuration/MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_STATUS',
			'configuration/MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_TEST_MODE',
            'configuration/MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ALLOWED',
			'configuration/MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_PAYMENT_ZONE',
			'configuration/MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_VISIBILITY_BY_AMOUNT',
			'configuration/MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_TOKENIZATION',
			'configuration/MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_AUTHENTICATE',
			'configuration/MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ORDER_STATUS',
			'configuration/MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_SORT_ORDER',
			'configuration/MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ENDCUSTOMER_INFO',
		);
	}


}
