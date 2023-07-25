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
 * Script : novalnet_cashpayment.php
 *
 */
require_once(DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php');

class novalnet_cashpayment
{
	var $code, $title, $enabled, $sort_order, $test_mode, $description;

	/**
	 * Core Function : Constructor()
	 *
	 */
	function __construct()
	{
		global $order;
		$this->code       	= 'novalnet_cashpayment';
		$this->title      	= ((defined('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_TEXT_TITLE')) ? MODULE_PAYMENT_NOVALNET_CASHPAYMENT_TEXT_TITLE : '');
		$this->description  = ((defined('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_TEXT_TITLE')) ? MODULE_PAYMENT_NOVALNET_CASHPAYMENT_TEXT_TITLE : '');
		$this->info       	= ((defined('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_ENDCUSTOMER_INFO')) ? trim(strip_tags(MODULE_PAYMENT_NOVALNET_CASHPAYMENT_ENDCUSTOMER_INFO)) : '');
		$this->sort_order 	= defined('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_SORT_ORDER') && trim(MODULE_PAYMENT_NOVALNET_CASHPAYMENT_SORT_ORDER) != ''? trim(MODULE_PAYMENT_NOVALNET_CASHPAYMENT_SORT_ORDER) : 0;
		$this->enabled    	= ((defined('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_STATUS') && MODULE_PAYMENT_NOVALNET_CASHPAYMENT_STATUS == 'true') ? true : false);
		$this->test_mode  	= ((defined('MODULE_PAYMENT_NOVALNET_CASHPAYMENT_TEST_MODE') && MODULE_PAYMENT_NOVALNET_CASHPAYMENT_TEST_MODE == 'true') ?true : false);
		if (is_object($order)) {
			$this->update_status();
		}
	}

	/**
	 * Core Function : update_status()
	 *
	 * check if zone is allowed to see module
	 */
	function update_status()
	{
		global $order;
		if (($this->enabled == true) && ((int) MODULE_PAYMENT_NOVALNET_CASHPAYMENT_PAYMENT_ZONE > 0)) {
			$check_flag = false;
			$check_query = xtc_db_query("select zone_id from ".TABLE_ZONES_TO_GEO_ZONES." where geo_zone_id = '".MODULE_PAYMENT_NOVALNET_CASHPAYMENT_PAYMENT_ZONE."' and zone_country_id = '".$order->delivery['country']['id']."' order by zone_id");
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
			'description' => NovalnetHelper::showPaymentDescription($this->code) . $this->info,
		];
		return $selection;
	}

	/**
	 * Core Function : pre_confirmation_check()
	 *
	 * Perform validations for post values
	 * @return boolean
	 */
	function pre_confirmation_check() {
		return false;
	}

	/**
	 * Core Function : process_button()
	 *
	 * Displays confirmation page
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
		$merchant_data     = NovalnetHelper::getMerchantData();
		$customer_data     = NovalnetHelper::getCustomerData();
		$transaction_data  = NovalnetHelper::getTransactionData();
		$custom_data       = NovalnetHelper::getCustomData();
		$transaction_data['transaction']['payment_type'] = NovalnetHelper::getPaymentName($this->code);
		$due_date_in_days = MODULE_PAYMENT_NOVALNET_CASHPAYMENT_DUE_DATE;
		if (!empty ($due_date_in_days) && is_numeric($due_date_in_days)) {
			$transaction_data['transaction']['due_date'] = NovalnetHelper::getDueDate($due_date_in_days);
		}
		$params = array_merge($merchant_data, $customer_data, $transaction_data, $custom_data);
		$response   = NovalnetHelper::sendRequest($params, NovalnetHelper::get_action_endpoint('payment'));
		if ($response['result']['status'] == 'SUCCESS') {
			$_SESSION['response'] = $response;
		} else {
			$error = (!empty($response['result']['status_text']) ? $response['result']['status_text'] : '');
			xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code . '&error=' . urlencode($error), 'SSL', true, false));
		}
		unset($_SESSION['novalnet']['payment_amount']);
		$order->info['comments'] .= NovalnetHelper::updateTransactionDetails($this->code, $_SESSION['response']);
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
			$check_query = xtc_db_query("select `value` from `gx_configurations` where `key` = 'configuration/MODULE_PAYMENT_NOVALNET_CASHPAYMENT_STATUS'");
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
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CASHPAYMENT_STATUS','false', '1', 'switcher',now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CASHPAYMENT_TEST_MODE','false', '2', 'switcher', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CASHPAYMENT_ALLOWED', '', '3', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CASHPAYMENT_PAYMENT_ZONE', '0', '4', 'geo-zone',now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CASHPAYMENT_VISIBILITY_BY_AMOUNT', '0', '5', now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CASHPAYMENT_DUE_DATE', '', '6', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CASHPAYMENT_ORDER_STATUS', '1', '7', 'order-status', now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CASHPAYMENT_CALLBACK_ORDER_STATUS', '2', '8', 'order-status', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CASHPAYMENT_SORT_ORDER', '14', '9', now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CASHPAYMENT_ENDCUSTOMER_INFO', '', '10', now())");
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
		return array(
			'configuration/MODULE_PAYMENT_NOVALNET_CASHPAYMENT_STATUS',
			'configuration/MODULE_PAYMENT_NOVALNET_CASHPAYMENT_TEST_MODE',
			'configuration/MODULE_PAYMENT_NOVALNET_CASHPAYMENT_ALLOWED',
			'configuration/MODULE_PAYMENT_NOVALNET_CASHPAYMENT_PAYMENT_ZONE',
			'configuration/MODULE_PAYMENT_NOVALNET_CASHPAYMENT_VISIBILITY_BY_AMOUNT',
			'configuration/MODULE_PAYMENT_NOVALNET_CASHPAYMENT_DUE_DATE',
			'configuration/MODULE_PAYMENT_NOVALNET_CASHPAYMENT_ORDER_STATUS',
			'configuration/MODULE_PAYMENT_NOVALNET_CASHPAYMENT_CALLBACK_ORDER_STATUS',
			'configuration/MODULE_PAYMENT_NOVALNET_CASHPAYMENT_SORT_ORDER',
			'configuration/MODULE_PAYMENT_NOVALNET_CASHPAYMENT_ENDCUSTOMER_INFO',
		);
	}
}
