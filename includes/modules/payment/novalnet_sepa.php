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
 * Script : novalnet_sepa.php
 *
 */
require_once(DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php');

class novalnet_sepa {
	var $code, $title, $enabled, $sort_order, $test_mode, $description;

	/**
	 * Core Function : Constructor()
	 *
	 */
	function __construct() {
		global $order;
		$this->code       		= 'novalnet_sepa';
		$this->title      		= ((defined('MODULE_PAYMENT_NOVALNET_SEPA_TEXT_TITLE')) ? MODULE_PAYMENT_NOVALNET_SEPA_TEXT_TITLE : '');
		$this->description      = ((defined('MODULE_PAYMENT_NOVALNET_SEPA_TEXT_TITLE')) ? MODULE_PAYMENT_NOVALNET_SEPA_TEXT_TITLE : '');
		$this->info       		= (defined('MODULE_PAYMENT_NOVALNET_SEPA_ENDCUSTOMER_INFO')) ? trim(strip_tags(MODULE_PAYMENT_NOVALNET_SEPA_ENDCUSTOMER_INFO)) : '';
		$this->sort_order 		= (defined('MODULE_PAYMENT_NOVALNET_SEPA_SORT_ORDER') && trim(MODULE_PAYMENT_NOVALNET_SEPA_SORT_ORDER) != '') ? trim(MODULE_PAYMENT_NOVALNET_SEPA_SORT_ORDER) : 0;
		$this->enabled    		= ((defined('MODULE_PAYMENT_NOVALNET_SEPA_STATUS') && MODULE_PAYMENT_NOVALNET_SEPA_STATUS == 'true') ? true : false);
		$this->test_mode  		= ((defined('MODULE_PAYMENT_NOVALNET_SEPA_TEST_MODE') && MODULE_PAYMENT_NOVALNET_SEPA_TEST_MODE == 'true') ? true : false);
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
		if (($this->enabled == true) && ((int) MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_ZONE > 0)) {
			$check_flag = false;
			$check_query = xtc_db_query("select zone_id from ".TABLE_ZONES_TO_GEO_ZONES." where geo_zone_id = '".MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_ZONE."' and zone_country_id = '".$order->delivery['country']['id']."' order by zone_id");
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
		'description' => '<link rel="stylesheet" type="text/css" href="ext/novalnet/css/novalnet.css">'.
						'<script type="text/javascript" src="' . DIR_WS_CATALOG . 'ext/novalnet/js/novalnet_sepa.js"></script>' .NovalnetHelper::showSepaDescription($this->code) . $this->info.
						'<script src="https://cdn.novalnet.de/js/v2/NovalnetUtility.js"></script>',
		'module_cost' => '',
		];
		$bic_field  = ['title' => '<div id="novalnet_sepa_bic" style="display:none;" ><span id="iban">BIC</span>'. xtc_draw_input_field('novalnet_sepa_bic', '', 'id="novalnet_sepa_bic_field" autocomplete="off" placeholder="BIC" onkeypress="return NovalnetUtility.formatBic(event);" onchange="return NovalnetUtility.formatBic(event);"') . '</div>',
		'field' => '',
		];
		$payment_details = xtc_db_query("SELECT payment_details, id FROM novalnet_transaction_detail WHERE customer_id='" . xtc_db_input($_SESSION['customer_id']) . "' AND payment_details !='' AND payment_type in ('DIRECT_DEBIT_SEPA', 'GUARANTEED_DIRECT_DEBIT_SEPA', 'INSTALMENT_DIRECT_DEBIT_SEPA') AND status IN ('CONFIRMED', 'ON_HOLD') ORDER BY id DESC LIMIT 10");
		$saved_card_details = [];
		$count = 0;
		while ($payment_detail = xtc_db_fetch_array($payment_details)) {
			$decode_details = json_decode($payment_detail['payment_details'], true);
			if (empty($decode_details['iban'])) {
				continue;
			}
			$decode_details['id'] = $payment_detail['id'];
			$saved_card_details[$count] = $decode_details;
			$count++;
		};
		unset($_SESSION['saved_card_details']);
		if (defined('MODULE_PAYMENT_NOVALNET_SEPA_TOKENIZATION') && MODULE_PAYMENT_NOVALNET_SEPA_TOKENIZATION == 'true') {
			if (!empty($saved_card_details)) {
				$payment_data = [];
				foreach ($saved_card_details as $key => $value) {
					if (empty($value['iban']) || in_array($value['iban'], $payment_data)) {
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
					$oneclick  = (!empty($saved_card_details)) ? '<nobr><input name = "novalnet_sepa" type="radio" class ="novalnet_sepa_saved_acc" value="'.$value['token'].'" '.$checked.' />'.sprintf(('IBAN %s'),$value['iban']) . '&nbsp;&nbsp;<a href="javascript:void(0);" id="'.$value['id'].'" class="token" >'.(defined('MODULE_PAYMENT_NOVALNET_REMOVE_DUPLICATE_TOKEN') ? MODULE_PAYMENT_NOVALNET_REMOVE_DUPLICATE_TOKEN : '').'</a></nobr>' : '';
					if(!empty($value['iban'])){
						$selection['fields'][] = [ 'title' =>   $oneclick ];
					}
					array_push($payment_data, $value['iban']);
				}
				$selection['fields'][] = [ 'title' => '<nobr><input type="radio" name = "novalnet_sepa"  id="novalnet_sepa_new" value="new" />'.MODULE_PAYMENT_NOVALNET_SEPA_NEW_ACCOUNT_DETAILS.'</nobr>'];
				$selection['fields'][] = ['title' => '<span id="iban" style="display:none;">'.MODULE_PAYMENT_NOVALNET_SEPA_IBAN.'</span>'. xtc_draw_input_field('novalnet_sepa_iban', '', 'id="novalnet_sepa_iban_field" style="display:none; width:170%" autocomplete="off" placeholder ="DE00 0000 0000 0000 0000 00" onkeypress="return NovalnetUtility.formatIban(event,\'novalnet_sepa_bic\');" onkeyup="return NovalnetUtility.formatIban(event,\'novalnet_sepa_bic\');" onchange="return NovalnetUtility.formatIban(event,\'novalnet_sepa_bic\');"'),
				];
				$selection['fields'][] = $bic_field;
				$selection['fields'][] = [
				'title' => '<nobr><div id="sepa_save_card" style="display:none;">'.xtc_draw_checkbox_field('nn_sepa_checkbox', 1 , false, 'id="novalnet_sepa_onclick"')." ".MODULE_PAYMENT_NOVALNET_SEPA_SAVE_CARD_DETAILS.'</div></nobr>',
				];
			}
		} else {
			$selection['fields'][] = [
				'title' => MODULE_PAYMENT_NOVALNET_SEPA_IBAN. xtc_draw_input_field('novalnet_sepa_iban', '', 'id="novalnet_sepa_iban_field" autocomplete="off" placeholder ="DE00 0000 0000 0000 0000 00" style="width:170%;"  onkeypress="return NovalnetUtility.formatIban(event,\'novalnet_sepa_bic\');" onkeyup="return NovalnetUtility.formatIban(event,\'novalnet_sepa_bic\');" onchange="return NovalnetUtility.formatIban(event,\'novalnet_sepa_bic\');"'), 
				'field' => '',
			];
			$selection['fields'][] = $bic_field;
		}
		if ((defined('MODULE_PAYMENT_NOVALNET_SEPA_TOKENIZATION') && MODULE_PAYMENT_NOVALNET_SEPA_TOKENIZATION == 'true') && empty($saved_card_details)) {
			$selection['fields'][] = [
				'title' => MODULE_PAYMENT_NOVALNET_SEPA_IBAN. xtc_draw_input_field('novalnet_sepa_iban', '', 'id="novalnet_sepa_iban_field" autocomplete="off" placeholder ="DE00 0000 0000 0000 0000 00" style="width:170%;"  onkeypress="return NovalnetUtility.formatIban(event,\'novalnet_sepa_bic\');" onkeyup="return NovalnetUtility.formatIban(event,\'novalnet_sepa_bic\');" onchange="return NovalnetUtility.formatIban(event,\'novalnet_sepa_bic\');"'),
			];
			$selection['fields'][] = $bic_field;
			$selection['fields'][] = [
				'title' => '<nobr><div id="sepa_save_card" style="display:block;">'.xtc_draw_checkbox_field('nn_sepa_checkbox', 1 , false, 'id="novalnet_sepa_onclick"')." ".MODULE_PAYMENT_NOVALNET_SEPA_SAVE_CARD_DETAILS.'</div></nobr>',
			];
		}

		if (defined('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_STATUS') && MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_STATUS == 'true') {
			if ($this->proceedToGuranteePayment()) {
				$proceed_to_sepa = false;
			} elseif (defined('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_FORCE') && MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_FORCE == 'true') {
				$proceed_to_sepa = true;
			}
		} else {
			$proceed_to_sepa = true;
		}

		if ($proceed_to_sepa) {
			return $selection;
		}
		return false;
	}

	/**
	 * Core Function : pre_confirmation_check ()
	 *
	 * Perform validations for post values
	 * @return boolean
	 */
	function pre_confirmation_check() {
		if(isset($_REQUEST['novalnet_sepa']) && $_REQUEST['novalnet_sepa'] == 'new' || !empty($_SESSION['novalnet_sepa'])){
			unset($_SESSION['novalnet_sepa']);
			$_SESSION['novalnet_sepa'] = $_REQUEST['novalnet_sepa'];
		}
		if (!empty($_SESSION['novalnet_sepa_token']) || !empty($_SESSION['novalnet_sepa_create_token'])) {
			unset($_SESSION['novalnet_sepa_token'], $_SESSION['novalnet_sepa_create_token']);
		}

		if (!empty($_REQUEST['novalnet_sepa']) && $_REQUEST['novalnet_sepa'] != 'new' && (defined('MODULE_PAYMENT_NOVALNET_SEPA_TOKENIZATION')
		&& MODULE_PAYMENT_NOVALNET_SEPA_TOKENIZATION == 'true')) {
			$_SESSION['novalnet_sepa_token'] = $_REQUEST['novalnet_sepa'];
		}
		else if (!empty($_REQUEST['novalnet_sepa_iban'])) {
			$_SESSION['novalnet_sepa_iban'] = $_REQUEST['novalnet_sepa_iban'];
			$_SESSION['novalnet_sepa_bic'] = !empty($_REQUEST['novalnet_sepa_bic']) ? $_REQUEST['novalnet_sepa_bic'] : '';
			if ((defined('MODULE_PAYMENT_NOVALNET_SEPA_TOKENIZATION') && MODULE_PAYMENT_NOVALNET_SEPA_TOKENIZATION == 'true') && !empty($_REQUEST['nn_sepa_checkbox'])) {
				$_SESSION['novalnet_sepa_create_token'] = $_REQUEST['nn_sepa_checkbox'];
			}
		} else {
			xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code . '&error=' .MODULE_PAYMENT_NOVALNET_VALID_ACCOUNT_CREDENTIALS_ERROR, 'SSL', true, false));
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
		$merchant_data    = NovalnetHelper::getMerchantData();
		$customer_data    = NovalnetHelper::getCustomerData();
		$due_date_in_days = MODULE_PAYMENT_NOVALNET_SEPA_DUE_DATE;
		$custom_data 	  = NovalnetHelper::getCustomData();
		$transaction_data = NovalnetHelper::getTransactionData();
		$transaction_data['transaction']['payment_type'] = NovalnetHelper::getPaymentName($this->code);
		// Add token data if it's enabled
		if((defined('MODULE_PAYMENT_NOVALNET_SEPA_TOKENIZATION') && MODULE_PAYMENT_NOVALNET_SEPA_TOKENIZATION == 'true')) {
			if (!empty($_SESSION['saved_card_details']) && empty($_SESSION['novalnet_sepa'])) {
				NovalnetHelper::getToeknizationDetails($this->code, $transaction_data);
				$params = array_merge($merchant_data, $customer_data, $transaction_data, $custom_data);
				$params['transaction']['payment_data']['token'] = $transaction_data['transaction']['payment_data']['token'];
			} else if ((isset($_SESSION['novalnet_sepa']) && $_SESSION['novalnet_sepa'] == 'new')) {
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
		if (!empty ($due_date_in_days) && is_numeric($due_date_in_days)) {
			$params['transaction']['due_date'] = NovalnetHelper::getDueDate($due_date_in_days);
		}

		if (!empty($params['transaction']['create_token'])) {
			$params['custom']['input1'] = 'store_payment_data';
			$params['custom']['inputval1'] = 1;
		}
		if ((defined('MODULE_PAYMENT_NOVALNET_SEPA_AUTHENTICATE') && MODULE_PAYMENT_NOVALNET_SEPA_AUTHENTICATE == 'authorize')
		&& (NovalnetHelper::getOrderAmount($_SESSION['novalnet']['payment_amount']) >= (defined('MODULE_PAYMENT_NOVALNET_SEPA_MANUAL_CHECK_LIMIT') ? MODULE_PAYMENT_NOVALNET_SEPA_MANUAL_CHECK_LIMIT : ''))) { // Authorize transaction
			$response = NovalnetHelper::sendRequest($params, NovalnetHelper::get_action_endpoint('authorize'));
		} else { // Captue or Zero-amount transaction
			if (defined('MODULE_PAYMENT_NOVALNET_SEPA_AUTHENTICATE') && MODULE_PAYMENT_NOVALNET_SEPA_AUTHENTICATE == 'zero_amount') {
				$params['transaction']['amount'] = 0;
				$params['transaction']['create_token'] = 1;
			}
			$response = NovalnetHelper::sendRequest($params, NovalnetHelper::get_action_endpoint('payment'));
		}
		if ($response['result']['status'] == 'SUCCESS') {
			$_SESSION['response'] = $response;
		} else {
			$error = (!empty($response['result']['status_text']) ? $response['result']['status_text'] : '');
			xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code . '&error=' . urlencode($error), 'SSL', true, false));
		}
		$order->info['comments'] .= NovalnetHelper::updateTransactionDetails($this->code, $_SESSION['response']);	
		unset($_SESSION['novalnet']['payment_amount'], $_SESSION['novalnet_sepa_iban'], $_SESSION['novalnet_sepa_token'], $_SESSION['novalnet_sepa_create_token'], $_SESSION['novalnet_sepa_bic'], $_SESSION['novalnet_sepa'], $_SESSION['saved_card_details']);
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
		$check_query = xtc_db_query("select `value` from `gx_configurations` where `key` = 'configuration/MODULE_PAYMENT_NOVALNET_SEPA_STATUS'");
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
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_SEPA_STATUS','false',  '1', 'switcher',now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_SEPA_TEST_MODE','false',  '2', 'switcher', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_SEPA_ALLOWED', '',  '3',  now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_ZONE', '0',  '4', 'geo-zone',now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_SEPA_VISIBILITY_BY_AMOUNT', '',  '5',  now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_SEPA_TOKENIZATION', 'true' , '6', 'switcher' , now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_SEPA_AUTHENTICATE', '', '7',  now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_SEPA_MANUAL_CHECK_LIMIT', '', '8',  now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_SEPA_DUE_DATE', '', '9', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_SEPA_ORDER_STATUS', '2',  '10', 'order-status', now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`,  `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_SEPA_SORT_ORDER', '1', '11',  now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`,  `last_modified`) values  ('configuration/MODULE_PAYMENT_NOVALNET_SEPA_ENDCUSTOMER_INFO', '',  '12',  now())");
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
		NovalnetHelper::includeAdminJS($this->code, true);
		$lang = (isset($_SESSION['language_code'])) ? strtoupper($_SESSION['language_code']) : 'DE';
		echo '<input type="hidden" name="lang_code" id="lang_code" value= "'.$lang.'" />';
		return array(
			'configuration/MODULE_PAYMENT_NOVALNET_SEPA_STATUS',
			'configuration/MODULE_PAYMENT_NOVALNET_SEPA_TEST_MODE',
			'configuration/MODULE_PAYMENT_NOVALNET_SEPA_ALLOWED',
			'configuration/MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_ZONE',
			'configuration/MODULE_PAYMENT_NOVALNET_SEPA_VISIBILITY_BY_AMOUNT',
			'configuration/MODULE_PAYMENT_NOVALNET_SEPA_TOKENIZATION',
			'configuration/MODULE_PAYMENT_NOVALNET_SEPA_AUTHENTICATE',
			'configuration/MODULE_PAYMENT_NOVALNET_SEPA_MANUAL_CHECK_LIMIT',
			'configuration/MODULE_PAYMENT_NOVALNET_SEPA_DUE_DATE',
			'configuration/MODULE_PAYMENT_NOVALNET_SEPA_ORDER_STATUS',
			'configuration/MODULE_PAYMENT_NOVALNET_SEPA_SORT_ORDER',
			'configuration/MODULE_PAYMENT_NOVALNET_SEPA_ENDCUSTOMER_INFO',
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
		$country_check = NovalnetHelper::checkGuaranteeCountries(strtoupper($order->billing['country']['iso_code_2']), MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_ALLOW_B2B, $order->billing['company']);
		if (defined('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_STATUS') && MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_STATUS == 'true') {
			if (NovalnetHelper::getOrderAmount($order->info['total']) >= $minimum_amount_gurantee && $country_check
			&& $order->info['currency'] == 'EUR' && NovalnetHelper::isBillingShippingsame()) {
				return true;
			}
		  return false;
		}
	}
}
