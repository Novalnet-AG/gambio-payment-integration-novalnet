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
 * Script : novalnet_cc.php
 *
 */
require_once(DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php');
class novalnet_cc {
	var $code, $title, $enabled, $sort_order, $test_mode, $inline_form, $description, $info;

	/**
	 * Core Function : Constructor()
	 *
	 */
	function __construct() {
		global $order;
		$this->code        	= 'novalnet_cc';
		$this->title       	= ((defined('MODULE_PAYMENT_NOVALNET_CC_TEXT_TITLE')) ? MODULE_PAYMENT_NOVALNET_CC_TEXT_TITLE : '');
		$this->description  = ((defined('MODULE_PAYMENT_NOVALNET_CC_TEXT_TITLE')) ? MODULE_PAYMENT_NOVALNET_CC_TEXT_TITLE : '');
		$this->info        	= ((defined('MODULE_PAYMENT_NOVALNET_CC_ENDCUSTOMER_INFO')) ? trim(strip_tags(MODULE_PAYMENT_NOVALNET_CC_ENDCUSTOMER_INFO)) : '');
		$this->sort_order  	= defined('MODULE_PAYMENT_NOVALNET_CC_SORT_ORDER') && MODULE_PAYMENT_NOVALNET_CC_SORT_ORDER != '' ? trim(MODULE_PAYMENT_NOVALNET_CC_SORT_ORDER) : 0;
		$this->enabled     	= ((defined('MODULE_PAYMENT_NOVALNET_CC_STATUS') && MODULE_PAYMENT_NOVALNET_CC_STATUS == 'true') ? true : false);
		$this->test_mode   	= ((defined('MODULE_PAYMENT_NOVALNET_CC_TEST_MODE') && MODULE_PAYMENT_NOVALNET_CC_TEST_MODE == 'true') ? true : false);
		$this->inline_form 	= ((defined('MODULE_PAYMENT_NOVALNET_CC_INLINE_FORM') && MODULE_PAYMENT_NOVALNET_CC_INLINE_FORM == 'true') ? true : false);
		$this->tmpOrders   	= false;
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
		if (($this->enabled == true) && ((int) MODULE_PAYMENT_NOVALNET_CC_PAYMENT_ZONE > 0)) {
			$check_flag = false;
			$check_query = xtc_db_query("select zone_id from ".TABLE_ZONES_TO_GEO_ZONES." where geo_zone_id = '".MODULE_PAYMENT_NOVALNET_CC_PAYMENT_ZONE."' and zone_country_id = '".$order->delivery['country']['id']."' order by zone_id");
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
	 * Core Function : javascript_validation()
	 *
	 * Javascript validation takes place
	 * @return boolean
	 */
	function javascript_validation() {
		return false;
	}

	/**
	 * Core Function : selection()
	 *
	 * Display checkout form in chekout payment page
	 * @return array
	 */
	function selection() {
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
			'description' => NovalnetHelper::showPaymentDescription($this->code) . $this->info ,
		];
		$payment_details = xtc_db_query("SELECT payment_details, id, order_no FROM novalnet_transaction_detail WHERE customer_id='" . xtc_db_input($_SESSION['customer_id']) . "' AND payment_details !='' AND payment_type = 'CREDITCARD' AND status IN ('CONFIRMED', 'ON_HOLD') ORDER BY id DESC LIMIT 10");
		$saved_card_details = [];
		$count = 0;
		while ($payment_detail = xtc_db_fetch_array($payment_details)) {
			$decode_details = json_decode($payment_detail['payment_details'], true);
			$check_value = isset($decode_details['card_number']) ? $decode_details['card_brand'] . $decode_details['card_number'] . $decode_details['card_expiry_month'] . $decode_details['card_expiry_year'] : '';
			if (empty($check_value)) {
				continue;
			}
			$decode_details['id'] = $payment_detail['id'];
			$saved_card_details[$count] = $decode_details;
			$count++;
		}
		$iframe_display = 'block';
		$selection['fields'][] = [
			'title' => '<script src="https://cdn.novalnet.de/js/v2/NovalnetUtility.js"></script>
						<script src="' . DIR_WS_CATALOG . 'ext/novalnet/js/novalnet_cc.min.js' . '" type="text/javascript"   integrity="sha384-2itH0SLPIkr00itHWaWTUIQU6V5xKG5GHSMAL4gEKdWpYtUGblcH8jN2zK8LzYAp"></script>'
		];
		if (defined('MODULE_PAYMENT_NOVALNET_CC_TOKENIZATION') && MODULE_PAYMENT_NOVALNET_CC_TOKENIZATION == 'true') {
			if (!empty($saved_card_details)) {
				$payment_data = [];
				foreach ($saved_card_details as $key => $value) {
					$check_string = $value['card_brand'] . $value['card_number'] . $value['card_expiry_month'] . $value['card_expiry_year'];
					if (empty($check_string) || in_array($check_string, $payment_data)) {
						continue;
					}
					if (empty($value)) {
						continue;
					}
					if(!empty($_SESSION['saved_card_details']) || !empty($saved_card_details)){
						unset($_SESSION['saved_card_details']);
						$_SESSION['saved_card_details'] = $saved_card_details;
					}
					$last4_digit = (!empty($value['last_four'])) ? $value['last_four'] : substr($value['card_number'],-4);
					$month = $value['card_expiry_year'];
					$expiry_month = substr($month,-2);
					$icon    = xtc_href_link('/images/icons/payment/novalnet_cc_' . strtolower($value['card_brand']) . '.png', '', 'SSL', false, false, false, true, true);
					$brand   = "<img src='$icon' alt='" . $value['card_brand']. "'/>";
					if ($key == 0){ $checked = "checked"; } else {$checked = "";}
					$oneclick  = (!empty($saved_card_details)) ? '<nobr><input name = "novalnet_cc_token" type="radio" class ="novalnet_cc_saved_acc" value="'.$value['token'].'" '.$checked.' onclick/>'.sprintf(MODULE_PAYMENT_NOVALNET_CC_TOKEN_TEXT,$brand, $last4_digit, $value['card_expiry_month'], $expiry_month) . '&nbsp;&nbsp;<a href="javascript:void(0);" id="'.$value['id'].'" class="cc_token" >'.(defined('MODULE_PAYMENT_NOVALNET_REMOVE_DUPLICATE_TOKEN') ? MODULE_PAYMENT_NOVALNET_REMOVE_DUPLICATE_TOKEN : '').'</a></nobr>' : '';
					if(!empty($last4_digit) && !empty($month) && !empty($expiry_month) && !empty($brand) && !empty($icon)){
						$selection['fields'][] = [ 'title' =>   $oneclick ];
					}
					array_push($payment_data, $check_string);
				}
				$selection['fields'][] = [ 'title' => '<nobr><input name = "novalnet_cc_token" type="radio" id="novalnet_cc_new" data-reload="1" value="new" onclick/>'.MODULE_PAYMENT_NOVALNET_CC_NEW_ACCOUNT_DETAILS.'</nobr>'];
				$iframe_display = 'none';
			}
		}
		$selection['fields'][] = [
			'field' => '<iframe frameborder="0" id="novalnet_iframe" scrolling="no" style="display:'.$iframe_display.'"></iframe>
					<input type="hidden" id="nn_pan_hash" name="nn_pan_hash" value="" />
					<input type="hidden" id="nn_uniqueid" name="nn_uniqueid" value="" />
					<input type="hidden" id="do_redirect" name="do_redirect" value="" />'.$this->renderIframe(),
		];
		if ((defined('MODULE_PAYMENT_NOVALNET_CC_TOKENIZATION') && MODULE_PAYMENT_NOVALNET_CC_TOKENIZATION == 'true') && (!empty($saved_card_details))) {
			$selection['fields'][] = ['title' => '<nobr><div id="cc_save_card" style="display:none;">'.xtc_draw_checkbox_field('nn_cc_onclick', 1 , false, '').' '.MODULE_PAYMENT_NOVALNET_CC_SAVE_CARD_DETAILS.'</div></nobr>'];
		}else if((defined('MODULE_PAYMENT_NOVALNET_CC_TOKENIZATION') && MODULE_PAYMENT_NOVALNET_CC_TOKENIZATION == 'true') && (empty($saved_card_details))){
			$selection['fields'][] = ['title' => '<nobr><div id="cc_save_card" style="display:inline;">'.xtc_draw_checkbox_field('nn_cc_onclick', 1 , false, '').' '.MODULE_PAYMENT_NOVALNET_CC_SAVE_CARD_DETAILS.'</div></nobr>'];
		}
		return $selection;
	}

	/**
	 * Core Function : pre_confirmation_check()
	 *
	 * Perform validations for post values
	 * @return boolean
	 */
	function pre_confirmation_check() {
		if((isset($_REQUEST['novalnet_cc_token']) && $_REQUEST['novalnet_cc_token'] == 'new') || !empty($_SESSION['novalnet_cc_new'])){
			unset($_SESSION['novalnet_cc_new']);
			$_SESSION['novalnet_cc_new'] = $_REQUEST['novalnet_cc_token'];
		}
		if (!empty($_SESSION['novalnet_cc_token']) || !empty($_SESSION['novalnet_cc_create_token']) || !empty($_SESSION['nn_do_redirect'])) {
			unset($_SESSION['novalnet_cc_token'], $_SESSION['novalnet_cc_create_token'], $_SESSION['nn_do_redirect']);
		}

		if (!empty($_REQUEST['nn_pan_hash']) && !empty($_REQUEST['nn_uniqueid'])) {
			$_SESSION['nn_pan_hash'] = $_REQUEST['nn_pan_hash'];
			$_SESSION['nn_uniqueid'] = $_REQUEST['nn_uniqueid'];
			if ((defined('MODULE_PAYMENT_NOVALNET_CC_TOKENIZATION') && MODULE_PAYMENT_NOVALNET_CC_TOKENIZATION == 'true') && !empty($_REQUEST['nn_cc_onclick'])) {
				$_SESSION['novalnet_cc_create_token'] = $_REQUEST['nn_cc_onclick'];
			}
		} elseif (!empty($_REQUEST['novalnet_cc_token']) && $_REQUEST['novalnet_cc_token'] != 'new'
		&& (defined('MODULE_PAYMENT_NOVALNET_CC_TOKENIZATION') && MODULE_PAYMENT_NOVALNET_CC_TOKENIZATION == 'true')) {
			$_SESSION['novalnet_cc_token'] = $_REQUEST['novalnet_cc_token'];
		} else {
			xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code . '&error=' .MODULE_PAYMENT_NOVALNET_VALID_ACCOUNT_CREDENTIALS_ERROR, 'SSL', true, false));
		}
		if (!empty($_REQUEST['do_redirect']) && $_REQUEST['do_redirect'] == '1') {
			$_SESSION['nn_do_redirect'] = 1;
		}
	}

	/**
	 * Core Function : confirmation()
	 *
	 * Perform validations for post values
	 * @return boolean
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
	 * Core Function : process_button()
	 *
	 * Payments redirects from shop to payment site (Note : if the payment is redirect)
	 * @return boolean
	 */
	function process_button() {
		return false;
	}

	 /**
	 * Core Function : before_process()
	 *
	 * Send params to Novalnet server (Note : if the payment uses curl request)
	 */
	function before_process() {
		global $order;
		$post = $_REQUEST;
		if (isset($post['tid'])) {
			$session_order_id = (!empty($_SESSION['nn_tempOID'])) ? $_SESSION['nn_tempOID'] : $_SESSION['tmp_oID'];
			if ($post['status'] == 'SUCCESS') { // Success
				if (NovalnetHelper::validateCheckSum($post)) { // Checksum success
					NovalnetHelper::handleRedirectSuccessResponse($post, $session_order_id, $this->code);
				} else { // Checksum fail
					NovalnetHelper::processTempOrderFail($this->code, $session_order_id, $post, MODULE_PAYMENT_NOVALNET_ERROR_MSG);
				}
			} else { // Failure
				NovalnetHelper::processTempOrderFail($this->code, $session_order_id, $post);
			}
			$this->tmpOrders = true; // After return to shop set tmpOrders to true for stop update status again.
		} else {
			if (!empty($_SESSION['nn_do_redirect']) && $_SESSION['nn_do_redirect'] == 1) {
				$this->tmpOrders = true;
			} else {
				$response = $this->doPaymentCall();
				if ($response['result']['status'] == 'SUCCESS') {
					$_SESSION['response'] = $response;
					$order->info['comments'] .= NovalnetHelper::updateTransactionDetails($this->code, $response);
				} else {
					$error = (!empty($response['result']['status_text']) ? $response['result']['status_text'] : '');
					xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code . '&error=' . urlencode($error), 'SSL', true, false));
				}
			}
		}
	}

	/**
	 * Core Function : payment_action()
	 *
	 * Send params to Novalnet server
	 */
	function payment_action()
	{
		global $insert_id;
		if (!empty($_SESSION['nn_txn_secret']) || !empty($_SESSION['nn_tempOID'])) {
			unset($_SESSION['nn_txn_secret'], $_SESSION['nn_tempOID']);
		}
		$response = $this->doPaymentCall($insert_id);
		unset($_SESSION['nn_do_redirect']);
		if ($response['result']['status'] == 'SUCCESS' && !empty($response['result']['redirect_url'])) {
			$_SESSION['nn_txn_secret'] = $response['transaction']['txn_secret'];
			$_SESSION['nn_tempOID'] = $insert_id;
			xtc_redirect($response['result']['redirect_url']);
		} else {
			NovalnetHelper::processTempOrderFail($this->code, $insert_id, $response);
		}
	}

	/**
	 * Function : doPaymentCall()
	 *
	 * @return $data
	 */
	function doPaymentCall($order_no = '') {
		global $order;
		$merchant_data    = NovalnetHelper::getMerchantData();
		$customer_data    = NovalnetHelper::getCustomerData();
		if(empty($_SESSION['novalnet_cc_token'])){
			$transaction_data['transaction'] = array_merge(NovalnetHelper::getTransactionData()['transaction'], NovalnetHelper::getCardDetails($order_no)['transaction']);
		}else{
			$transaction_data = NovalnetHelper::getTransactionData();
		}
		$transaction_data['transaction']['payment_type'] = NovalnetHelper::getPaymentName($this->code);
		$custom_data	  = NovalnetHelper::getCustomData();
		if (!empty($_SESSION['saved_card_details']) && isset($_SESSION['novalnet_cc_new']) && $_SESSION['novalnet_cc_new'] != 'new') {
			NovalnetHelper::getToeknizationDetails($this->code, $transaction_data);
			$params = array_merge($merchant_data, $customer_data, $transaction_data, $custom_data);
			$params['transaction']['payment_data']['token'] = $transaction_data['transaction']['payment_data']['token'];
		}else{
			NovalnetHelper::getToeknizationDetails($this->code, $transaction_data);
			$params = array_merge($merchant_data, $customer_data, $transaction_data, $custom_data);
		}
		if (!empty($params['transaction']['create_token'])) {
			$params['custom']['input1'] = 'store_payment_data';
			$params['custom']['inputval1'] = 1;
		}
		if ((defined('MODULE_PAYMENT_NOVALNET_CC_AUTHENTICATE') && MODULE_PAYMENT_NOVALNET_CC_AUTHENTICATE == 'authorize')
		&& NovalnetHelper::getOrderAmount($_SESSION['novalnet']['payment_amount']) >= (defined('MODULE_PAYMENT_NOVALNET_CC_MANUAL_CHECK_LIMIT') ? MODULE_PAYMENT_NOVALNET_CC_MANUAL_CHECK_LIMIT : '')) { // Authorize transaction
			$data = NovalnetHelper::sendRequest($params, NovalnetHelper::get_action_endpoint('authorize'));
		} else { // Captue or Zero-amount transaction
			if (MODULE_PAYMENT_NOVALNET_CC_AUTHENTICATE == 'zero_amount') {
				$params['transaction']['amount'] = 0;
				$params['transaction']['create_token'] = 1;
			}
			$data = NovalnetHelper::sendRequest($params, NovalnetHelper::get_action_endpoint('payment'));
		}
		if ($data['result']['status'] != 'SUCCESS' ) {
			$error = (!empty($data['result']['status_text']) ? $data['result']['status_text'] : '');
			xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code . '&error=' . urlencode($error), 'SSL', true, false));
		}
		unset($_SESSION['novalnet']['payment_amount'], $_SESSION['payment_details'], $_SESSION['nn_pan_hash'], $_SESSION['nn_uniqueid'], $_SESSION['novalnet_cc_token'], $_SESSION['novalnet_cc_create_token'], $_SESSION['novalnet_cc_new'], $_SESSION['saved_card_details']);
		return $data;
	}

	/**
	 * Core Function : after_process()
	 *
	 * Send params to Novalnet server (Note : if the payment uses curl request)
	 * @param none
	 * @return void
	 */
	function after_process() {
		global $order, $insert_id;
		if(!$this->tmpOrders && !empty($_SESSION['response'])) {
			NovalnetHelper::updateOrderStatus($insert_id, $order->info['comments'], $_SESSION['response'], $this->code);
			NovalnetHelper::sendTransactionUpdate($insert_id);
		}
	}

	/**
	 * Core Function : get_error()
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
	 *
	 * Core Function : check()
	 *
	 * Checks for payment installation status
	 * @return boolean
	 */
	function check() {
		if (!isset ($this->_check)) {
		$check_query = xtc_db_query("select `value` from `gx_configurations` where `key` = 'configuration/MODULE_PAYMENT_NOVALNET_CC_STATUS'");
		$this->_check = xtc_db_num_rows($check_query);
		}
		return $this->_check;
	}

	 /**
	 *
	 * Core Function : install()
	 *
	 * Payment module installation
	 */
	function install() {
		$label = "font-weight:normal;font-family:Roboto,Arial,sans-serif;font-size:13px;line-height:1.42857";
		$input_field = "height:30px;border:1px solid #ccc;";
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_CC_STATUS','false',  '1', 'switcher',now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_CC_TEST_MODE','false',  '2', 'switcher', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_CC_ALLOWED', '',  '3',  now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_CC_PAYMENT_ZONE', '0',  '4', 'geo-zone',now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CC_VISIBILITY_BY_AMOUNT', '', '5', now());");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CC_INLINE_FORM','true', '6', 'switcher' ,now());");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CC_3D_SECURE_FORCE','false', '7', 'switcher' ,now());");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CC_TOKENIZATION', 'true' , '8', 'switcher' , now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CC_AUTHENTICATE', '', '9',  now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CC_MANUAL_CHECK_LIMIT', '', '10',  now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CC_ORDER_STATUS', '2',  '11', 'order-status', now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CC_CSS_STANDARD_STYLE', '.$label.', '12', now());");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CC_CSS_STANDARD_STYLE_INPUT', '.$input_field.', '17', now());");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CC_CSS_TEXT', '.$label.', '13', now());");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CC_SORT_ORDER', '1', '14',  now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CC_ENDCUSTOMER_INFO', '', '15', now());");
	}

	/**
	 * Core Function : remove()
	 *
	 * Payment module uninstallation
	 */
	function remove() {
		xtc_db_query("delete from `gx_configurations` where `key` in ('" . implode("', '", $this->keys()) . "')");
	}

	/**
	 * Core Function : keys()
	 *
	 * @return array keys to display in payment configuration (Backend)
	 * @return boolean
	 */
	function keys() {
		NovalnetHelper::includeAdminJS($this->code, true);
		$lang = (isset($_SESSION['language_code'])) ? strtoupper($_SESSION['language_code']) : 'DE';
		echo '<input type="hidden" name="lang_code" id="lang_code" value= "'.$lang.'" />';
		return array(
			'configuration/MODULE_PAYMENT_NOVALNET_CC_STATUS',
			'configuration/MODULE_PAYMENT_NOVALNET_CC_TEST_MODE',
			'configuration/MODULE_PAYMENT_NOVALNET_CC_ALLOWED',
			'configuration/MODULE_PAYMENT_NOVALNET_CC_PAYMENT_ZONE',
			'configuration/MODULE_PAYMENT_NOVALNET_CC_VISIBILITY_BY_AMOUNT',
			'configuration/MODULE_PAYMENT_NOVALNET_CC_INLINE_FORM',
			'configuration/MODULE_PAYMENT_NOVALNET_CC_3D_SECURE_FORCE',
			'configuration/MODULE_PAYMENT_NOVALNET_CC_TOKENIZATION',
			'configuration/MODULE_PAYMENT_NOVALNET_CC_AUTHENTICATE',
			'configuration/MODULE_PAYMENT_NOVALNET_CC_MANUAL_CHECK_LIMIT',
			'configuration/MODULE_PAYMENT_NOVALNET_CC_ORDER_STATUS',
			'configuration/MODULE_PAYMENT_NOVALNET_CC_CSS_STANDARD_STYLE',
			'configuration/MODULE_PAYMENT_NOVALNET_CC_CSS_STANDARD_STYLE_INPUT',
			'configuration/MODULE_PAYMENT_NOVALNET_CC_CSS_TEXT',
			'configuration/MODULE_PAYMENT_NOVALNET_CC_SORT_ORDER',
			'configuration/MODULE_PAYMENT_NOVALNET_CC_ENDCUSTOMER_INFO',
		);
	}

	/**
	 * To display Iframe in checkout page
	 *
	 * @return string
	 */
	function renderIframe() {
		$data = $this->getNovalnetCCFormDetails();
		$cc_hidden_field = "<input type='hidden' value='".$data."' id='nn_cc_iframe_data'>
		<input type='hidden' value='".MODULE_PAYMENT_NOVALNET_CC_CSS_STANDARD_STYLE."' id='nn_css_label'>
		<input type='hidden' value='".MODULE_PAYMENT_NOVALNET_CC_CSS_STANDARD_STYLE_INPUT."' id='nn_css_input'>
		<input type='hidden' value='".MODULE_PAYMENT_NOVALNET_CC_CSS_TEXT."' id='nn_css_text'>
		<input type='hidden' value='".NovalnetHelper::isBillingShippingsame()."' id='nn_shipping'>";
		return $cc_hidden_field;
	}

	/**
	 * To get Iframe content in checkout page
	 *
	 * @return json data
	 */
	function getNovalnetCCFormDetails() {
		global $order;
		$iframedata = array(
			'client_key'             => MODULE_PAYMENT_NOVALNET_CLIENT_KEY,
			'product_id'             => MODULE_PAYMENT_NOVALNET_PROJECT_ID,
			'inline_form'            => ((defined('MODULE_PAYMENT_NOVALNET_CC_INLINE_FORM') && MODULE_PAYMENT_NOVALNET_CC_INLINE_FORM == 'true') ? '1' : '0'),
			'test_mode'              => ((defined('MODULE_PAYMENT_NOVALNET_CC_TEST_MODE') && MODULE_PAYMENT_NOVALNET_CC_TEST_MODE == 'true') ? '1' : '0'),
			'enforce_3d'             => ((defined('MODULE_PAYMENT_NOVALNET_CC_3D_SECURE_FORCE') && MODULE_PAYMENT_NOVALNET_CC_3D_SECURE_FORCE == 'true') ? '1' : '0'),
			'first_name'             => $order->billing['firstname'],
			'last_name'              => $order->billing['lastname'],
			'email'                  => $order->customer['email_address'],
			'street'                 => $order->billing['street_address'],
			'city'                   => $order->billing['city'],
			'zip'                    => $order->customer['postcode'],
			'country_code'           => $order->billing['country']['iso_code_2'],
			'amount'                 => $order->info['total']*100,
			'currency'               => $order->info['currency'],
			'lang'                   => ((isset($_SESSION['language_code'])) ? strtoupper($_SESSION['language_code']) : 'DE'),
			'iframe_error'           => defined('MODULE_PAYMENT_NOVALNET_CC_IFRAME_ERROR') ? MODULE_PAYMENT_NOVALNET_CC_IFRAME_ERROR : '',
			'iframe_holder_label'    => defined('MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_HOLDER') ? MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_HOLDER : '',
			'iframe_holder_input'    => defined('MODULE_PAYMENT_NOVALNET_CC_IFRAME_HOLDER_INPUT_TEXT') ? MODULE_PAYMENT_NOVALNET_CC_IFRAME_HOLDER_INPUT_TEXT : '',
			'iframe_holder_error'    => defined('MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_HOLDER_ERROR') ? MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_HOLDER_ERROR : '',
			'iframe_number_label'   => defined('MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_NO') ? MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_NO : '',
			'iframe_number_input'   => defined('MODULE_PAYMENT_NOVALNET_CC_IFRAME_NUMBER_INPUT_TEXT') ? MODULE_PAYMENT_NOVALNET_CC_IFRAME_NUMBER_INPUT_TEXT : '',
			'iframe_number_error'   => defined('MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_NO_ERROR') ? MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_NO_ERROR : '',
			'iframe_expire_label'   => defined('MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_VALID_DATE') ? MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_VALID_DATE : '',
			'iframe_expire_error'   => defined('MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_VALID_DATE_ERROR') ? MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_VALID_DATE_ERROR : '',
			'iframe_cvc_label'      => defined('MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_CVC') ? MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_CVC : '',
			'iframe_cvc_input'      => defined('MODULE_PAYMENT_NOVALNET_CC_IFRAME_CVC_INPUT_TEXT') ? MODULE_PAYMENT_NOVALNET_CC_IFRAME_CVC_INPUT_TEXT : '',
			'iframe_cvc_error'      => defined('MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_CVC_ERROR') ? MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_CVC_ERROR : '',
		);
	  return json_encode($iframedata);
	}
}
