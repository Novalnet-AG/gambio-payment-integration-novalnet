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
 * Script : novalnet_paypal.php
 *
 */
require_once(DIR_FS_CATALOG . 'includes/external/novalnet/NovalnetHelper.class.php');
class novalnet_paypal {
    var $code,$title,$public_title,$description,$sort_order,$enabled,$test_mode,$form_action_url;

    /**
     * Core Function : Constructor()
     *
     */
    public function __construct() {
        global $order;
        $post               = $_REQUEST;
        $this->code         = 'novalnet_paypal';
        $this->title        = 'Novalnet '. MODULE_PAYMENT_NOVALNET_PAYPAL_TEXT_TITLE;
        $this->public_title = MODULE_PAYMENT_NOVALNET_PAYPAL_PUBLIC_TITLE;
        $this->sort_order   = 0;
        if (strpos(MODULE_PAYMENT_INSTALLED, $this->code) !== false) {
            $this->sort_order   = defined('MODULE_PAYMENT_NOVALNET_PAYPAL_SORT_ORDER') && MODULE_PAYMENT_NOVALNET_PAYPAL_SORT_ORDER != '' ? MODULE_PAYMENT_NOVALNET_PAYPAL_SORT_ORDER : 0;
            $this->enabled   = (MODULE_PAYMENT_NOVALNET_PAYPAL_ENABLE_MODULE == 'True');
            $this->test_mode = (MODULE_PAYMENT_NOVALNET_PAYPAL_TEST_MODE == 'True'); // To check the test mode
            // To process the paypal site
            if (empty($post['novalnet_paypal_change_account'])) {
                $this->form_action_url = 'https://payport.novalnet.de/paypal_payport';
            }
            NovalnetHelper::getLastSuccessPayment($this->code); // By default last transaction payment select in checkout page
        }

        if (is_object($order))
            $this->update_status();
    }

    /**
     * Core Function : update_status()
     *
     * check if zone is allowed to see module
     */
    function update_status() {
        global $order;
        if ($_SESSION['shipping']['id'] == 'selfpickup_selfpickup') {
            $this->enabled = false;
        }
        if (($this->enabled == true) && ((int) MODULE_PAYMENT_NOVALNET_PAYPAL_PAYMENT_ZONE > 0)) {
            $check_flag  = false;
            $check_query = xtc_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_NOVALNET_PAYPAL_PAYMENT_ZONE . "' and zone_country_id = '" . $order->delivery['country']['id'] . "' order by zone_id");
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
     * @return array
     */
    function selection() {
        // Get order amount
        $order_amount = NovalnetHelper::getPaymentAmount();
        // Payment hide in checkout page when condition was true
        if (NovalnetHelper::merchantValidate($this->code) || strpos(MODULE_PAYMENT_INSTALLED, 'novalnet_config') === false || !NovalnetHelper::hidePaymentVisibility($this->code, $order_amount)) {
            if (!empty($_SESSION['payment']) && $_SESSION['payment'] == $this->code) { unset($_SESSION['payment']); }
            return false;
        }
        // Unset the novalnet tid session variable
        if (!empty($_SESSION['payment']) && isset($_SESSION['novalnet'][$this->code]['tid']) && $_SESSION['payment'] != $this->code) { // Unset tid
            unset($_SESSION['novalnet'][$this->code]['tid']);
        }
        // To check the test mode in shop
        $test_mode       = $this->test_mode == 1 ? MODULE_PAYMENT_NOVALNET_TEST_MODE_MSG : '';
        // Merchant can show the information for end customer in checkout page
        $endcustomerinfo = trim(strip_tags(MODULE_PAYMENT_NOVALNET_PAYPAL_ENDCUSTOMER_INFO)) != '' ? trim(strip_tags(MODULE_PAYMENT_NOVALNET_PAYPAL_ENDCUSTOMER_INFO)) : '';
        // Get the end customer serialize details from database for one click shopping
        $payment_details = NovalnetHelper::getPaymentDetails($_SESSION['customer_id'], $this->code);
        $payment_details = unserialize($payment_details['payment_details']);
        $this->description  = MODULE_PAYMENT_NOVALNET_PAYPAL_REDIRECTION_TEXT_DESCRIPTION.'<br>'.MODULE_PAYMENT_NOVALNET_PAYPAL_REDIRECTION_BROWSER_TEXT_DESCRIPTION;
        // To process the Normal paypal payment
        if ( MODULE_PAYMENT_NOVALNET_PAYPAL_SHOP_TYPE != 'ONECLICK' || (MODULE_PAYMENT_NOVALNET_PAYPAL_SHOP_TYPE == 'ONECLICK' && empty($payment_details))) { // Displaying iframe form type
            return $selection  = array(
                'id'          => $this->code,
                'module'      => $this->public_title,
                'description' => '<div id="nn_normal_description">'.$this->description.'<br></div><div>' . $test_mode . '<br>' . $endcustomerinfo.'</div><script src="' . DIR_WS_CATALOG . 'includes/external/novalnet/js/novalnet_paypal.js' . '" type="text/javascript"></script>'.NovalnetHelper::enableJavascript(),
        );
        } else if (MODULE_PAYMENT_NOVALNET_PAYPAL_SHOP_TYPE == 'ONECLICK' && !empty($payment_details)) {
			$selection  = array(
                'id'          => $this->code,
                'module'      => $this->public_title,
                'description' => '<div id="nn_normal_description">'.$this->description.'<br></div><div>' . $test_mode . '<br>' . $endcustomerinfo.'</div><script src="' . DIR_WS_CATALOG . 'includes/external/novalnet/js/novalnet_paypal.js' . '" type="text/javascript"></script>',
        );
            // Display the One click details in checkout page
            $form_show = isset($_SESSION['novalnet'][$this->code]['novalnet_paypal_change_account']) ? $_SESSION['novalnet'][$this->code]['novalnet_paypal_change_account'] : 1;
            $oneclick  = (MODULE_PAYMENT_NOVALNET_PAYPAL_SHOP_TYPE == 'ONECLICK' && !empty($payment_details)) ? '<nobr><span id ="novalnet_paypal_new_acc" style="color:blue"><u><b>' . MODULE_PAYMENT_NOVALNET_PAYPAL_NEW_ACCOUNT . '</b></u></span></nobr>' : '';
            $selection['fields'][] = array(
                        'title' => $oneclick,
                        'field' => '');
            if(!empty($payment_details['paypal_transaction_tid'])) {
                    $selection['fields'][] =   array(
                        'title' =>'<div class= nn_paypal_ref_details >'. MODULE_PAYMENT_NOVALNET_PAYPAL_TRANSACTION_TID.'</div>',
                        'field' => '<div class= nn_paypal_ref_details style="margin: 3% 0% 0% 0%" >'. $payment_details['paypal_transaction_tid'].'</div>'
                    );
            }
            $selection['fields'][] = array(
                        'title' => '<div class= nn_paypal_ref_details >'. MODULE_PAYMENT_NOVALNET_PAYPAL_ORDER_TRANSACTION_TID.'</div>',
                        'field' =>'<div class= nn_paypal_ref_details style="margin: 4% 0% 0% 0%" ><nobr>'. $payment_details['novalnet_transaction_tid'].'<input type="hidden" id="nn_transaction_tid" name="nn_transaction_tid" value="' . $payment_details['novalnet_transaction_tid'] . '"/><input type="hidden" id="nn_root_paypal_catalog" value="' . DIR_WS_CATALOG . '"/>
                        <input type="hidden" name="novalnet_paypal_change_account" id="novalnet_paypal_change_account" value="' . $form_show . '"/></nobr></div>'
            );
            $selection['fields'][] = array(
                        'title' => '',
                         'field' => '<div class="nn_paypal_acc" style="display:none"><input type="hidden" id="nn_lang_paypal_new_account" value="' . MODULE_PAYMENT_NOVALNET_PAYPAL_NEW_ACCOUNT . '"/><input type="hidden" id="nn_lang_paypal_given_account" value="' . MODULE_PAYMENT_NOVALNET_PAYPAL_GIVEN_ACCOUNT . '"/><input id="nn_redirect_desc" type="hidden" value="'.MODULE_PAYMENT_NOVALNET_PAYPAL_REDIRECTION_TEXT_DESCRIPTION.'"><input id="nn_redirect_browser_desc" type="hidden" value="'.MODULE_PAYMENT_NOVALNET_PAYPAL_REDIRECTION_BROWSER_TEXT_DESCRIPTION.'"><input type="hidden" id="nn_paypal_one_click_desc" value="' . MODULE_PAYMENT_NOVALNET_PAYPAL_ONE_CLICK_TEXT_DESCRIPTION .'" /></div>'.NovalnetHelper::enableJavascript()
            );
        }
        return $selection;
    }

    /**
     * Core Function : pre_confirmation_check()
     *
     * Perform validations for post values
     */
    function pre_confirmation_check() {
        $post = $_REQUEST;
        // Get the payment type
        $_SESSION['novalnet'][$this->code]['novalnet_paypal_change_account'] = (MODULE_PAYMENT_NOVALNET_PAYPAL_SHOP_TYPE == 'ONECLICK') ? $post['novalnet_paypal_change_account'] : '0';
        // Show the javascript error message if javascript not enabled
        if (!empty($post['nn_js_enabled'])) {
            $payment_error_return = 'payment_error=' . $this->code . '&error_message=' . MODULE_PAYMENT_NOVALNET_NO_SCRIPT;
             xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
        }
        // Assign the reference transaction tid in session
        if (isset($post['nn_transaction_tid'])) {
            $_SESSION['novalnet'][$this->code]['nn_transaction_tid'] = $post['nn_transaction_tid'];
        }
    }

    /**
     * Core Function : confirmation()
     *
     * Displays confirmation page
     */
    function confirmation() {
        // Assign order amount in session
        $_SESSION['novalnet'][$this->code]['payment_amount'] = NovalnetHelper::getPaymentAmount();
    }

    /**
     * Core Function : process_button()
     *
     * Payments redirects from shop to payment site
     */
    function process_button() {
        global $order;
        // To process the normal paypal payment
        if (isset($_SESSION['novalnet'][$this->code]['payment_amount']) && $_SESSION['novalnet'][$this->code]['novalnet_paypal_change_account'] != '1') { // Generate the redirect payments params
            $_SESSION['novalnet'][$this->code]['order_obj'] = array_merge((array) $order, array( 'payment_amount' => $_SESSION['novalnet'][$this->code]['payment_amount'] ));
            $urlparam  = NovalnetHelper::getCommonParms($_SESSION['novalnet'][$this->code]['order_obj'], $this->test_mode ); // Get the common payment params
            // To process the on hold product
            if($this->getOnholdValue($urlparam)) {
				  $urlparam['on_hold'] = 1;
			}
			// To send the create payment ref param to novalnet server
            if(in_array( MODULE_PAYMENT_NOVALNET_PAYPAL_SHOP_TYPE, array( 'ONECLICK', 'ZEROAMOUNT'))) {
               $urlparam['create_payment_ref'] = 1;
            }
            $vendor_details = NovalnetHelper::getVendorDetails($this->code);
			// To process the zero amount booking and param send to the novalnet server
            if (MODULE_PAYMENT_NOVALNET_PAYPAL_SHOP_TYPE == 'ZEROAMOUNT') {
				if(!in_array($vendor_details['tariff_type'], array( 1, 3, 4 ))) {
					unset($vendor_details['tariff_type'],$urlparam['on_hold']);
					$urlparam['amount'] = 0;
					$_SESSION['novalnet'][$this->code]['serialize_data']  = serialize($urlparam);
					$_SESSION['novalnet'][$this->code]['reference_transaction'] = 1;
                } else {
					unset($urlparam['create_payment_ref']);
				}
            }
           // To encode the vendor configuration and get the hash value
            NovalnetHelper::generateHashValue($urlparam, array('auth_code', 'product', 'tariff', 'amount', 'test_mode', 'uniqid'));
            $urlparam['implementation'] = 'PHP';
            $urlparam['return_method']    = $urlparam['error_return_method'] = 'POST';
            $urlparam['return_url']       = $urlparam['error_return_url'] = xtc_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL');
            $urlparam['user_variable_0']  = (ENABLE_SSL == true) ? HTTPS_SERVER : HTTP_SERVER . DIR_WS_CATALOG;
            // Assign novalnet payment params in the hidden fields
            foreach ($urlparam as $k => $v) {
                $process_button_string .= xtc_draw_hidden_field($k, $v);
            }
            return $process_button_string;
        } else if (!isset($_SESSION['novalnet'][$this->code]['payment_amount'])) {
            // Redirect to checkout page for invaild amount
            $payment_error_return = 'payment_error=' . $this->code . '&error_message=' . MODULE_PAYMENT_NOVALNET_AMOUNT_ERROR_MESSAGE;
            xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
        }
    }

    /**
     * Core Function : before_process()
     *
     * Send params to Novalnet server
     */
    function before_process() {
        global $order;
        $post = $_REQUEST;
        // To process the response validation and to update the transaction details in shop
        if (isset($post['tid'])) {
            if(MODULE_PAYMENT_NOVALNET_PAYPAL_SHOP_TYPE == 'ONECLICK') {
                // Store the response value in database for one click shopping type
                 $_SESSION['novalnet'][$this->code]['serialize_data'] = serialize(array(
                    'paypal_transaction_tid'   => $post['paypal_transaction_id'],
                    'novalnet_transaction_tid' => $post['tid'],
                ));
            }
            // To validate the response
            $form_comments           = NovalnetHelper::validateRedirectResponse($this->code, $post);
            // Novalnet transaction details update the orders
            $order->info['comments'] = !empty($_SESSION['novalnet'][$this->code]['novalnet_comments']) ? $_SESSION['novalnet'][$this->code]['novalnet_comments'] : $form_comments['novalnet_comments'];
        } else if (MODULE_PAYMENT_NOVALNET_PAYPAL_SHOP_TYPE == 'ONECLICK' && $_SESSION['novalnet'][$this->code]['novalnet_paypal_change_account'] == '1' && !empty($_SESSION['novalnet'][$this->code]['nn_transaction_tid'])) { // To process the one click shopping
            $param_inputs = array_merge((array) $order, $_SESSION['novalnet'][$this->code]);
            $urlparam     = NovalnetHelper::getCommonParms($param_inputs); // Get the novalnet common payment params
            // To process the on hold product
            if($this->getOnholdValue($urlparam)) {
				  $urlparam['on_hold'] = 1;
			}
            $urlparam['payment_ref']     = $_SESSION['novalnet'][$this->code]['nn_transaction_tid'];
            $_SESSION['novalnet'][$this->code]['reference_transaction'] = 1;
            // payment call send to novalnet server
            $response                = NovalnetHelper::doPaymentCurlCall('https://payport.novalnet.de/paygate.jsp', $urlparam);
            parse_str($response, $payment_response);
            if ($payment_response['status'] == '100') { //Payment success
                NovalnetHelper::getPostValues($this->code, $urlparam, $payment_response);
            } else { // Payment failed
				unset($_SESSION['novalnet'][$this->code]['reference_transaction']);
                $stauts_failed = NovalnetHelper::getServerResponse($payment_response);
                $payment_error_return = 'payment_error=' . $this->code . '&error_message=' . utf8_decode($stauts_failed);
                xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
            }
        }
    }

    /**
     * Core Function : after_process()
     *
     * Send params to Novalnet server (Note : if the payment uses curl request)
     */
    function after_process() {
        global $insert_id;
        // Update the order status in shop
        NovalnetHelper::updateOrderStatus($insert_id, $this->code);
        // Perform paygate second call for transaction confirmations / order_no update
        NovalnetHelper::doSecondCallProcess(array( 'payment_type' => $this->code, 'order_no' => $insert_id ));
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
            'error' => utf8_encode($error_message)
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
     */
    function check() {
        if (!isset($this->_check)) {
            $check_query  = xtc_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_NOVALNET_PAYPAL_ALLOWED'");
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
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . "
        (configuration_key, configuration_value, configuration_group_id, sort_order,set_function, use_function, date_added)
        VALUES
        ('MODULE_PAYMENT_NOVALNET_PAYPAL_ALLOWED', '', '6', '1', '', '', now()),
        ('MODULE_PAYMENT_NOVALNET_PAYPAL_ALIAS', 'NN_PP', '6', '0', '', '', now()),
        ('MODULE_PAYMENT_NOVALNET_PAYPAL_ENABLE_MODULE','False', '6', '2', 'xtc_mod_select_option(array(\'True\' => MODULE_PAYMENT_NOVALNET_TRUE,\'False\' => MODULE_PAYMENT_NOVALNET_FALSE),\'MODULE_PAYMENT_NOVALNET_PAYPAL_ENABLE_MODULE\'," . MODULE_PAYMENT_NOVALNET_PAYPAL_ENABLE_MODULE . ",' , '',now()),
        ('MODULE_PAYMENT_NOVALNET_PAYPAL_TEST_MODE','False', '6', '3', 'xtc_mod_select_option(array(\'True\' => MODULE_PAYMENT_NOVALNET_TRUE,\'False\' => MODULE_PAYMENT_NOVALNET_FALSE),\'MODULE_PAYMENT_NOVALNET_PAYPAL_TEST_MODE\'," . MODULE_PAYMENT_NOVALNET_PAYPAL_TEST_MODE . ",' , '', now()),
        ('MODULE_PAYMENT_NOVALNET_PAYPAL_SHOP_TYPE','False', '6', '8', 'xtc_mod_select_option(array(\'False\' => MODULE_PAYMENT_NOVALNET_OPTION_NONE,\'ONECLICK\' => MODULE_PAYMENT_NOVALNET_PAYPAL_ONE_CLICK,\'ZEROAMOUNT\' => MODULE_PAYMENT_NOVALNET_PAYPAL_ZERO_AMOUNT,),\'MODULE_PAYMENT_NOVALNET_PAYPAL_SHOP_TYPE\'," . MODULE_PAYMENT_NOVALNET_PAYPAL_SHOP_TYPE . ",' ,'',now()),
        ('MODULE_PAYMENT_NOVALNET_PAYPAL_VISIBILITY_BY_AMOUNT', '', '6', '4','',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_PAYPAL_ENDCUSTOMER_INFO', '', '6', '5','',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_PAYPAL_SORT_ORDER', '0', '6', '6', '',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_PAYPAL_PENDING_ORDER_STATUS', '0',  '6', '7', 'xtc_cfg_pull_down_order_statuses(', 'xtc_get_order_status_name', now()),
        ('MODULE_PAYMENT_NOVALNET_PAYPAL_ORDER_STATUS', '0',  '6', '8', 'xtc_cfg_pull_down_order_statuses(', 'xtc_get_order_status_name', now()),
        ('MODULE_PAYMENT_NOVALNET_PAYPAL_PAYMENT_ZONE', '0', '6', '9', 'xtc_cfg_pull_down_zone_classes(', 'xtc_get_zone_class_title',now()),
        ('MODULE_PAYMENT_NOVALNET_PAYPAL_TRANS_REFERENCE1', '', '6', '10', '',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_PAYPAL_TRANS_REFERENCE2', '', '6', '11', '',  '', now())");
    }

    /**
     * Core Function : remove()
     *
     * Payment module uninstallation
     */
    function remove() {
		xtc_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", array_merge($this->keys(),array('MODULE_PAYMENT_NOVALNET_PAYPAL_ALIAS'))) . "')");
    }

    /**
     * Core Function : keys()
     *
     * Return keys to display in payment configuration (Backend)
     */
    function keys() {
        global $gx_version; // Get teh gambio version
        echo '<script src="' . DIR_WS_CATALOG . 'includes/external/novalnet/js/novalnet_paypal.js" type="text/javascript"></script>';
		$alias_menu = array();
		if($gx_version >= '3.1.1.0' ) {
			$alias_menu = array_merge($alias_menu,array('MODULE_PAYMENT_NOVALNET_PAYPAL_ALIAS'));
		}
        $return_key = array_merge(array(
            'MODULE_PAYMENT_NOVALNET_PAYPAL_ALLOWED',
            'MODULE_PAYMENT_NOVALNET_PAYPAL_ENABLE_MODULE',
            'MODULE_PAYMENT_NOVALNET_PAYPAL_TEST_MODE',
            'MODULE_PAYMENT_NOVALNET_PAYPAL_SHOP_TYPE',
            'MODULE_PAYMENT_NOVALNET_PAYPAL_VISIBILITY_BY_AMOUNT',
            'MODULE_PAYMENT_NOVALNET_PAYPAL_ENDCUSTOMER_INFO',
            'MODULE_PAYMENT_NOVALNET_PAYPAL_SORT_ORDER',
            'MODULE_PAYMENT_NOVALNET_PAYPAL_PENDING_ORDER_STATUS',
            'MODULE_PAYMENT_NOVALNET_PAYPAL_ORDER_STATUS',
            'MODULE_PAYMENT_NOVALNET_PAYPAL_PAYMENT_ZONE',
            'MODULE_PAYMENT_NOVALNET_PAYPAL_TRANS_REFERENCE1',
            'MODULE_PAYMENT_NOVALNET_PAYPAL_TRANS_REFERENCE2'
        ),$alias_menu);
        foreach ($return_key as $key => $value) {
            if( ($gx_version >= '3.1.1.0' && $value == 'MODULE_PAYMENT_NOVALNET_PAYPAL_ALIAS')) {
				unset($return_key[$key]);
                break;
			}
        }
        return $return_key;
    }

    /**
     * To get On hold value
     *
     * return boolean
     */
     function getOnholdValue($urlparam) {
		 if (trim(MODULE_PAYMENT_NOVALNET_MANUAL_CHECK_LIMIT) > 0 && $urlparam['amount'] >= trim(MODULE_PAYMENT_NOVALNET_MANUAL_CHECK_LIMIT)) { // If condition true then order proceed on-hold transaction
			    return true;
            }
	 }
}

