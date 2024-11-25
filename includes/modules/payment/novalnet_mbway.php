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
 * Script : novalnet_mbway.php
 *
 */
require_once(DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php');

class novalnet_mbway {
	var $code, $title, $enabled, $sort_order, $test_mode, $info, $description;

	/**
	 * Core Function : Constructor()
	 *
	 */
	function __construct() {
		global $order;
		$this->code       = 'novalnet_mbway';
		$this->title      = ((defined('MODULE_PAYMENT_NOVALNET_MBWAY_TEXT_TITLE')) ? MODULE_PAYMENT_NOVALNET_MBWAY_TEXT_TITLE : '');
		$this->description= ((defined('MODULE_PAYMENT_NOVALNET_MBWAY_TEXT_TITLE')) ? MODULE_PAYMENT_NOVALNET_MBWAY_TEXT_TITLE : '');
		$this->info       = ((defined('MODULE_PAYMENT_NOVALNET_MBWAY_ENDCUSTOMER_INFO')) ? trim(strip_tags(MODULE_PAYMENT_NOVALNET_MBWAY_ENDCUSTOMER_INFO)) : '');
		$this->sort_order = defined('MODULE_PAYMENT_NOVALNET_MBWAY_SORT_ORDER') && MODULE_PAYMENT_NOVALNET_MBWAY_SORT_ORDER != '' ? trim(MODULE_PAYMENT_NOVALNET_MBWAY_SORT_ORDER) : 0;
		$this->enabled    = ((defined('MODULE_PAYMENT_NOVALNET_MBWAY_STATUS') && MODULE_PAYMENT_NOVALNET_MBWAY_STATUS == 'true') ? true : false);
		$this->test_mode  = ((defined('MODULE_PAYMENT_NOVALNET_MBWAY_TEST_MODE') && MODULE_PAYMENT_NOVALNET_MBWAY_TEST_MODE == 'true') ? true : false);
		$this->tmpOrders  = true;
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
		if (($this->enabled == true) && ((int) MODULE_PAYMENT_NOVALNET_MBWAY_PAYMENT_ZONE > 0)) {
			$check_flag = false;
			$check_query = xtc_db_query("select zone_id from ".TABLE_ZONES_TO_GEO_ZONES." where geo_zone_id = '".MODULE_PAYMENT_NOVALNET_MBWAY_PAYMENT_ZONE."' and zone_country_id = '".$order->delivery['country']['id']."' order by zone_id");

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
			if (!empty($_SESSION['payment']) && $_SESSION['payment'] == $this->code) { unset($_SESSION['payment']); }
			return false;
		}
		$selection = [
			'id'          => $this->code,
			'module'      => $this->title,
			'description' => '<link rel="stylesheet" type="text/css" href="ext/novalnet/css/novalnet.min.css">'.'<script type=text/javascript src="' . DIR_WS_CATALOG . 'ext/novalnet/js/novalnet.min.js" integrity="sha384-XpTqEgxNginGQEEsAKXY/Gu4SEBFJgtt+NWp5bhIGrQUVmIQ686g8rzazycrtTiK"></script>'. NovalnetHelper::showPaymentDescription($this->code) . $this->info,
		];
		$selection['fields'][] = [
			'title' =>'<nobr><span>'. MODULE_PAYMENT_NOVALNET_MBWAY_MOBILE_NO.'</nobr></span>'.'<br>'. xtc_draw_input_field('novalnet_mbway_mobile_no', '', 'id="novalnet_mbway_mobile_no" autocomplete="off" placeholder ="'.MODULE_PAYMENT_NOVALNET_MBWAY_MOBILE_NO.'" style="width:170%;" oninput="validateNumericInputmbway(this)" '), 
			'field' => '',

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
		 if (!empty($_REQUEST['novalnet_mbway_mobile_no'])) {
			$_SESSION['novalnet_mbway_mobile_no'] = $_REQUEST['novalnet_mbway_mobile_no'];
        	
		} else {
			xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code . '&error=' .MODULE_PAYMENT_NOVALNET_MBWAY_VALID_MOBILENO_CREDENTIALS_ERROR, 'SSL', true, false));
		}
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
	 */
	function before_process() {
		$post = $_REQUEST;
		if (isset($post['tid'])) {
			$session_order_id = (!empty($_SESSION['nn_tempOID'])) ? $_SESSION['nn_tempOID'] : $_SESSION['tmp_oID'];
			if ($post['status'] == 'SUCCESS') { // Success
				if (NovalnetHelper::validateCheckSum($post)) { // Checksum success
					NovalnetHelper::handleRedirectSuccessResponse($post, $session_order_id, $this->code);
				} else { // Checksum fail
					NovalnetHelper::processTempOrderFail($this->code, $session_order_id, $post,MODULE_PAYMENT_NOVALNET_ERROR_MSG);
				}
			} else { // Failure
				NovalnetHelper::processTempOrderFail($this->code, $session_order_id, $post);
			}
		}
	}

	/**
	 * Core Function : payment_action()
	 */
	function payment_action() {

		global $insert_id;
		
		if (!empty($_SESSION['nn_txn_secret']) || !empty($_SESSION['nn_tempOID'])) {
			unset($_SESSION['nn_txn_secret']);
			unset($_SESSION['nn_tempOID']);
		}
		$response = NovalnetHelper::getRedirectData($insert_id, $this->code);
		if ($response['result']['status'] == 'SUCCESS' && !empty($response['result']['redirect_url'])) {
			$_SESSION['nn_txn_secret'] = $response['transaction']['txn_secret'];
			$_SESSION['nn_tempOID'] = $insert_id;
			xtc_redirect($response['result']['redirect_url']);
		} else {
			NovalnetHelper::processTempOrderFail($this->code, $insert_id, $response);
		}
	}

	/**
	 * Core Function : after_process()
	 *
	 */
	function after_process() {
		global $order, $insert_id;
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
		$check_query = xtc_db_query("select `value` from `gx_configurations` where `key` = 'configuration/MODULE_PAYMENT_NOVALNET_MBWAY_STATUS'");
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
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_MBWAY_STATUS','false', '1', 'switcher',now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_MBWAY_TEST_MODE','false', '2', 'switcher', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_MBWAY_ALLOWED', '', '3', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_MBWAY_PAYMENT_ZONE', '0', '4', 'geo-zone',now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_MBWAY_VISIBILITY_BY_AMOUNT', '', '5', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_MBWAY_ORDER_STATUS', '2', '6', 'order-status', now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_MBWAY_SORT_ORDER', '28', '7', now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_MBWAY_ENDCUSTOMER_INFO', '', '8', now())");
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
		NovalnetHelper::includeAdminJS($this->code);
		return array(
			'configuration/MODULE_PAYMENT_NOVALNET_MBWAY_STATUS',
			'configuration/MODULE_PAYMENT_NOVALNET_MBWAY_TEST_MODE',
			'configuration/MODULE_PAYMENT_NOVALNET_MBWAY_ALLOWED',
			'configuration/MODULE_PAYMENT_NOVALNET_MBWAY_PAYMENT_ZONE',
			'configuration/MODULE_PAYMENT_NOVALNET_MBWAY_VISIBILITY_BY_AMOUNT',
			'configuration/MODULE_PAYMENT_NOVALNET_MBWAY_ORDER_STATUS',
			'configuration/MODULE_PAYMENT_NOVALNET_MBWAY_SORT_ORDER',
			'configuration/MODULE_PAYMENT_NOVALNET_MBWAY_ENDCUSTOMER_INFO',
		);
	}
}
