<?php
/**
 * Novalnet payment module related file
 * This module is used for real time processing of
 * Novalnet transaction of customers.
 *
 * Copyright (c) Novalnet
 *
 * Released under the GNU General Public License
 * This free contribution made by request.
 * If you have found this script useful a small
 * recommendation as well as a comment on merchant form
 * would be greatly appreciated.
 *
 * Script : NovalnetHelper.class.php
 *
 */
include_once(DIR_FS_INC . 'xtc_format_price_order.inc.php');
include_once(DIR_FS_INC . 'xtc_validate_email.inc.php');
include_once(DIR_FS_INC . 'xtc_get_countries_with_iso_codes.inc.php');
include_once (DIR_FS_INC.'xtc_php_mail.inc.php');
class NovalnetHelper {

    /**
     * Generate 30 digit unique string
     *
     * return string
     */
    public static function uniqueRandomString() {
        $getUniqueid = array( 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', '3', '4', '5', '6', '7', '8', '9', '0' );
        shuffle($getUniqueid);
        return substr(implode($getUniqueid, ""), 0, 30);
    }

    /**
     * Load the language contents from the novalnet package language file (classes directory)
     *
     * @param string $langid
     * @return array
     */
    public static function loadLocaleContents($langid) {
        $lang_file = DIR_WS_LANGUAGES . $langid . '/modules/payment/novalnet.php';
        if (!file_exists($lang_file)) {
            $lang_file = DIR_WS_LANGUAGES .'german/modules/payment/novalnet.php'; // Default language file
        }
        return include_once($lang_file);
    }

    /**
     * Return refill hash - Direct Debit SEPA
     *
     * @param string paymentname
     * return string
     */
    public static function getSepaRefillHash($paymentname) {
		return (!empty($_SESSION['novalnet']['novalnet_sepa']['hash']) && (MODULE_PAYMENT_NOVALNET_SEPA_AUTO_FILL == 'True') ? $_SESSION['novalnet']['novalnet_sepa']['hash'] : ((MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_REFILL == 'True') ? self::getLastSuccessTransProcessKey($paymentname) : ''));
    }
     /**
     * Return last successful transaction payment status
     *
     * @return string
     */
    public static function getLastSuccessTransProcessKey() {
		 $sqlQuerySet = xtc_db_fetch_array(xtc_db_query("SELECT payment_type, process_key FROM novalnet_transaction_detail WHERE customer_id='" . xtc_db_input($_SESSION['customer_id']) . "'order by id desc limit 1"));
        return ($sqlQuerySet['process_key'] != '') ? $sqlQuerySet['process_key'] : '';
    }

    /**
     * Validate the backend configuration and display the error message
     *
     * return boolean
     */
    public static function validateMerchantConfiguration() {
		if(self::novalnetStringCheck(MODULE_PAYMENT_INSTALLED, $_GET['module'])) {
			list( $error,$merchant_api_error,$type) = self::merchantValidate($_GET['module']);
		}
        // To validate the vendor configuration in shop backend
        if ($merchant_api_error && self::novalnetStringCheck(MODULE_PAYMENT_INSTALLED, $_GET['module']) && empty($_GET['action']) && $_GET['action'] != 'edit'  && $_GET['module'] == 'novalnet_config') { // Through Novalnet Vendor Configuration error message in backend
			// To validate Subscription Tariff period 1 and show error message
			if ($merchant_api_error && in_array($type, array('subscription_period' , 'subscription_period2', 'subscription_period_amount'))) { // To validate Subscription Tariff period 2 and show error message
				echo self::displayErrorMessage(MODULE_PAYMENT_NOVALNET_CONFIG_BLOCK_TITLE,$error);
			} else { // To validate the other vendor configuration and show the error message
				echo self::displayErrorMessage(MODULE_PAYMENT_NOVALNET_CONFIG_BLOCK_TITLE,'');
			}
        }
        //To validate the prepayment payment reference in shop backend
		if((defined('MODULE_PAYMENT_NOVALNET_INVOICE_ENABLE_MODULE') && $_GET['module'] == 'novalnet_invoice') || (defined('MODULE_PAYMENT_NOVALNET_PREPAYMENT_ENABLE_MODULE') && $_GET['module'] == 'novalnet_prepayment') || (defined('MODULE_PAYMENT_NOVALNET_SEPA_ENABLE_MODULE') && $_GET['module'] == 'novalnet_sepa')  && constant('MODULE_PAYMENT_'.strtoupper($_GET['module']).'_ENABLE_MODULE') == 'True') {
			if (self::novalnetStringCheck(MODULE_PAYMENT_INSTALLED, 'novalnet_invoice') && $merchant_api_error && $type == 'invoice_due_date') {
				if (!isset($_GET['action']) && $_GET['action'] != 'edit' && $_GET['module'] == 'novalnet_invoice') {
					echo self::displayErrorMessage(MODULE_PAYMENT_NOVALNET_INVOICE_BLOCK_TITLE, $error);
				}
			} else if (in_array($_GET['module'], array('novalnet_invoice','novalnet_prepayment')) && self::novalnetStringCheck(MODULE_PAYMENT_INSTALLED, $_GET['module']) && $merchant_api_error && $type == 'payment_reference') {
				if (!isset($_GET['action']) && $_GET['action'] != 'edit' && in_array($_GET['module'] ,array('novalnet_invoice','novalnet_prepayment'))) {
					echo self::displayErrorMessage(constant('MODULE_PAYMENT_'.strtoupper($_GET['module']).'_BLOCK_TITLE'), $error);
				}
			} else if (self::novalnetStringCheck(MODULE_PAYMENT_INSTALLED, 'novalnet_sepa') && $merchant_api_error && $type == 'sepa_due_date') {
				if (!isset($_GET['action']) && $_GET['action'] != 'edit' && $_GET['module'] == 'novalnet_sepa' ) {
					echo self::displayErrorMessage(MODULE_PAYMENT_NOVALNET_SEPA_BLOCK_TITLE, $error);
				}
			} else  if( self::novalnetStringCheck(MODULE_PAYMENT_INSTALLED, $_GET['module']) && ( self::novalnetStringCheck(MODULE_PAYMENT_INSTALLED, 'novalnet_sepa') || self::novalnetStringCheck(MODULE_PAYMENT_INSTALLED, 'novalnet_invoice') ) && $merchant_api_error ) {
				if($type == 'amount_invaild') {
					if (!isset($_GET['action']) && $_GET['action'] != 'edit' && in_array($_GET['module'] ,array('novalnet_sepa','novalnet_invoice'))) {
						echo self::displayErrorMessage(constant('MODULE_PAYMENT_'.strtoupper($_GET['module']).'_BLOCK_TITLE'), $error);
					}
				} else if($type == 'guarantee_minimum') {
					if (!isset($_GET['action']) && $_GET['action'] != 'edit' && in_array($_GET['module'] ,array('novalnet_sepa','novalnet_invoice'))) {
						echo self::displayErrorMessage(constant('MODULE_PAYMENT_'.strtoupper($_GET['module']).'_BLOCK_TITLE'), $error);
					}
				} else if($type == 'guarantee_maximum') {
					if (!isset($_GET['action']) && $_GET['action'] != 'edit' && in_array($_GET['module'] ,array('novalnet_sepa','novalnet_invoice'))) {
						echo self::displayErrorMessage(constant('MODULE_PAYMENT_'.strtoupper($_GET['module']).'_BLOCK_TITLE'), $error);
					}
				}
			}
		}
		if ( $merchant_api_error && $type == 'function_exits') {
				if (!isset($_GET['action']) && $_GET['action'] != 'edit' ) {
					echo self::displayErrorMessage(MODULE_PAYMENT_NOVALNET_CONFIG_BLOCK_TITLE,$error);
				}
		}
        return true;
    }

    /**
     * Checks for the given string in given text
     *
     * @param  string $value
     * @param  string $data
     * @return boolean
     */
    public static function novalnetStringCheck($value, $data = 'novalnet') {
        return (strpos($value, $data) !== false);
    }


    /**
     * Validate the payment configuration and hide in checkout page
     *
     * @param string $payment_name
     * @return boolean
     */
    public static function merchantValidate($payment_name) {
		$pattern = "/^\d+\|\d+\|[\w-]+\|\w+\|\w+\|(|\d+)\|(|\d+)\|(|\d+)\|(|\d+)\|(|\w+)\|(|\w+)$/";
        $value   = MODULE_PAYMENT_NOVALNET_VENDOR_ID . '|' . MODULE_PAYMENT_NOVALNET_PRODUCT_ID . '|' . MODULE_PAYMENT_NOVALNET_TARIFF_ID . '|' . MODULE_PAYMENT_NOVALNET_AUTHCODE . '|' . MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY . '|' . MODULE_PAYMENT_NOVALNET_MANUAL_CHECK_LIMIT . '|' . MODULE_PAYMENT_NOVALNET_REFERRER_ID . '|' . MODULE_PAYMENT_NOVALNET_GATEWAY_TIMEOUT . '|' . MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD2_AMOUNT . '|' . MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD2 . '|' . MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD;
        preg_match($pattern, $value, $match);
        if(MODULE_PAYMENT_NOVALNET_PUBLIC_KEY == '') {
			return array(MODULE_PAYMENT_NOVALNET_CONFIG_BLOCK_TITLE,true,'basic_configuration');
        } else if (empty($match[0])) {
			return array(MODULE_PAYMENT_NOVALNET_CONFIG_BLOCK_TITLE,true,'basic_configuration');
		} else if ((MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD2_AMOUNT != '' && MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD2 == '') || (MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD2_AMOUNT == '' && MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD2 != '')) {
			return array(MODULE_PAYMENT_NOVALNET_VALID_MERCHANT_CREDENTIALS_ERROR,true);
		} else if (((MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD !='' && !preg_match('/^[1-9]+\d*[d|m|y]{1}$/', MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD)))) {
            return array(MODULE_PAYMENT_NOVALNET_VAILD_SUBSCRIPTION_PERIOD_ERROR,true,'subscription_period');
        } else if( MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD2_AMOUNT != '' && (!preg_match('/^[1-9]\d*[d|m|y]{1}$/', MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD2) || (MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD !='' && !preg_match('/^[1-9]\d*[d|m|y]{1}$/', MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD)))) {
			return array(MODULE_PAYMENT_NOVALNET_VAILD_SUBSCRIPTION_PERIOD_ERROR,true,'subscription_period2');
		} else if (MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD2_AMOUNT!='' && MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD2 !='' && MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD == '') {
			return array(MODULE_PAYMENT_NOVALNET_VALID_MERCHANT_CREDENTIALS_ERROR,true);
		} elseif ((MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_TO != '' && !self::validateEmail(MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_TO)) || (MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_BCC != '' && !self::validateEmail(MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_BCC))) {
            return array(MODULE_PAYMENT_NOVALNET_CONFIG_BLOCK_TITLE,true,'callback_mail');
        } else if (MODULE_PAYMENT_NOVALNET_MANUAL_CHECK_LIMIT != '' && !is_numeric(MODULE_PAYMENT_NOVALNET_MANUAL_CHECK_LIMIT)) {
            return array(MODULE_PAYMENT_NOVALNET_CONFIG_BLOCK_TITLE,true,'on_hold_check');
        } else if (self::novalnetStringCheck(MODULE_PAYMENT_INSTALLED, $payment_name)  && in_array($payment_name, array( 'novalnet_invoice', 'novalnet_prepayment' )) && ((defined('MODULE_PAYMENT_NOVALNET_PREPAYMENT_ENABLE_MODULE') && $payment_name == 'novalnet_prepayment') || (defined('MODULE_PAYMENT_NOVALNET_INVOICE_ENABLE_MODULE') && $payment_name == 'novalnet_invoice')) && constant('MODULE_PAYMENT_' . strtoupper($payment_name) . '_ENABLE_MODULE') == 'True' && constant('MODULE_PAYMENT_' . strtoupper($payment_name) . '_PAYMENT_REFERENCE1') == 'False' && constant('MODULE_PAYMENT_' . strtoupper($payment_name) . '_PAYMENT_REFERENCE2') == 'False' && constant('MODULE_PAYMENT_' . strtoupper($payment_name) . '_PAYMENT_REFERENCE3') == 'False') {
			return array(MODULE_PAYMENT_NOVALNET_PAYMENT_REFERENCE_ERROR,true,'payment_reference');
        } else if (self::novalnetStringCheck(MODULE_PAYMENT_INSTALLED, $payment_name)  && $payment_name == 'novalnet_invoice' && MODULE_PAYMENT_NOVALNET_INVOICE_DUE_DATE != '' && !is_numeric(trim(MODULE_PAYMENT_NOVALNET_INVOICE_DUE_DATE))) {
			return array(MODULE_PAYMENT_INVOICE_DUE_DATE_INVAILD,true,'invoice_due_date');
		} else  if ($payment_name == 'novalnet_sepa' && MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_DUE_DATE != '' && (!is_numeric(trim(MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_DUE_DATE)) || MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_DUE_DATE < 7)) {
			return array(MODULE_PAYMENT_NOVALNET_SEPA_DUE_DATE_ERROR,true,'sepa_due_date');
		} else if (in_array($payment_name, array('novalnet_invoice','novalnet_sepa')) &&
		constant('MODULE_PAYMENT_'.strtoupper($payment_name).'_GUARANTEE') == 'True' && (constant('MODULE_PAYMENT_'.strtoupper($payment_name).'_GUARANTEE_MAXIMUM_ORDER_AMOUNT') != '' || constant('MODULE_PAYMENT_'.strtoupper($payment_name).'_GUARANTEE_MINIMUM_ORDER_AMOUNT') != '' )) {
			if(trim(constant('MODULE_PAYMENT_'.strtoupper($payment_name).'_GUARANTEE_MINIMUM_ORDER_AMOUNT')) != '') {
				if (!ctype_digit(constant('MODULE_PAYMENT_'.strtoupper($payment_name).'_GUARANTEE_MINIMUM_ORDER_AMOUNT'))) {
					return array(MODULE_PAYMENT_NOVALNET_AMOUNT_ERROR_MESSAGE,true,'amount_invaild');
				} else if(constant('MODULE_PAYMENT_'.strtoupper($payment_name).'_GUARANTEE_MINIMUM_ORDER_AMOUNT') < 2000 || constant('MODULE_PAYMENT_'.strtoupper($payment_name).'_GUARANTEE_MINIMUM_ORDER_AMOUNT') > 500000) {
					return array(MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_MINIMUM_AMOUNT_ERROR,true,'guarantee_minimum');
				}
			}
			if(trim(constant('MODULE_PAYMENT_'.strtoupper($payment_name).'_GUARANTEE_MAXIMUM_ORDER_AMOUNT')) != '') {
				if(!ctype_digit(constant('MODULE_PAYMENT_'.strtoupper($payment_name).'_GUARANTEE_MAXIMUM_ORDER_AMOUNT'))) {
					return array(MODULE_PAYMENT_NOVALNET_AMOUNT_ERROR_MESSAGE,true,'amount_invaild');
				} else if((constant('MODULE_PAYMENT_'.strtoupper($payment_name).'_GUARANTEE_MAXIMUM_ORDER_AMOUNT') > 500000 ) || (constant('MODULE_PAYMENT_'.strtoupper($payment_name).'_GUARANTEE_MAXIMUM_ORDER_AMOUNT') <=constant('MODULE_PAYMENT_'.strtoupper($payment_name).'_GUARANTEE_MINIMUM_ORDER_AMOUNT'))) {
					return array(MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_MAXIMUM_AMOUNT_ERROR,true,'guarantee_maximum');
				}
			}
		} else if (!function_exists('base64_encode') || !function_exists('base64_decode') || !function_exists('bin2hex') || !function_exists('pack') || !function_exists('crc32') || !function_exists('md5') || !function_exists('curl_init')) {
			return array(MODULE_PAYMENT_NOVALNET_CONFIG_BLOCK_FUNC_ERROR,true,'function_exits');
        }
        return false;
    }

    /**
     * Show error message in backend
     *
     * @param string $error_payment_name
     * @param string $other_error
     * @return string
     */
    public static function displayErrorMessage($error_payment_name, $other_error) {
        $_SESSION['flag'] = true;
        return '<div class="message_stack_container"><div class = "alert alert-danger">' . $error_payment_name . '<br/><br/>' . ($other_error != '' ? $other_error : MODULE_PAYMENT_NOVALNET_VALID_MERCHANT_CREDENTIALS_ERROR) . '<button type="button" class="close" data-dismiss="alert">×</button></div></div>';
    }

    /**
     * Validate E-mail address
     *
     * @param string $emails
     * @return boolean
     */
    static function validateEmail($emails) {
        $email = explode(',', $emails);
        foreach ($email as $value) {
            if (!xtc_validate_email($value))
                return false;
        }
        return true;
    }

    /**
     * select the last successfull payment
     *
     * @param string $payment_name
     */
    public static function getLastSuccessPayment($payment_name) {
        if ($_SESSION['account_type'] != '' && empty($_SESSION['payment']) && MODULE_PAYMENT_NOVALNET_LAST_SUCCESSFULL_PAYMENT_SELECTION == 'True') { // Last successfull payment type
			$sqlQuerySet = xtc_db_fetch_array(xtc_db_query("SELECT payment_type FROM novalnet_transaction_detail WHERE customer_id='". xtc_db_input($_SESSION['customer_id']) ."' ORDER BY id DESC LIMIT 1"));
			if(!empty($sqlQuerySet['payment_type']) && $payment_name == $sqlQuerySet['payment_type'] ) {
				$_SESSION['payment'] = $payment_name;
			}
        }
    }


    /**
     * Validate status of fraud module
     *
     * @param string $payment_name
     * @param string $fraud_module
     * @return boolean
     */
    public static function setFraudModuleStatus($payment_name, $fraud_module,$order_amount) {
        global $order;
        $customer_iso_code = strtoupper($order->billing['country']['iso_code_2']);
        return (!$fraud_module || !in_array($customer_iso_code, array( 'DE', 'AT', 'CH' )) || (is_numeric(constant('MODULE_PAYMENT_' . strtoupper($payment_name) . '_CALLBACK_LIMIT')) && constant('MODULE_PAYMENT_' . strtoupper($payment_name) . '_CALLBACK_LIMIT') > $order_amount) || (!empty($_SESSION['novalnet'][$payment_name]['guarantee']))) ? false : true;
    }


    /**
     * To get previous account details from database
     *
     * @param integer $customers_id
     * @param string $payment_name
     * @return array
     */
    public static function getPaymentDetails($customers_id, $payment_name) {
		return xtc_db_fetch_array(xtc_db_query("SELECT payment_details,process_key,payment_type FROM novalnet_transaction_detail WHERE customer_id='" . xtc_db_input($customers_id) . "' and payment_type = '" . xtc_db_input($payment_name) . "' AND reference_transaction = '0' AND payment_details != '' ORDER BY id DESC LIMIT 1"));
    }

    /**
     * Return payment amount of given order
     *
     * @return integer
     */
    public static function getPaymentAmount() {
        global $order;
        return (sprintf('%0.2f', $order->info['total']) * 100); // Convert into cents
    }

    /**
     * To Visible payment in checkout for  Minimum  order amount
     *
     * @param string $payment_name
     * @param integer $order_amount
     * @return boolean
     */
    public static function hidePaymentVisibility($payment_name, $order_amount) {
		$visibility_amount = constant('MODULE_PAYMENT_' . strtoupper($payment_name) . '_VISIBILITY_BY_AMOUNT');
		return ($visibility_amount == '' || (int) $visibility_amount <= (int) $order_amount);
    }

    /**
     * Validate the DOB field in checkout page
     *
     * @param string $date
     * return boolean
     */
    public static function validateDuedate($date) {
		$due_date = explode('-', $date);
		//checkdate(month, day, year)
		return (checkdate($due_date['1'], $due_date['2'], $due_date['0']));
    }

    /**
     * Validate input form fraud module fields
     *
     * @param array $datas
     * @param string $fraud_module
     * @param string $payment_name
     * @return string
     */
    public static function validateCallbackFields($datas, $fraud_module, $payment_name) {
		 return ($fraud_module == 'CALLBACK' && (!is_numeric(trim($datas[$payment_name . '_fraud_tel'])))) ?  MODULE_PAYMENT_NOVALNET_FRAUDMODULE_TELEPHONE_ERROR : ( ($fraud_module == 'SMS' && (!is_numeric($datas[$payment_name . '_fraud_mobile']) || strlen($datas[$payment_name . '_fraud_mobile']) < 8)) ? MODULE_PAYMENT_NOVALNET_FRAUDMODULE_SMS_ERROR : '');
    }

    /**
     * Return address information of given order
     *
     * @param array $datas
     * @return array
     */
    public static function getCustomerAddressInfo($datas) {
        $value = array();
        foreach (array( 'firstname', 'lastname', 'gender', 'street_address', 'city', 'postcode', 'telephone', 'country', 'company' ) as $values)
            $value[$values] = ((isset($datas['billing'][$values]) && $datas['billing'][$values] != '') ? $datas['billing'][$values] : $datas['customer'][$values]);

        $value['country'] = ((isset($datas['billing']['country']['iso_code_2']) && $datas['billing']['country']['iso_code_2'] != '') ? $datas['billing']['country']['iso_code_2'] : $datas['customer']['country']['iso_code_2']);
        return $value;
    }

    /**
     * Return countries list
     *
     * @return array
     */
    static function sepaBankCountry() {
        $countries       = function_exists('xtc_get_countriesList') ? xtc_get_countriesList() : xtc_get_countries();
        $countrieslist   = sizeof($countries);
        $countries_array = array();
        $countries_array[] = array(
                'id'   => '',
                'text' => MODULE_PAYMENT_NOVALNET_SELECT_STATUS_OPTION
            );
        for ($i = 0, $n = $countrieslist; $i < $n; $i++) {
            $country_id        = xtc_get_countries_with_iso_codes($countries[$i]['countries_id']);
            $countries_array[] = array(
                'id' 	=> $country_id['countries_iso_code_2'],
                'text' 	=> $countries[$i]['countries_name']
            );
        }
        return $countries_array;
    }

    /**
     * To get Merchant credentials
     *
     * @return array
     */
    public static function getVendorDetails() {
        $tariff_details = explode('-', MODULE_PAYMENT_NOVALNET_TARIFF_ID);
        return array(
            'vendor' 	  => trim(MODULE_PAYMENT_NOVALNET_VENDOR_ID),
            'auth_code'   => trim(MODULE_PAYMENT_NOVALNET_AUTHCODE),
            'product' 	  => trim(MODULE_PAYMENT_NOVALNET_PRODUCT_ID),
            'tariff' 	  => $tariff_details[1],
            'access_key'  => trim(MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY),
            'tariff_type' => $tariff_details[0],
            'manual_limit'=> trim(MODULE_PAYMENT_NOVALNET_MANUAL_CHECK_LIMIT)
        );
    }

    /**
     * Get the payment key
     *
     * @param string $payment_name
     * @return integer
     */
    public static function getPaymentKey($payment_name) {
        $payment_key = array(
            'novalnet_cc' 			=> '6',
            'novalnet_prepayment' 	=> '27',
            'novalnet_invoice' 		=> '27',
            'novalnet_instantbank' 	=> '33',
            'novalnet_paypal' 		=> '34',
            'novalnet_sepa' 		=> '37',
            'novalnet_ideal' 		=> '49',
            'novalnet_eps' 			=> '50',
            'novalnet_giropay' 		=> '69',
            'novalnet_przelewy24'   => '78',
        );
        return $payment_key[$payment_name];
    }

    /**
     * Generate Novalnet gateway parameters based on payment selection
     *
     * @param array $datas
     * @return array
     */
    function getCommonParms($datas) {
        include(DIR_FS_CATALOG . 'release_info.php'); // Get shop version
        $payment_name          = ((isset($datas['payment'])) ? $datas['payment'] : $datas['info']['payment_method']);
        $language              = ((isset($_SESSION['language_code'])) ? strtoupper($_SESSION['language_code']) : 'DE');
        $customer_info_address = self::getCustomerAddressInfo($datas);
        $customer_info_details = self::getCustomerdetails($datas['customer']['email_address']);
        $vendor_details        = self::getVendorDetails($payment_name);
        $client_ip             = xtc_get_ip_address();
        $remote_ip             = self::getIpAddress($client_ip);
        $system_ip             = self::getIpAddress($_SERVER['SERVER_ADDR']);
        $urlparam              = array_merge(array(
            'vendor' 				=> $vendor_details['vendor'],
            'product' 				=> $vendor_details['product'],
            'key' 					=> self::getPaymentKey($payment_name),
            'tariff' 				=> $vendor_details['tariff'],
            'auth_code' 			=> $vendor_details['auth_code'],
            'currency' 				=> $datas['info']['currency'],
            'first_name' 			=> $customer_info_address['firstname'],
            'last_name' 			=> $customer_info_address['lastname'],
            'gender' 				=> $customer_info_address['gender'],
            'email' 				=> $datas['customer']['email_address'],
            'street' 				=> $customer_info_address['street_address'],
            'search_in_street' 		=> 1,
            'city' 					=> $customer_info_address['city'],
            'zip' 					=> $customer_info_address['postcode'],
            'lang' 					=> $language,
            'language'	 			=> $language,
            'country' 				=> $customer_info_address['country'],
            'country_code' 			=> $customer_info_address['country'],
            'tel' 					=> $customer_info_address['telephone'],
            'test_mode' 			=> constant('MODULE_PAYMENT_' . strtoupper($payment_name) . '_TEST_MODE') == 'True' ? 1 : 0,
            'customer_no' 			=> ((!empty($_SESSION['customer_id'])) ? $_SESSION['customer_id'] : 'guest'),
            'amount' 				=> $datas['payment_amount'],
            'system_name' 			=> 'Gambio',
            'system_version' 		=> $gx_version . '-NN-' . '11.1.1',
            'system_url' 			=> ((ENABLE_SSL == true) ? HTTPS_SERVER : HTTP_SERVER . DIR_WS_CATALOG),
            'system_ip' 			=> $system_ip,
            'remote_ip' 			=> $remote_ip,
            'notify_url' 			=> MODULE_PAYMENT_NOVALNET_CALLBACK_URL
        ), array_filter(array(
			'fax' 					=> $customer_info_details['customers_fax'],
			'birthdate'				=> $customer_info_details['customers_dob'],
            'company'				=> $customer_info_address['company'],
            'referrer_id' 			=> trim(MODULE_PAYMENT_NOVALNET_REFERRER_ID),
            'tariff_period' 		=> trim(MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD),
            'tariff_period2' 		=> trim(MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD2),
            'tariff_period2_amount' => trim(MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD2_AMOUNT),
            'inputval1' 			=> trim(strip_tags(constant('MODULE_PAYMENT_' . strtoupper($payment_name) . '_TRANS_REFERENCE1'))),
            'input1' 				=> (trim(strip_tags(constant('MODULE_PAYMENT_' . strtoupper($payment_name) . '_TRANS_REFERENCE1'))) != '') ? 'Reference1' : '',
            'inputval2' 			=> trim(strip_tags(constant('MODULE_PAYMENT_' . strtoupper($payment_name) . '_TRANS_REFERENCE2'))),
            'input2' 				=> (trim(strip_tags(constant('MODULE_PAYMENT_' . strtoupper($payment_name) . '_TRANS_REFERENCE2'))) != '') ? 'Reference2' : ''
        )));
        self::getAffDetails($urlparam); // Appending affiliate parameters
        return $urlparam;
    }

    /**
     *  Validate the ipv6 for ipaddress
     *
     * @param array $ipaddress
     * return integer
     */
     public static function getIpAddress($ipaddress) {
		 return (filter_var($ipaddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) || $ipaddress == '::1') ? '127.0.0.1' : $ipaddress;
	 }

    /**
     * Collect Customer DOB, FAX, Gender information from the database
     *
     * @param string $customer_email
     * @return array
     */
	public static function getCustomerdetails($customer_email) {
        if ($customer_email != '') { // Get enduser info from database
            $querySearch = (isset($_SESSION['customer_id']) && $_SESSION['customers_status']['customers_status_id'] != '1') ? 'customers_id= "' . xtc_db_input($_SESSION['customer_id']) . '"' : 'customers_email_address= "' . xtc_db_input($customer_email) . '"';
            $customer_dbvalue = xtc_db_fetch_array(xtc_db_query("SELECT customers_id, customers_cid, customers_gender, customers_dob, customers_fax FROM " . TABLE_CUSTOMERS . " WHERE " . $querySearch . " ORDER BY customers_id DESC LIMIT 1"));
            $customer_dbvalue['customers_dob'] = (!in_array($customer_dbvalue['customers_dob'], array( '0000-00-00 00:00:00', '1000-01-01 00:00:00'))) ? date('Y-m-d', strtotime($customer_dbvalue['customers_dob'])) : '';
            return $customer_dbvalue;
        }
    }

    /**
     * Communicate transaction parameters with Novalnet paygate
     *
     * @param string $paygate_url
     * @param array $datas
     * @param boolean $build_query
     * @return array
     */
    public static function doPaymentCurlCall($paygate_url, $datas, $build_query = true) {
	$paygate_query = ($build_query) ? http_build_query($datas) : $datas;
	    $curl          = curl_init($paygate_url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $paygate_query);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, ((MODULE_PAYMENT_NOVALNET_GATEWAY_TIMEOUT != '' && MODULE_PAYMENT_NOVALNET_GATEWAY_TIMEOUT > 240) ? MODULE_PAYMENT_NOVALNET_GATEWAY_TIMEOUT : 240)); // Custom cURL time-out
        if (trim(MODULE_PAYMENT_NOVALNET_PROXY) != '') { // Custom proxy option
            curl_setopt($curl, CURLOPT_PROXY, trim(MODULE_PAYMENT_NOVALNET_PROXY));
        }
        $response = curl_exec($curl);
        $error_no = curl_errno($curl);
        $error    = curl_error($curl);
        curl_close($curl);
        if ($error_no > 0) {
            xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $_SESSION['payment'] . '&error=' . $error, 'SSL', true, false));
        }
        return $response;
    }

    /**
     * Validate redirect payment server response
     *
     * @param string $payment_name
     * @param array $payment_response
     * @return array
     */
    public static function validateRedirectResponse($payment_name, $payment_response) {
        if ($payment_response['hash2'] != self::generatemd5Value($payment_response)) {
            $payment_error_return = 'payment_error=' . $payment_name . '&error_message=' . html_entity_decode(MODULE_PAYMENT_NOVALNET_TRANSACTION_REDIRECT_ERROR);
            xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
        }
        $payment_response = NovalnetHelper::decodePaygateResponse($payment_response);
        $payment_response = NovalnetHelper::checkPaymentStatus($payment_name, $payment_response);
        // Response value stored in session
        return $payment_response;
    }

    /**
     * Form transaction comments and assign session value
     *
     * @param string $payment_name
     * @param array $response
     * @param array $payment_param
     * @return array
     */
    public static function checkPaymentStatus($payment_name, $response,$payment_param = '') {
        global $order;
        $order_comments = $transaction_status_failed = '';
        if ($response['status'] == 100 || ($payment_name == 'novalnet_paypal' && $response['status'] == 90)) { // Payment Success
			$order_comments = self::transactionCommentsForm($response, $payment_name );
			if (in_array($payment_name, array( 'novalnet_invoice', 'novalnet_prepayment' ))) {
                list($order_invoice_comments, $bank_details) = self::formInvoicePrepaymentComments($response);
                $_SESSION['novalnet'][$payment_name]['serialize_data'] = serialize($bank_details);
            }
            $response['novalnet_comments'] = $_SESSION['novalnet'][$payment_name]['novalnet_comments'] = ((($order->info['comments'] != '') ? PHP_EOL . $order->info['comments'] : '')) . $order_comments.(!empty($order_invoice_comments) ? $order_invoice_comments : '');
            if ($payment_name == 'novalnet_sepa') { // Sepa hash value added for sepa payment process
                $sepa_hash = !empty($_SESSION['novalnet'][$payment_name]['hash']) ? $_SESSION['novalnet'][$payment_name]['hash'] :  '';
			}
            $redirect_payments = array('novalnet_cc','novalnet_paypal','novalnet_ideal','novalnet_eps','novalnet_instantbank','novalnet_giropay','novalnet_przelewy24');
			$_SESSION['novalnet'][$payment_name] = array(
				'vendor_id'				=> in_array($payment_name,$redirect_payments) ? $response['vendor'] : $payment_param['vendor'],
				'product_id'			=> in_array($payment_name,$redirect_payments) ? $response['product'] :  $payment_param['product'],
				'tariff_id'				=> in_array($payment_name,$redirect_payments) ? $response['tariff'] : $payment_param['tariff'],
				'authcode'				=> in_array($payment_name,$redirect_payments) ? $response['auth_code'] : $payment_param['auth_code'],
				'tid' 					=> $response['tid'],
                'gateway_response' 		=> $response,
                'payment_id' 			=> !empty ($response['key']) ? $response['key'] :  $payment_param['key'],
                'reference_transaction' => isset($_SESSION['novalnet'][$payment_name]['reference_transaction']) ? '1' : '0',
				'serialize_data' 		=> !empty($_SESSION['novalnet'][$payment_name]['serialize_data']) ? $_SESSION['novalnet'][$payment_name]['serialize_data'] : '',
                'test_mode' 			=> $response['test_mode'],
                'status' 				=> $response['status'],
                'customer_id' 			=> $response['customer_no'],
                'intial_order_amount'   => $_SESSION['novalnet'][$payment_name]['nn_order_amount'],
                'process_key' 			=> ($payment_name == 'novalnet_sepa') ? $sepa_hash : '',
                'next_payment_date' 			=> in_array($payment_name, array( 'novalnet_paypal', 'novalnet_cc' )) ? $response['next_subs_cycle'] : $response['paid_until'] );
			return $response;
        } else { // Payment failed
			unset($_SESSION['novalnet'][$payment_name]['reference_transaction']);
			$transaction_status_failed = NovalnetHelper::getServerResponse($response);
            $payment_error_return      = 'payment_error=' . $payment_name . '&error_message=' . $transaction_status_failed;
            xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
        }

    }

    /**
     * Perform the decoding paygate response process for redirection payment methods
     *
     * @param array $datas
     * @return string
     */
    public static function decodePaygateResponse($datas) {
		foreach (array(
                'auth_code',
                'product',
                'tariff',
                'amount',
                'test_mode',
                'uniqid'
        ) as $value) {
            $datas[$value] = self::generateDecode($datas[$value]);
        }
        return $datas;
    }

    /**
     * Validate pin field
     *
     * @param string $payment_module
     * @param array $datas
     * @param string $fraud_module
     */
    public static function validateUserInputsOnCallback($payment_module, $datas, $fraud_module) {
        $datas = array_map('trim', $datas);
        if (in_array($fraud_module, array( 'CALLBACK', 'SMS' ))) {
            if (!isset($datas[$payment_module . '_new_pin']) && isset($datas[$payment_module . '_fraud_pin']) && (!preg_match('/^[a-zA-Z0-9]+$/', $datas[$payment_module . '_fraud_pin']))) {
				$fraud_module_error = $datas[$payment_module . '_fraud_pin'] == '' ? MODULE_PAYMENT_NOVALNET_FRAUDMODULE_PIN_EMPTY : MODULE_PAYMENT_NOVALNET_FRAUDMODULE_PIN_NOTVALID;
                $payment_error_return = 'payment_error=' . $payment_module . '&error_message=' . $fraud_module_error;
                xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
            }
        }
    }

    /**
     * Redirect to checkout on sucess using fraud module
     *
     * @param string $fraud_module
     * @param boolean $fraud_module_status
     * @param string $payment_type
     */
    public static function gotoPaymentOnCallback($fraud_module, $fraud_module_status, $payment_type) {
        if (in_array($fraud_module, array('CALLBACK', 'SMS')) && $fraud_module_status) {
            $fraudModule_message  = ($fraud_module == 'SMS') ? MODULE_PAYMENT_NOVALNET_FRAUDMODULE_SMS_PIN_INFO : MODULE_PAYMENT_NOVALNET_FRAUDMODULE_PIN_INFO;
            $payment_error_return = 'payment_error=' . $payment_type . '&payment_message=' . $fraudModule_message;
            xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
        }
    }

    /**
     * Perform server XML request
     *
     * @param string $requesttype
     * @param string $payment_type
     * @return array
     */
    public static function doCallbackRequest($requesttype, $payment_type) {
        $vendor_details = self::getVendorDetails($payment_type);
        $client_ip      = xtc_get_ip_address();
        $remote_ip      = self::getIpAddress($client_ip);
        $xml            = '<?xml version="1.0" encoding="UTF-8"?>
        <nnxml>
            <info_request>
              <vendor_id>' . $vendor_details['vendor'] . '</vendor_id>
              <vendor_authcode>' . $vendor_details['auth_code'] . '</vendor_authcode>
              <remote_ip>' . $remote_ip . '</remote_ip>
              <request_type>' . $requesttype . '</request_type>
              <tid>' . $_SESSION['novalnet'][$payment_type]['tid'] . '</tid>';
        if ($requesttype == 'PIN_STATUS')
            $xml .= '<pin>' . trim($_SESSION['novalnet'][$payment_type][$payment_type . '_fraud_pin']) . '</pin>';
        $xml .= '</info_request></nnxml>';
        $xml_response = self::doPaymentCurlCall('https://payport.novalnet.de/nn_infoport.xml', $xml, false);
        return $xml_response;
    }

    /**
     * Validate callback amount
     *
     * @param string $payment_module
     * @param string $fraud_module
     */
    public static function validateAmountOnCallback($payment_module, $fraud_module) {
        $orderamount = self::getPaymentAmount();
        if ($_SESSION['novalnet'][$payment_module]['intial_order_amount'] != $orderamount) {
            if (isset($_SESSION['novalnet'][$payment_module])) {
                unset($_SESSION['novalnet'][$payment_module]);
            }
            $payment_error_return = 'payment_error=' . $payment_module . '&error_message=' . MODULE_PAYMENT_NOVALNET_AMOUNT_CHANGE_ERROR;
            xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
        }
        if ($payment_module == 'novalnet_sepa' && $fraud_module && (MODULE_PAYMENT_NOVALNET_SEPA_SHOP_TYPE == 'ZEROAMOUNT')) {
			$orderamount = 0;
        }
    }

    /**
     * Return Invoice / Prepayment comments
     *
     * @param array $data
     * @return array
     */
    public static function formInvoicePrepaymentComments($data) {
        $trans_comments = PHP_EOL . MODULE_PAYMENT_NOVALNET_INVOICE_COMMENTS_PARAGRAPH . PHP_EOL;
        $trans_comments .= ($data['due_date'] != '') ? MODULE_PAYMENT_NOVALNET_DUE_DATE . date(DATE_FORMAT, strtotime($data['due_date'])) . PHP_EOL : '';
        $trans_comments .= MODULE_PAYMENT_NOVALNET_INV_PRE_ACCOUNT_HOLDER . PHP_EOL;
        $trans_comments .= MODULE_PAYMENT_NOVALNET_IBAN . ': ' . $data['invoice_iban'] . PHP_EOL;
        $trans_comments .= MODULE_PAYMENT_NOVALNET_BIC . ': ' . $data['invoice_bic'] . PHP_EOL;
        $trans_comments .= MODULE_PAYMENT_NOVALNET_BANK . $data['invoice_bankname'] . ' ' . $data['invoice_bankplace'] . PHP_EOL;
        $trans_comments .= MODULE_PAYMENT_NOVALNET_AMOUNT . xtc_format_price_order($data['amount'], 1, $data['currency']);
        $bank_details = array(
            'tid'            => $data['tid'],
            'account_holder' => 'NOVALNET AG',
            'bank_name'      => $data['invoice_bankname'],
            'bank_city'      => $data['invoice_bankplace'],
            'amount'         => $data['amount'] * 100,
            'currency'       => $data['currency'],
            'bank_iban'      => $data['invoice_iban'],
            'bank_bic'       => $data['invoice_bic'],
            'due_date'       => $data['due_date']
        );
        return array( $trans_comments, $bank_details );
    }

    /**
     * Perform order status update with custom order status (as per Merchant selection)
     *
     * @param integer $order_id
     * @param string $payment_name
     */
    public static function updateOrderStatus($order_id, $payment_name) {
        global $order;
        if (in_array($payment_name, array( 'novalnet_invoice','novalnet_prepayment' ))) {
            $_SESSION['novalnet'][$payment_name]['payment_ref'] = array(
                'payment_reference1' => (constant('MODULE_PAYMENT_' . strtoupper($payment_name) . '_PAYMENT_REFERENCE1') == 'True') ? '1' : '0',
                'payment_reference2' => (constant('MODULE_PAYMENT_' . strtoupper($payment_name) . '_PAYMENT_REFERENCE2') == 'True') ? '1' : '0',
                'payment_reference3' => (constant('MODULE_PAYMENT_' . strtoupper($payment_name) . '_PAYMENT_REFERENCE3') == 'True') ? '1' : '0'
            );
            $order->info['comments'] .= self::formInvoicePrepaymentPaymentReference(serialize($_SESSION['novalnet'][$payment_name]['payment_ref']), $payment_name);
        }
        $payment_order_status = in_array($_SESSION['novalnet'][$payment_name]['gateway_response']['tid_status'], array(85,86,90)) ?constant('MODULE_PAYMENT_'.strtoupper($payment_name).'_PENDING_ORDER_STATUS') : constant('MODULE_PAYMENT_' . strtoupper($payment_name) . '_ORDER_STATUS');
        // Update the Merchant selected order status in shop
        xtc_db_perform(TABLE_ORDERS, array(
            'orders_status' => $payment_order_status,
            'comments' 		=> $order->info['comments']
        ), "update", "orders_id='$order_id'");
        xtc_db_perform(TABLE_ORDERS_STATUS_HISTORY, array(
            'orders_status_id' => $payment_order_status,
            'comments' 		   => $order->info['comments']
        ), "update", "orders_id='$order_id'");
    }

    /**
     * Build reference comments for invoice
     *
     * @param array $payment_reference
     * @param string $payment_type
     * @param array $datas
     * @return string
     */
    public static function formInvoicePrepaymentPaymentReference($payment_reference, $payment_type, $datas = '') {
		global $insert_id;
        $vendor_details           = NovalnetHelper::getVendorDetails($payment_type);
        $payment_reference        = unserialize($payment_reference);
        $payment_reference        = array( $payment_reference['payment_reference1'],
										   $payment_reference['payment_reference2'],
										   $payment_reference['payment_reference3'],
        );
        $payment_ref_comments     = '';
        $enable_payment_reference = array_count_values($payment_reference);
        $i                        = 1;
        $info                     = array(
            ('BNR-' . (isset($datas['product']) ? $datas['product'] : $vendor_details['product']) . '-' . (isset($datas['order_no']) ? $datas['order_no'] : $insert_id) . PHP_EOL),
            'TID ' . (isset($datas['tid']) ? $datas['tid'] . PHP_EOL : $_SESSION['novalnet'][$payment_type]['tid'] . PHP_EOL),
            MODULE_PAYMENT_NOVALNET_ORDER_NUMBER . (isset($datas['order_no']) ? $datas['order_no'] : $insert_id)
        );
        foreach ($payment_reference as $key => $value) {
            if ($value == 1) {
                $payment_ref_comments .= (($enable_payment_reference['1'] == '1') ? MODULE_PAYMENT_NOVALNET_INVPRE_REF . $info[$key] : sprintf(MODULE_PAYMENT_NOVALNET_INVPRE_REF_MULTI, ' ' . $i++) . ' ' . $info[$key]);
            }
        }
        $reference_text             = ($enable_payment_reference['1'] == '1') ? MODULE_PAYMENT_NOVALNET_PAYMENT_SINGLE_TEXT : MODULE_PAYMENT_NOVALNET_PAYMENT_MULTI_TEXT;
        $payment_reference_comments = PHP_EOL . $reference_text . PHP_EOL . $payment_ref_comments;
        return $payment_reference_comments;
    }

    /**
     * To process Affiliate account
     *
     * @param reference $urlparam
     */
    public static function getAffDetails(&$urlparam) {
        $_SESSION['novalnet']['nn_access_key'] = MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY;
        if ($_SESSION['customer_id'] != '' && $_SESSION['customers_status']['customers_status_id'] != DEFAULT_CUSTOMERS_STATUS_ID_GUEST && (!isset($_SESSION['nn_aff_id']) || empty($_SESSION['nn_aff_id']))) { // To process affliate customers only
            $affilated_details = xtc_db_fetch_array(xtc_db_query('SELECT aff_id FROM novalnet_aff_user_detail WHERE customer_id = "' . xtc_db_input($_SESSION['customer_id']) . '" ORDER BY id DESC LIMIT 1'));
            if (isset($affilated_details['aff_id']) && !empty($affilated_details['aff_id'])) { // Set affilate id
                $_SESSION['nn_aff_id'] = $affilated_details['aff_id']; // Assign affilate id from database
            }
        }
        if (isset($_SESSION['nn_aff_id'])) { // Get affilate details
            $affilated_details = xtc_db_fetch_array(xtc_db_query('SELECT aff_authcode, aff_accesskey FROM novalnet_aff_account_detail WHERE aff_id = "' . xtc_db_input($_SESSION['nn_aff_id']) . '"'));
            if (trim($affilated_details['aff_accesskey'])!= '' && trim($affilated_details['aff_authcode']) != '' && $_SESSION['nn_aff_id'] != '') { // Assign access key for affilate id
                $urlparam['vendor']        = $_SESSION['nn_aff_id'];
                $urlparam['auth_code']     = $affilated_details['aff_authcode'];
                $_SESSION['novalnet']['nn_access_key'] = $affilated_details['aff_accesskey']; // Assign affilate access key
            }
        }
    }

    /**
     *  To proceed to second call for novalnet gateway
     *
     * @param array $datas
     */
    public static function doSecondCallProcess($datas) {
        self::logInitialTransaction($datas); // Insert transaction details to Novalnet table
        if (isset($_SESSION['novalnet'][$datas['payment_type']]['gateway_response']['subs_id']) && $_SESSION['novalnet'][$datas['payment_type']]['gateway_response']['subs_id'] != '') { // If subscription order to insert the transaction details to Novalnet subscripton table
            xtc_db_perform('novalnet_subscription_detail', array(
                'order_no'			 => $datas['order_no'],
                'subs_id'			 => $_SESSION['novalnet'][$datas['payment_type']]['gateway_response']['subs_id'],
                'tid'				 => $_SESSION['novalnet'][$datas['payment_type']]['tid'],
                'signup_date' 		 => date('Y-m-d H:i:s'),
                'termination_reason' => '',
                'termination_at' 	 => ''
            ), "insert");
        }
        if (isset($_SESSION['nn_aff_id'])) { // If affilate id customer to insert in transaction details to affliated table
            xtc_db_perform('novalnet_aff_user_detail', array(
                'aff_id' 		=> $_SESSION['nn_aff_id'],
                'customer_id' 	=> $_SESSION['customer_id'],
                'aff_order_no' 	=> $datas['order_no']
            ), 'insert');
            unset($_SESSION['nn_aff_id']);
        }
        self::doPaymentSecondCall($datas); // Order no to update the Novalnet server (do post back call)
    }

	/**
	 * Get payment title
	 *
	 * @param string $payment
	 * @return string
	 */
	public static function getPaymentName($payment) {
		$payment_title = array(
			'novalnet_cc'         => 'CREDITCARD',
			'novalnet_sepa'       => 'DIRECT_DEBIT_SEPA',
			'novalnet_invoice'    => 'INVOICE_START',
			'novalnet_instantbank'=> 'ONLINE_TRANSFER',
			'novalnet_ideal'      => 'IDEAL',
			'novalnet_paypal'     => 'PAYPAL',
			'novalnet_przelewy24' => 'PRZELEWY24',
			'novalnet_prepayment' => 'INVOICE_START',
			'novalnet_eps'        => 'EPS',
			'novalnet_giropay'    => 'GIROPAY'
		 );
		return $payment_title[$payment];
	}

    /**
     * To store Novalnet transaction details in table
     *
     * @param array $datas
     */
     public static function logInitialTransaction($datas) {
		self::testMailNotification($datas, $_SESSION['novalnet'][$datas['payment_type']]);
		$amount              = !empty($_SESSION['novalnet'][$datas['payment_type']]['gateway_response']['amount']) ? str_replace('.', '', $_SESSION['novalnet'][$datas['payment_type']]['gateway_response']['amount']) : $datas['recurring_amount'];
		$payment_name        = self::getPaymentName($datas['payment_type']);
		xtc_db_perform('novalnet_transaction_detail', array(
            'tid'                   => !empty($_SESSION['novalnet'][$datas['payment_type']]['tid']) ? $_SESSION['novalnet'][$datas['payment_type']]['tid'] : $datas['recurring_tid'],
            'vendor'                => !empty($_SESSION['novalnet'][$datas['payment_type']]['vendor_id'])?$_SESSION['novalnet'][$datas['payment_type']]['vendor_id'] : $datas['vendor'],
            'product'               => !empty($_SESSION['novalnet'][$datas['payment_type']]['product_id']) ? $_SESSION['novalnet'][$datas['payment_type']]['product_id'] : $datas['product'],
            'tariff_id'             => !empty($_SESSION['novalnet'][$datas['payment_type']]['tariff_id']) ? $_SESSION['novalnet'][$datas['payment_type']]['tariff_id'] : $datas['tariff_id'] ,
            'auth_code'             => !empty($_SESSION['novalnet'][$datas['payment_type']]['authcode'])? $_SESSION['novalnet'][$datas['payment_type']]['authcode'] : $datas['auth_code'] ,
            'subs_id'               => !empty($_SESSION['novalnet'][$datas['payment_type']]['gateway_response']['subs_id']) ? $_SESSION['novalnet'][$datas['payment_type']]['gateway_response']['subs_id'] : $datas['subs_id']  ,
            'payment_id'            => !empty($_SESSION['novalnet'][$datas['payment_type']]['payment_id']) ? $_SESSION['novalnet'][$datas['payment_type']]['payment_id'] : $datas['payment_id']  ,
            'payment_type'          => $datas['payment_type'],
            'amount'                => (!empty($amount)) ? $amount : 0,
            'currency'              => !empty($_SESSION['novalnet'][$datas['payment_type']]['gateway_response']['currency']) ? $_SESSION['novalnet'][$datas['payment_type']]['gateway_response']['currency'] : $datas['currency']  ,
            'gateway_status'        => !empty($_SESSION['novalnet'][$datas['payment_type']]['gateway_response']['tid_status']) ? $_SESSION['novalnet'][$datas['payment_type']]['gateway_response']['tid_status'] : $datas['tid_status']  ,
            'order_no'              => !empty($datas['new_order_no']) ? $datas['new_order_no'] : $datas['order_no'],
            'novalnet_order_date'   => date('Y-m-d H:i:s'),
            'test_mode'             => (((isset($_SESSION['novalnet'][$datas['payment_type']]['gateway_response']['test_mode']) && $_SESSION['novalnet'][$datas['payment_type']]['gateway_response']['test_mode'] == 1) || ($datas['test_mode'] == 1 )) ? 1 : 0),
            'payment_details'       => !empty($_SESSION['novalnet'][$datas['payment_type']]['serialize_data']) ? $_SESSION['novalnet'][$datas['payment_type']]['serialize_data'] : $datas['invoice_payment_details'],
            'customer_id'           => !empty($_SESSION['novalnet'][$datas['payment_type']]['gateway_response']['customer_no']) ? $_SESSION['novalnet'][$datas['payment_type']]['gateway_response']['customer_no'] : $datas['customer_id'] ,
            'reference_transaction' => isset($_SESSION['novalnet'][$datas['payment_type']]['reference_transaction']) ? $_SESSION['novalnet'][$datas['payment_type']]['reference_transaction'] : 0,
            'payment_ref'           => isset($_SESSION['novalnet'][$datas['payment_type']]['payment_ref']) ? serialize($_SESSION['novalnet'][$datas['payment_type']]['payment_ref']) : (!empty($datas['payment_ref']) ? $datas['payment_ref'] : ''),
            'next_payment_date'            => isset($_SESSION['novalnet'][$datas['payment_type']]['next_payment_date'])? $_SESSION['novalnet'][$datas['payment_type']]['next_payment_date'] : (!empty($datas['next_payment_date']) ? $datas['next_payment_date'] : ''),
            'process_key'           => !empty($_SESSION['novalnet'][$datas['payment_type']]['process_key']) ? $_SESSION['novalnet'][$datas['payment_type']]['process_key'] : '',
            'refund_amount'         => !empty($datas['refund_amount']) ? $datas['refund_amount'] : ''
        ), "insert");
        xtc_db_perform('novalnet_callback_history', array(
			 'callback_datetime'    => date('Y-m-d H:i:s'),
			 'payment_type'         => (($_SESSION['novalnet'][$datas['payment_type']]['payment_id'] == 41 ) ? 'GUARANTEED_INVOICE_START' : (($_SESSION['novalnet'][$datas['payment_type']]['payment_id'] == 40) ? 'GUARANTEED_DIRECT_DEBIT_SEPA' : $payment_name)),
			 'original_tid'         =>   !empty($_SESSION['novalnet'][$datas['payment_type']]['tid']) ? $_SESSION['novalnet'][$datas['payment_type']]['tid'] : $datas['recurring_tid'],
			 'callback_tid'         => '',
			 'order_amount'         => (!empty($amount)) ? $amount : 0,
			 'callback_amount'      =>(in_array($datas['payment_type'], array('novalnet_invoice', 'novalnet_prepayment')) || in_array($_SESSION['novalnet'][$datas['payment_type']]['gateway_response']['tid_status'], array(85,86,90)) || (!empty($datas['tid_status']) && $datas['tid_status'] == 90) ) ? '0' : $amount,
			 'order_no'             =>  !empty($datas['new_order_no']) ? $datas['new_order_no'] : $datas['order_no'],
			), "insert");
    }

    /**
     * Perform Paygate second call for updating order_no in novalnet server
     *
     * @param array $datas
     */
    public static function doPaymentSecondCall($datas) {
		$client_ip      = xtc_get_ip_address();
        $remote_ip      = self::getIpAddress($client_ip);
        $urlparam       = array(
            'vendor' 	=> $_SESSION['novalnet'][$datas['payment_type']]['vendor_id'],
            'product' 	=> $_SESSION['novalnet'][$datas['payment_type']]['product_id'],
            'auth_code'	=> $_SESSION['novalnet'][$datas['payment_type']]['authcode'],
            'tariff'	=> $_SESSION['novalnet'][$datas['payment_type']]['tariff_id'],
            'key' 		=> $_SESSION['novalnet'][$datas['payment_type']]['payment_id'],
            'status' 	=> 100,
            'remote_ip' => $remote_ip,
            'tid' 		=> $_SESSION['novalnet'][$datas['payment_type']]['tid'],
            'order_no' 	=> $datas['order_no']
        );
        if (in_array($datas['payment_type'], array('novalnet_invoice', 'novalnet_prepayment'))) {
            $urlparam['invoice_ref'] = 'BNR-' . $urlparam['product'] . '-' . $datas['order_no'];
        }
        self::doPaymentCurlCall('https://payport.novalnet.de/paygate.jsp', $urlparam, false);
        if (isset($_SESSION['novalnet'])) {
            unset($_SESSION['novalnet']);
        }
    }

    /**
     * Encode the config parameters before transaction.
     *
     * @param array $datas
     * @param array $encoded_values
     */
    public static function generateHashValue(&$datas,$encoded_values) {
		$datas['uniqid'] 	= time();
		foreach ( $encoded_values as $value ) {
			$data = $datas[ $value ];
			try {
				$crc = sprintf('%u', crc32( $data ) );
				$data = $crc . "|" . $data;
				$data = bin2hex( $data . trim($_SESSION['novalnet']['nn_access_key']));
				$data = strrev( base64_encode( $data) );
			} catch ( Exception $e ) {
				echo('Error: ' . $e );
			}
			$datas[ $value ] = $data;
		}
		$datas['hash'] =  self::generatemd5Value($datas);
    }

    /**
     * Perform the decoding process for redirection payment methods
     *
     * @param array $data
     * @return string
     */
    public static function generateDecode($data) {
        try {
            $data = base64_decode(strrev($data));
            $data = pack("H" . strlen($data), $data);
            $data = substr($data, 0, stripos($data, trim($_SESSION['novalnet']['nn_access_key'])));
            $pos  = strpos($data, "|");
            if ($pos === false) {
                return ("Error: CKSum not found!");
            }
            $crc   = substr($data, 0, $pos);
            $value = trim(substr($data, $pos + 1));
            if ($crc != sprintf('%u', crc32($value))) {
                return ("Error; CKSum invalid!");
            }
            return $value;
        }
        catch (Exception $e) {
            echo ('Error: ' . $e);
        }
        return $data;
    }

    /**
     * To generate the HASH  value
     *
     * @param array $data
     * @return string
     */
    public static function generatemd5Value($data) {
        return md5($data['auth_code'] . $data['product'] . $data['tariff'] . $data['amount'] . $data['test_mode'] . $data['uniqid'] . strrev(trim($_SESSION['novalnet']['nn_access_key'])));
    }

    /**
     * To get Novalnet transaction information from novalnet table
     *
     * @param integer $order_no
     * @return array
     */
    public static function getNovalnetTransDetails($order_no) {
        return xtc_db_fetch_array(xtc_db_query("SELECT tid, vendor, product, tariff_id, auth_code, refund_amount,subs_id, payment_id, payment_type, amount, currency, gateway_status, novalnet_order_date, test_mode, customer_id, payment_details,payment_ref,next_payment_date,order_no FROM novalnet_transaction_detail WHERE order_no='" . xtc_db_input($order_no) . "'"));
    }

    /**
     * To form server response in session and transaction comments  while processing one click shopping
     *
     * @param string $paymentname
     * @param array $params
     * @param array $payment_response
     */
    public static function getPostValues($paymentname, $params, $payment_response) {
		global $order;
		$serialize_data = '';
		if($paymentname == 'novalnet_cc' && constant('MODULE_PAYMENT_'.strtoupper($paymentname).'_SHOP_TYPE') == 'ONECLICK' && !empty($params['create_payment_ref'])) {
			$serialize_data = ($paymentname == 'novalnet_cc') ? serialize(array(
							'cc_holder' 		=> $payment_response['cc_holder'],
							'cc_no' 			=> $payment_response['cc_no'],
							'cc_exp_year' 		=> $payment_response['cc_exp_year'],
							'cc_exp_month' 		=> $payment_response['cc_exp_month'],
							'tid_status' 		=> $payment_response['tid_status'],
							'cc_card_type' 		=> $payment_response['cc_card_type'],
							'tid' 				=> $payment_response['tid'] ))
							: '';
		}
		$_SESSION['novalnet'][$paymentname] = array(
						'vendor_id'				=> $params['vendor'],
						'product_id'			=> $params['product'],
						'authcode'				=> $params['auth_code'],
						'tariff_id'				=> $params['tariff'],
						'tid' 					=> $payment_response['tid'],
						'payment_id' 			=> $params['key'],
						'test_mode' 			=> $payment_response['test_mode'],
						'reference_transaction' => ( isset($_SESSION['novalnet'][$paymentname]['reference_transaction']) ) ? '1' : '0',
						'gateway_response' 		=> $payment_response,
						'gateway_response' 		=> $payment_response,
						'next_payment_date' 	=> $payment_response['next_subs_cycle'],
						'serialize_data' 		=> !empty($_SESSION['novalnet'][$paymentname]['serialize_data']) ? $_SESSION['novalnet'][$paymentname]['serialize_data'] : $serialize_data,
		);
		$user_comments  = !empty($order->info['comments']) ? PHP_EOL . $order->info['comments'] : '';
		$trans_comments = self::transactionCommentsForm($payment_response, $paymentname );
		$order->info['comments']           = $user_comments . $trans_comments;
	}

	/**
	 *  Form Novalnet transactions comments
	 *
	 * @param array $response
	 * @param string $paymentname
	 * @return string
	 */
	public static function transactionCommentsForm ($response,$paymentname) {
		$trans_comments = PHP_EOL . MODULE_PAYMENT_NOVALNET_TRANSACTION_DETAILS . PHP_EOL . MODULE_PAYMENT_NOVALNET_TRANSACTION_ID . $response['tid'] . ((($response['test_mode'] == 1) || constant('MODULE_PAYMENT_' . strtoupper($paymentname) . '_TEST_MODE') == 'True') ? PHP_EOL . MODULE_PAYMENT_NOVALNET_TEST_ORDER_MESSAGE . PHP_EOL : '');
		return $trans_comments;
	}

	/**
     *  To show javascript error message if not enable javascript in browser
     *
     * @return string
     */
    public static function enableJavascript() {
		return '<nobr><noscript><input type="hidden" name="nn_js_enabled" value="1"><br/><div style="color:red"><b>'. MODULE_PAYMENT_NOVALNET_NO_SCRIPT . '</b></div></noscript></nobr>';
	 }

	/**
	 * To send test mail notification to Merchant
	 *
	 * @param $paymentname
	 * @param $response
	 */
	public static function testMailNotification($datas, $response) {
		if((constant('MODULE_PAYMENT_' . strtoupper($datas['payment_type']) . '_TEST_MODE') == 'False' && $response['test_mode'] == 1 ) && MODULE_PAYMENT_NOVALNET_TEST_ORDER_EMAIL_NOTIFICATION == 'True') {
			xtc_php_mail(EMAIL_FROM, STORE_NAME, STORE_OWNER_EMAIL_ADDRESS, STORE_OWNER, '', '', '', '', '', sprintf(MODULE_PAYMENT_NOVALNET_TEST_TRANSACTION_EMAIL_HEADING,STORE_NAME), sprintf(MODULE_PAYMENT_NOVALNET_TEST_TRANSACTION_EMAIL_CONTENT, $datas['order_no']),'');
		}
	}

	/**
	 *  Get server response
	 *
	 * @param array $response
	 * return string
	 */
	 public static  function getServerResponse($response) {
		return (!empty($response['status_desc']) ? $response['status_desc'] : (!empty($response['status_text']) ? $response['status_text'] : (!empty($response['status_message']) ? $response['status_message'] : MODULE_PAYMENT_NOVALNET_TRANSACTION_ERROR)));
	 }
}

