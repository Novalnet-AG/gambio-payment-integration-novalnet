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
 * Script : novalnet_guarantee_sepa.php
 */
require_once(DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php');

class novalnet_guarantee_sepa {
	var $code, $title, $enabled, $sort_order, $test_mode;

	/**
	 * Core Function : Constructor()
	 *
	 */
	function __construct() {
		global $order;
		$this->code       = 'novalnet_guarantee_sepa';
		$this->title      = ((defined('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_TEXT_TITLE')) ? MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_TEXT_TITLE : '');
		$this->info       = ((defined('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_ENDCUSTOMER_INFO')) ? trim(strip_tags(MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_ENDCUSTOMER_INFO)) : '');
		$this->sort_order = defined('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_SORT_ORDER') && trim(MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_SORT_ORDER) != '' ? trim(MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_SORT_ORDER) : 0;
		$this->enabled    = ((defined('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_STATUS') && MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_STATUS == 'true') ? true : false);
		$this->test_mode  = ((defined('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_TEST_MODE') && MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_TEST_MODE == 'true') ? true : false);
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
		if (($this->enabled == true) && ((int) MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_PAYMENT_ZONE > 0)) {
			$check_flag = false;
			$check_query = xtc_db_query("select zone_id from ".TABLE_ZONES_TO_GEO_ZONES." where geo_zone_id = '".MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_PAYMENT_ZONE."' and zone_country_id = '".$order->delivery['country']['id']."' order by zone_id");
			while ($check = xtc_db_fetch_array($check_query)) {
				if ($check['zone_id'] < 1) {
					$check_flag = true;
					break;
				} elseif ($check['zone_id'] == $order->delivery['zone_id']) {
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
		global $order;
		if (NovalnetHelper::checkMerchantCredentials() || strpos(MODULE_PAYMENT_INSTALLED, 'novalnet_config') === false) {
			if (!empty($_SESSION['payment']) && $_SESSION['payment'] == $this->code) {
				unset($_SESSION['payment']);
			}
			return false;
		}
		if (!$this->proceedToGuranteePayment())
			return false;

		// Get customer details
		$customer_details = NovalnetHelper::collectCustomerDobGenderFax($order->customer['email_address']);
		$selection = [
			'id'          => $this->code,
			'module'      => MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_TEXT_TITLE,
			'description' => '<link rel="stylesheet" type="text/css" href="ext/novalnet/css/novalnet.css">' . NovalnetHelper::showSepaDescription($this->code) . $this->info
				.'<script type="text/javascript" src="' . DIR_WS_CATALOG . 'ext/novalnet/js/novalnet_sepa.js"></script>'
				.'<script src="https://cdn.novalnet.de/js/v2/NovalnetUtility.js"></script>',
			'module_cost' => $this->cost,
	   ];
	   if(MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_FORCE != 'true'){
		$selection = ['description' => '<input type="hidden" name="nn_sepa_birthdate_error" id="nn_sepa_birthdate_error" value="'.MODULE_PAYMENT_NOVALNET_GUARANTEE_DOB_ERROR_MSG.'" onblur="return validateDateFormat(this)">'];
	   }
	   $is_b2b = (!empty($order->billing['company']) && MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_ALLOW_B2B == 'true') ? true : false;
	   if (!$is_b2b || (MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_FORCE == 'false')) {
			$dob_field = [
				'title' => MODULE_PAYMENT_GUARANTEE_DOB_FIELD. NovalnetHelper::getGuaranteeField($this->code.'birthdate', $customer_details),
			];
	   }
		$bic_field = ['title' => '<div id="novalnet_sepa_bic" style="display:none;" ><span id="iban">BIC</span>' . xtc_draw_input_field('novalnet_sepa_bic', '', 'id="novalnet_sepa_bic_field" autocomplete="off" placeholder="BIC" onkeypress="return NovalnetUtility.formatBic(event);" onchange="return NovalnetUtility.formatBic(event);"') . '</div>'
		];
		$payment_details = xtc_db_query("SELECT payment_details, id FROM novalnet_transaction_detail WHERE customer_id='" . xtc_db_input($_SESSION['customer_id']) . "' AND payment_details !='' AND payment_type in ('DIRECT_DEBIT_SEPA', 'GUARANTEED_DIRECT_DEBIT_SEPA', 'INSTALMENT_DIRECT_DEBIT_SEPA') AND status IN ('CONFIRMED', 'ON_HOLD') ORDER BY id DESC LIMIT 10");
		$sepa_saved_card_details = [];
		$count = 0;
		while ($payment_detail = xtc_db_fetch_array($payment_details)) {
			$decode_details = json_decode($payment_detail['payment_details'], true);
			$decode_details['id'] = $payment_detail['id'];			
			$sepa_saved_card_details[$count] = $decode_details;
			$count++;
		}
		if (MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_TOKENIZATION == 'true') {
			if (!empty($sepa_saved_card_details) || !empty($_SESSION['sepa_saved_card_details'])) {
				unset($_SESSION['sepa_saved_card_details']);
				$payment_data = [];
				foreach ($sepa_saved_card_details as $key => $value) {
					if (in_array($value['iban'], $payment_data)) {
						continue;
					}
					if (empty($value)) {
						continue;
					}				
					$_SESSION['sepa_saved_card_details'] = $sepa_saved_card_details;
					$oneclick  = (!empty($sepa_saved_card_details)) ? '<nobr><input name = "novalnet_sepa_token" type="radio" class ="novalnet_sepa_saved_acc" value="'.$value['token'].'" onclick/>'.sprintf(('IBAN %s'),$value['iban']) . '&nbsp;&nbsp;<a href="javascript:void(0);" id="'.$value['id'].'" class="token" >Delete</a></nobr>' : '';
					if(!empty($value['iban'])){
						$selection['fields'][] = [ 'title' =>   $oneclick ];
					}
					array_push($payment_data, $value['iban']);
				}
				$selection['fields'][] = [ 'title' => '<nobr><input type="radio" name = "novalnet_sepa_token"  id="novalnet_sepa_new" value="new" onclick/>'.MODULE_PAYMENT_NOVALNET_SEPA_NEW_ACCOUNT_DETAILS.'</nobr>'];
				
				$selection['fields'][] = ['title' => '<span id="iban" style="display:none;">'.MODULE_PAYMENT_NOVALNET_SEPA_IBAN.'</span>'. xtc_draw_input_field('novalnet_sepa_iban', '', 'id="novalnet_sepa_iban_field" autocomplete="off" placeholder ="DE00 0000 0000 0000 0000 00" style="display:none;" onkeypress="return NovalnetUtility.formatIban(event,\'novalnet_sepa_bic\');" onkeyup="return NovalnetUtility.formatIban(event,\'novalnet_sepa_bic\');" onchange="return NovalnetUtility.formatIban(event,\'novalnet_sepa_bic\');"'),
				];
				$selection['fields'][] = $bic_field;
				$selection['fields'][] = $dob_field;
				$selection['fields'][] = ['title' => '<nobr><div id="sepa_save_card" style="display:none;">'.xtc_draw_checkbox_field('nn_sepa_checkbox', 1 , false, 'id="novalnet_sepa_onclick"')." ".MODULE_PAYMENT_NOVALNET_SEPA_SAVE_CARD_DETAILS.'</div></nobr>',
				];
			} else{
				$selection['fields'][] = [
					'title' => MODULE_PAYMENT_NOVALNET_SEPA_IBAN. xtc_draw_input_field('novalnet_sepa_iban', '', 'id="novalnet_sepa_iban_field" autocomplete="off" placeholder ="DE00 0000 0000 0000 0000 00" onkeypress="return NovalnetUtility.formatIban(event,\'novalnet_sepa_bic\');" onkeyup="return NovalnetUtility.formatIban(event,\'novalnet_sepa_bic\');" onchange="return NovalnetUtility.formatIban(event,\'novalnet_sepa_bic\');"'),
				];
				$selection['fields'][] = $bic_field;
				$selection['fields'][] = $dob_field;	
				$selection['fields'][] = ['title' => '<nobr><div id="sepa_save_card" style="display:block;">'.xtc_draw_checkbox_field('nn_sepa_checkbox', 1 , false, 'id="novalnet_sepa_onclick"')." ".MODULE_PAYMENT_NOVALNET_SEPA_SAVE_CARD_DETAILS.'</div></nobr>',
				];	
			}	
		} else if (MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_TOKENIZATION == 'false') {
			$selection['fields'][] = [
				'title' => MODULE_PAYMENT_NOVALNET_SEPA_IBAN. xtc_draw_input_field('novalnet_sepa_iban', '', 'id="novalnet_sepa_iban_field" autocomplete="off" placeholder ="DE00 0000 0000 0000 0000 00" onkeypress="return NovalnetUtility.formatIban(event,\'novalnet_sepa_bic\');" onkeyup="return NovalnetUtility.formatIban(event,\'novalnet_sepa_bic\');" onchange="return NovalnetUtility.formatIban(event,\'novalnet_sepa_bic\');"'),
			];
			$selection['fields'][] = $bic_field;
			$selection['fields'][] = $dob_field;
		}
		return $selection;
	}

	/**
	 * Core Function : confirmation()
	 *
	 * Perform validations for post values
	 * @return boolean
	 */
	function pre_confirmation_check() {
		global $order;
		if (!empty($_SESSION['novalnet_sepa_new'])) {
			unset($_SESSION['novalnet_sepa_new']);
		}
		if (isset($_REQUEST['novalnet_sepa_token']) && $_REQUEST['novalnet_sepa_token'] == 'new') {
			$_SESSION['novalnet_sepa_new'] = 'new';
		}
		if (isset($_SESSION['error_message'])) {
			unset($_SESSION['error_message']);
		}
		if (!empty($_SESSION['novalnet_guarantee_sepa_token']) || !empty($_SESSION['novalnet_guarantee_sepa_create_token'])) {
			unset($_SESSION['novalnet_guarantee_sepa_token']);
			unset($_SESSION['novalnet_guarantee_sepa_create_token']);
		}
		if (!empty($_SESSION['novalnet_guarantee_sepa_birthdate']) || !empty($_SESSION['novalnet_sepa_iban'])) {
			unset($_SESSION['novalnet_guarantee_sepa_birthdate']);
			unset($_SESSION['novalnet_sepa_iban']);
		}

		if (!empty($_REQUEST['novalnet_sepa_token']) && ($_REQUEST['novalnet_sepa_token'] != 'new')
		&& MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_TOKENIZATION == 'true') {
			$_SESSION['novalnet_guarantee_sepa_token'] = $_REQUEST['novalnet_sepa_token'];
		} else {
			if (!empty($_REQUEST['novalnet_sepa_iban'])) {
				$_SESSION['novalnet_sepa_iban'] = $_REQUEST['novalnet_sepa_iban'];
				$_SESSION['novalnet_sepa_bic'] = !empty($_REQUEST['novalnet_sepa_bic']) ? $_REQUEST['novalnet_sepa_bic'] : '';
				if (MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_TOKENIZATION == 'true' && !empty($_REQUEST['nn_sepa_checkbox'])
				&& $_REQUEST['nn_sepa_checkbox'] != 'new') {
					$_SESSION['novalnet_guarantee_sepa_create_token'] = $_REQUEST['nn_sepa_checkbox'];
				}
			} else {
				xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code . '&error=' .MODULE_PAYMENT_NOVALNET_VALID_ACCOUNT_CREDENTIALS_ERROR, 'SSL', true, false));
			}
		}
		if (!empty($_REQUEST['novalnet_guarantee_sepabirthdate'])) {
			$error_message = NovalnetHelper::dateOfBirth($_REQUEST['novalnet_guarantee_sepabirthdate']);
			if (!empty($error_message) && ((MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_FORCE == 'true'
			&& MODULE_PAYMENT_NOVALNET_SEPA_STATUS == 'true' ))) {
				$_SESSION['error_message'] = $error_message;
			} elseif (!empty($error_message)) {
				xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code . '&error=' . urlencode($error_message) .MODULE_PAYMENT_NOVALNET_VALID_ACCOUNT_CREDENTIALS_ERROR, 'SSL', true, false));
			}
			$_SESSION['novalnet_guarantee_sepa_birthdate'] = date("Y-m-d", strtotime($_REQUEST['novalnet_guarantee_sepabirthdate']));
		} elseif (empty($order->billing['company'])) {
			$_SESSION['error_message'] = MODULE_PAYMENT_NOVALNET_GUARANTEE_DOB_EMPTY_ERROR_MESSAGE;
			xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code . '&error=' . urlencode($_SESSION['error_message']), 'SSL', true, false));
		}
	}

	/**
	 * Core Function : process_button()
	 *
	 * Displays confirmation page
	 * @return boolean
	 */
	function confirmation() {
		return false;
	}

	/**
	 * Core Function : process_button()
	 *
	 * Displays confirmation page
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
		if (!empty($_SESSION['error_message']) && MODULE_PAYMENT_NOVALNET_SEPA_STATUS == 'true' && MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_FORCE == 'true') {
			$this->code = 'novalnet_sepa';
			$order->info['payment_method'] = 'novalnet_sepa';
			$order->info['payment_class'] = 'novalnet_sepa';
			if(!empty($_SESSION['novalnet_guarantee_sepa_token'])) {
				$_SESSION[$this->code . '_token'] = $_SESSION['novalnet_guarantee_sepa_token'];
				unset($_SESSION['novalnet_guarantee_sepa_token']);
			}
			if(!empty($_SESSION['novalnet_guarantee_sepa_create_token'])) {
				$_SESSION[$this->code . '_create_token'] = $_SESSION['novalnet_guarantee_sepa_create_token'];
				unset($_SESSION['novalnet_guarantee_sepa_create_token']);
			}
			unset($_SESSION['error_message']);
		}
		
		$merchant_data    = NovalnetHelper::getMerchantData();
		$customer_data    = NovalnetHelper::getCustomerData();
		$transaction_data = NovalnetHelper::getTransactionData();
		$custom_data 	  = NovalnetHelper::getCustomData();
		$transaction_data['transaction']['payment_type'] = NovalnetHelper::getPaymentName($this->code);
		if (!empty($_SESSION['novalnet_guarantee_sepa_birthdate']) &&  (empty($order->billing['company'])
		|| MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_ALLOW_B2B == 'false')) {
			if(isset($customer_data['customer']['billing']['company'])) {
				unset($customer_data['customer']['billing']['company']);
			}
			$customer_data['customer']['birth_date'] = $_SESSION['novalnet_guarantee_sepa_birthdate'];
		} elseif (!empty($order->billing['company'])) {
			$customer_data['customer']['billing']['company'] = $order->billing['company'];
		}
		// Add token data if it's enabled
		if (MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_TOKENIZATION == 'true') {
			if (!empty($_SESSION['sepa_saved_card_details']) && empty($_SESSION['novalnet_sepa_new'])) {
				NovalnetHelper::getToeknizationDetails($this->code, $transaction_data);		
				$params = array_merge($merchant_data, $customer_data, $transaction_data, $custom_data);
				$params['transaction']['payment_data']['token'] = $transaction_data['transaction']['payment_data']['token'];
			}else if ((isset($_SESSION['novalnet_sepa_new']) && $_SESSION['novalnet_sepa_new'] == 'new')) { 
				NovalnetHelper::getToeknizationDetails($this->code, $transaction_data);
				$transaction_data['transaction']['payment_data'] = NovalnetHelper::getAccountDetails()['transaction']['payment_data'];
				$params = array_merge($merchant_data, $customer_data, $transaction_data, $custom_data);
			} else {
				NovalnetHelper::getToeknizationDetails($this->code, $transaction_data);
				$transaction_data['transaction']['payment_data'] = NovalnetHelper::getAccountDetails()['transaction']['payment_data'];
				$params = array_merge($merchant_data, $customer_data, $transaction_data, $custom_data);
			}
		} else {
			NovalnetHelper::getToeknizationDetails($this->code, $transaction_data);
			$transaction_data['transaction']['payment_data'] = NovalnetHelper::getAccountDetails()['transaction']['payment_data'];
			$params = array_merge($merchant_data, $customer_data, $transaction_data, $custom_data);
		}
		$due_date_in_days = constant('MODULE_PAYMENT_' . strtoupper($this->code) . '_DUE_DATE');
		if (!empty ($due_date_in_days) && is_numeric($due_date_in_days)) {
			$params['transaction']['due_date'] = NovalnetHelper::getDueDate($due_date_in_days);
		}
		if($this->code == 'novalnet_sepa'){
			if (MODULE_PAYMENT_NOVALNET_SEPA_AUTHENTICATE == 'true' && (NovalnetHelper::getOrderAmount($order->info['total']) >= MODULE_PAYMENT_NOVALNET_SEPA_MANUAL_CHECK_LIMIT)) { // Authorize transaction
				$response = NovalnetHelper::sendRequest($params, NovalnetHelper::get_action_endpoint('authorize'));
			} else { // Captue transaction
				$response = NovalnetHelper::sendRequest($params, NovalnetHelper::get_action_endpoint('payment'));
			}
		}else if($this->code == 'novalnet_guarantee_sepa'){
			if (MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_AUTHENTICATE == 'true' && (NovalnetHelper::getOrderAmount($order->info['total']) >= MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_MANUAL_CHECK_LIMIT)) { // Authorize transaction
				$response = NovalnetHelper::sendRequest($params, NovalnetHelper::get_action_endpoint('authorize'));
			} else { // Captue transaction
				$response = NovalnetHelper::sendRequest($params, NovalnetHelper::get_action_endpoint('payment'));
			}
		}
		if ($response['result']['status'] == 'SUCCESS') {
			$_SESSION['response'] = $response;
		} else {
			$error = (!empty($response['result']['status_text']) ? $response['result']['status_text'] : '');
			xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code . '&error=' . urlencode($error), 'SSL', true, false));
		}
		$order->info['comments'] .= NovalnetHelper::updateTransactionDetails($this->code, $_SESSION['response']);
		unset($_SESSION['novalnet_sepa_iban']);
		unset($_SESSION[$this->code .'_token']);
		unset($_SESSION[$this->code . '_create_token']);
		unset($_SESSION['novalnet_guarantee_sepa_birthdate']);
		unset($_SESSION['novalnet_sepa_bic']);
		unset($_SESSION['novalnet_sepa_new']);
		unset($_SESSION['sepa_saved_card_details']);
	}

	/**
	 * Core Function : after_process()
	 *
	 * Send params to Novalnet server (Note : if the payment uses curl request)
	 */
	function after_process() {
		 global $order, $insert_id;
		 NovalnetHelper::updateOrderStatus($insert_id, $order->info['comments'], $_SESSION['response'], $this->code);
		 NovalnetHelper::sendTransactionUpdate($insert_id);
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
	 * Core Function : check()
	 *
	 * Checks for payment installation status
	 * @return boolean
	 */
	function check() {
		if (!isset ($this->_check)) {
		$check_query = xtc_db_query("select `value` from `gx_configurations` where `key` = 'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_STATUS'");
		$this->_check = xtc_db_num_rows($check_query);
		}
		return $this->_check;
	}

	/**
	 * Core Function : install()
	 *
	 * Payment module installation
	 */
	function install() {
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA', 'false', '1', 'switcher',now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_STATUS','false', '2', 'switcher',now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_TEST_MODE','false', '3', 'switcher', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_FORCE','true', '4', 'switcher', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_MINIMUM_ORDER_AMOUNT', '', '5', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_ALLOW_B2B','true', '6', 'switcher', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_ALLOWED', '', '7', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_PAYMENT_ZONE', '0', '8', 'geo-zone',now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_TOKENIZATION','true' , '9', 'switcher' , now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_DUE_DATE', '', '10', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_AUTHENTICATE','false', '11' ,'switcher', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_MANUAL_CHECK_LIMIT', '', '12', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_ORDER_STATUS', '2', '13', 'order-status', now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_SORT_ORDER', '8', '14', now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_ENDCUSTOMER_INFO', '', '15', now());");
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
	 */
	function keys() {
		NovalnetHelper::includeAdminJS($this->code, true);
		return array(
			'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA',
			'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_STATUS',
			'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_TEST_MODE',
			'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_FORCE',
			'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_MINIMUM_ORDER_AMOUNT',
			'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_ALLOW_B2B',
			'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_ALLOWED',
			'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_PAYMENT_ZONE',
			'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_TOKENIZATION',
			'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_DUE_DATE',
			'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_AUTHENTICATE',
			'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_MANUAL_CHECK_LIMIT',
			'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_ORDER_STATUS',
			'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_SORT_ORDER',
			'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_ENDCUSTOMER_INFO',
		);
	}

	/**
	* To Proceed and validate Guarantee payment basic requirements in checkout
	*
	* @return boolean
	*/
	function proceedToGuranteePayment() {
		global $order;
		$minimum_amount_gurantee    = trim(MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_MINIMUM_ORDER_AMOUNT) != '' ? MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_MINIMUM_ORDER_AMOUNT : '999';
		$country_check = NovalnetHelper::checkGuaranteeCountries(strtoupper($order->billing['country']['iso_code_2']),MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_ALLOW_B2B, $order->billing['company']);
		if (MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_STATUS == 'true') {
			if (NovalnetHelper::getOrderAmount($order->info['total']) >= $minimum_amount_gurantee && $country_check && $order->info['currency'] == 'EUR' && NovalnetHelper::isBillingShippingsame()) {
				return true;
			}
		  return false;
		}
	}
}

