<?php
/**
 * Novalnet payment module
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
 * Script : novalnet_eps.php
 *
 */
require_once(DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php');
class novalnet_eps {
    var $code, $title, $public_title, $description, $sort_order, $enabled, $test_mode, $form_action_url;

    /**
     * Core Function : Constructor()
     *
     */
    public function __construct() {
        global $order;
        $this->code         = 'novalnet_eps';
        $this->title        = defined('MODULE_PAYMENT_NOVALNET_EPS_TEXT_TITLE') ? 'Novalnet '. MODULE_PAYMENT_NOVALNET_EPS_TEXT_TITLE : '';
        $this->public_title = defined('MODULE_PAYMENT_NOVALNET_EPS_PUBLIC_TITLE') ? MODULE_PAYMENT_NOVALNET_EPS_PUBLIC_TITLE : '';
        $this->sort_order   = 0;
        if (strpos(MODULE_PAYMENT_INSTALLED, $this->code) !== false) {
			$this->sort_order   = defined('MODULE_PAYMENT_NOVALNET_EPS_SORT_ORDER') && MODULE_PAYMENT_NOVALNET_EPS_SORT_ORDER != '' ? MODULE_PAYMENT_NOVALNET_EPS_SORT_ORDER : 0;
            $this->enabled         = ((defined('MODULE_PAYMENT_NOVALNET_EPS_ENABLE_MODULE') && MODULE_PAYMENT_NOVALNET_EPS_ENABLE_MODULE == 'True') ? true : false);
            $this->test_mode       = ((defined('MODULE_PAYMENT_NOVALNET_EPS_TEST_MODE') && MODULE_PAYMENT_NOVALNET_EPS_TEST_MODE == 'True') ? true : false); // To check the test mode
            $this->tmpOrders       = true;
            $this->tmpStatus       = MODULE_PAYMENT_NOVALNET_PAYMENT_PENDING_STATUS;            
            $this->form_action_url = 'https://payport.novalnet.de/giropay';
            NovalnetHelper::getLastSuccessPayment($this->code); // By default last transaction payment select in checkout page
        }
        if (is_object($order)) {
            $this->update_status();
        }
    }

    /**
     * Core Function : update_status()
     *
     * check if zone is allowed to see module
     *
     */
    function update_status() {
        global $order;
        if ($_SESSION['shipping']['id'] == 'selfpickup_selfpickup') {
            $this->enabled = false;
        }
        if (($this->enabled == true) && ((int) MODULE_PAYMENT_NOVALNET_EPS_PAYMENT_ZONE > 0)) {
            $check_flag  = false;
            $check_query = xtc_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_NOVALNET_EPS_PAYMENT_ZONE . "' and zone_country_id = '" . $order->delivery['country']['id'] . "' order by zone_id");
            while ($check = xtc_db_fetch_array($check_query)) {
                if ($check['zone_id'] < 1) {
                    $check_flag = true;
                    break;
                } elseif ($check['zone_id'] == $order->delivery['zone_id']) {
                    $check_flag = true;
                    break;
                }
            }
            if ($check_flag == false) {
                $this->enabled = false;
            }
        }
    }

    /**
     * Core Function : selection()
     *
     * Display checkout form in chekout payment page
     * @return payment form
     */
    function selection() {
		// Get order amount
        $order_amount = NovalnetHelper::getPaymentAmount();
        // Payment hide in checkout page when condition was true
        if (NovalnetHelper::merchantValidate($this->code) || strpos(MODULE_PAYMENT_INSTALLED, 'novalnet_config') === false || !NovalnetHelper::hidePaymentVisibility($this->code, $order_amount)) {
            if (!empty($_SESSION['payment']) && $_SESSION['payment'] == $this->code) {
                unset($_SESSION['payment']);
            }
            return false;
        }
        // To check the test mode in shop
        $test_mode       = $this->test_mode == 1 ? MODULE_PAYMENT_NOVALNET_TEST_MODE_MSG : '';
        // Merchant can show the information for end customer in checkout page
        $endcustomerinfo = trim(strip_tags(MODULE_PAYMENT_NOVALNET_EPS_ENDCUSTOMER_INFO)) != '' ? trim(strip_tags(MODULE_PAYMENT_NOVALNET_EPS_ENDCUSTOMER_INFO)) : '';
		global $gx_version;
		require DIR_FS_CATALOG . 'release_info.php';
		$shop_version = str_replace('.','',$gx_version);
		$shop_version = str_replace('v','',$shop_version);
		if($shop_version > '3810') {
			$payment_title= str_replace('Novalnet','',$this->title);
			$title = $payment_title;
		} else {
			$title = $this->public_title;
		}        
        $selection       = array(
                'id' 	 	  => $this->code,
                'module' 	  => $title,
                'description' => MODULE_PAYMENT_NOVALNET_REDIRECT_DESC . '<br>' . $test_mode . '<br>' . $endcustomerinfo);
		if($shop_version > '3810') {
			if((!defined('MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY')) || MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY == 'True'){
				$selection['logo_url'] = xtc_href_link('images/icons/payment/novalnet_eps.png', '', 'SSL', false, false, false, true, true);
			}else {
				$selection['logo_url'] = '';
				$selection['logo_alt'] = '';
			}
			
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
        return false;
    }

    /**
     * Core Function : confirmation()
     *
     * Displays confirmation page
     * @return boolean
     */
    function confirmation() {
		// Assign order amount in session
        $_SESSION['novalnet'][$this->code]['payment_amount'] = NovalnetHelper::getPaymentAmount();
    }

    /**
     * Core Function : process_button()
     *
     * Payments redirects from shop to payment site
     * @return boolean
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
        // To process the response validation and to update the transaction details in shop
        if (isset($post['tid'])) {
			// To validate the response
            $before_process_response = NovalnetHelper::validateRedirectResponse($this->code, $post);
            // Novalnet transaction details update the orders
            $order->info['comments'] = $before_process_response['novalnet_comments'];
			xtc_db_perform(TABLE_ORDERS, array(
				'comments' 		=> $order->info['comments']
			), "update", "orders_id=".$post['order_no']);
			xtc_db_perform(TABLE_ORDERS_STATUS_HISTORY, array(
				'comments' 		   => $order->info['comments']
			), "update", "orders_id=".$post['order_no']);

        } else {
			$order->info['order_status'] = MODULE_PAYMENT_NOVALNET_PAYMENT_PENDING_STATUS;
		}
    }

    /**
     * Core Function : payment_action()
     *
     * Send params to Novalnet server
     */    
    function payment_action() {
		global $order,$insert_id;
        // To process and generate the redirect params
        if (isset($_SESSION['novalnet'][$this->code]['payment_amount'])) { // Generate the redirect params
            $_SESSION['novalnet'][$this->code]['order_obj'] = array_merge((array) $order, array(
                'payment_amount' => $_SESSION['novalnet'][$this->code]['payment_amount']
            ));
            // Get the common payment params
            $urlparam       = NovalnetHelper::getCommonParms($_SESSION['novalnet'][$this->code]['order_obj'], $this->test_mode);
            // To encode the vendor configuration and get the hash value
            NovalnetHelper::generateHashValue($urlparam, array('auth_code', 'product', 'tariff', 'amount', 'test_mode', 'uniqid'));
            $urlparam['implementation']   = 'PHP';
            $urlparam['return_method']    = $urlparam['error_return_method'] = 'POST';
            $urlparam['return_url']       = $urlparam['error_return_url'] = xtc_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL');
            $urlparam['user_variable_0']  = (ENABLE_SSL == true) ? HTTPS_SERVER : HTTP_SERVER . DIR_WS_CATALOG;
            $urlparam['payment_type']     = 'EPS';
            $urlparam['order_no']         = $insert_id;
            $_SESSION['novalnet'][$this->code]['urlparam'] = $urlparam;
            xtc_redirect(xtc_href_link('checkout_novalnet_confirmation.php', '', 'SSL', true, false));            
        }
	}

    /**
     * Core Function : after_process()
     *
     * Send post back params(acknowledgement) to Novalnet server
     */
    function after_process() {
        global $insert_id;
        // Update the order status in shop
        NovalnetHelper::updateOrderStatus($insert_id, $this->code);
        // Perform paygate second call for transaction confirmations / order_no update
        NovalnetHelper::doSecondCallProcess(array(
            'payment_type' => $this->code,
            'order_no' => $insert_id
        ));
    }

    /**
     * Core Function : get_error()
     *
     * Show validation / error message
     * @return array
     */
    function get_error() {
		// Get the error message from shop
        $error_message = (isset($_GET['payment_message']) ? $_GET['payment_message'] : $_GET['error_message']);
        // Show the Error message in front end
        return array(
			'title' => $this->code,
			'error' => utf8_decode($error_message)
		);
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
     * Core Function : check()
     *
     * Checks for payment installation status
     * @return boolean
     */
    function check() {
        if (!isset($this->_check)) {
            $check_query  = xtc_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_NOVALNET_EPS_ALLOWED'");
            $this->_check = xtc_db_num_rows($check_query);
        }
        return $this->_check;
    }

    /**
     * Core Function : install()
     *
     * Payment module installation
     * @return boolean
     */
    function install() {
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . "
        (configuration_key, configuration_value, configuration_group_id, sort_order,set_function, use_function, date_added)
      VALUES
        ('MODULE_PAYMENT_NOVALNET_EPS_ALLOWED', '', '6', '1', '', '', now()),
        ('MODULE_PAYMENT_NOVALNET_EPS_ALIAS', 'NN_EPS', '6', '0', '', '', now()),
        ('MODULE_PAYMENT_NOVALNET_EPS_ENABLE_MODULE','False', '6', '2', 'xtc_mod_select_option(array(\'True\' => MODULE_PAYMENT_NOVALNET_TRUE,\'False\' => MODULE_PAYMENT_NOVALNET_FALSE),\'MODULE_PAYMENT_NOVALNET_EPS_ENABLE_MODULE\'," .'MODULE_PAYMENT_NOVALNET_EPS_ENABLE_MODULE'. ",' , '',now()),
        ('MODULE_PAYMENT_NOVALNET_EPS_TEST_MODE','False', '6', '3', 'xtc_mod_select_option(array(\'True\' => MODULE_PAYMENT_NOVALNET_TRUE,\'False\' => MODULE_PAYMENT_NOVALNET_FALSE),\'MODULE_PAYMENT_NOVALNET_EPS_TEST_MODE\'," .'MODULE_PAYMENT_NOVALNET_EPS_TEST_MODE'. ",' , '', now()),
        ('MODULE_PAYMENT_NOVALNET_EPS_VISIBILITY_BY_AMOUNT', '', '6', '4','',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_EPS_ENDCUSTOMER_INFO', '', '6', '5','',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_EPS_SORT_ORDER', '0', '6', '6', '',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_EPS_ORDER_STATUS', '0',  '6', '7', 'xtc_cfg_pull_down_order_statuses(', 'xtc_get_order_status_name', now()),
        ('MODULE_PAYMENT_NOVALNET_EPS_PAYMENT_ZONE', '0', '6', '8', 'xtc_cfg_pull_down_zone_classes(', 'xtc_get_zone_class_title',now()),
        ('MODULE_PAYMENT_NOVALNET_EPS_TRANS_REFERENCE1', '', '6', '9', '',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_EPS_TRANS_REFERENCE2', '', '6', '10', '',  '', now())");
    }

    /**
     * Core Function : remove()
     *
     * Payment module uninstallation
     * @return boolean
     */
    function remove() {
		xtc_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", array_merge($this->keys(),array('MODULE_PAYMENT_NOVALNET_EPS_ALIAS'))) . "')");
    }

    /**
     * Core Function : keys()
     *
     * Return keys to display in payment configuration (Backend)
     * @return boolean
     */
    function keys() {
       global $gx_version; // Get teh gambio version
		$alias_menu = array();
		if($gx_version >= '3.1.1.0' ) {
			$alias_menu = array_merge($alias_menu,array('MODULE_PAYMENT_NOVALNET_EPS_ALIAS'));
		}
	    $return_key = array_merge(array(
			'MODULE_PAYMENT_NOVALNET_EPS_ALLOWED',
            'MODULE_PAYMENT_NOVALNET_EPS_ENABLE_MODULE',
            'MODULE_PAYMENT_NOVALNET_EPS_TEST_MODE',
            'MODULE_PAYMENT_NOVALNET_EPS_VISIBILITY_BY_AMOUNT',
            'MODULE_PAYMENT_NOVALNET_EPS_ENDCUSTOMER_INFO',
            'MODULE_PAYMENT_NOVALNET_EPS_SORT_ORDER',
            'MODULE_PAYMENT_NOVALNET_EPS_ORDER_STATUS',
            'MODULE_PAYMENT_NOVALNET_EPS_PAYMENT_ZONE',
            'MODULE_PAYMENT_NOVALNET_EPS_TRANS_REFERENCE1',
            'MODULE_PAYMENT_NOVALNET_EPS_TRANS_REFERENCE2'
        ),$alias_menu);
		foreach ($return_key as $key => $value) {
            if( ($gx_version >= '3.1.1.0' && $value == 'MODULE_PAYMENT_NOVALNET_EPS_ALIAS')) {
				unset($return_key[$key]);
                break;
			}
        }
        return $return_key;
    }
}

