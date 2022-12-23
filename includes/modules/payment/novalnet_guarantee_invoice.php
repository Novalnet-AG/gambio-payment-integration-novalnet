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
 * Script : novalnet_guarantee_invoice.php
 *
 */
require_once(DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php');

class novalnet_guarantee_invoice {
	var $code, $title, $enabled, $sort_order, $test_mode;

	/**
	 * Core Function : Constructor()
	 *
	 */
	function __construct() {
		global $order;
		$this->code 	  = 'novalnet_guarantee_invoice';
		$this->title      = ((defined('MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_TEXT_TITLE')) ? MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_TEXT_TITLE : '');
		$this->info       = ((defined('MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_ENDCUSTOMER_INFO')) ? trim(strip_tags(MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_ENDCUSTOMER_INFO)) : '');
		$this->sort_order = defined('MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_SORT_ORDER') && trim(MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_SORT_ORDER) != '' ? trim(MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_SORT_ORDER) : 0;
		$this->enabled    = ((defined('MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_STATUS') && MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_STATUS == 'true') ? true : false);
		$this->test_mode  = ((defined('MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_TEST_MODE') && MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_TEST_MODE == 'true') ? true : false);
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
		if (($this->enabled == true) && ((int) MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_PAYMENT_ZONE > 0)) {
			$check_flag = false;
			$check_query = xtc_db_query("select zone_id from ".TABLE_ZONES_TO_GEO_ZONES." where geo_zone_id = '".MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_PAYMENT_ZONE."' and zone_country_id = '".$order->delivery['country']['id']."' order by zone_id");
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
		global $order;
		if (NovalnetHelper::checkMerchantCredentials() || strpos(MODULE_PAYMENT_INSTALLED, 'novalnet_config') === false) {
			if (!empty($_SESSION['payment']) && $_SESSION['payment'] == $this->code) {
				unset($_SESSION['payment']);
			}
			return false;
		}
		// Get customer details
		$customer_details = NovalnetHelper::collectCustomerDobGenderFax($order->customer['email_address']);
		
		if (defined('MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_STATUS') && MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_STATUS == 'true') {
			if ($this->proceedToGuranteePayment()) {
				$selection = [
					'id'          => $this->code,
					'module'      => defined('MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_TEXT_TITLE') ? MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_TEXT_TITLE : '',
					'description' => NovalnetHelper::showPaymentDescription($this->code) . $this->info,
				];
				$is_b2b = ((defined('MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_ALLOW_B2B') && MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_ALLOW_B2B == 'true') && !empty($order->billing['company'])) ? true : false;
				if (!$is_b2b) { 
					$selection['fields'][] = ['title' =>'<span id="novalnet_guarantee_invoice_dob_label" style="display:block;">' .MODULE_PAYMENT_GUARANTEE_DOB_FIELD.'</span>'.'<input type="hidden" name="nn_invoice_birthdate_error" id="nn_invoice_birthdate_error" value="'.MODULE_PAYMENT_NOVALNET_GUARANTEE_DOB_ERROR_MSG.'" onblur="return validateDateFormat(this)">'.'<script type=text/javascript src="' . DIR_WS_CATALOG . 'ext/novalnet/js/novalnet.js"></script>'. '<script src="https://cdn.novalnet.de/js/v2/NovalnetUtility.js"></script>', ];
					$selection['fields'][] = ['title' => NovalnetHelper::getGuaranteeField($this->code.'birthdate', $customer_details)];
				}
			} else {
				return false;
			}
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
		if (!empty($_REQUEST['novalnet_guarantee_invoicebirthdate'])) {
			$error_message = NovalnetHelper::dateOfBirth($_REQUEST['novalnet_guarantee_invoicebirthdate']);
			if (!empty($error_message) && (((defined('MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_FORCE') && MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_FORCE == 'true')
			&& (defined('MODULE_PAYMENT_NOVALNET_INVOICE_STATUS') && MODULE_PAYMENT_NOVALNET_INVOICE_STATUS == 'true')))) {
				$_SESSION['error_message'] = $error_message;
			} elseif (!empty($error_message)) {
				xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code . '&error=' . urlencode($error_message) .MODULE_PAYMENT_NOVALNET_VALID_ACCOUNT_CREDENTIALS_ERROR, 'SSL', true, false));
			}
			$_SESSION['novalnet_guarantee_invoicebirthdate'] = date("Y-m-d", strtotime($_REQUEST['novalnet_guarantee_invoicebirthdate']));
		} elseif (empty($order->billing['company'])) {
			$_SESSION['error_message'] = defined('MODULE_PAYMENT_NOVALNET_GUARANTEE_DOB_EMPTY_ERROR_MESSAGE') ? MODULE_PAYMENT_NOVALNET_GUARANTEE_DOB_EMPTY_ERROR_MESSAGE : '';
			xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code . '&error=' . urlencode($_SESSION['error_message']), 'SSL', true, false));
		} else {
			return false;
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
	 function before_process(){
		global $order;
		if (!empty($_SESSION['error_message']) && (defined('MODULE_PAYMENT_NOVALNET_INVOICE_STATUS') && MODULE_PAYMENT_NOVALNET_INVOICE_STATUS == 'true') && (defined('MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_FORCE') && MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_FORCE == 'true')) {
			$this->code = 'novalnet_invoice';
			$order->info['payment_method'] = 'novalnet_invoice';
			$order->info['payment_class'] = 'novalnet_invoice';
			unset($_SESSION['error_message']);
		}
		$merchant_data    = NovalnetHelper::getMerchantData();
		$customer_data    = NovalnetHelper::getCustomerData();
		$transaction_data = NovalnetHelper::getTransactionData();
		$transaction_data['transaction']['payment_type'] = NovalnetHelper::getPaymentName($this->code);
		$custom_data = NovalnetHelper::getCustomData();

		if (!empty($_SESSION['novalnet_guarantee_invoicebirthdate']) && (empty($order->billing['company'])
		|| (defined('MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_ALLOW_B2B') && MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_ALLOW_B2B == 'false'))) {
			if(isset($customer_data['customer']['billing']['company'])) {
				unset($customer_data['customer']['billing']['company']);
			}
			$customer_data['customer']['birth_date'] = $_SESSION['novalnet_guarantee_invoicebirthdate'];
		} elseif (!empty($order->billing['company'])) {
			$customer_data['customer']['billing']['company'] = $order->billing['company'];
		}
		$params = array_merge($merchant_data, $customer_data, $transaction_data, $custom_data);
		if($this->code == 'novalnet_invoice'){
			$due_date_in_days = defined('MODULE_PAYMENT_NOVALNET_INVOICE_DUE_DATE') ? MODULE_PAYMENT_NOVALNET_INVOICE_DUE_DATE : '';
			if (!empty ($due_date_in_days) && is_numeric($due_date_in_days)) {
				$params['transaction']['due_date'] = NovalnetHelper::getDueDate($due_date_in_days);
			}
			if ((defined('MODULE_PAYMENT_NOVALNET_INVOICE_AUTHENTICATE') && MODULE_PAYMENT_NOVALNET_INVOICE_AUTHENTICATE == 'true')&& (NovalnetHelper::getOrderAmount($order->info['total']) >= (defined('MODULE_PAYMENT_NOVALNET_INVOICE_MANUAL_CHECK_LIMIT') ? MODULE_PAYMENT_NOVALNET_INVOICE_MANUAL_CHECK_LIMIT : ''))) { // Authorize transaction
				$response = NovalnetHelper::sendRequest($params, NovalnetHelper::get_action_endpoint('authorize'));
			} else { // Captue transaction
				$response = NovalnetHelper::sendRequest($params, NovalnetHelper::get_action_endpoint('payment'));
			}
		}else if($this->code == 'novalnet_guarantee_invoice'){
			if ((defined('MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_AUTHENTICATE') && MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_AUTHENTICATE == 'true')
			&& (NovalnetHelper::getOrderAmount($order->info['total']) >= (defined('MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_MANUAL_CHECK_LIMIT') ? MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_MANUAL_CHECK_LIMIT : ''))) { // Authorize transaction
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
		unset($_SESSION['novalnet_guarantee_invoicebirthdate']);
	 }
	 
	/**
	 * Core Function : after_process()
	 *
	 * Send params to Novalnet server (Note : if the payment uses curl request)
	 */
	function after_process() {
		global $order, $insert_id;
		if (in_array($_SESSION['response']['transaction']['status'], array('CONFIRMED', 'ON_HOLD')) 
		 || ($_SESSION['response']['transaction']['status'] == 'PENDING' && $_SESSION['response']['transaction']['payment_type'] == 'INVOICE')
		) {
			$order->info['comments'] .= PHP_EOL. MODULE_PAYMENT_NOVALNET_PAYMENT_REFERENCE_TEXT .PHP_EOL;
			$order->info['comments'] .= sprintf(MODULE_PAYMENT_NOVALNET_PAYMENT_REFERENCE, 1, ('TID ' . $_SESSION['response']['transaction']['tid'])) . PHP_EOL;
			$order->info['comments'] .= sprintf(MODULE_PAYMENT_NOVALNET_PAYMENT_REFERENCE, 2, ('BNR-' . MODULE_PAYMENT_NOVALNET_PROJECT_ID . '-' . $insert_id)) . PHP_EOL;
		}
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
		$check_query = xtc_db_query("select `value` from `gx_configurations` where `key` = 'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_STATUS'");
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
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`,`last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE', 'false', '1', 'switcher', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_STATUS','false',  '2', 'switcher',now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_TEST_MODE','false',  '3', 'switcher', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_FORCE','true',  '4', 'switcher', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_MINIMUM_ORDER_AMOUNT', '',  '5',  now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_ALLOW_B2B','true',  '6', 'switcher', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_ALLOWED', '',  '7',  now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_PAYMENT_ZONE', '0',  '8', 'geo-zone',now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_AUTHENTICATE','false', '10' ,'switcher', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_MANUAL_CHECK_LIMIT', '', '11',  now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_ORDER_STATUS', '2',  '12', 'order-status', now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_SORT_ORDER', '7', '13', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_ENDCUSTOMER_INFO', '', '14', now());");
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
			'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE',
			'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_STATUS',
			'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_TEST_MODE',
			'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_FORCE',
			'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_MINIMUM_ORDER_AMOUNT',
			'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_ALLOW_B2B',
			'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_ALLOWED',
			'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_PAYMENT_ZONE',
			'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_AUTHENTICATE',
			'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_MANUAL_CHECK_LIMIT',
			'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_ORDER_STATUS',
			'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_SORT_ORDER',
			'configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_ENDCUSTOMER_INFO',
		);
	}

	/**
	* To Proceed and validate Guarantee payment basic requirements in checkout
	*
	* @return boolean
	*/
	function proceedToGuranteePayment() {
		global $order;
		$b2b = defined('MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_ALLOW_B2B') ? MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_ALLOW_B2B : '';
		$minimum_amount_gurantee    = trim(MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_MINIMUM_ORDER_AMOUNT) != '' ? MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_MINIMUM_ORDER_AMOUNT : '999';
		if (defined('MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_STATUS') && MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_STATUS == 'true') {
			$country_check = NovalnetHelper::checkGuaranteeCountries(strtoupper($order->billing['country']['iso_code_2']),$b2b, $order->billing['company']);
			if (NovalnetHelper::getOrderAmount($order->info['total']) >= $minimum_amount_gurantee && $country_check && $order->info['currency'] == 'EUR' && NovalnetHelper::isBillingShippingsame()) {
				return true;
			}
		  return false;
		}
	}
}

