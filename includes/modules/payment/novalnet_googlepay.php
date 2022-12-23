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
 * Script : novalnet_googlepay.php
 *
 */
require_once(DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php');

class novalnet_googlepay {
	var $code, $title, $enabled, $sort_order, $test_mode;

	/**
	 * Core Function : Constructor()
	 *
	 */
	function __construct() {
		global $order;
		$this->code = 'novalnet_googlepay';
		$this->title = ((defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_TEXT_TITLE')) ? MODULE_PAYMENT_NOVALNET_GOOGLEPAY_TEXT_TITLE : '');
		$this->sort_order   = defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_SORT_ORDER') && trim(MODULE_PAYMENT_NOVALNET_GOOGLEPAY_SORT_ORDER) != '' ? trim(MODULE_PAYMENT_NOVALNET_GOOGLEPAY_SORT_ORDER) : 0;
		$this->info      	= (defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ENDCUSTOMER_INFO') && MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ENDCUSTOMER_INFO != '') ? trim(strip_tags(MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ENDCUSTOMER_INFO)) : '';
		$this->enabled      = ((defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_STATUS') && MODULE_PAYMENT_NOVALNET_GOOGLEPAY_STATUS == 'true') ? true : false);
		$this->test_mode    = ((defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_TEST_MODE') && MODULE_PAYMENT_NOVALNET_GOOGLEPAY_TEST_MODE == 'true') ? true : false);
		$this->tmpOrders = false;
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
		if (($this->enabled == true) && ((int) MODULE_PAYMENT_NOVALNET_GOOGLEPAY_PAYMENT_ZONE > 0)) {
			$check_flag = false;
			$check_query = xtc_db_query("select zone_id from ".TABLE_ZONES_TO_GEO_ZONES." where geo_zone_id = '".MODULE_PAYMENT_NOVALNET_GOOGLEPAY_PAYMENT_ZONE."' and zone_country_id = '".$order->delivery['country']['id']."' order by zone_id");

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
		if (NovalnetHelper::checkMerchantCredentials($this->code) || strpos(MODULE_PAYMENT_INSTALLED, 'novalnet_config') === false || !NovalnetHelper::hidePaymentVisibility($this->code)) {
			if (!empty($_SESSION['payment']) && $_SESSION['payment'] == $this->code) {
				unset($_SESSION['payment']);
			}
			return false;
		}
		$display_page = defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_DISPLAY') ? explode('|', MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_DISPLAY) : array();
		if ($this->enabled === true && in_array('checkout page',$display_page)) {
					$selection = [
							'id'          => $this->code,
							'module'      => $this->title,
							'description' => '<link rel="stylesheet" type="text/css" href="ext/novalnet/css/novalnet.css">' .NovalnetHelper::showPaymentDescription($this->code) . $this->info
											 .'<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>'
											 .'<script src="https://cdn.novalnet.de/js/v2/NovalnetUtility.js"></script>'
											 .'<script src="https://cdn.novalnet.de/js/v3/payment.js"></script>'
											  .'<script src="' . DIR_WS_CATALOG . 'ext/novalnet/js/novalnet_wallet.js" type="text/javascript"></script>'.$this->getParam(),
						 ];

					$selection['fields'][] =  ['title' => '
												<div id="novalnet_googlepay_wallet_div" style="display:none" >
												<div id="novalnet_googlepay_wallet_button" ></div>
												<input type="hidden" id="nn_google_wallet" name="nn_google_wallet" value="" />
												<input type="hidden" id="nn_wallet_doredirect" name="nn_wallet_doredirect" value="" />
													<script type="text/javascript">
													novalnet_checkout_page("novalnet_googlepay");
													</script></div>'];
			return $selection;
		}
	}

	/**
	 * Core Function : pre_confirmation_check()
	 *
	 * Perform validations for post values
	 * @return boolean
	 */
	function pre_confirmation_check() {
		if (!empty($_REQUEST['nn_google_wallet']) || !empty($_SESSION['wallet_token'])) {
			unset($_SESSION['wallet_token']);
			$_SESSION['wallet_token'] = $_REQUEST['nn_google_wallet'];
		}
		if (!empty($_REQUEST['nn_wallet_doredirect']) || !empty($_SESSION['nn_wallet_doredirect'])) {
			unset($_SESSION['nn_wallet_doredirect']);
			$_SESSION['nn_wallet_doredirect'] = $_REQUEST['nn_wallet_doredirect'];
		}
		return false;
	}

	/**
	 * Core Function : confirmation()
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
	 * Payments redirects from shop to payment site
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
		if (!empty($_SESSION['received_data'])) {
		$receivedData = $_SESSION['received_data'];
			$_SESSION['nn_wallet_doredirect'] = $receivedData['transaction']['doRedirect'];
		}	
		$post = $_REQUEST;			
		if (isset($post['tid'])) {
			if ($post['status'] == 'SUCCESS') {
				if (NovalnetHelper::validateCheckSum($post)) {
					$transaction_details = array('transaction' =>array('tid' => $post['tid']));
					$action = NovalnetHelper::get_action_endpoint('transaction_details');
					$response = NovalnetHelper::sendRequest($transaction_details, $action);
					$_SESSION['response'] = $response;
				} else {
					NovalnetHelper::processTempOrderFail($this->code, $_SESSION['nn_tempOID'], $post, MODULE_PAYMENT_NOVALNET_ERROR_MSG);
				}
			} else {
				NovalnetHelper::processTempOrderFail($this->code, $_SESSION['nn_tempOID'], $post);
			}
		} else {
			if (!empty($_SESSION['nn_wallet_doredirect']) && $_SESSION['nn_wallet_doredirect'] == 'true') {
				$this->tmpOrders = true;
			} else {
				$response = $this->doPaymentCall();
				if ($response['result']['status'] == 'SUCCESS') {
					$_SESSION['response'] = $response;
				} else {
					$error = (!empty($response['result']['status_text']) ? $response['result']['status_text'] : '');
					xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code . '&error=' . urlencode($error), 'SSL', true, false));
				}
			}
		}
		$order->info['comments'] .= NovalnetHelper::updateTransactionDetails($this->code, $_SESSION['response']);	
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
			unset($_SESSION['nn_txn_secret']);
			unset($_SESSION['nn_tempOID']);
		}
		$response = $this->doPaymentCall($insert_id);
		if ($response['result']['status'] == 'SUCCESS' && !empty($response['result']['redirect_url'])) {
			$_SESSION['nn_txn_secret'] = $response['transaction']['txn_secret'];
			$_SESSION['nn_tempOID'] = $insert_id;
			$_SESSION['nn_redirect_url'] = $response['result']['redirect_url'];
			if (empty($_SESSION['received_data'])) {
				xtc_redirect($response['result']['redirect_url']);
			}
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
		include DIR_FS_CATALOG . 'release_info.php';
		$receivedData = $_SESSION['received_data'];
		$merchant_data     = NovalnetHelper::getMerchantData();
		$custom_data 	   = NovalnetHelper::getCustomData();
		if (empty($_SESSION['received_data'])) {
			$customer_data     = NovalnetHelper::getCustomerData();
			$transaction_data  = NovalnetHelper::getTransactionData();
			$transaction_data['transaction']['payment_type'] = NovalnetHelper::getPaymentName($this->code);
			$transaction_data['transaction']['payment_data']['wallet_token'] = $_SESSION['wallet_token'];
			$custom_data = NovalnetHelper::getCustomData();
			$params = array_merge($merchant_data, $customer_data, $transaction_data, $custom_data);
		} else{
			$customer_data     = NovalnetHelper::getCustomerData($receivedData);
			$transaction_data  = NovalnetHelper::getTransactionData($receivedData);
			$params = array_merge($merchant_data, $customer_data, $transaction_data, $custom_data);			
			$params['transaction']['payment_type'] = NovalnetHelper::getPaymentName($this->code);	
			if (!empty($_SESSION['tmp_oID'])) {
				$params['transaction']['order_no'] = $_SESSION['tmp_oID'];
			}
			$params['transaction']['payment_data']['wallet_token'] = $_SESSION['token'];
		}
		if($_SESSION['nn_wallet_doredirect'] == 'true') {
			$params['transaction']['order_no' ]           = $order_no;
			$params['transaction']['return_url']          = ((isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI'];
			$params['transaction']['error_return_url']    = ((isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI'];
		}
		// payment call send to novalnet server
		if ((defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_AUTHENTICATE') && MODULE_PAYMENT_NOVALNET_GOOGLEPAY_AUTHENTICATE == 'true') && (NovalnetHelper::getOrderAmount($order->info['total']) >= (defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_MANUAL_CHECK_LIMIT') ? MODULE_PAYMENT_NOVALNET_GOOGLEPAY_MANUAL_CHECK_LIMIT : ''))) { // for on-hold transaction
			$data = NovalnetHelper::sendRequest($params, NovalnetHelper::get_action_endpoint('authorize'));
		} else {
			$data = NovalnetHelper::sendRequest($params, NovalnetHelper::get_action_endpoint('payment'));
		}
		if ($data['result']['status'] != 'SUCCESS' ) {
			$error = (!empty($data['result']['status_text']) ? $data['result']['status_text'] : '');
			xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code . '&error=' . urlencode($error), 'SSL', true, false));
		}	
		return $data;
	}
	/**
	 * Core Function : after_process()
	 *
	 */
	function after_process() {
		global $order, $insert_id;
		if (!empty($_SESSION['received_data']) && !empty($_SESSION['nn_tempOID'])) {
			NovalnetHelper::updateOrderStatus($_SESSION['nn_tempOID'], $order->info['comments'], $_SESSION['response'], $this->code);
			NovalnetHelper::sendTransactionUpdate($_SESSION['nn_tempOID']);
		} else {
			NovalnetHelper::updateOrderStatus($insert_id, $order->info['comments'], $_SESSION['response'], $this->code);
			NovalnetHelper::sendTransactionUpdate($_SESSION['nn_tempOID']);
		}
		unset($_SESSION['response']);
		unset($_SESSION['received_data']);
		unset($_SESSION['shipping']);
		unset($_SESSION['nn_wallet_doredirect']);
		unset($_SESSION['wallet_token']);
		unset($_SESSION['token']);
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
		$check_query = xtc_db_query("select `value` from `gx_configurations` where `key` = 'configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_STATUS'");
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
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_STATUS','false',  '1', 'switcher',now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_TEST_MODE','false',  '2', 'switcher', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ALLOWED', '',  '3',  now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_PAYMENT_ZONE', '0',  '4', 'geo-zone',now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_VISIBILITY_BY_AMOUNT', '0', '5', now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_AUTHENTICATE','false', '6' ,'switcher', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_MANUAL_CHECK_LIMIT', '', '7',  now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUSINESS_NAME', '', '8',  now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ENFORCE_3D_SECCURE','false', '9' ,'switcher', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_MERCHANT_ID', '', '10',  now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_TYPE', 'Default', '11',  now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_THEME', 'Dark', '12',  now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_HEIGHT', '', '13',  now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_DISPLAY', 'shopping cart page|product page|checkout page', '11', 'multiselect', now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ORDER_STATUS', '2',  '14', 'order-status', now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_SORT_ORDER', '4', '15',  now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ENDCUSTOMER_INFO', '', '16', now());");
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
		echo '<style> ul.select2-selection__rendered{ overflow: scroll !important;} </style>';
		$button_display = defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_DISPLAY') ? MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_DISPLAY : '';
		echo '<input type="hidden" name="nn_button_display_page[]" id="nn_button_display_page" value= "'.$button_display.'" />';
		return array('configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_STATUS',
					 'configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_TEST_MODE',
					 'configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ALLOWED',
					 'configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_PAYMENT_ZONE',
					 'configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_VISIBILITY_BY_AMOUNT',
					 'configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_AUTHENTICATE',
					 'configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_MANUAL_CHECK_LIMIT',
					 'configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUSINESS_NAME',
					 'configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ENFORCE_3D_SECCURE',
					 'configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_MERCHANT_ID',
					 'configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_TYPE',
					 'configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_THEME',
					 'configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_HEIGHT',
					 'configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_DISPLAY',
					 'configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ORDER_STATUS',
					 'configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_SORT_ORDER',
					 'configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ENDCUSTOMER_INFO',
					);
	}

	/**
	 * Show GooglePay button Cart Page
	 */
	function googlepayCart_page_button() {
		if ($this->enabled === true
		&& $_SESSION['cart']->show_total() > 0
		&& (!isset($_SESSION['allow_checkout']) || $_SESSION['allow_checkout'] == 'true') && (strpos(MODULE_PAYMENT_INSTALLED, 'novalnet_config') == true && MODULE_PAYMENT_NOVALNET_SIGNATURE !='' && MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY !='' && MODULE_PAYMENT_NOVALNET_TARIFF_ID !=''))
		{
		   echo '<script src="' . DIR_WS_CATALOG . 'ext/novalnet/js/novalnet_wallet.js" type="text/javascript"></script>'.$this->getParam().'<input type="hidden" id="novalnet_googlepay_enable" value="'.$this->enabled.'"/><script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script><script src="https://cdn.novalnet.de/js/v2/NovalnetUtility.js"></script><script src="https://cdn.novalnet.de/js/v3/payment.js"></script>';
		}
	}

	/**
	 * Show GooglePay button Product Page
	 */
	function googlepayProduct_page_button() {
		if ($this->enabled === true
		&& (strpos(MODULE_PAYMENT_INSTALLED, 'novalnet_config') == true && MODULE_PAYMENT_NOVALNET_SIGNATURE !='' && MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY !='' && MODULE_PAYMENT_NOVALNET_TARIFF_ID !='')) {
			echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script><script src="' . DIR_WS_CATALOG . 'ext/novalnet/js/novalnet_wallet.js" type="text/javascript"></script>'.$this->getParam().'<input type="hidden" id="novalnet_googlepay_enable" value="'.$this->enabled.'"/><script src="https://cdn.novalnet.de/js/v2/NovalnetUtility.js"></script><script src="https://cdn.novalnet.de/js/v3/payment.js"></script><input type="hidden" id="nn_hidden_field" value="'.$_POST['products_qty'].'"/>';
		}
	}

	/**
	 * Get parameter for GooglePay process
	 *
	 * @return string Hidden fields with GooglePay data.
	 */
	function getParam() {
		global $xtPrice;
		require_once(DIR_WS_CLASSES.'order.php');
		$order = new order();
		// customers default address
		$customers = xtc_db_fetch_array(xtc_db_query("SELECT customer_id FROM admin_access_users limit 1"));
		$address_query = xtc_db_fetch_array(xtc_db_query("SELECT entry_country_id  FROM ". TABLE_ADDRESS_BOOK ." WHERE customers_id  = '". $customers['customer_id'] ."'"));
		// countries
		$country_query = xtc_db_fetch_array(xtc_db_query("SELECT countries_name, countries_iso_code_2, address_format_id FROM ". TABLE_COUNTRIES ." WHERE countries_id = '". $address_query['entry_country_id'] ."'"));
		
		// googlepay button types
		$googlepay_button_type = (defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_TYPE') && !empty(MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_TYPE)) ? MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_TYPE : 'plain';
		$googlepay_button_theme = (defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_THEME') && !empty(MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_THEME)) ? MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_THEME : 'Default';
		$display_page = defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_DISPLAY') ? explode('|', MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_DISPLAY) : array();
		$current_page = explode('.php', basename($_SERVER['PHP_SELF']));
		$data = array(
			 'client_key' 				=> MODULE_PAYMENT_NOVALNET_CLIENT_KEY,
			 'test_mode' 				=> (defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_TEST_MODE') && MODULE_PAYMENT_NOVALNET_GOOGLEPAY_TEST_MODE == 'true') ? '1' : '0',
			 'currency' 				=> $order->info['currency'],
			 'product_name' 			=> $order->products[0]['name'],
			 'country_code' 			=> (isset($country_query['countries_iso_code_2']) ? $country_query['countries_iso_code_2'] : ''),
			 'seller_name' 				=> (defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUSINESS_NAME') && MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUSINESS_NAME != '') ? MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUSINESS_NAME : STORE_NAME,
			 'lang' 					=> ((isset($_SESSION['language_code'])) ? strtoupper($_SESSION['language_code']) : 'DE'),
			 'googlepay_button_type' 	=> $googlepay_button_type,
			 'googlepay_button_theme' 	=> $googlepay_button_theme,
			 'googlepay_button_height' 	=> MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_HEIGHT,
			 'payment_method' 			=> 'GOOGLEPAY',
			 'product_type'				=> $GLOBALS['product']->data['product_type'],
			 'current_page'				=> $current_page[0],
			 'environment'				=> (defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_TEST_MODE') && MODULE_PAYMENT_NOVALNET_GOOGLEPAY_TEST_MODE == 'true') ? 'SANDBOX' : 'PRODUCTION',
			 'enforce_3d'				=> (defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ENFORCE_3D_SECCURE') && MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ENFORCE_3D_SECCURE == 'true') ? true : false,
			 'partner_id'				=> (defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_MERCHANT_ID') && !empty(MODULE_PAYMENT_NOVALNET_GOOGLEPAY_MERCHANT_ID)) ? MODULE_PAYMENT_NOVALNET_GOOGLEPAY_MERCHANT_ID : ''
			 );
		if (!empty($GLOBALS['product']->data['products_id']) && in_array('product page',$display_page)) {
			$data['total_amount'] = (string)($order->info['total'] * 100);
			$data['product_id'] = $GLOBALS['product']->data['products_id'];
			$data['payment_page'] = 'product_page';
		} else {
			$data['total_amount'] = (string)($order->info['total'] * 100);
		}

		$data['show_shipping_option'] = 0;

		if($GLOBALS['product']->data['product_type'] && $GLOBALS['product']->data['product_type'] != 2) {
			$data['show_shipping_option'] = 1;
		}
			
		$articleDetails = [];
		foreach($order->products as $key => $products) {
			
			if($products['product_type'] != 2) {
				$data['show_shipping_option'] = 1;
			}
			
			$articleDetails[] = array('label'=> $products['name']. ' x ' .$products['qty'],
									  'amount' => (string)(($products['qty'] * $products['price'])*100),
					 				  'type' => 'SUBTOTAL',
								);
			
			if ($products['discount_allowed'] > 0) {
				$articleDetails[] = array('label'=> 'discount',
											'amount' => (string)($products['discount_allowed']*100),
											'type' => 'SUBTOTAL');
			}
		}
		// price incl tax
			
		if ($order->info['tax'] != 0) {	
			$articleDetails[] = array(
				'label'		=> 'Tax',
				'amount' 	=> (string)((round($order->info['tax'], 2))*100),
				'type' 		=> 'SUBTOTAL'
			);
		}
		if(empty($articleDetails) && !empty($GLOBALS['product']->data['products_id'])) {
			$products_price = $xtPrice->xtcGetPrice($GLOBALS['product']->data['products_id'], true, 1, $GLOBALS['product']->data['products_tax_class_id'], $GLOBALS['product']->data['products_price'], 1, 0, true, true, '', true);
			$articleDetails[] = array('label'=> $GLOBALS['product']->data['products_name'] . ' x ' .$GLOBALS['product']->data['qty'],
									  'amount' => (string)($products_price['plain'] * 100) * $GLOBALS['product']->data['qty'],
									  'type' => 'SUBTOTAL',
								);
			$data['total_amount'] = (string)($products_price['plain'] * 100);
		}
		
		if ($current_page[0] == 'checkout_payment') {
				$articleDetails[] = array('label'=> 'shipping',
											'amount' => (string)($order->info['shipping_cost']*100),
											'type' => 'SUBTOTAL');
		}
		$data['orig_amount'] = $data['total_amount'];
		$input = json_encode($data);
		$googlepay_hidden_field = "<input type='hidden' value='".$input."' id='novalnet_googlepay_data'>".
								"<input type='hidden' value='".htmlentities(json_encode($articleDetails))."' id='nn_article_details'>".
								"<input type='hidden' value='".htmlentities(json_encode($shippingDetails))."' id='nn_shipping_details'>";
		return $googlepay_hidden_field;
	}
}

?>
