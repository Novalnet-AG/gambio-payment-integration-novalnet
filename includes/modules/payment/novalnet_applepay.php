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
 * Script : novalnet_applepay.php
 */

require_once(DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php');

class novalnet_applepay {
	var $code, $title, $enabled, $sort_order, $test_mode, $description, $info;

	/**
	 * Core Function : Constructor()
	 *
	 */
	function __construct() {
		global $order;
		$this->code 		= 'novalnet_applepay';
		$this->title 		= ((defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_TEXT_TITLE')) ? MODULE_PAYMENT_NOVALNET_APPLEPAY_TEXT_TITLE : '');
		$this->description 	= ((defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_TEXT_TITLE')) ? MODULE_PAYMENT_NOVALNET_APPLEPAY_TEXT_TITLE : '');
		$this->sort_order   = defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_SORT_ORDER') && MODULE_PAYMENT_NOVALNET_APPLEPAY_SORT_ORDER != '' ? trim(MODULE_PAYMENT_NOVALNET_APPLEPAY_SORT_ORDER) : 0;
		$this->info       	= (defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_ENDCUSTOMER_INFO') && MODULE_PAYMENT_NOVALNET_APPLEPAY_ENDCUSTOMER_INFO != '') ? trim(strip_tags(MODULE_PAYMENT_NOVALNET_APPLEPAY_ENDCUSTOMER_INFO)) : '';
		$this->enabled      = ((defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_STATUS') && MODULE_PAYMENT_NOVALNET_APPLEPAY_STATUS == 'true') ? true : false);
		$this->test_mode    = ((defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_TEST_MODE') && MODULE_PAYMENT_NOVALNET_APPLEPAY_TEST_MODE == 'true') ? true : false);
		$display_payment_page = (defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_DISPLAY')) ? explode('|', MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_DISPLAY) : array();
		if ($this->enabled === true && (in_array('shopping cart page', $display_payment_page) || in_array('product page', $display_payment_page)) && ((basename($_SERVER['PHP_SELF']) == 'shopping_cart.php') || (basename($_SERVER['PHP_SELF']) == 'product_info.php'))) {
			$this->tmpOrders = true;
			$this->form_action_url = xtc_href_link(FILENAME_CHECKOUT_PROCESS, 'novalnet_applepay=true', 'SSL');
		} else if ($this->enabled === true && in_array('checkout page',$display_payment_page) && (basename($_SERVER['PHP_SELF']) == 'checkout_payment.php')) {
			$this->tmpOrders = false;
		}
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
		if (($this->enabled == true) && ((int) MODULE_PAYMENT_NOVALNET_APPLEPAY_PAYMENT_ZONE > 0)) {
			$check_flag = false;
			$check_query = xtc_db_query("select zone_id from ".TABLE_ZONES_TO_GEO_ZONES." where geo_zone_id = '".MODULE_PAYMENT_NOVALNET_APPLEPAY_PAYMENT_ZONE."' and zone_country_id = '".$order->delivery['country']['id']."' order by zone_id");

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
		if (!empty($order->info['deduction']) || $_SESSION['customers_status']['customers_status_show_price_tax'] == '0') {
			$_SESSION['novalnet']['deduction_amount'] = isset($order->info['deduction']) ? $order->info['deduction'] : 0;
			$_SESSION['novalnet']['payment_amount'] = ($order->info['total'] + (round($order->info['tax'], 2)));
		} else if (isset($order->info['deduction'])){
			$_SESSION['novalnet']['deduction_amount'] = $order->info['deduction'];
			$_SESSION['novalnet']['payment_amount'] = $order->info['total'];
		}
		$display_page = defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_DISPLAY') ? explode('|', MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_DISPLAY) : array();
		if ($this->enabled === true && in_array('checkout page',$display_page)) {
			$selection = [
					'id'          => $this->code,
					'module'      => $this->title,
					'description' => '<link rel="stylesheet" type="text/css" href="ext/novalnet/css/novalnet.css">' .NovalnetHelper::showPaymentDescription($this->code) . $this->info
									 .'<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>'
									 .'<script src="https://cdn.novalnet.de/js/v2/NovalnetUtility.js"></script>'
									 .'<script src="https://cdn.novalnet.de/js/v3/payment.js"></script>'
									 .'<script src="' . DIR_WS_CATALOG . 'ext/novalnet/js/novalnet_wallet.js" type="text/javascript"></script>'.$this->getParam($order).xtc_draw_hidden_field('nn_applepay_estotal_label', (defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_ESTIMATEDTOTAL_LABEL') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_ESTIMATEDTOTAL_LABEL : ''), 'id="nn_applepay_estotal_label"').xtc_draw_hidden_field('nn_applepay_total_label', (defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_TOTAL_LABEL') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_TOTAL_LABEL : ''), 'id="nn_applepay_total_label"')
			];
			$selection['fields'][] =  ['title' => '
										<div id="novalnet_applepay_wallet_div" style="display:none" >
										<div id="novalnet_applepay_wallet_button" ></div>
										<input type="hidden" id="nn_wallet" name="nn_wallet" value="" />
										<script type="text/javascript">novalnet_checkout_page("novalnet_applepay");</script></div>'
			];
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
		if (!empty($_REQUEST['nn_wallet']) || !empty($_SESSION['wallet_token'])) {
			unset($_SESSION['wallet_token']);
			$_SESSION['wallet_token'] = $_REQUEST['nn_wallet'];
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
		global $order;
		if($_SESSION['customers_status']['customers_status_show_price_tax'] == '0' && isset($order->info['deduction_amount'])) {
			$_SESSION['novalnet']['wallet_amount'] = ($order->info['total'] - (round($order->info['deduction_amount'], 2))) +  round($order->info['tax'], 2);
		} else {
			$_SESSION['novalnet']['wallet_amount'] = $order->info['total'];
		}
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
		include DIR_FS_CATALOG . 'release_info.php';
		$receivedData = $_SESSION['received_data'];
		// Generate Novalnet payment parameters
		$merchant_data     = NovalnetHelper::getMerchantData();
		$custom_data 	   = NovalnetHelper::getCustomData();
		$customer_data     = NovalnetHelper::getCustomerWalletData($receivedData);
		$transaction_data  = NovalnetHelper::getTransactionData($receivedData);
		$transaction_data['transaction']['payment_type'] = NovalnetHelper::getPaymentName($this->code);
		$params = array_merge($merchant_data, $customer_data, $transaction_data, $custom_data);
		if (empty($_SESSION['received_data'])) {
			$params['transaction']['payment_data']['wallet_token'] = $_SESSION['wallet_token'];
		}
		else {
			$customer_data     = NovalnetHelper::getCustomerWalletData($receivedData);
			$transaction_data  = NovalnetHelper::getTransactionData($receivedData);
			$params = array_merge($merchant_data, $customer_data, $transaction_data, $custom_data);
			$params['transaction']['payment_type'] = NovalnetHelper::getPaymentName($this->code);
			if (!empty($_SESSION['tmp_oID'])) {
				$params['transaction']['order_no'] = $_SESSION['tmp_oID'];
			}
			$params['transaction']['payment_data']['wallet_token'] = $_SESSION['token'];
		}
		// payment call send to novalnet server
		if ((defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_AUTHENTICATE') && MODULE_PAYMENT_NOVALNET_APPLEPAY_AUTHENTICATE == 'true') && (NovalnetHelper::getOrderAmount($_SESSION['novalnet']['payment_amount']) >= (defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_MANUAL_CHECK_LIMIT') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_MANUAL_CHECK_LIMIT : ''))) { // for on-hold transaction
			$response = NovalnetHelper::sendRequest($params, NovalnetHelper::get_action_endpoint('authorize'));
		} else {
			$response = NovalnetHelper::sendRequest($params, NovalnetHelper::get_action_endpoint('payment'));
		}
		unset($_SESSION['novalnet']['payment_amount'], $_SESSION['novalnet']['deduction_amount'], $_SESSION['novalnet']['wallet_amount'], $_SESSION['wallet_token']);
		$_SESSION['response'] = $response;  // store response in session variable
		$order->info['comments'] .= NovalnetHelper::updateTransactionDetails($this->code, $_SESSION['response']);
	}

	/**
	 * Core Function : after_process()
	 *
	 */
	function after_process() {
		global $order, $insert_id;
		NovalnetHelper::updateOrderStatus($insert_id, $order->info['comments'], $_SESSION['response'], $this->code);
		NovalnetHelper::sendTransactionUpdate($insert_id);
		unset($_SESSION['received_data']);
		unset($_SESSION['shipping']);
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
		$check_query = xtc_db_query("select `value` from `gx_configurations` where `key` = 'configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_STATUS'");
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
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_STATUS','false',  '1', 'switcher',now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_TEST_MODE','false',  '2', 'switcher', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_ALLOWED', '',  '3',  now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_PAYMENT_ZONE', '0',  '4', 'geo-zone',now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_VISIBILITY_BY_AMOUNT', '0', '5', now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_AUTHENTICATE','false', '6' ,'switcher', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_MANUAL_CHECK_LIMIT', '', '7',  now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_BUSINESS_NAME', '', '8',  now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_ORDER_STATUS', '2',  '8', 'order-status', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_TYPE', 'Default', '9',  now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_THEME', 'Dark', '10',  now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_HEIGHT', '', '11',  now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_CORNER_RADIUS', '', '13',  now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_DISPLAY', 'shopping cart page|product page|checkout page', '12', 'multiselect', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_SORT_ORDER', '10', '13',  now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_ENDCUSTOMER_INFO', '', '14', now());");
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
		echo "<input type='hidden' value='".$this->getApplepayDisplay()."' id='nn_applepay_display'>";
		$button_display = defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_DISPLAY') != '' ? MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_DISPLAY : '';
		echo '<input type="hidden" name="nn_button_display_page[]" id="nn_button_display_page" value= "'.$button_display.'" />';
		return array('configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_STATUS',
					 'configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_TEST_MODE',
					 'configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_ALLOWED',
					 'configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_PAYMENT_ZONE',
					 'configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_VISIBILITY_BY_AMOUNT',
					 'configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_AUTHENTICATE',
					 'configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_MANUAL_CHECK_LIMIT',
					 'configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_BUSINESS_NAME',
					 'configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_ORDER_STATUS',
					 'configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_TYPE',
					 'configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_THEME',
					 'configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_HEIGHT',
					 'configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_CORNER_RADIUS',
					 'configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_DISPLAY',
					 'configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_SORT_ORDER',
					 'configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_ENDCUSTOMER_INFO',
					);
	}

	function getApplepayDisplay() {
		$display_pages = [
			'cartpage' 	   => defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_DISPLAY_CARTPAGE') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_DISPLAY_CARTPAGE : '',
			'productpage'  => defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_DISPLAY_PRODUCTPAGE') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_DISPLAY_PRODUCTPAGE : '',
			'checkoutpage' => defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_DISPLAY_CHECKOUTPAGE') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_DISPLAY_CHECKOUTPAGE : '',
			'plain' => defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_PLAIN') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_PLAIN : '',
			'buy' => defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_BUY') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_BUY : '',
			'donate' => defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_DONATE') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_DONATE : '',
			'book' => defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_BOOK') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_BOOK : '',
			'checkout' => defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_CHECKOUT') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_CHECKOUT : '',
			'order' => defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_ORDER') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_ORDER : '',
			'subscribe' => defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_SUBSCRIBE') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_SUBSCRIBE : '',
			'pay' => defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_PAY') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_PAY : '',
			'contribute' => defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_CONTRIBUTE') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_CONTRIBUTE : '',
			'tip' => defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_TIP') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_TIP : '',
			'rent' => defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_RENT') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_RENT : '',
			'reload' => defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_RELOAD') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_RELOAD : '',
			'support' => defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_SUPPORT') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_SUPPORT : '',
			'dark' => defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_DARK') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_DARK : '',
			'light' => defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_LIGHT') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_LIGHT : '',
			'lightoutline' => defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_LIGHT_OUTLINE') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_LIGHT_OUTLINE : '',
			'placeholder_text' => defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_PAGES_TEXT') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_PAGES_TEXT : '',
		];
		return json_encode($display_pages);
	}

	/**
	 * Show ApplePay button Cart Page
	 */
	function applepayCart_page_button() {
		if ($this->enabled === true
		&& $_SESSION['cart']->show_total() > 0
		&& (!isset($_SESSION['allow_checkout']) || $_SESSION['allow_checkout'] == 'true') && (strpos(MODULE_PAYMENT_INSTALLED, 'novalnet_config') == true && MODULE_PAYMENT_NOVALNET_SIGNATURE !='' && MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY !='' && MODULE_PAYMENT_NOVALNET_TARIFF_ID !=''))
		{
		   include_once(DIR_FS_CATALOG . 'lang/'. $_SESSION['language'].'/modules/payment/novalnet_applepay.php');
		   echo '<script src="' . DIR_WS_CATALOG . 'ext/novalnet/js/novalnet_wallet.js" type="text/javascript"></script>'.xtc_draw_hidden_field('nn_applepay_total_label', (defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_TOTAL_LABEL') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_TOTAL_LABEL : ''), 'id="nn_applepay_total_label"'). $this->getParam().'<input type="hidden" id = novalnet_applepay_enable value="'.$this->enabled.'"/><script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script><script src="https://cdn.novalnet.de/js/v3/payment.js"></script></script>';
		}
	}

	/**
	 * Show ApplePay button Product Page
	 */
	function applepayProduct_page_button() {
		if ($this->enabled === true
		&& (strpos(MODULE_PAYMENT_INSTALLED, 'novalnet_config') == true && MODULE_PAYMENT_NOVALNET_SIGNATURE !='' && MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY !='' && MODULE_PAYMENT_NOVALNET_TARIFF_ID !='')) {
			include_once(DIR_FS_CATALOG .'lang/'. $_SESSION['language'].'/modules/payment/novalnet_applepay.php');
			echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>';
			echo '<script src="' . DIR_WS_CATALOG . 'ext/novalnet/js/novalnet_wallet.js" type="text/javascript"></script>' . xtc_draw_hidden_field('nn_applepay_total_label', (defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_TOTAL_LABEL') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_TOTAL_LABEL : ''), 'id="nn_applepay_total_label"').$this->getParam();
			echo '<input type="hidden" id="novalnet_applepay_enable" value="'.$this->enabled.'"/>';
			echo '<script src="https://cdn.novalnet.de/js/v3/payment.js" type="text/javascript"></script>';
			echo '<input type="hidden" id="nn_hidden_field" value="'.$_POST['products_qty'].'"/>';
		}
	}

	/**
	 * Get parameter for ApplePay process
	 *
	 * @return string Hidden fields with ApplePay data.
	 */
	function getParam($nn_order = '') {
		global $xtPrice, $gx_version;
		include DIR_FS_CATALOG . 'release_info.php';
		require_once(DIR_WS_CLASSES.'order.php');
		$order = new order();
		$customers = xtc_db_fetch_array(xtc_db_query("SELECT customer_id FROM admin_access_users limit 1"));
		$address_query = xtc_db_fetch_array(xtc_db_query("SELECT entry_country_id  FROM ". TABLE_ADDRESS_BOOK ." WHERE customers_id  = '". $customers['customer_id'] ."'"));
		// countries
		$country_query = xtc_db_fetch_array(xtc_db_query("SELECT countries_name, countries_iso_code_2, address_format_id FROM ". TABLE_COUNTRIES ." WHERE countries_id = '". $address_query['entry_country_id'] ."'"));
		$applepay_button_type = (defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_TYPE') && !empty(MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_TYPE)) ? MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_TYPE : 'plain';
		$applepay_button_theme = (defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_THEME') && !empty(MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_THEME)) ? MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_THEME : 'Default';
		$display_page = defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_DISPLAY') ? explode('|', MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_DISPLAY) : array();
		$current_page = explode('.php', basename($_SERVER['PHP_SELF']));
		$data = array(
			 'client_key'               => MODULE_PAYMENT_NOVALNET_CLIENT_KEY,
			 'test_mode'                => (defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_TEST_MODE') && MODULE_PAYMENT_NOVALNET_APPLEPAY_TEST_MODE == 'true') ? '1' : '0',
			 'currency'                 => $order->info['currency'],
			 'product_name'             => $order->products[0]['name'],
			 'country_code'             => (isset($country_query['countries_iso_code_2']) ? $country_query['countries_iso_code_2'] : ''),
			 'seller_name' 			    => (defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_BUSINESS_NAME') && MODULE_PAYMENT_NOVALNET_APPLEPAY_BUSINESS_NAME != '') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_BUSINESS_NAME : STORE_NAME,
			 'shop_name'                => STORE_NAME,
			 'lang'                     => ((isset($_SESSION['language_code'])) ? strtoupper($_SESSION['language_code']) : 'DE'),
			 'applepay_button_type'     => $applepay_button_type,
			 'applepay_button_theme'    => $applepay_button_theme,
			 'applepay_button_height'   => defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_HEIGHT') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_HEIGHT : '',
			 'applepay_button_radius'   => defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_CORNER_RADIUS') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_CORNER_RADIUS : '',
			 'payment_method'           => 'APPLEPAY',
			 'product_type'             => isset($GLOBALS['product']->data['product_type']) ? $GLOBALS['product']->data['product_type'] : '',
			 'current_page'				=> $current_page[0],
			 'environment'				=> (defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_TEST_MODE') && MODULE_PAYMENT_NOVALNET_APPLEPAY_TEST_MODE == 'true') ? 'SANDBOX' : 'PRODUCTION',
			 'customer_id'				=> !isset($_SESSION['customer_id']) && empty($_SESSION['customer_id']) ? 0 : $_SESSION['customer_id'],
			 'login_url'				=> xtc_href_link(FILENAME_LOGIN, '', 'SSL'),
			 'shop_version'				=> ($gx_version >= '4.7.1.2') ? 1 : 0,
			 );

		if ($gx_version >= '4.7.1.2') {
			$data['customer_id'] = !isset($_SESSION['customer_id']) && empty($_SESSION['customer_id']) ? 0 : $_SESSION['customer_id'];
			$data['login_url']   = xtc_href_link(FILENAME_LOGIN, '', 'SSL');
			$data['shipping_page']   = defined('FILENAME_CHECKOUT_SHIPPING') ? FILENAME_CHECKOUT_SHIPPING : '';
		}
		if (!empty($GLOBALS['product']->data['products_id']) && in_array('product page',$display_page)) {
			$data['total_amount'] = (string)($order->info['total'] * 100);
			$data['product_id'] = $GLOBALS['product']->data['products_id'];
			$data['payment_page'] = 'product_page';
		} else {
			if (!empty($_SESSION['novalnet']['payment_amount']) && $_SESSION['customers_status']['customers_status_show_price_tax'] == '0') {
				$data['total_amount'] = (string) ($_SESSION['novalnet']['payment_amount'] * 100);
			} else {
				$data['total_amount'] = (string)($order->info['total'] * 100);
			}
		}
		$data['show_shipping_option'] = 0;
		if(isset($GLOBALS['product']->data['product_type']) && $GLOBALS['product']->data['product_type'] != 2) {
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
		}
		if (empty($nn_order)) {
			if (!empty($order->info['tax']) && $_SESSION['customers_status']['customers_status_show_price_tax'] != '0') {	// Price incl tax
				$articleDetails[] = array(
					'label'		=> (defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_INCL_TAX_LABEL') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_INCL_TAX_LABEL : ''),
					'amount' 	=> (string)((round($order->info['tax'], 2))*100),
					'type' 		=> 'SUBTOTAL'
				);
			} else if (!empty($order->info['tax']) && $_SESSION['customers_status']['customers_status_show_price_tax'] == '0') {	// Price excl tax
				$articleDetails[] = array(
					'label'		=> (defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_EXCL_TAX_LABEL') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_EXCL_TAX_LABEL : ''),
					'amount' 	=> (string)((round($order->info['tax'], 2))*100),
					'type' 		=> 'SUBTOTAL'
				);
			}
		}
		if(empty($articleDetails) && !empty($GLOBALS['product']->data['products_id'])) {
			$products_price = $xtPrice->xtcGetPrice($GLOBALS['product']->data['products_id'], true, 1, $GLOBALS['product']->data['products_tax_class_id'], $GLOBALS['product']->data['products_price'], 1, 0, true, true, '', true);
			$articleDetails[] = array('label'=> $GLOBALS['product']->data['products_name'] . ' x ' .$GLOBALS['product']->data['qty'],
									  'amount' => (string)($products_price['plain'] * 100) * $GLOBALS['product']->data['qty'],
									  'type' => 'SUBTOTAL',
								);
			$tax_rate = $xtPrice->getTaxRateByTaxClassId($GLOBALS['product']->data['products_tax_class_id']);
			$tax = $xtPrice->xtcGetTax($GLOBALS['product']->data['products_price'], $tax_rate);
			if($_SESSION['customers_status']['customers_status_show_price_tax'] != '0'){
				$articleDetails[] = array(
					'label'		=> (defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_INCL_TAX_LABEL') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_INCL_TAX_LABEL : ''),
					'amount' 	=> (string)((round($order->info['tax'], 2))*100),
					'type' 		=> 'SUBTOTAL'
				);
			} else {
				$articleDetails[] = array(
					'label'		=> (defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_EXCL_TAX_LABEL') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_EXCL_TAX_LABEL : ''),
					'amount' 	=> (string)((round($tax, 2))*100),
					'type' 		=> 'SUBTOTAL'
				);
			}
			if ($_SESSION['customers_status']['customers_status_ot_discount_flag'] == '1' && $_SESSION['customers_status']['customers_status_ot_discount']!='0.00') {
				$discount_price = round($xtPrice->xtcFormat($GLOBALS['product']->data['products_price'], false) / 100 * $_SESSION['customers_status']['customers_status_ot_discount']*1, 2);
				$articleDetails[] = array(
					'label'		=> $_SESSION['customers_status']['customers_status_discount']. '%' .'Discount',
					'amount' 	=> (string) (round($discount_price, 2) *100),
					'type' 		=> 'SUBTOTAL'
				);
			}
			$data['total_amount'] = (string)(($products_price['plain'] * 100) - round($discount_price, 2) *100);
		}

		if ($current_page[0] == 'checkout_payment') {
				$articleDetails[] = array('label'=> (defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_SHIPPING_LABEL') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_SHIPPING_LABEL : ''),
											'amount' => (string)($nn_order->info['shipping_cost']*100),
											'type' => 'SUBTOTAL');
			if(!empty($nn_order->info['deduction']) || ($_SESSION['customers_status']['customers_status_ot_discount_flag'] == '1' && $_SESSION['customers_status']['customers_status_ot_discount']!='0.00')) {		// To add discount
				$discount_price = round($xtPrice->xtcFormat($order->info['subtotal'], false) / 100 * $_SESSION['customers_status']['customers_status_ot_discount']*1, 2);
				$deduction = abs($discount_price + (isset($nn_order->info['deduction']) ? $nn_order->info['deduction'] : 0));
				$articleDetails[] = array('label'=> defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_DISCOUNT_AND_GIFT_VOUCHER_LABEL') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_DISCOUNT_AND_GIFT_VOUCHER_LABEL : '',
											'amount' => (string) (round($deduction, 2) * 100),
											'type' => 'SUBTOTAL');
			}
			if (!empty($nn_order->info['tax']) && $_SESSION['customers_status']['customers_status_show_price_tax'] != '0') {	// Price incl tax
				$articleDetails[] = array(
					'label'		=> (defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_INCL_TAX_LABEL') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_INCL_TAX_LABEL : ''),
					'amount' 	=> (string)((round($nn_order->info['tax'], 2))*100),
					'type' 		=> 'SUBTOTAL'
				);
			} else if (!empty($nn_order->info['tax']) && $_SESSION['customers_status']['customers_status_show_price_tax'] == '0') {	// Price excl tax
				$articleDetails[] = array(
					'label'		=> (defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_EXCL_TAX_LABEL') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_EXCL_TAX_LABEL : ''),
					'amount' 	=> (string)((round($nn_order->info['tax'], 2))*100),
					'type' 		=> 'SUBTOTAL'
				);
			}
			if ($_SESSION['customers_status']['customers_status_show_price_tax'] != 0) {
				$data['total_amount'] = (string)($nn_order->info['total'] * 100);
				$data['orig_amount'] = $data['total_amount'];
			} else {
				$data['total_amount'] = (string)(($nn_order->info['total'] * 100) + (round($nn_order->info['tax'], 2) * 100));
				$data['orig_amount'] = (string)(($nn_order->info['total'] * 100) + (round($nn_order->info['tax'], 2) * 100));
			}
		}
		if ($current_page[0] != 'checkout_payment') {
			if ($_SESSION['customers_status']['customers_status_show_price_tax'] != 0) {
				$data['orig_amount'] = $data['total_amount'];
			} else {
				$data['orig_amount'] = !empty($_SESSION['novalnet']['payment_amount']) ? (string)($_SESSION['novalnet']['payment_amount'] * 100) : (string)($data['total_amount'] + (round($order->info['tax'], 2) * 100));
			}
		}
			$input = json_encode($data);
	  $applepay_hidden_field = "<input type='hidden' value='".$input."' id='novalnet_applepay_data'>".
								"<input type='hidden' value='".htmlentities(json_encode($articleDetails))."' id='nn_article_details'>";
	  return $applepay_hidden_field;
	}
}

?>
