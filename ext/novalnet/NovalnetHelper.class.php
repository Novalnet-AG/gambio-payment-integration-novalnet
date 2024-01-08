<?php
/**
 * Novalnet payment module
 *
 * This script contains the helper function for all the payments
 *
 * @author     Novalnet AG
 * @copyright  Copyright (c) Novalnet
 * @license    https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link       https://www.novalnet.de
 *
 * Script: NovalnetHelper.class.php
 */
include_once(DIR_FS_INC . 'xtc_format_price_order.inc.php');
include_once(DIR_FS_INC . 'xtc_validate_email.inc.php');
include_once (DIR_FS_INC.'xtc_php_mail.inc.php');

class NovalnetHelper {
	/**
	 * Status mapper.
	 *
	 * @var array
	 */
	public static $statuses = array(
		'ON_HOLD'     => array( '85', '91', '98', '99', '84' ),
		'CONFIRMED'   => array( '100' ),
		'PENDING'     => array( '90', '80', '86', '83', '75' ),
		'DEACTIVATED' => array( '103' ),
	);

	public function  __construct($lang = '') {
		if (!empty($lang)) {
			foreach (glob(DIR_FS_CATALOG.'lang/'.$lang.'/modules/payment/novalnet*.php') as $filename) {
				include_once $filename;
			}
		} else {
			include_once(DIR_FS_CATALOG ."lang/". $_SESSION['language']."/modules/payment/novalnet.php");
		}
	}

	/**
	 * Validate the merchant credentials
	 *
	 */
	public static function validateMerchantConfiguration() {
		$error_display = self::checkMerchantCredentials();
		if (isset($_GET['module']) && strpos(MODULE_PAYMENT_INSTALLED, $_GET['module']) && $_GET['module'] == 'novalnet_config' && (!isset($_GET['action']) || $_GET['action'] != 'edit')) {
			if ($error_display) {
				echo self::displayErrorMessage(defined('MODULE_PAYMENT_NOVALNET_GLOBAL_CONFIG_TEXT_TITLE') ? MODULE_PAYMENT_NOVALNET_GLOBAL_CONFIG_TEXT_TITLE : '');
			}
		}
	}

	/**
	 * Check the merchant credentials are empty
	 *
	 * @return boolean
	 */
	public static function checkMerchantCredentials() {
		if ((!defined('MODULE_PAYMENT_NOVALNET_SIGNATURE') || MODULE_PAYMENT_NOVALNET_SIGNATURE == '' )
		|| (!defined('MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY') || MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY == '' )) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Display error message
	 *
	 * @param $error_payment_name
	 *
	 * @return string
	 */
	public static function displayErrorMessage($error_payment_name) {
		$_SESSION['error_msg_displayed'] = true;
		return '<div class="message_stack_container" style="display:block"><div class = "alert alert-danger">' . $error_payment_name . '<br/><br/>'. (defined('MODULE_PAYMENT_NOVALNET_VALID_MERCHANT_CREDENTIALS_ERROR') ? MODULE_PAYMENT_NOVALNET_VALID_MERCHANT_CREDENTIALS_ERROR : '') . '<button type="button" class="close" data-dismiss="alert">×</button></div></div>';
	}

	/**
	 * Check instalment payment conditions
	 *
	 * @param $order_amount
	 * @param $payment_name
	 *
	 * @return boolean
	 * */
	public static function checkInstalmentConditions($order_amount, $payment_name) {
		global $order;
		$minimum_amount_gurantee    =  constant('MODULE_PAYMENT_'. strtoupper($payment_name) . '_MINIMUM_ORDER_AMOUNT') !='' ? constant('MODULE_PAYMENT_'. strtoupper($payment_name) . '_MINIMUM_ORDER_AMOUNT') : '1998';
		if (isset($order->billing)) {
			$country_check = self::checkGuaranteeCountries(strtoupper($order->billing['country']['iso_code_2']),constant('MODULE_PAYMENT_'.strtoupper($payment_name).'_ALLOW_B2B'), $order->billing['company']);
		}
		if ($order_amount >= $minimum_amount_gurantee  && $country_check && $order->info['currency'] == 'EUR' && self::isBillingShippingsame()) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get Instaments date
	 *
	 * @param $novalnet_instalment_cycle
	 * @param $novalnet_recurring_period_cycle
	 *
	 * @return $instalment_date_invoice
	 */
	public static function getInstalmentDate($novalnet_instalment_cycle, $novalnet_recurring_period_cycle) {
		$total_invoice_instalment_cycle = !empty($novalnet_instalment_cycle) ? $novalnet_instalment_cycle[count($novalnet_instalment_cycle)-1]:'';
		$current_month_invoice = date('m');
			for ($i=0; $i<$total_invoice_instalment_cycle; $i++) {
			  $last_day = date('Y-m-d', strtotime('+' . ((int) $novalnet_recurring_period_cycle) * $i . 'months'));
			  $instalment_date_month[] = date('m', strtotime('+' . ((int) $novalnet_recurring_period_cycle) * $i . 'months'));
			  if ($current_month_invoice > 12) {
				$current_month_invoice = $current_month_invoice - 12;
			  }
			  if ($current_month_invoice == $instalment_date_month[$i]) {
				  $instalment_date_invoice[] = date('Y-m-d', strtotime('+' . ((int) $novalnet_recurring_period_cycle) * $i . 'months'));
			  } else {
				  $instalment_date_invoice[] = date('Y-m-d', strtotime($instalment_date_invoice[$i] . ' last day of previous month' , strtotime ($last_day)));
			   }
				 $current_month_invoice =  $current_month_invoice + (int) $novalnet_recurring_period_cycle;
			}
		$instalment_date_invoice = implode('/', $instalment_date_invoice);
		return $instalment_date_invoice;
	}

	/**
	 * Get available instalments
	 *
	 * @param $amount
	 * @param $total_period
	 * @param $currency
	 *
	 * @return $cycles
	 */
	public static function getInstalmentCycles($amount, $total_period, $currency) {
		$i = 0; $cycles = '';
		foreach ($total_period as $period) {
			$cycle_amount = number_format($amount / $period, 2);
			if ($cycle_amount >= 9.99) {
				$cycle_amount = xtc_format_price_order($cycle_amount, 1, $currency);
				$cycles .= '<option value='.$period.'>'.sprintf(MODULE_PAYMENT_NOVALNET_INSTALLMENT_PER_MONTH_CYCLE, $period) . $cycle_amount .' '. MODULE_PAYMENT_NOVALNET_INSTALLMENT_PER_MONTH_FRONTEND.'</option>';
				$i++;
			}
		}
		return $cycles;
	}

	/**
	 * Show payment description and test mode notification to the payments
	 *
	 * @param $payment_name
	 *
	 * @return $payment_description
	 */
	public static function showPaymentDescription($payment_name) {
		// Payment method description
		$payment_description = '<link rel="stylesheet" type="text/css" href="ext/novalnet/css/novalnet.css"><div class="novalnet-info-box">';
		// Add TestMode Label if the payment in Test Mode.
		if (constant('MODULE_PAYMENT_'. strtoupper($payment_name) . '_TEST_MODE') == 'true') {
			$payment_description .= '<div class="novalnet-test-mode">'.MODULE_PAYMENT_NOVALNET_TESTMODE.'</div>';
		}
		$payment_description .= constant('MODULE_PAYMENT_'.strtoupper($payment_name).'_TEXT_INFO');
		if (($payment_name == 'novalnet_cc') && (MODULE_PAYMENT_NOVALNET_CC_AUTHENTICATE == 'zero_amount')) {
			$payment_description .= defined('MODULE_PAYMENT_NOVALNET_ZEROAMOUNT_BOOKING_TEXT') ? MODULE_PAYMENT_NOVALNET_ZEROAMOUNT_BOOKING_TEXT : '';
		}
		$payment_description .= '</div>';
		return $payment_description;
	}

	/**
	 * Show payment description and SEPA mandate text if the payment use IBAN field to the sepa payments
	 *
	 * @param $payment_name
	 *
	 * @return string
	 */
	public static function showSepaDescription($payment_name){
		$test_mode = '';
		$zeroamount_description = '';
		if (constant('MODULE_PAYMENT_'. strtoupper($payment_name) . '_TEST_MODE') == 'true') {
			$test_mode = '<div class="novalnet-test-mode">'.MODULE_PAYMENT_NOVALNET_TESTMODE.'</div>';
		}
		if (($payment_name == 'novalnet_sepa') && (MODULE_PAYMENT_NOVALNET_SEPA_AUTHENTICATE == 'zero_amount')) {
			$zeroamount_description .= defined('MODULE_PAYMENT_NOVALNET_ZEROAMOUNT_BOOKING_TEXT') ? MODULE_PAYMENT_NOVALNET_ZEROAMOUNT_BOOKING_TEXT : '';
		}
		$sepa_description = '<div class ="novalnet-info-box">' .$test_mode. '<span style="list-style-type:disc">'.constant('MODULE_PAYMENT_NOVALNET_SEPA_DESC').$zeroamount_description.'</span>';
		if($payment_name=='novalnet_instalment_sepa'){
			$sepa_description .= '<br><br>'.constant('MODULE_PAYMENT_NOVALNET_SEPA_INSTALMENT_FORM_MANDATE_CONFIRM_TEXT');
		    $sepa_description .= '<br>'.constant('MODULE_PAYMENT_NOVALNET_SEPA_INSTALMENT_ABOUT_MANDATE_TEXT');
		    $sepa_description .= '</div>';
		}else{
		$sepa_description .= '<br><br>'.constant('MODULE_PAYMENT_NOVALNET_SEPA_FORM_MANDATE_CONFIRM_TEXT');
		$sepa_description .= '<br>'.constant('MODULE_PAYMENT_NOVALNET_SEPA_ABOUT_MANDATE_TEXT');
		$sepa_description .= '</div>';
		}
		return $sepa_description;
	}

	/**
	 * Get the order total amount and convert it into minimum unit amount (cents in Euro)
	 *
	 * @param $order_amount
	 *
	 * @return int
	 */
	public static function getOrderAmount($order_amount) {
		global $order;
		if(($_SESSION['customers_status']['customers_status_show_price_tax'] == '0') && !isset($_SESSION['novalnet']['payment_amount']) && empty($_SESSION['novalnet']['payment_amount'])) {
			$order_amount += (round($order->info['tax'], 2)) ;
		} else if(!empty($_SESSION['novalnet']['payment_amount'])) {
			$order_amount = $_SESSION['novalnet']['payment_amount'];
		}
			return (sprintf('%0.2f', $order_amount) * 100);

	}

	/**
	 * Check payment is available for the order amount
	 *
	 * @param $payment_name
	 *
	 * @return boolean
	 */
	public static function hidePaymentVisibility($payment_name) {
		global $order;
		if (!empty($order->info['total'])) {
			$order_amount = self::getOrderAmount($order->info['total']);
			$visibility_amount = constant('MODULE_PAYMENT_' . strtoupper($payment_name) . '_VISIBILITY_BY_AMOUNT');
			return ($visibility_amount == '' || (int) $visibility_amount <= (int) $order_amount);
		}
	}

	/**
	 * Get paygate request url
	 *
	 * @param string $action
	 *
	 * @return string
	 */
	public static function get_action_endpoint($action) {
		$endpoint = 'https://payport.novalnet.de/v2/';
		return $endpoint . str_replace('_', '/', $action);
	}

	/**
	 * Initial call to get redirect URL
	 *
	 * @param $order_no
	 * @param $payment_name
	 * @param $paypal_sheet_details
	 *
	 * @return $response
	 */
	public static function getRedirectData($order_no, $payment_name, $paypal_sheet_details = []) {
		global $order;
		if (isset($_SESSION['novalnet']['payment_amount'])) {
			unset($_SESSION['novalnet']['payment_amount']);
		}
		$merchant_data     = self::getMerchantData();
		$customer_data     = self::getCustomerData();
	
	   if($payment_name == 'novalnet_mbway'){
		$customer_data['customer']['mobile'] =$_SESSION['novalnet_mbway_mobile_no'];
		}
		$transaction_data  = self::getTransactionData();
		$custom_data       = self::getCustomData();
		$transaction_data['transaction']['payment_type'] = self::getPaymentName($payment_name);
		if (!empty($paypal_sheet_details)) {
			$params = array_merge($merchant_data, $customer_data, $transaction_data, $custom_data, $paypal_sheet_details);
		} else {
			$params = array_merge($merchant_data, $customer_data, $transaction_data, $custom_data);
		}
		$params['transaction']['order_no' ]        = $order_no;
		$params['transaction']['return_url']       = ((isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI'];
		$params['transaction']['error_return_url'] = ((isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI'];
		if ($payment_name == 'novalnet_paypal'){
			if ((defined('MODULE_PAYMENT_NOVALNET_PAYPAL_AUTHENTICATE') && MODULE_PAYMENT_NOVALNET_PAYPAL_AUTHENTICATE == 'true') && (NovalnetHelper::getOrderAmount($order->info['total']) >= (defined('MODULE_PAYMENT_NOVALNET_PAYPAL_MANUAL_CHECK_LIMIT') ? MODULE_PAYMENT_NOVALNET_PAYPAL_MANUAL_CHECK_LIMIT : ''))) {
				$response = self::sendRequest($params, self::get_action_endpoint('authorize'));
			} else {
				$response = self::sendRequest($params, self::get_action_endpoint('payment'));
			}
		} else {
			$response = self::sendRequest($params, self::get_action_endpoint('payment'));
		}
		return $response;
	}

	/**
	 * Send request to server
	 *
	 * @param $data
	 * @param $paygate_url url
	 *
	 * @return $result
	 */
	public static function sendRequest($data, $paygate_url) {
		$headers = self::getHeadersParam();
		$json_data = json_encode($data);
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $paygate_url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		$result = curl_exec($curl);
		if (curl_errno($curl)) {
			echo 'Request Error:' . curl_error($curl);
			return $result;
		}
		curl_close($curl);
		$result = json_decode($result, true);
		return $result;
	}

	/**
	 * Get payment response text
	 *
	 * @param array $response
	 *
	 * @return string
	 */
	public static function getServerResponse($response) {
		if (!empty($response['status_desc'])) {
			return $response['status_desc'];
		} elseif (!empty($response['status_text'])) {
			return $response['status_text'];
		} elseif (!empty($response['status_message'])) {
			return $response['status_message'];
		} elseif (!empty($response['result']['status_text'])) {
			return $response['result']['status_text'];
		} else {
			return MODULE_PAYMENT_NOVALNET_TRANSACTION_ERROR;
		}
	}

	/**
	 * Get Novalnet transaction details from novalnet table
	 *
	 * @param array $order_no
	 *
	 * @return integer
	 */
	public static function getNovalnetTransDetails($order_no) {
        $txn_details = xtc_db_fetch_array(xtc_db_query("SELECT * FROM novalnet_transaction_detail WHERE order_no='" . xtc_db_input($order_no) . "'"));
        if(!empty($txn_details['payment_id'])) {
			$callback_amounts  = xtc_db_fetch_array(xtc_db_query("SELECT sum(callback_amount) AS callback_amounts_total  FROM novalnet_callback_history WHERE payment_type NOT IN ('PRZELEWY24_REFUND', 'RETURN_DEBIT_SEPA', 'CREDITCARD_BOOKBACK', 'PAYPAL_BOOKBACK', 'CREDITCARD_CHARGEBACK', 'REFUND_BY_BANK_TRANSFER_EU', 'REVERSAL','CASHPAYMENT_REFUND','GUARANTEED_SEPA_BOOKBACK','GUARANTEED_INVOICE_BOOKBACK','INSTALMENT_SEPA_BOOKBACK','INSTALMENT_INVOICE_BOOKBACK') AND original_tid = " . $txn_details['tid']));
			$txn_details['callback_amount'] = (!empty($txn_details['callback_amount'])) ? ($txn_details['callback_amount'] + $callback_amounts['callback_amounts_total']) : $callback_amounts['callback_total_amount'];
			$callback_refund  = xtc_db_fetch_array(xtc_db_query("SELECT sum(callback_amount) AS callback_refund_total  FROM novalnet_callback_history WHERE payment_type IN ('PRZELEWY24_REFUND', 'RETURN_DEBIT_SEPA', 'CREDITCARD_BOOKBACK', 'PAYPAL_BOOKBACK', 'CREDITCARD_CHARGEBACK', 'REFUND_BY_BANK_TRANSFER_EU', 'REVERSAL','CASHPAYMENT_REFUND','GUARANTEED_SEPA_BOOKBACK','GUARANTEED_INVOICE_BOOKBACK','INSTALMENT_SEPA_BOOKBACK','INSTALMENT_INVOICE_BOOKBACK') AND original_tid = " . $txn_details['tid']));
			$txn_details['refund_amount'] = (!empty($txn_details['refund_amount'])) ? ($txn_details['refund_amount'] + $callback_refund['callback_refund_total']) : $callback_refund['callback_refund_total'];
		}
        return $txn_details;
    }

	/**
	 * Get merchant data
	 *
	 * @return $data
	 */
	public static function getMerchantData() {
		$data = [];
		$data['merchant'] = [
			'signature' => MODULE_PAYMENT_NOVALNET_SIGNATURE,
			'tariff'    => MODULE_PAYMENT_NOVALNET_TARIFF_ID,
		];
		return $data;
	}

	/**
	 * Get customer data
	 *
	 * @param $receivedData
	 *
	 * @return $data
	 */
	public static function getCustomerData($receivedData = '') {
		global $order;
		$data['customer'] = [
			'gender'      => !empty($order->billing['gender']) ? $order->billing['gender'] : 'u',
			'first_name'  => (!empty($receivedData) ? $receivedData['order']['billing']['contact']['firstName'] : $order->billing['firstname']),
			'last_name'   => (!empty($receivedData) ? $receivedData['order']['billing']['contact']['lastName'] : $order->billing['lastname']),
			'email'       => (!empty($receivedData)) ? (($order->info['payment_method'] == 'novalnet_googlepay') ? $receivedData['order']['billing']['contact']['email'] : $receivedData['order']['shipping']['contact']['email']) : $order->customer['email_address'],
			'customer_ip' => self::getIPAddress(),
			'customer_no' => $_SESSION['customer_id'],
			'billing'     => [
				'street'            => (!empty($receivedData) ? $receivedData['order']['billing']['contact']['addressLines'] : $order->billing['street_address']),
				'city'              => (!empty($receivedData) ? $receivedData['order']['billing']['contact']['locality'] : $order->billing['city']),
				'zip'               => (!empty($receivedData) ? $receivedData['order']['billing']['contact']['postalCode'] : $order->billing['postcode']),
				'country_code'      => (!empty($receivedData) ? $receivedData['order']['billing']['contact']['countryCode'] : (isset($order->billing['country_iso_code_2']) ? $order->billing['country_iso_code_2'] : $order->billing['country']['iso_code_2'])),
				'search_in_street'  => '1',
			],
		];
		if (!empty($order->billing['company'])) {
			$data['customer']['billing']['company'] = $order->billing['company'];
		}
		if (self::isBillingShippingsame()) {
			$data['customer']['shipping']['same_as_billing'] = 1;
		} else {
			$data['customer']['shipping']    = [
				'street'        => (!empty($receivedData) ? $receivedData['order']['shipping']['contact']['addressLines'] : $order->delivery['street_address']),
				'city'          => (!empty($receivedData) ? $receivedData['order']['shipping']['contact']['locality'] : $order->delivery['city']),
				'zip'           => (!empty($receivedData) ? $receivedData['order']['shipping']['contact']['postalCode'] : $order->delivery['postcode']),
				'country_code'  => (!empty($receivedData) ? $receivedData['order']['shipping']['contact']['countryCode'] : (isset($order->delivery['country_iso_code_2']) ? $order->delivery['country_iso_code_2'] : $order->delivery['country']['iso_code_2'])),
			];
			if (!empty($order->delivery['company'])) {
				$data['customer']['shipping']['company'] = $order->delivery['company'];
			}
		}
		return $data;
	}

	/**
	 * Get payment method type for payment request
	 *
	 * @param $payment
	 *
	 * @return $payment_title[$payment]
	 */
	public static function getPaymentName($payment) {
		$payment_title = array(
			'novalnet_applepay'               => 'APPLEPAY',
			'novalnet_googlepay'              => 'GOOGLEPAY',
			'novalnet_alipay'                 => 'ALIPAY',
			'novalnet_invoice'                => 'INVOICE',
			'novalnet_prepayment'             => 'PREPAYMENT',
			'novalnet_sepa'                   => 'DIRECT_DEBIT_SEPA',
			'novalnet_cc'                     => 'CREDITCARD',
			'novalnet_cashpayment'            => 'CASHPAYMENT',
			'novalnet_ideal'                  => 'IDEAL',
			'novalnet_wechatpay'              => 'WECHATPAY',
			'novalnet_trustly'                => 'TRUSTLY',
			'novalnet_online_bank_transfer'   => 'ONLINE_BANK_TRANSFER',
			'novalnet_instantbank'            => 'ONLINE_TRANSFER',
			'novalnet_giropay'                => 'GIROPAY',
			'novalnet_eps'                    => 'EPS',
			'novalnet_przelewy24'             => 'PRZELEWY24',
			'novalnet_paypal'                 => 'PAYPAL',
			'novalnet_bancontact'             => 'BANCONTACT',
			'novalnet_multibanco'             => 'MULTIBANCO',
			'novalnet_guarantee_invoice'      => 'GUARANTEED_INVOICE',
			'novalnet_guarantee_sepa'         => 'GUARANTEED_DIRECT_DEBIT_SEPA',
			'novalnet_postfinance'            => 'POSTFINANCE',
			'novalnet_postfinance_card'       => 'POSTFINANCE_CARD',
			'novalnet_instalment_invoice'     => 'INSTALMENT_INVOICE',
			'novalnet_instalment_sepa'        => 'INSTALMENT_DIRECT_DEBIT_SEPA',
			'novalnet_direct_debit_ach'       => 'DIRECT_DEBIT_ACH',
			'novalnet_blik'                   => 'BLIK',
			'novalnet_payconiq'               => 'PAYCONIQ',
			'novalnet_mbway'                  => 'MBWAY',
		);
		return $payment_title[$payment];
	}

	/**
	 * Get transaction data
	 *
	 * @param $receivedData
	 *
	 * @return $data
	 */
	public static function getTransactionData($receivedData = '') {
		global $order, $gx_version;
		include DIR_FS_CATALOG . 'release_info.php';
		$amount = (!empty($_SESSION['novalnet']['payment_amount']) && empty($_SESSION['novalnet']['wallet_amount']) ? (self::getOrderAmount($_SESSION['novalnet']['payment_amount'])) : (isset($_SESSION['novalnet']['wallet_amount']) ? (self::getOrderAmount($_SESSION['novalnet']['wallet_amount'])) : 0));
		$data['transaction'] = [
			'amount'           => (!empty($receivedData) ? $receivedData['transaction']['amount'] : (!empty($amount) ? $amount : (isset($order->info['total']) ? self::getOrderAmount($order->info['total']) : 0))),
			'currency'         => $order->info['currency'],
			'test_mode'        => constant('MODULE_PAYMENT_'. strtoupper($order->info['payment_class']) . '_TEST_MODE') == 'true' ? 1 : 0,
			'system_name'      => 'Gambio',
			'system_version'   => $gx_version . '-NN(12.1.0)',
			'system_url'       => (defined('ENABLE_SSL') ? (ENABLE_SSL == true ? HTTPS_SERVER : HTTP_SERVER . DIR_WS_CATALOG) : (HTTPS_CATALOG_SERVER . DIR_WS_CATALOG)),
			'system_ip'        => $_SERVER['SERVER_ADDR'],
		];
		if (isset($_SESSION['novalnet_deduction'])) {
			$data['transaction']['amount'] = (sprintf('%0.2f', $_SESSION['novalnet_deduction']) * 100);
		}
		return $data;
	}

	/**
	 * Get account like IBAN, BIC etc.,
	 *
	 * @return $data
	 */
	public static function getAccountDetails() {
		if (!empty($_SESSION['novalnet_sepa_iban'])) {
			$data['transaction']['payment_data'] ['iban'] = $_SESSION['novalnet_sepa_iban'];
			if(!empty($_SESSION['novalnet_sepa_bic'])) {
				$data['transaction']['payment_data'] ['bic'] = $_SESSION['novalnet_sepa_bic'];
			}
		}
		return $data;
	}


	/**
	 * Get Card details like pesudo hash etc.,
	 *
	 * @param $order_no
	 *
	 * @return $data
	 */
	public static function getCardDetails($order_no) {
		$data = array();
		if (empty($_SESSION['novalnet_cc_token'])) {
			$data['transaction']['payment_data'] = [
				'pan_hash'   => $_SESSION['nn_pan_hash'],
				'unique_id'  => $_SESSION['nn_uniqueid']
			];
			$data['transaction']['enforce_3d'] = (MODULE_PAYMENT_NOVALNET_CC_3D_SECURE_FORCE == 'true') ? 1 : 0;
			if(!empty($_SESSION['nn_do_redirect'])) {
				$data['transaction']['order_no' ]           = $order_no;
				$data['transaction']['return_url']          = ((isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI'];
				$data['transaction']['error_return_url']    = ((isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI'];
			}
		}
		return $data;
	}

	/**
	 * Get tokenization details
	 *
	 * @param $payment_name
	 * @param $transaction_data
	 *
	 * @return none
	 */
	public static function getToeknizationDetails($payment_name, &$transaction_data) {

		if (constant('MODULE_PAYMENT_' . strtoupper($payment_name) . '_TOKENIZATION')=='true') {
			if(!empty($_SESSION[$payment_name . '_token'])) { // Reference transaction
				$transaction_data['transaction']['payment_data']['token'] = $_SESSION[$payment_name . '_token'];
				unset($_SESSION[$payment_name . '_token']);
			} elseif(!empty($_SESSION[$payment_name . '_create_token']) && $_SESSION[$payment_name . '_create_token'] == '1') { // New transaction
				$transaction_data['transaction']['create_token'] = 1;
				unset($_SESSION[$payment_name . '_create_token']);
			}
		}
	}

	/**
	 * Get request header
	 */
	public static function getHeadersParam() {
		$encoded_data        = base64_encode(MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY);
		$headers = [
			'Content-Type:application/json',
			'Charset:utf-8',
			'Accept:application/json',
			'X-NN-Access-Key:' . $encoded_data
		];
		return $headers;
	}

	/**
	 * Get due date
	 *
	 * @param $days
	 *
	 * @return $due_date
	 */
	public static function getDueDate($days) {
		 $due_date = date("Y-m-d",strtotime('+' . $days . ' days'));
		 return $due_date;
	}

	/**
	 * Get request custom block data
	 *
	 * @return $data
	 */
	public static function getCustomData() {
		$data = [];
		$data['custom'] = [
			'lang' => (isset($_SESSION['language_code'])) ? strtoupper($_SESSION['language_code']) : 'DE',
		];
		return $data;
	}

	/**
	 * Insert transaction, bank and nearest store details in the database
	 *
	 * @param $payment_method
	 * @param $response
	 *
	 * @return mixed
	 */
	public static function updateTransactionDetails($payment_method, $response, $status = true) {
		if ($status && $response['result']['status'] == 'SUCCESS') {
			$txn_details = self::getTransactionDetails($response);
			// Invoice payments
			if((in_array($response['transaction']['payment_type'], array('INVOICE', 'PREPAYMENT')))
			|| (in_array($response['transaction']['payment_type'], array('GUARANTEED_INVOICE', 'INSTALMENT_INVOICE'))
			&& $response['transaction']['status'] != 'PENDING')) {
				$txn_details .= self::getBankDetails($response);
			}
			// Cashpayment
			if ($response['transaction']['payment_type'] == 'CASHPAYMENT') {
				$txn_details .= self::getNearestStoreDetails($response);
			}
			if ($status && in_array($response['transaction']['payment_type'], array('INSTALMENT_INVOICE', 'INSTALMENT_DIRECT_DEBIT_SEPA')) && (in_array($response['transaction']['status'], array('CONFIRMED', 'ON_HOLD')))) {
				$txn_details .= self::formInstalmentPaymentReference($response);
			}
			return xtc_db_prepare_input($txn_details);
		}
	}

	/**
	 * Get transaction details
	 *
	 * @param $response
	 *
	 * @return $txn_details
	 */
	public static function getTransactionDetails($response) {
		$txn_details = '';
		if (!empty($response ['transaction']['tid'])) {
			$txn_details .= PHP_EOL . MODULE_PAYMENT_NOVALNET_TRANSACTION_ID . $response['transaction']['tid'];
			$txn_details .= ($response ['transaction']['test_mode'] == 1) ? PHP_EOL . MODULE_PAYMENT_NOVALNET_PAYMENT_MODE . PHP_EOL : '';
		}
		if ($response ['transaction']['amount'] == 0) {
			$txn_details .= PHP_EOL . MODULE_PAYMENT_NOVALNET_ZEROAMOUNT_BOOKING_MESSAGE;
		}
		// Only for Guarantee and instalment payments
		if (in_array($response['transaction']['payment_type'], array('GUARANTEED_INVOICE', 'GUARANTEED_DIRECT_DEBIT_SEPA', 'INSTALMENT_INVOICE', 'INSTALMENT_DIRECT_DEBIT_SEPA'))
			&& $response['transaction']['status'] == 'PENDING') {
			$txn_details .= PHP_EOL . MODULE_PAYMENT_NOVALNET_MENTION_GUARANTEE_PAYMENT_PENDING_TEXT . PHP_EOL;
		}
		// Only for Multibanco
		if ($response['transaction']['payment_type'] == 'MULTIBANCO') {
			$amount = xtc_format_price_order($response['transaction']['amount']/100, 1, $response['transaction']['currency']);
			$txn_details .= PHP_EOL . PHP_EOL . sprintf(MODULE_PAYMENT_NOVALNET_MULTIBANCO_NOTE, $amount);
			$txn_details .= PHP_EOL . sprintf(MODULE_PAYMENT_NOVALNET_PARTNER_PAYMENT_REFERENCE, $response['transaction']['partner_payment_reference']) . PHP_EOL;
		}
		return $txn_details;
	}

	/**
	 * Get Novalnet bank details and its reference
	 *
	 * @param $response
	 *
	 * @return $note
	 */
	public static function getBankDetails($response, $order_no = '') {
		$amount = xtc_format_price_order($response['transaction']['amount']/100, 1, $response['transaction']['currency']);
		if (!empty($response['instalment']['cycle_amount'])) {
			$amount = xtc_format_price_order($response ['instalment']['cycle_amount']/100, 1, $response['transaction']['currency']);
		}
		$note = PHP_EOL .sprintf(MODULE_PAYMENT_NOVALNET_AMOUNT_TRANSFER_NOTE, $amount) . PHP_EOL; // Assign ON_HOLD text by default
		if($response['transaction']['status'] != 'ON_HOLD' && !empty($response['transaction']['due_date'])) { // If due date is not empty
			if(!empty($response['instalment']['cycle_amount'])) { // For Instalment payment
				$note  = PHP_EOL .sprintf(MODULE_PAYMENT_NOVALNET_INSTALMENT_AMOUNT_TRANSFER_NOTE_DUE_DATE, $amount, $response ['transaction']['due_date'] ) . PHP_EOL;
			} else { // Other than Instalment payment
				$note = PHP_EOL .sprintf(MODULE_PAYMENT_NOVALNET_AMOUNT_TRANSFER_NOTE_DUE_DATE, $amount, $response['transaction']['due_date']) . PHP_EOL;
			}
		} else if(!empty( $response['instalment']['cycle_amount'] )) { // For Instalment payment without due date
			$note  = PHP_EOL . sprintf(MODULE_PAYMENT_NOVALNET_INSTALMENT_AMOUNT_TRANSFER_NOTE, $amount) . PHP_EOL;
		}
		$bank_details = array(
			'account_holder' =>PHP_EOL.MODULE_PAYMENT_NOVALNET_ACCOUNT_HOLDER ,
			'bank_name'      =>MODULE_PAYMENT_NOVALNET_BANK_NAME ,
			'bank_place'     =>MODULE_PAYMENT_NOVALNET_BANK_PLACE ,
			'iban'           =>MODULE_PAYMENT_NOVALNET_IBAN ,
			'bic'            =>MODULE_PAYMENT_NOVALNET_BIC ,
		);
		foreach ($bank_details as $key => $text) {
            if (! empty($response ['transaction']['bank_details'][ $key ])) {
                $note .= $text. $response['transaction']['bank_details'][ $key ] . PHP_EOL;
            }
        }
		if(!empty($order_no) && $response['transaction']['payment_type'] != 'INSTALMENT_INVOICE') {
			$note .= PHP_EOL.MODULE_PAYMENT_NOVALNET_PAYMENT_REFERENCE_TEXT .PHP_EOL;
			$note .= sprintf(MODULE_PAYMENT_NOVALNET_PAYMENT_REFERENCE, 1, ('TID ' . $response['transaction']['tid'])) . PHP_EOL;
			$invoice_ref = (isset($response['transaction']['invoice_ref']) && !empty($response['transaction']['invoice_ref'])) ? $response['transaction']['invoice_ref'] : 'BNR-' . MODULE_PAYMENT_NOVALNET_PROJECT_ID . '-' . $order_no;
			$note .= sprintf(MODULE_PAYMENT_NOVALNET_PAYMENT_REFERENCE, 2, $invoice_ref) . PHP_EOL;
		}
		return $note;
	 }

	/**
	 * Get instalment details to store in Novalnet Transaction details
	 *
	 * @param $response
	 * @param $total_amount
	 *
	 * @return string
	 */
	public static function storeInstalmentdetails($response, $total_amount)
    {
        if (empty($response['instalment'])) {
            return '{}';
        }
        $instalment = $response['instalment'];
        if (isset($instalment['cycle_dates'])) {
            $total_cycles = count($instalment['cycle_dates']);
        }
        $cycle_amount = $instalment['cycle_amount'];
        $last_cycle_amount = $total_amount - ($cycle_amount * ($total_cycles - 1)) ;
        $cycles = $instalment['cycle_dates'];
        $cycle_details = array();
		if (!empty($cycles)) {
			foreach ($cycles as $cycle => $cycle_date) {
				$cycle_details[$cycle -1 ]['date'] = $cycle_date;
				if(!empty($cycles[$cycle + 1])) {
					$cycle_details[$cycle -1 ]['next_instalment_date'] = $cycles[$cycle + 1];
				}
				$cycle_details[$cycle -1 ]['status'] = 'Pending';
				if (!empty($instalment['cycles_executed']) && $cycle == $instalment['cycles_executed']) {
					$cycle_details[$cycle -1 ]['reference_tid'] = !empty($instalment['tid']) ? $instalment['tid'] : (!empty($response['transaction']['tid']) ? $response['transaction']['tid'] : '');
					$cycle_details[$cycle -1 ]['status'] = 'Paid';
					$cycle_details[$cycle -1 ]['paid_date'] = date('Y-m-d H:i:s');
				}
				$cycle_details[$cycle -1 ]['instalment_cycle_amount'] = ($cycle == $total_cycles)?$last_cycle_amount : $instalment['cycle_amount'];
				$cycle_details[$cycle -1 ]['instalment_cycle_amount_orginal_amount'] = ($cycle == $total_cycles)?$last_cycle_amount : $instalment['cycle_amount'];
			}
		}
        return (!empty($cycle_details) ? json_encode($cycle_details) : '{}');
    }

	/**
	 * Add instalment details in end customer comments
	 *
	 * @param $response
	 *
	 * @return $txn_details
	 */
	public static function formInstalmentPaymentReference($response) {
		$txn_details = '';
		$order_total = self::getOrderAmount($_SESSION['novalnet']['payment_amount']);
        $total_amount = ($response['transaction']['amount'] < $order_total) ? $order_total : $response['transaction']['amount'];
		if ($response['transaction']['status'] == 'CONFIRMED') {
        	self::storeInstalmentdetails($response, $total_amount);
		}
        $amount = xtc_format_price_order($response['instalment']['cycle_amount']/100, 1, $response['transaction']['currency']);
        if (!empty($response['instalment']['currency'])) {
            $amount = xtc_format_price_order($response['instalment']['cycle_amount']/100, 1, $response['instalment']['currency']);
        }
		if ($response['result']['status'] == 'SUCCESS') {
			if ($response['transaction']['payment_type'] == 'INSTALMENT_INVOICE' && (in_array($response['transaction']['status'], array('CONFIRMED', 'ON_HOLD')))) {
				$txn_details .= PHP_EOL . MODULE_PAYMENT_NOVALNET_PAYMENT_REFERENCE_TEXT . PHP_EOL;
				$txn_details .= sprintf(MODULE_PAYMENT_NOVALNET_PAYMENT_REFERENCE, 1, ('TID ' . $response['transaction']['tid'])) . PHP_EOL;
				if (!empty($response['transaction']['invoice_ref'])) {
					$txn_details .= sprintf(MODULE_PAYMENT_NOVALNET_PAYMENT_REFERENCE, 2, $response['transaction']['invoice_ref']) . PHP_EOL;
				} elseif(!empty($response['transaction']['order_no'])) {
					$txn_details .= sprintf(MODULE_PAYMENT_NOVALNET_PAYMENT_REFERENCE, 2, ('BNR-' . MODULE_PAYMENT_NOVALNET_PROJECT_ID . '-' . $response['transaction']['order_no'])) . PHP_EOL;
				} else {
					$txn_details .= sprintf(MODULE_PAYMENT_NOVALNET_PAYMENT_REFERENCE, 2, ('BNR-' . MODULE_PAYMENT_NOVALNET_PROJECT_ID . '-###SHOPORDERNUMBER###')) . PHP_EOL;
				}
			}
			if ($response['transaction']['status'] == 'CONFIRMED') {
				$txn_details .=  PHP_EOL . PHP_EOL . MODULE_PAYMENT_NOVALNET_INSTALMENT_INSTALMENTS_INFO . PHP_EOL . MODULE_PAYMENT_NOVALNET_INSTALMENT_PROCESSED_INSTALMENTS . $response['instalment']['cycles_executed'] . PHP_EOL;
				$txn_details .=  MODULE_PAYMENT_NOVALNET_INSTALMENT_DUE_INSTALMENTS . $response['instalment']['pending_cycles'] . PHP_EOL;
				$txn_details .=  MODULE_PAYMENT_NOVALNET_INSTALMENT_NEXT_INSTALMENT_AMOUNT . $amount . PHP_EOL;
				if(!empty($response['instalment']['next_cycle_date'])) {
					$txn_details .=  MODULE_PAYMENT_NOVALNET_INSTALMENT_NEXT_INSTALMENT_DATE . date('Y-m-d', strtotime($response['instalment']['next_cycle_date'])) . PHP_EOL;
				}
			}
		}
		return $txn_details;
	 }

	/**
	 * Get nearest Cashpayment supported stores
	 *
	 * @param $response
	 *
	 * @return $txn_details
	 */
	public static function getNearestStoreDetails($response) {
		$txn_details = '';
		if (! empty($response['transaction']['due_date'])) {
			$txn_details .= PHP_EOL . PHP_EOL . MODULE_PAYMENT_NOVALNET_TRANS_SLIP_EXPIRY_DATE . date(DATE_FORMAT,strtotime($response['transaction']['due_date']));
		}
		$txn_details .= PHP_EOL . PHP_EOL .MODULE_PAYMENT_NOVALNET_NEAREST_STORE_DETAILS . PHP_EOL ;
		if (!empty($response['transaction']['nearest_stores'])) {
			foreach ($response['transaction']['nearest_stores'] as $store) {
                $txn_details .= PHP_EOL . $store['store_name'];
                $txn_details .= PHP_EOL . $store['street'];
                $txn_details .= PHP_EOL . $store['zip'] . ' ' . $store['city'];
                $country_name = xtc_db_fetch_array(xtc_db_query("select countries_name from " . TABLE_COUNTRIES . " where countries_iso_code_2 = '" . $store['country_code'] . "'"));
                if (!empty($country_name)) {
                    $txn_details .= PHP_EOL . $country_name['countries_name'];
                }
                $txn_details .= PHP_EOL . PHP_EOL;
            }
		}
		return $txn_details;
	}

	/**
	 * Get shop order status id
	 *
	 * @param $transaction_status
	 * @param $payment_method
	 *
	 * @return $order_status_id
	 */
	public static function getOrderStatus($transaction_status, $payment_method) {
		$order_status_id = constant('MODULE_PAYMENT_' . strtoupper($payment_method) . '_ORDER_STATUS');
		if ($transaction_status == 'ON_HOLD') {
			$order_status_id = MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_COMPLETE;
		} elseif (!in_array($payment_method, array('novalnet_invoice', 'novalnet_prepayment', 'novalnet_cashpayment', 'novalnet_multibanco')) && $transaction_status == 'PENDING') {
			$order_status_id = 1;
		}
		return $order_status_id;
	}

	/**
	 * Update order status and insert the transaction details in the database
	 *
	 * @param $order_id
	 * @param $txn_details
	 * @param $response
	 * @param $payment_method
	 *
	 * @return mixed
	 */
	public static function updateOrderStatus($order_id, $txn_details, $response, $payment_method) {
		global $order;
		if(empty($order) && !empty($order_id)) {
			$order = new order($order_id);
		}
		$customerId = isset($order->customer['ID']) ? $order->customer['ID'] : (isset($order->customer['csID']) ? $order->customer['csID'] : '');
		$customerId = !empty($response['customer']['customer_no']) ? $response['customer']['customer_no'] : $customerId;

		$payment_status = [];
		$status_update  = [];
		$payment_details = [];
		$payment_status['orders_status'] = $status_update['orders_status_id'] = self::getOrderStatus($response['transaction']['status'], $payment_method);
		$order->info['comments'] = $txn_details;
		$payment_status['comments'] = $status_update['comments']  = xtc_db_prepare_input($order->info['comments']);
		$novalnet_transaction_details = array(
			'order_no'      => $order_id,
			'tid'           => $response['transaction']['tid'],
			'amount'        => $response['transaction']['amount'],
			'currency'      => $response['transaction']['currency'],
			'customer_id'   => $customerId,
			'payment_type'  => $response['transaction']['payment_type'],
			'test_mode'     => $response['transaction']['test_mode'],
			'status'        => $response['transaction']['status'],
		);
		if (in_array($response['transaction']['payment_type'], array('INSTALMENT_INVOICE', 'INSTALMENT_DIRECT_DEBIT_SEPA'))
			&& ($response['transaction']['status'] == 'CONFIRMED')) {
				$order_total = self::getOrderAmount($_SESSION['novalnet']['payment_amount']);
				$total_amount = ($response['transaction']['amount'] < $order_total) ? $order_total : $response['transaction']['amount'];
				$novalnet_transaction_details['instalment_cycle_details'] = self::storeInstalmentdetails($response, $total_amount);
		}
		if (in_array($response['transaction']['payment_type'], array('INVOICE', 'PREPAYMENT', 'GUARANTEED_INVOICE', 'INSTALMENT_INVOICE'))) {
			$payment_details = $response['transaction']['bank_details'];
			$payment_details['novalnet_due_date'] = $response['transaction']['due_date'];
		} elseif ($response['transaction']['payment_type'] === 'CASHPAYMENT') {
			$payment_details = $response['transaction']['nearest_stores'];
			$payment_details['novalnet_checkout_token'] = $response['transaction']['checkout_token'];
            $payment_details['novalnet_checkout_js'] = $response['transaction']['checkout_js'];
			$payment_details['novalnet_due_date'] = $response['transaction']['due_date'];
		} elseif (($response['transaction']['payment_type'] != 'GOOGLEPAY') && !empty($response['transaction']['payment_data']['token']) && constant('MODULE_PAYMENT_'. strtoupper($payment_method) . '_TOKENIZATION') == 'true') {
			$payment_details = $response['transaction']['payment_data'];
			$check_duplicate_token = true;
			if (empty($response['custom']['inputval1'])) {
					$check_duplicate_token = false;
					$payment_details['token'] = $payment_details['token'];
			}
	
			if($payment_method !== 'novalnet_direct_debit_ach'){
			if (constant('MODULE_PAYMENT_'. strtoupper($payment_method) . '_AUTHENTICATE') == 'zero_amount') {
				$payment_details['zero_amount_booking'] = 1;
			}
		}
			// Delete duplicate tokens in novalnet table
			if(in_array($response['transaction']['payment_type'], array('DIRECT_DEBIT_SEPA', 'GUARANTEED_DIRECT_DEBIT_SEPA', 'INSTALMENT_DIRECT_DEBIT_SEPA', 'CREDITCARD','DIRECT_DEBIT_ACH'))
			&& $check_duplicate_token){
				self::checkDuplicateToken($customer_id, $response);
			}
		} elseif (!empty($response['transaction']['payment_data']['token']) && constant('MODULE_PAYMENT_'. strtoupper($payment_method) . '_AUTHENTICATE') == 'zero_amount' && constant('MODULE_PAYMENT_'. strtoupper($payment_method) . '_TOKENIZATION') == 'false') {
			$payment_details = array(
					'token' => $response['transaction']['payment_data']['token'],
					'zero_amount_booking' => 1
				);
		}
		$novalnet_transaction_details['payment_details'] = json_encode($payment_details);
		xtc_db_perform('novalnet_transaction_detail', $novalnet_transaction_details, 'insert');
		xtc_db_perform(TABLE_ORDERS, $payment_status, "update", "orders_id='$order_id'");
		xtc_db_perform(TABLE_ORDERS_STATUS_HISTORY, $status_update, "update", "orders_id='$order_id'");
	}

	/**
	 * Check duplicate token in novalnet table and update it
	 *
	 * @param $customer_id
	 * @param $response
	 *
	 * @return none
	 */
	public static function checkDuplicateToken($customer_id, $response) {
		$check_string = 'No Data';
		if(empty($response['transaction']['payment_data'])) {
			return true;
		}
		$payment_details = xtc_db_query("SELECT payment_details, id, amount, order_no FROM novalnet_transaction_detail WHERE customer_id='" . xtc_db_input($customerId) . "' AND payment_details !='' AND status = 'CONFIRMED' AND payment_type = '{$response['transaction']['payment_type']}'");
		if (in_array($response['transaction']['payment_type'], array('DIRECT_DEBIT_SEPA', 'GUARANTEED_DIRECT_DEBIT_SEPA', 'INSTALMENT_DIRECT_DEBIT_SEPA','DIRECT_DEBIT_ACH'))) {
			$check_string = (!empty($response['transaction']['payment_data']['iban'])) ? $response['transaction']['payment_data']['iban'] : $check_string;
			$check_data_payment = "SEPA";
		} elseif($response['transaction']['payment_type'] == 'CREDITCARD') {
			$check_value = $response['transaction']['payment_data'];
			$check_string = $check_value['card_brand'] . $check_value['card_number'] . $check_value['card_expiry_month'] . $check_value['card_expiry_year'];
			$check_data_payment = "CARD";
		}
		if($check_string == 'No Data' || empty($check_string)) {
			return true;
		}
		while ($payment_detail = xtc_db_fetch_array($payment_details)) {
			$payment_data = json_decode($payment_detail['payment_details'], true);
			$cardDetails = "";
			if ($payment_detail['amount'] <= 0 && !empty($payment_data['zero_amount_booking']) && $payment_data['zero_amount_booking'] == 1) {
				$cardDetails = array(
					'token' => $payment_data['token'],
					'zero_amount_booking' => $payment_data['zero_amount_booking']
				);
				$cardDetails = json_encode($cardDetails);
			}
			if($check_data_payment == "CARD") {
				$check_data = $payment_data['card_brand'] . $payment_data['card_number'] . $payment_data['card_expiry_month'] . $payment_data['card_expiry_year'];
			}else if($check_data_payment == "SEPA"){
				$check_data = $payment_data['iban'];
			}
			if(($check_string == $check_data) && !empty($payment_detail['id'])) {
				$payment_details = xtc_db_query("UPDATE novalnet_transaction_detail SET payment_details = '$cardDetails' WHERE id = {$payment_detail['id']} AND order_no = {$payment_detail['order_no']} AND customer_id = {$customer_id}");
			}
		}
	}

	/**
	 * Hadnle temporary created order for the failure transaction
	 *
	 * @param string $payment_method
	 * @param int $order_id
	 * @param array $response
	 * @param string $error_text
	 *
	 * @return none
	 */
	public static function processTempOrderFail($payment_method, $order_id, $response, $error_text = '') {
		if (!empty($order_id)) {
			$status_text = self::getServerResponse($response);
			$status_text = (!empty($status_text)) ? $status_text : $error_text;
			self::updateTempOrderFail($order_id, $response['tid'], $status_text);
			xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $payment_method . '&error=' . urlencode($status_text), 'SSL', true, false));
		}
	}

	/**
	 * Update order status for the temporary created order.
	 *
	 * @param int $order_id
	 * @param bigint $tid
	 * @param string $status_text
	 *
	 * @return none
	 */
	public static function updateTempOrderFail($order_id, $tid, $status_text) {
		$note = '';
		$note  = (!empty($tid)) ? PHP_EOL . MODULE_PAYMENT_NOVALNET_TRANSACTION_ID . $tid . PHP_EOL : '';
		$note .= $status_text;
		xtc_db_query('UPDATE '.TABLE_ORDERS.' SET orders_status = '.MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED.', comments = "'.xtc_db_prepare_input($note).'" WHERE orders_id= '.$order_id);
		xtc_db_query('UPDATE ' . TABLE_ORDERS_STATUS_HISTORY . ' SET orders_status_id = ' . MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED . ', comments = "'.xtc_db_prepare_input($note).'" WHERE orders_id='.$order_id);
	}

	/**
	 * Handle redirect payments success response
	 *
	 * @param $payment_code
	 * @param $request
	 * @param $session_order_id
	 *
	 * @return none
	 * */
	public static function handleRedirectSuccessResponse($request, $session_order_id, $payment_code) {
		global $order;
		if(empty($order) && !empty($session_order_id)) {
			$order = new order($session_order_id);
		}
		$transaction_details = array('transaction' => array('tid' => $request['tid']));
		$response = self::sendRequest($transaction_details, self::get_action_endpoint('transaction_details'));
		$order_no = (!empty($response['transaction']['order_no'])) ? $response['transaction']['order_no'] : $session_order_id;
		$order->info['comments'] .= self::updateTransactionDetails($payment_code, $response);
		if(!empty($order_no)) {
			self::updateOrderStatus($order_no, $order->info['comments'], $response, $payment_code);
		}
	}

	/**
	 * Send transaction update call to update order_no in Novalnet
	 *
	 * @param $order_no
	 *
	 * @return none
	 */
	public static function sendTransactionUpdate($order_no) {
		$transaction_param = [
			'transaction' => [
				'tid'       => $_SESSION['response']['transaction']['tid'],
				'order_no'  => $order_no,
			],
		];
		$params = array_merge($transaction_param, self::getCustomData());
		self::sendRequest($params, self::get_action_endpoint('transaction_update'));
		if (isset($_SESSION['response'])) {
			unset($_SESSION['response']);
		}
	}

	/**
	 * Validate response checksum
	 *
	 * @param $data
	 *
	 * @return boolean
	 */
	public static function validateCheckSum($data) {
		$payment_access_key = MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY;
		if (!empty($data['checksum']) && !empty($data['tid']) && !empty($data['status']) && !empty($_SESSION['nn_txn_secret']) && !empty($payment_access_key)) {
			$checksum = hash('sha256', $data['tid'] . $_SESSION['nn_txn_secret'] . $data['status'] . strrev($payment_access_key));
			if ($checksum == $data['checksum']) {
				return true;
			}
		}
		return false;
	 }

	/**
	 * Check for the success status of the
	 * Novalnet payment call.
	 *
	 * @param $data.
	 *
	 * @return boolean
	 */
	public static function is_success_status( $data ) {
		return ( ( ! empty( $data['result']['status'] ) && 'SUCCESS' === $data['result']['status'] ) || ( ! empty( $data['status'] ) && 'SUCCESS' === $data['status'] ) );
	}

	/**
	* To form guarantee payment order confirmation mail
	*
	* @param $datas
	* @param $db_details
	*/
    public static function sendOrderUpdateMail($datas, $db_details = ''){
		if($db_details == '') {
			$order = new order($datas['order_no']);
			$customername  = $order->customer['firstname'] . ' ' . $order->customer['lastname'];
			$customeremail = $order->customer['email_address'];
		} else {
			$customer_dbvalue = xtc_db_fetch_array(xtc_db_query("SELECT customers_firstname, customers_lastname, customers_email_address FROM " . TABLE_CUSTOMERS . " WHERE customers_id= ". xtc_db_input($db_details['customer_id']) . "  ORDER BY customers_id DESC LIMIT 1"));
			$customername  = $customer_dbvalue['customers_firstname'] . $customer_dbvalue['customers_lastname'];
			$customeremail = $customer_dbvalue['customers_email_address'];
		}
		$subject = sprintf(MODULE_PAYMENT_GUARANTEE_PAYMENT_MAIL_SUBJECT, $datas['order_no'], STORE_NAME);
        $get_mail_content = NovalnetHelper::get_mail_content_array_novalnet($datas['comments'], $datas);
         $html_mail= $get_mail_content['smarty']->fetch(DIR_FS_CATALOG.'lang/' . $get_mail_content['lang'] . '/original_mail_templates/order_mail.html');
         $txt_mail= $get_mail_content['smarty']->fetch(DIR_FS_CATALOG.'lang/' . $get_mail_content['lang'] . '/original_mail_templates/order_mail.txt');
		xtc_php_mail(EMAIL_FROM, STORE_NAME, $customeremail, STORE_OWNER, '', '', '', '', '', $subject, $html_mail, $txt_mail);
	}

	/**
     * To form mail templete as like default mail
     *
     * @param $data array
     */

	 public static function get_mail_content_array_novalnet($comments,$data) {
		$smarty = new Smarty;
        if(empty($data['order'])){
            $t_order = new order($data['order_no']);
	    }else{
			$t_order = $data['order'];
		}
        $order_lang = xtc_db_fetch_array(xtc_db_query("SELECT currency,language FROM ".TABLE_ORDERS." WHERE orders_id='". xtc_db_input($data['order_no']) ."'"));
         $mail_lang_set =  xtc_db_fetch_array(xtc_db_query("SELECT code FROM ".TABLE_LANGUAGES." WHERE directory='". xtc_db_input($order_lang['language']) ."'"));

		$t_order_id = $data['order_no'];
		$t_language = $order_lang['language'];
	    $t_language_id = $mail_lang_set['code'];
        // SET CONTENT DATA
        $smarty->assign('csID', $t_order->customer['csID']);
        $smarty->assign('customer_vat', $t_order->customer['vat_id']);
        $smarty->assign('order_data', NovalnetHelper::getOrderData($t_order_id,$order_lang['currency']));
        $t_order_total = NovalnetHelper::getTotalData($t_order_id);
        $smarty->assign('order_total', $t_order_total['data']);
        $smarty->assign('language', $t_language);
        $smarty->assign('language_id', $t_language_id);
        $smarty->assign('tpl_path', DIR_FS_CATALOG . StaticGXCoreLoader::getThemeControl()->getThemeHtmlPath());
        $smarty->assign('logo_path',
                                HTTP_SERVER . DIR_WS_CATALOG . StaticGXCoreLoader::getThemeControl()
                                    ->getThemeImagePath());
        $smarty->assign('oID', $t_order_id);
        $t_payment_method = '';
        if ($t_order->info['payment_method'] != '' && $t_order->info['payment_method'] != 'no_payment') {
            $t_payment_method = PaymentTitleProvider::getStrippedTagsTitle($t_order->info['payment_method']);
            $smarty->assign('PAYMENT_MODUL', $t_order->info['payment_method']);
        }
        $smarty->assign('PAYMENT_METHOD', $t_payment_method);
        $smarty->assign('NAME', $t_order->customer['name']);
        $smarty->assign('GENDER', $t_order->customer['gender']);
        $smarty->assign('COMMENTS', nl2br($comments));
        $smarty->assign('EMAIL', $t_order->customer['email_address']);
        $smarty->assign('PHONE', $t_order->customer['telephone']);
        if (defined('EMAIL_SIGNATURE')) {
            $smarty->assign('EMAIL_SIGNATURE_HTML', nl2br(EMAIL_SIGNATURE));
            $smarty->assign('EMAIL_SIGNATURE_TEXT', EMAIL_SIGNATURE);
        }
        // PREPARE HTML MAIL
        $smarty->assign('address_label_customer', xtc_address_format($t_order->customer['format_id'],$t_order->customer, 1, '', '<br />'));
        $smarty->assign('address_label_shipping', xtc_address_format($t_order->delivery['format_id'], $t_order->delivery, 1,'','<br />'));
        $smarty->assign('address_label_payment', xtc_address_format($t_order->billing['format_id'], $t_order->billing , 1, '', '<br />'));
        return array( 'lang' =>  $t_language ,'smarty' => $smarty);
    }

	/**
     * Get order data
     *
     * @param $oID
     * @param $currency
     *
     * @return $order_data
     */
    public static function getOrderData($oID, $currency) {
			require_once(DIR_FS_INC . 'xtc_get_attributes_model.inc.php');

			$order_query = "SELECT
									  op.products_id,
									  op.orders_products_id,
									  op.products_model,
									  op.products_name,
									  op.checkout_information,
									  op.final_price,
									  op.products_shipping_time,
									  op.products_quantity,
									  opqu.quantity_unit_id,
									  opqu.unit_name
								  FROM " . TABLE_ORDERS_PRODUCTS . " op
								  LEFT JOIN orders_products_quantity_units opqu USING (orders_products_id)
								  WHERE op.orders_id = '" . (int)$oID . "'";
			$order_data = array();
			$order_query = xtc_db_query($order_query);
			while($order_data_values = xtc_db_fetch_array($order_query)) {
				$attributes_query = "SELECT
									  products_options,
									  products_options_values,
									  price_prefix,
									  options_values_price
									  FROM " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . "
									  WHERE orders_products_id='" . $order_data_values['orders_products_id'] . "'
									  AND orders_id='" . (int)$oID . "'";
				$attributes_data = '';
				$attributes_model = '';
				$attributes_query = xtc_db_query($attributes_query);
				while($attributes_data_values = xtc_db_fetch_array($attributes_query)) {
					$attributes_data .= '<br />' . $attributes_data_values['products_options'] . ':' . $attributes_data_values['products_options_values'];
					$attributes_model .= '<br />' . xtc_get_attributes_model($order_data_values['products_id'], $attributes_data_values['products_options_values'], $attributes_data_values['products_options']);
				}

				// properties
				$coo_properties_control = MainFactory::create_object('PropertiesControl');
				$t_properties_array = $coo_properties_control->get_orders_products_properties($order_data_values['orders_products_id']);

				if(ACTIVATE_SHIPPING_STATUS == 'true') {
					$shipping_time = $order_data_values['products_shipping_time'];
				}
				else {
					$shipping_time = '';
				}
				// BOF GM_MOD GX-Customizer
				require(DIR_FS_CATALOG . 'gm/modules/gm_gprint_order.php');
				$order_data[] = array(
					'PRODUCTS_MODEL' 			=> $order_data_values['products_model'],
					'PRODUCTS_NAME' 			=> $order_data_values['products_name'],
					'CHECKOUT_INFORMATION' 		=> $order_data_values['checkout_information'],
					'CHECKOUT_INFORMATION_TEXT' => html_entity_decode_wrapper(strip_tags($order_data_values['checkout_information'])),
					'PRODUCTS_SHIPPING_TIME' 	=> $shipping_time,
					'PRODUCTS_ATTRIBUTES' 		=> $attributes_data,
					'PRODUCTS_ATTRIBUTES_MODEL' => $attributes_model,
					'PRODUCTS_PROPERTIES' 		=> $t_properties_array,
					'PRODUCTS_PRICE' 			=> 	xtc_format_price_order($order_data_values['final_price'], 1, $currency),
					'PRODUCTS_SINGLE_PRICE' 	=> xtc_format_price_order($order_data_values['final_price'] / $order_data_values['products_quantity'], 1, $currency),
					'PRODUCTS_QTY' 				=> gm_prepare_number($order_data_values['products_quantity'], ','),
					'UNIT' 						=> $order_data_values['unit_name']
				);
			}
			return $order_data;
	}

	/**
	 * Get total data
	 *
	 * @param $oID
	 *
	 * @return array
	 */
	public static function getTotalData($oID) {
		// get order_total data
		$oder_total_query = "SELECT
									title,
									text,
									class,
									value,
									sort_order
								FROM " . TABLE_ORDERS_TOTAL . "
								WHERE orders_id='" . (int)$oID . "'
								ORDER BY sort_order ASC";

		$order_total = array();
		$oder_total_query = xtc_db_query($oder_total_query);
		while($oder_total_values = xtc_db_fetch_array($oder_total_query)) {
			$order_total[] = array(
				'TITLE' => $oder_total_values['title'],
				'CLASS' => $oder_total_values['class'],
				'VALUE' => $oder_total_values['value'],
				'TEXT' 	=> $oder_total_values['text']
			);
			if($oder_total_values['class'] == 'ot_total') {
				$total = $oder_total_values['value'];
			}
		}
		return array('data' => $order_total, 'total' => $total);
	}


	/**
	 * Validate customer date or birth
	 *
	 * @param $dob
	 *
	 * @return $error_message
	 */
	public static function dateOfBirth($dob) {
		$error_message = '';
		if (time() < strtotime('+18 years', strtotime($dob))) {
			$error_message = MODULE_PAYMENT_GUARANTEE_PAYMENT_AGE_ERROR_MESSAGE;
			return $error_message;
		}
	}

	/**
	 * Validate customer email
	 *
	 * @param $emails
	 *
	 * @return mixed
	 */
	public static function validateEmail($emails) {
		$email = explode(',', $emails);
		foreach ($email as $value) {
			// Validate E-mail.
			if (!xtc_validate_email($value)) {
				return false;
			}
		}
		return $value;
	}

	/**
	 * Get customer details
	 *
	 * @param $customer_email
	 *
	 * @return array
	 */
	public static function collectCustomerDobGenderFax($customer_email = '') {
		if ($customer_email != '') {
			$querySearch = (isset($_SESSION['customer_id']) && $_SESSION['customers_status']['customers_status_id'] != '1') ? 'customers_id= "'. xtc_db_input($_SESSION['customer_id']).'"' : 'customers_email_address= "'. xtc_db_input($customer_email).'"';
			$select_query = xtc_db_query("SELECT customers_id, customers_cid, customers_gender, customers_dob, customers_fax, customers_vat_id FROM ". TABLE_CUSTOMERS . " WHERE ".$querySearch." ORDER BY customers_id");
			$customer_dbvalue = xtc_db_fetch_array($select_query);
			if (!empty($customer_dbvalue)) {
				$customer_dbvalue['customers_dob'] = (($customer_dbvalue['customers_dob'] != '0000-00-00 00:00:00') && ($customer_dbvalue['customers_dob'] )) ? date('d.m.Y', strtotime($customer_dbvalue['customers_dob'])) : '';
				if($customer_dbvalue['customers_dob']) {
					$year = substr($customer_dbvalue['customers_dob'], -4);
					if($year < '1900') {
						return '';
					}
				}
				return $customer_dbvalue;
			}
		}

		return array('u', '', '', '', '');
	}

	/**
	 * Create date of birth field
	 *
	 * @param $name
	 * @param $customer_details
	 *
	 * @return string
	 */
	public static function getGuaranteeField($name, $customer_details) {
		$placeholder = MODULE_PAYMENT_NOVALNET_GUARANTEE_DOB_FORMAT;
		return xtc_draw_input_field($name, (isset($customer_details['customers_dob']) ? $customer_details['customers_dob'] : ''), 'id="' . $name . '" placeholder="' . $placeholder . '" autocomplete="OFF" maxlength="10" onkeydown="return NovalnetUtility.isNumericBirthdate(this,event)"') . xtc_draw_hidden_field('', $_SESSION['language_code'], 'id="nn_shop_lang"');
	}

	/**
	 * Check billing and shpping address is same
	 *
	 * @return boolean
	 */
	public static function isBillingShippingsame() {
		global $order;
		$delivery_address = array(
			'street'   => ($order->delivery['street_address']),
			'city'     => ( $order->delivery['city']),
			'postcode' => ( $order->delivery['postcode']),
			'country'  => (isset($order->delivery['country_iso_code_2']) ? $order->delivery['country_iso_code_2'] : $order->delivery['country']['iso_code_2']),
		);
		$billing_address = array(
			'street'   => ($order->billing['street_address']),
			'city'     => ($order->billing['city']),
			'postcode' => ($order->billing['postcode']),
			'country'  => (isset($order->billing['country_iso_code_2']) ? $order->billing['country_iso_code_2'] : $order->billing['country']['iso_code_2']),
		);
		if ((empty($delivery_address['street']) && empty($delivery_address['city']) && empty($delivery_address['postcode']) && empty($delivery_address['country'])) &&
            !empty($billing_address)) {
            return true;
        } elseif ($billing_address === $delivery_address) {
            return true;
        }
        return false;
	}

	/**
	 * Check guarantee payments allowed countries for B2B and B2C
	 *
	 * @param $country
	 * @param $is_b2b
	 * @param $company
	 *
	 * @return $is_valid
	 */
	public static function checkGuaranteeCountries($country, $is_b2b, $company) {
		$listOfAllCountriesInEU = [
				'AT', // Österreich
				'BE', // Belgien
				'BG', // Bulgarien
				'CY', // Zypern
				'CZ', // Tschechische Republik
				'CH', // Schweiz
				'DE', // Deutschland
				'DK', // Dänemark
				'EE', // Estland
				'ES', // Spanien
				'FI', // Finnland
				'FR', // Frankreich
				'GR', // Griechenland
				'HR', // Kroatien
				'HU', // Ungarn
				'IE', // Irland
				'IT', // Italien
				'LT', // Litauen
				'LU', // Luxemburg
				'LV', // Lettland
				'MT', // Malta
				'NL', // Niederlande
				'PL', // Polen
				'PT', // Portugal
				'RO', // Rumänien
				'SE', // Schweden
				'SI', // Slowenien
				'SK', // Slowakei
				'XI', // Nordirland
		];
		$listOfDachCountries = [
				'AT', // Österreich
				'CH', // Schweiz
				'DE', // Deutschland
		];
		$is_valid =(!empty($company) && $is_b2b == 'true') ? in_array($country, $listOfAllCountriesInEU, true) : in_array($country, $listOfDachCountries, true);
		return $is_valid;
	}

	/**
	 * Get customer IP address
	 *
	 * @return string
	 */
	public static function getIPAddress() {
		$ip_keys = [ 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR' ];
		foreach ($ip_keys as $key) {
			if (array_key_exists($key, $_SERVER) === true) {
				foreach (explode(',', $_SERVER[$key]) as $ip) {
					$ip = trim($ip);
					if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === true) {
						return $ip;
					}
				}
			}
		}
		return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false;
	}

	/**
	 * Includes the support scripts in payment configuration page
	 *
	 * @param $payment_code
	 * @param $authorize
	 *
	 * @return none
	 */
	public static function includeAdminJS($payment_code, $authorize = false) {
		if (isset($_GET['module']) && $_GET['module'] == $payment_code && isset($_GET['action']) && $_GET['action'] == 'edit') {
			echo '<script src="' . DIR_WS_CATALOG . 'ext/novalnet/js/novalnet.js" type="text/javascript"></script>';
			if ($authorize) {
				$auth = constant('MODULE_PAYMENT_'. strtoupper($payment_code) . '_AUTHENTICATE');
				$elementID = $payment_code . '_auth';
				echo '<input type="hidden" id="' . $elementID . '" value= '.$auth.' />';
			}
		}
	}

	/**
	 * Get customer data
	 *
	 * @param $receivedData
	 *
	 * @return $data
	 */
	public static function getCustomerWalletData($receivedData = '') {
		global $order;
		$data['customer'] = [
			'gender'      => !empty($order->billing['gender']) ? $order->billing['gender'] : 'u',
			'first_name'  => (!empty($receivedData) ? $receivedData['order']['billing']['contact']['firstName'] : $order->billing['firstname']),
			'last_name'   => (!empty($receivedData) ? $receivedData['order']['billing']['contact']['lastName'] : $order->billing['lastname']),
			'email'       => (!empty($receivedData) ? (($order->info['payment_method'] == 'novalnet_googlepay') ? $receivedData['order']['billing']['contact']['email'] : $receivedData['order']['shipping']['contact']['email']) : $order->customer['email_address']),
			'customer_ip' => self::getIPAddress(),
			'customer_no' => $_SESSION['customer_id'],
			'billing'     => [
				'street'            => (!empty($receivedData) ? $receivedData['order']['billing']['contact']['addressLines'] : $order->billing['street_address']),
				'city'              => (!empty($receivedData) ? $receivedData['order']['billing']['contact']['locality'] : $order->billing['city']),
				'zip'               => (!empty($receivedData) ? $receivedData['order']['billing']['contact']['postalCode'] : $order->billing['postcode']),
				'country_code'      => (!empty($receivedData) ? $receivedData['order']['billing']['contact']['countryCode'] : $order->billing['country']['iso_code_2']),
				'search_in_street'  => '1',
			],
		];
		if (!empty($order->billing['company'])) {
			$data['customer']['billing']['company'] = $order->billing['company'];
		}
		if (self::isBillingShippingsame()) {
			$data['customer']['shipping']['same_as_billing'] = 1;
		} else if (!empty($receivedData['order']['shipping'])) {
			$data['customer']['shipping']    = [
				'street'        => (!empty($receivedData) ? $receivedData['order']['shipping']['contact']['addressLines'] : $order->delivery['street_address']),
				'city'          => (!empty($receivedData) ? $receivedData['order']['shipping']['contact']['locality'] : $order->delivery['city']),
				'zip'           => (!empty($receivedData) ? $receivedData['order']['shipping']['contact']['postalCode'] : $order->delivery['postcode']),
				'country_code'  => (!empty($receivedData) ? $receivedData['order']['shipping']['contact']['countryCode'] : $order->delivery['country']['iso_code_2']),
			];
			if (!empty($order->delivery['company'])) {
				$data['customer']['shipping']['company'] = $order->delivery['company'];
			}
		}
		return $data;
	}

	/**
	* Update order status in the shop
	*
	* @param integer $order_id
	* @param string $order_status
	* @param string $message
	*/
	public static function updateStatus($order_id, $order_status, $message) {
		$comments = xtc_db_fetch_array(xtc_db_query("SELECT comments FROM ". TABLE_ORDERS ." WHERE orders_id = '$order_id'"));
		xtc_db_perform(TABLE_ORDERS, array(
			'orders_status' => $order_status,
		), "update", "orders_id = '$order_id'");
		xtc_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments, customer_id) values ('".xtc_db_input($order_id)."', '".xtc_db_input($order_status)."', '" .date('Y-m-d H:i:s') . "', '1', '".xtc_db_prepare_input($message)."', '0')");
	}
}
?>
