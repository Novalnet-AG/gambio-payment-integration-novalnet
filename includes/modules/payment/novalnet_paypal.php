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
 * Script : novalnet_paypal.php
 *
 */
require_once(DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php');

class novalnet_paypal {
	var $code, $title, $enabled, $sort_order, $test_mode, $description;

	/**
	 * Core Function : Constructor()
	 *
	 */
	function __construct() {
		global $order;
		$this->code       		= 'novalnet_paypal';
		$this->title      		= ((defined('MODULE_PAYMENT_NOVALNET_PAYPAL_TEXT_TITLE')) ? MODULE_PAYMENT_NOVALNET_PAYPAL_TEXT_TITLE : '');
		$this->description      = ((defined('MODULE_PAYMENT_NOVALNET_PAYPAL_TEXT_TITLE')) ? MODULE_PAYMENT_NOVALNET_PAYPAL_TEXT_TITLE : '');
		$this->info       		= (defined('MODULE_PAYMENT_NOVALNET_PAYPAL_ENDCUSTOMER_INFO') ? trim(strip_tags(MODULE_PAYMENT_NOVALNET_PAYPAL_ENDCUSTOMER_INFO)) : '');
		$this->sort_order 		= defined('MODULE_PAYMENT_NOVALNET_PAYPAL_SORT_ORDER') && trim(MODULE_PAYMENT_NOVALNET_PAYPAL_SORT_ORDER) != '' ? trim(MODULE_PAYMENT_NOVALNET_PAYPAL_SORT_ORDER) : 0;
		$this->enabled    		= ((defined('MODULE_PAYMENT_NOVALNET_PAYPAL_STATUS') && MODULE_PAYMENT_NOVALNET_PAYPAL_STATUS == 'true') ? true : false);
		$this->test_mode  		= ((defined('MODULE_PAYMENT_NOVALNET_PAYPAL_TEST_MODE') && MODULE_PAYMENT_NOVALNET_PAYPAL_TEST_MODE == 'true') ? true : false);
		$this->tmpOrders  		= true;
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
		if (($this->enabled == true) && ((int) MODULE_PAYMENT_NOVALNET_PAYPAL_PAYMENT_ZONE > 0)) {
			$check_flag = false;
			$check_query = xtc_db_query("select zone_id from ".TABLE_ZONES_TO_GEO_ZONES." where geo_zone_id = '".MODULE_PAYMENT_NOVALNET_PAYPAL_PAYMENT_ZONE."' and zone_country_id = '".$order->delivery['country']['id']."' order by zone_id");
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
			'description' => NovalnetHelper::showPaymentDescription($this->code) .  '<span id ="notification_buyer_wrap">' . $this->info.'</span>',
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
		global $order;
		$post = $_REQUEST;
		if (isset($post['tid'])) {
			$session_order_id = (!empty($_SESSION['nn_tempOID'])) ? $_SESSION['nn_tempOID'] : $_SESSION['tmp_oID'];
			if ($post['status'] == 'SUCCESS') { // Success
				if (NovalnetHelper::validateCheckSum($post)) { // Checksum success
					NovalnetHelper::handleRedirectSuccessResponse($post, $session_order_id, $this->code);
				} else { // Checksum fail
					NovalnetHelper::processTempOrderFail($this->code, $session_order_id, $post, MODULE_PAYMENT_NOVALNET_ERROR_MSG);
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
		$paypal_sheet_details = $this->paypal_sheet_details();
		$response = NovalnetHelper::getRedirectData($insert_id, $this->code, $paypal_sheet_details);
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
	 * Send params to Novalnet server (Note : if the payment uses curl request)
	 */
	function after_process() {
		unset($_SESSION['nn_tempOID'], $_SESSION['nn_txn_secret']);
		return false;
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
		$check_query = xtc_db_query("select `value` from `gx_configurations` where `key` = 'configuration/MODULE_PAYMENT_NOVALNET_PAYPAL_STATUS'");
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
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_PAYPAL_STATUS','false', '1', 'switcher',now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_PAYPAL_TEST_MODE','false', '2', 'switcher', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_PAYPAL_ALLOWED', '', '3', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_PAYPAL_PAYMENT_ZONE', '0', '4', 'geo-zone',now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_PAYPAL_VISIBILITY_BY_AMOUNT', '', '5', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_PAYPAL_AUTHENTICATE','false', '7' ,'switcher', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_PAYPAL_MANUAL_CHECK_LIMIT', '', '8', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_PAYPAL_ORDER_STATUS', '2', '9', 'order-status', now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_PAYPAL_SORT_ORDER', '17', '10', now()) ");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_PAYPAL_ENDCUSTOMER_INFO', '', '11', now())");
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
			'configuration/MODULE_PAYMENT_NOVALNET_PAYPAL_STATUS',
			'configuration/MODULE_PAYMENT_NOVALNET_PAYPAL_TEST_MODE',
			'configuration/MODULE_PAYMENT_NOVALNET_PAYPAL_ALLOWED',
			'configuration/MODULE_PAYMENT_NOVALNET_PAYPAL_PAYMENT_ZONE',
			'configuration/MODULE_PAYMENT_NOVALNET_PAYPAL_VISIBILITY_BY_AMOUNT',
			'configuration/MODULE_PAYMENT_NOVALNET_PAYPAL_AUTHENTICATE',
			'configuration/MODULE_PAYMENT_NOVALNET_PAYPAL_MANUAL_CHECK_LIMIT',
			'configuration/MODULE_PAYMENT_NOVALNET_PAYPAL_ORDER_STATUS',
			'configuration/MODULE_PAYMENT_NOVALNET_PAYPAL_SORT_ORDER',
			'configuration/MODULE_PAYMENT_NOVALNET_PAYPAL_ENDCUSTOMER_INFO',
		);
	}

	/**
	 * Paypal sheet details
	 *
	 * @return $data
	 */
	function paypal_sheet_details() {
		global $order;
		foreach ($order->products as $products){
			if (isset($products['attributes'])) {
				foreach ($products['attributes'] as $attr => $value) {
					$attributes[] = ', ' . $value['option'] . ':' . $value['value'];
				}
			}
			if ($products['product_type'] == 1) {
				$product_type = 'physical';
			} else {
				$product_type = 'digital';
			}
			$productId = str_split($products['id']);
			$product_desc = xtc_db_fetch_array(xtc_db_query("select products_description from " . TABLE_PRODUCTS_DESCRIPTION . " where language_id = '" . $_SESSION['languages_id'] . "' and products_id = '" . $productId[0] . "'"));
			$data['cart_info']['line_items'][] = array(
						'name'        => $products['name']. ' x ' .$products['qty'] . (isset($attributes) ? $attributes : ''),
						'price'       => (string) (round((float) $products['price'] * 100)),
						'quantity'    => $products['qty'],
						'description' => !empty($product_desc['products_description']) ? strip_tags($product_desc['products_description']) : '',
						'category'    => $product_type,
			);

		}
		$nn_discount = xtc_db_fetch_array(xtc_db_query("SELECT value FROM " . TABLE_ORDERS_TOTAL . " where class = 'ot_discount' AND orders_id = " . $_SESSION['tmp_oID']));
		$nn_coupon = xtc_db_fetch_array(xtc_db_query("SELECT value FROM " . TABLE_ORDERS_TOTAL . " where class = 'ot_coupon' AND orders_id = " . $_SESSION['tmp_oID']));
		
		if (!empty($nn_discount) || !empty($nn_coupon)) {
			$discount_amount = isset($nn_discount['value']) ? $nn_discount['value'] : 0;
			$coupon_amount = isset($nn_coupon['value']) ? $nn_coupon['value'] : 0;

		$deduction = (string) ((round((float) $discount_amount * 100)) + (round((float) $coupon_amount * 100)));
			$data['cart_info']['line_items'][] = array(
						'name'        => 'Discount',
						'price'       => $deduction,
						'quantity'    => 1,
			);
		}
		$data['cart_info']['items_tax_price'] = (string) (round((float) $order->info['tax'] * 100));
		$data['cart_info']['items_shipping_price'] = (string) (round((float) $order->info['shipping_cost'] * 100));
		return $data;
	}
}

