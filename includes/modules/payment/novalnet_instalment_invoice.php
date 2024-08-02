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
 * Script : novalnet_instalment_invoice.php
 *
 */
require_once(DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php');

class novalnet_instalment_invoice {
	var $code, $title, $enabled, $sort_order, $test_mode, $description, $info;

	/**
	 * Core Function : Constructor()
	 *
	 */
	function __construct() {
		global $order;
		$this->code       	= 'novalnet_instalment_invoice';
		$this->title      	= ((defined('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_TEXT_TITLE')) ? MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_TEXT_TITLE : '');
		$this->description  = ((defined('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_TEXT_TITLE')) ? MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_TEXT_TITLE : '');
		$this->info       	= ((defined('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_ENDCUSTOMER_INFO')) ? trim(strip_tags(MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_ENDCUSTOMER_INFO)) : '');
		$this->sort_order 	= defined('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_SORT_ORDER')
		&& MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_SORT_ORDER != '' ? trim(MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_SORT_ORDER) : 0;
		$this->enabled    	= ((defined('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_STATUS')
		&& MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_STATUS == 'true') ? true : false);
		$this->test_mode  	= ((defined('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_TEST_MODE')
		&& MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_TEST_MODE == 'true') ? true : false);
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
		if (($this->enabled == true) && ((int) MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_PAYMENT_ZONE > 0)) {
			$check_flag = false;
			$check_query = xtc_db_query("select zone_id from ".TABLE_ZONES_TO_GEO_ZONES." where geo_zone_id = '".MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_PAYMENT_ZONE."' and zone_country_id = '".$order->delivery['country']['id']."' order by zone_id");
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
		if (isset($_SESSION['novalnet']['payment_amount'])) {
			unset($_SESSION['novalnet']['payment_amount']);
		}
		$order_amount = isset($order->info) ? NovalnetHelper::getOrderAmount($order->info['total']) : 0;
		if (NovalnetHelper::checkMerchantCredentials() || strpos(MODULE_PAYMENT_INSTALLED, 'novalnet_config') === false) {
			if (!empty($_SESSION['payment']) && $_SESSION['payment'] == $this->code) {
				unset($_SESSION['payment']);
			}
			return false;
		}
		if (empty(NovalnetHelper::checkInstalmentConditions($order_amount, $this->code))) {
			return false;
		}
		// Get customer details
		$customer_details = NovalnetHelper::collectCustomerDobGenderFax($order->customer['email_address']);
		$selection = [
			'id'          => $this->code,
			'module'      => $this->title,
			'description' => NovalnetHelper::showPaymentDescription($this->code) . $this->info
										 . '<script src="https://cdn.novalnet.de/js/v2/NovalnetUtility.js"></script>'
										 . '<script type=text/javascript src="' . DIR_WS_CATALOG . 'ext/novalnet/js/novalnet_instalment.min.js" integrity="sha384-4+pAQsd6hvb5AKo0PU6xmxcHDmB8t6+cIUHPIQFVLcji4UldtYx+LA8LVnGc4T4z"></script>'
										 . '<input type="hidden" name="nn_instalment_birthdate_error" id="nn_instalment_birthdate_error" value="'.MODULE_PAYMENT_NOVALNET_GUARANTEE_DOB_ERROR_MSG.'">',
		];
		if (($order->billing['company'] == '') || ($order->billing['company'] != '' && MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_ALLOW_B2B == 'false')) {
			$selection['fields'][] = ['title' => '<span style="display:block;">'.MODULE_PAYMENT_GUARANTEE_DOB_FIELD.'</span>' ];
			$selection['fields'][] = ['title' => '<span id="novalnet_instalment_invoicebirthdate" style="display:block;">'.NovalnetHelper::getGuaranteeField($this->code.'birthdate', $customer_details).'</span>'] ;
		}

		//Instalment cycles
		$novalnet_instalment_cycle = defined('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_CYCLE') ? explode('|', MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_CYCLE) : array();
		$novalnet_recurring_period_cycle = '1m';
		$selection['fields'][] = ['title' => (sprintf(( '<nobr>'.MODULE_PAYMENT_NOVALNET_INSTALLMENT_TEXT.'</nobr>'), xtc_format_price_order($order_amount/100, 1, $order->info['currency'])))];
		$selection['fields'][] = [
					'field' => ''.xtc_draw_hidden_field('', MODULE_PAYMENT_NOVALNET_INSTALLMENT_CYCLES_FRONTEND , 'id="nn_cycles_frontend"').xtc_draw_hidden_field('', MODULE_PAYMENT_NOVALNET_INSTALLMENT_AMOUNT_FRONTEND , 'id="nn_amount_frontend"').xtc_draw_hidden_field('', MODULE_PAYMENT_NOVALNET_INSTALLMENT_FRONTEND , 'id="nn_installment_frontend"').
					xtc_draw_hidden_field('', $order->info['currency'], 'id="nn_installmnet_currency"').'<select class="form-control" name="novalnet_global_recurring_period_cycles_invoice" id ="novalnet_global_recurring_period_cycles_invoice" >'.NovalnetHelper::getInstalmentCycles($order_amount/100,$novalnet_instalment_cycle, $order->info['currency']).'</select><br><table id="novalnet_instalment_invoice_table" style="display:none; "><thead></thead><tbody></tbody></table><input type ="hidden" id="order_amount" name="order_amount" value="'.$order_amount/'100'.'"><input type ="hidden" id="nn_instalment_date" name="nn_instalment_date" value="'.NovalnetHelper::getInstalmentDate($novalnet_instalment_cycle,$novalnet_recurring_period_cycle).'">',
		];
		if (NovalnetHelper::getInstalmentCycles($order_amount/100,$novalnet_instalment_cycle, $order->info['currency']) == '0') {
			return false;
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
		global $order;
		$_SESSION['novalnet_instalment_invoicebirthdate'] = isset($_REQUEST['novalnet_instalment_invoicebirthdate']) ?  date('Y-m-d',strtotime($_REQUEST['novalnet_instalment_invoicebirthdate'])) : '';
		$_SESSION['novalnet_instalment_invoice_cycles'] = $_REQUEST['novalnet_global_recurring_period_cycles_invoice'];
		if (($order->billing['company'] == '') || ($order->billing['company'] != ''
		&& (defined('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_ALLOW_B2B') && MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_ALLOW_B2B == 'false'))) {
			if ($_REQUEST['novalnet_instalment_invoicebirthdate'] == '') {
				$error_message = MODULE_PAYMENT_GUARANTEE_PAYMENT_DOB_EMPTY_ERROR_MESSAGE;
			} else if ($_REQUEST['novalnet_instalment_invoicebirthdate'] != '') {
				$error_message = NovalnetHelper::dateOfBirth($_REQUEST['novalnet_instalment_invoicebirthdate']);
			}
		}
		if (isset($error_message) && $error_message != '') {
			xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code . '&error=' . urlencode($error_message), 'SSL', true, false));
		}
	}


	/**
	 * Core Function : confirmation()
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
		if (isset($order->info['deduction'])) {
			$_SESSION['novalnet_deduction'] = $order->info['total'];
		}
	}

	 /**
	 * Core Function : process_button()
	 *
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
		$merchant_data    = NovalnetHelper::getMerchantData();
		$customer_data    = NovalnetHelper::getCustomerData();
		$transaction_data = NovalnetHelper::getTransactionData();
		$transaction_data['transaction']['payment_type'] = NovalnetHelper::getPaymentName($this->code);
		$custom_data = NovalnetHelper::getCustomData();
		if (!empty($_SESSION['novalnet_instalment_invoicebirthdate']) &&  (empty($order->billing['company'])
		|| (defined('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_ALLOW_B2B') && MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_ALLOW_B2B == 'false'))){
			if(isset($customer_data['customer']['billing']['company'])) {
				unset($customer_data['customer']['billing']['company']);
			}
			$customer_data['customer']['birth_date'] = $_SESSION['novalnet_instalment_invoicebirthdate'];
		} elseif (!empty($order->billing['company'])) {
			$customer_data['customer']['billing']['company'] = $order->billing['company'];
		}
		$params = array_merge($merchant_data, $customer_data, $transaction_data, $custom_data);
		// Instalment payments params
		$params['instalment'] = [
			'interval' => '1m',
			'cycles'    => $_SESSION['novalnet_instalment_invoice_cycles'],
		];
		if(empty($_SESSION['novalnet']['payment_amount'])){
			$_SESSION['novalnet']['payment_amount'] =  $order->info['total'];
		}
		// To perform on-hold process
		if ((defined('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_AUTHENTICATE') && MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_AUTHENTICATE == 'true')
		&& (NovalnetHelper::getOrderAmount($_SESSION['novalnet']['payment_amount']) >= (defined('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_MANUAL_CHECK_LIMIT') ? MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_MANUAL_CHECK_LIMIT : ''))) {
			$response = NovalnetHelper::sendRequest($params, NovalnetHelper::get_action_endpoint('authorize'));
		} else {
			$response = NovalnetHelper::sendRequest($params, NovalnetHelper::get_action_endpoint('payment'));
		}
		if ($response['result']['status'] == 'SUCCESS') {
			$_SESSION['response'] = $response;
		} else {
			$error = (!empty($response['result']['status_text']) ? $response['result']['status_text'] : '');
			xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code . '&error=' . urlencode($error), 'SSL', true, false));
		}
		$order->info['comments'] .= NovalnetHelper::updateTransactionDetails($this->code, $_SESSION['response']);
		unset($_SESSION['novalnet_instalment_invoicebirthdate'], $_SESSION['novalnet_instalment_invoice_cycles']);
	}

	/**
	 * Core Function : after_process()
	 *
	 * Send params to Novalnet server (Note : if the payment uses curl request)
	 */
	function after_process() {
		global $order, $insert_id;
		if($_SESSION['response']['transaction']['payment_type'] == 'INSTALMENT_INVOICE' && (in_array($_SESSION['response']['transaction']['status'], array('CONFIRMED', 'ON_HOLD')))) {
			$order->info['comments'] = str_replace('###SHOPORDERNUMBER###', $insert_id, $order->info['comments']);
		}
		NovalnetHelper::updateOrderStatus($insert_id, $order->info['comments'], $_SESSION['response'], $this->code);
		$response = NovalnetHelper::sendTransactionUpdate($insert_id);
		unset($_SESSION['novalnet_deduction']);
		unset($_SESSION['novalnet']['payment_amount']);
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
			$check_query = xtc_db_query("select `value` from `gx_configurations` where `key` = 'configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_STATUS'");
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
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`,`last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_BASIC_REQ', 'false', '1', 'switcher',now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_STATUS','false',  '2', 'switcher',now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_TEST_MODE','false',  '3', 'switcher', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_CYCLE','2|3|4|5|6|7|8|9|10|11|12', '4', 'multiselect' , now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_MINIMUM_ORDER_AMOUNT', '1998',  '5',  now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_ALLOWED', '',  '6',  now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_ALLOW_B2B','true',  '7', 'switcher', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_PAYMENT_ZONE', '0',  '8', 'geo-zone',now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_AUTHENTICATE','false', '9' ,'switcher', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_MANUAL_CHECK_LIMIT', '', '10',  now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_ORDER_STATUS', '2',  '11', 'order-status', now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_SORT_ORDER', '13', '12', now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_ENDCUSTOMER_INFO', '', '13', now());");


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
		echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
			  <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.4/js/select2.min.js"></script>
				<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.4/css/select2.min.css" rel="stylesheet">';
		echo '<style> ul.select2-selection__rendered{ height: 60px; overflow: scroll !important;} </style>';
		$cycles_display = defined('MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_CYCLE') ? MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_CYCLE : '';
	    echo '<input type="hidden" name="novalnet_instalment_invoice_cycle[]" id="novalnet_instalment_invoice_cycle" value= "'.$cycles_display.'" />';
		return array(
			'configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_BASIC_REQ',
			'configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_STATUS',
			'configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_TEST_MODE',
			'configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_CYCLE',
			'configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_MINIMUM_ORDER_AMOUNT',
			'configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_ALLOW_B2B',
			'configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_ALLOWED',
			'configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_PAYMENT_ZONE',
			'configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_AUTHENTICATE',
			'configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_MANUAL_CHECK_LIMIT',
			'configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_ORDER_STATUS',
			'configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_SORT_ORDER',
			'configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_ENDCUSTOMER_INFO',
		);
	}
}
