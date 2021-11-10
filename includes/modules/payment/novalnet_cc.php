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
 * Script : novalnet_cc.php
 *
 */
require_once(DIR_FS_CATALOG . 'includes/external/novalnet/NovalnetHelper.class.php');
class novalnet_cc {
    var $code, $title, $public_title, $sort_order, $description, $form_action_url, $enabled, $test_mode;

    /**
     * Core Function : Constructor()
     *
     */
    function novalnet_cc() {
        global $order;
        $post               = $_REQUEST;
        $this->code         = 'novalnet_cc';
        $this->title        = MODULE_PAYMENT_NOVALNET_CC_TEXT_TITLE;
        $this->public_title = MODULE_PAYMENT_NOVALNET_CC_PUBLIC_TITLE;
        $this->sort_order   = 0;
        if (strpos(MODULE_PAYMENT_INSTALLED, $this->code) !== false) { // Allow if payment enable only
			$this->enabled   = (MODULE_PAYMENT_NOVALNET_CC_ENABLE_MODULE == 'True');
			if (MODULE_PAYMENT_NOVALNET_CC_3D_SECURE == 'True'  && (empty($post['novalnet_ccchange_account']))) {
				$this->form_action_url =  'https://payport.novalnet.de/pci_payport';
			}
			$this->sort_order   = defined('MODULE_PAYMENT_NOVALNET_CC_SORT_ORDER') && MODULE_PAYMENT_NOVALNET_CC_SORT_ORDER != '' ? MODULE_PAYMENT_NOVALNET_CC_SORT_ORDER : 0;
			$this->test_mode = (MODULE_PAYMENT_NOVALNET_CC_TEST_MODE == 'True'); // To check the test mode
			NovalnetHelper::getLastSuccessPayment($this->code); // By default last transaction payment select in checkout page
        }
        if (is_object($order))
            $this->update_status();
    }

    /**
     * check if zone is allowed to see module
     *
     * Core Function : update_status()
     */
    function update_status() {
        global $order;
        if ($_SESSION['shipping']['id'] == 'selfpickup_selfpickup') {
            $this->enabled = false;
        }
        if (($this->enabled == true) && ((int) MODULE_PAYMENT_NOVALNET_CC_PAYMENT_ZONE > 0)) {
            $check_flag  = false;
            $check_query = xtc_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_NOVALNET_CC_PAYMENT_ZONE . "' and zone_country_id = '" . $order->delivery['country']['id'] . "' order by zone_id");
            while ($check = xtc_db_fetch_array($check_query)) {
                if ($check['zone_id'] < 1 || ($check['zone_id'] == $order->delivery['zone_id']) ) {
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
     * @return payment form
     */
    function selection()     {
		// Get ordet total amount
		$order_amount = NovalnetHelper::getPaymentAmount();
		// Payment hide in checkout page when condition was true
        if (NovalnetHelper::merchantValidate($this->code) || !NovalnetHelper::hidePaymentVisibility($this->code, $order_amount) || strpos(MODULE_PAYMENT_INSTALLED, 'novalnet_config') === false) { // Payment hide in checkout page
            if (!empty($_SESSION['payment']) && $_SESSION['payment'] == $this->code) {
                unset($_SESSION['payment']);
            }
            return false;
        }
        // Unset the novalnet session variable
        if (!empty($_SESSION['payment']) && isset($_SESSION['novalnet'][$this->code]['tid']) && $_SESSION['payment'] != $this->code) { // Unset tid
            unset($_SESSION['novalnet'][$this->code]['tid']);
        }
        // Get the end customer serialize details from database for one click shopping
        $payment_details = NovalnetHelper::getPaymentDetails($_SESSION['customer_id'], $this->code);
        $payment_details = unserialize($payment_details['payment_details']);
        // To check the test mode in shop
        $test_mode       = $this->test_mode == 1 ? MODULE_PAYMENT_NOVALNET_TEST_MODE_MSG : '';
        // Merchant can show the information for end customer in checkout page
        $endcustomerinfo = trim(strip_tags(MODULE_PAYMENT_NOVALNET_CC_ENDCUSTOMER_INFO)) != '' ? trim(strip_tags(MODULE_PAYMENT_NOVALNET_CC_ENDCUSTOMER_INFO)) : '';
        $this->description = (MODULE_PAYMENT_NOVALNET_CC_3D_SECURE == 'True') ? MODULE_PAYMENT_NOVALNET_CC_REDIRECTION_TEXT_DESCRIPTION :  MODULE_PAYMENT_NOVALNET_CC_TEXT_DESCRIPTION;
        // To process the normal iframe in checkout page
        if ( MODULE_PAYMENT_NOVALNET_CC_SHOP_TYPE != 'ONECLICK' || (MODULE_PAYMENT_NOVALNET_CC_SHOP_TYPE == 'ONECLICK' && empty($payment_details) || MODULE_PAYMENT_NOVALNET_CC_3D_SECURE == 'True')) { // Displaying iframe form type
			$selection = array('id'       => $this->code,
							'module'      => $this->public_title,
							'description' => $this->description .'<br>'. $test_mode . '<br>' . $endcustomerinfo, // Displaying notification message and payment description
							'fields'       => array( array('title'=> $this->getIframecontent().'<script src="' . DIR_WS_CATALOG . 'includes/external/novalnet/js/novalnet_cc.js' . '" type="text/javascript"></script><br>'.NovalnetHelper::enableJavascript())));
			return $selection;
        } else if (!empty($payment_details)) {
			$selection  = array(
                'id'          => $this->code,
                'module'      => $this->public_title,
                'description' => $this->description .'<br>'. $test_mode . '<br>' . $endcustomerinfo);
			// Display credit card form for One click shopping and iframe
			$form_show = isset($_SESSION['novalnet'][$this->code]['novalnet_ccchange_account']) ? $_SESSION['novalnet'][$this->code]['novalnet_ccchange_account'] : 1;
            $oneclick  = (MODULE_PAYMENT_NOVALNET_CC_SHOP_TYPE == 'ONECLICK' && !empty($payment_details)) ? '<nobr><span id ="novalnet_cc_new_acc" style="color:blue"><u><b>' . MODULE_PAYMENT_NOVALNET_CC_NEW_ACCOUNT . '</b></u></span></nobr>' : '';
            $selection['fields'][] = array('title' => $oneclick,
										'field' => '');
            $selection['fields'][] =   array( 'title' =>'<div class= nn_cc_ref_details>'. MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_TYPE.'</div>',
												  'field' => '<div class= nn_cc_ref_details>'. $payment_details['cc_card_type'].'</div>'
            );
            $selection['fields'][] = array(
                        'title' => '<div class= nn_cc_ref_details>'. MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_HOLDER.'</div>',
                        'field' =>'<div class= nn_cc_ref_details><nobr>'. $payment_details['cc_holder'].'</nobr></div>'
			);
            $selection['fields'][] = array( 'title' => '<div class= nn_cc_ref_details>'. MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_NO .'</div>',
												'field' => '<div class= nn_cc_ref_details>'.$payment_details['cc_no'] . '<input type="hidden" id="nn_payment_ref_tid_cc" name="nn_payment_ref_tid" value="' . $payment_details['tid'] . '"/><input type="hidden" name="novalnet_ccchange_account" id="novalnet_ccchange_account" value="' . $form_show . '"/></div>'
            );
            $selection['fields'][] = array( 'title' => '<div class= nn_cc_ref_details>'. MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_VALID_DATE.'</div>',
												'field' => '<div class= nn_cc_ref_details>'. $payment_details['cc_exp_month'] . ' / ' . $payment_details['cc_exp_year'] . '</div>'.xtc_draw_hidden_field('', DIR_WS_CATALOG, 'id="nn_root_cc_catalog"').'<script src="' . DIR_WS_CATALOG . 'includes/external/novalnet/js/novalnet_cc.js' . '" type="text/javascript"></script>',
            );
            $selection['fields'][] = array(  'title' => '',
												 'field' => '<div class="nn_cc_acc" style="display:none">
												 '.$this->getIframecontent().'
												 <input type="hidden" id="nn_lang_cc_new_account" value="' . MODULE_PAYMENT_NOVALNET_CC_NEW_ACCOUNT . '"/><input type="hidden" id="nn_lang_cc_given_account" value="' . MODULE_PAYMENT_NOVALNET_CC_GIVEN_ACCOUNT . '"/><input type="hidden" id="nn_cc_iframe_load" name="nn_cc_iframe_load" value="" /></div>'.'<br>'.NovalnetHelper::enableJavascript()
			);
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
        $post = $_REQUEST;
        // Get the payment type
        $_SESSION['novalnet'][$this->code]['novalnet_ccchange_account'] = (MODULE_PAYMENT_NOVALNET_CC_SHOP_TYPE == 'ONECLICK' && MODULE_PAYMENT_NOVALNET_CC_3D_SECURE == 'False' ) ? $post['novalnet_ccchange_account'] : '0';
        $_SESSION['novalnet'][$this->code]['nn_cc_pan_hash'] = !empty($post['nn_pan_hash']) ? $post['nn_pan_hash'] : '';
        $_SESSION['novalnet'][$this->code]['nn_cc_unique_id'] = !empty($post['nn_cc_uniqueid']) ? $post['nn_cc_uniqueid'] : '';
        // if javascript not enable in system then show the error message
		if (!empty($post['nn_js_enabled'])) {
			$payment_error_return = 'payment_error=' . $this->code . '&error_message=' . MODULE_PAYMENT_NOVALNET_NO_SCRIPT;
             xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
		}
		// To validate the pan hash and unique id
        if ($post['novalnet_ccchange_account'] != '1' && (empty($post['nn_pan_hash']) || empty($post['nn_cc_uniqueid']))) {
			$payment_error_return = 'payment_error=' . $this->code . '&error_message=' . MODULE_PAYMENT_NOVALNET_VALID_CC_DETAILS;
             xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
		}

		// Assign the reference transaction tid in session
        if (isset($post['nn_payment_ref_tid'])) {
			$_SESSION['novalnet'][$this->code]['nn_payment_ref_tid'] = $post['nn_payment_ref_tid'];
			// if cc_3d enable unset the reference transaction details
			if (MODULE_PAYMENT_NOVALNET_CC_3D_SECURE == 'True' || $post['novalnet_ccchange_account'] == 0) {
				unset($_SESSION['novalnet'][$this->code]['nn_payment_ref_tid']);
			}
        }
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
     * Payments redirects from shop to payment site (Note : if the payment is redirect)
     */
    function process_button() {
        global $order;
        // To process the CC_3D form
        if (MODULE_PAYMENT_NOVALNET_CC_3D_SECURE == 'True' && $_SESSION['novalnet'][$this->code]['novalnet_ccchange_account'] != '1') { // To process redirection method
            $param_inputs = array_merge((array) $order, $_SESSION['novalnet'][$this->code]);
            //To get novalnet common payment params
            $urlparam     = NovalnetHelper::getCommonParms($param_inputs);
            // To process the on hold process
            if (trim(MODULE_PAYMENT_NOVALNET_MANUAL_CHECK_LIMIT) > 0 && $urlparam['amount'] >= trim(MODULE_PAYMENT_NOVALNET_MANUAL_CHECK_LIMIT)) { // If condition true then order proceed on-hold transaction
                $urlparam['on_hold'] = 1;
            }
            // Get the vendor configuration details
            $vendor_details = NovalnetHelper::getVendorDetails($this->code);
            // To process the zero amount booking and send params to novalnet server
            if (!in_array($vendor_details['tariff_type'], array( 1, 3, 4 )) && (MODULE_PAYMENT_NOVALNET_CC_SHOP_TYPE == 'ZEROAMOUNT')) {
                unset($urlparam['on_hold'],$vendor_details['tariff_type']);
                $urlparam['create_payment_ref'] = 1;
                $urlparam['amount']             = 0;
                $_SESSION['novalnet'][$this->code]['serialize_data']  = serialize($urlparam);
                $_SESSION['novalnet'][$this->code]['reference_transaction'] = 1;
            }
            // To encode the vendor configuration and get the hash value
            NovalnetHelper::generateHashValue($urlparam, array('auth_code', 'product', 'tariff', 'amount', 'test_mode', 'uniqid'));
            $urlparam['cc_3d'] = 1;
            $urlparam['implementation']  = 'PHP_PCI';
            $urlparam['pan_hash']        = $_SESSION['novalnet'][$this->code]['nn_cc_pan_hash'];
			$urlparam['unique_id']       = $_SESSION['novalnet'][$this->code]['nn_cc_unique_id'];
            $urlparam['return_method']   = $urlparam['error_return_method'] = 'POST';
            $urlparam['return_url']      = $urlparam['error_return_url'] = xtc_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL');
            // Assign novalnet payment params in the hidden fields
            foreach ($urlparam as $keys => $values) {
                $process_button_string .= xtc_draw_hidden_field($keys, $values);
            }
            return $process_button_string;
        }
    }

    /**
     * Core Function : before_process()
     *
     * Send params to Novalnet server (Note : if the payment uses curl request)
     */
    function before_process() {
        global $order;
        $post         = $_REQUEST;
        $param_inputs = array_merge((array) $order, $_SESSION['novalnet'][$this->code]);
        // To process the CC_3D response validation and to update the transaction details in shop
        if (isset($post['tid'])) {
            // To validate the response
            $before_process_response = NovalnetHelper::validateRedirectResponse($this->code, $post);
            // Novalnet transaction details update the orders
            $order->info['comments'] = !empty($_SESSION['novalnet'][$this->code]['novalnet_comments']) ? $_SESSION['novalnet'][$this->code]['novalnet_comments'] : $before_process_response['novalnet_comments'];
        } else if (!isset($post['tid']) && $_SESSION['novalnet'][$this->code]['novalnet_ccchange_account'] != '1' && MODULE_PAYMENT_NOVALNET_CC_3D_SECURE != 'True' && empty($_SESSION['novalnet'][$this->code]['nn_payment_ref_tid'])) { // To process credit card iframe with curl call request
			//To get novalnet common payment params
            $urlparam = NovalnetHelper::getCommonParms($param_inputs, $this->test_mode);
            // To process the on hold product
            if (trim(MODULE_PAYMENT_NOVALNET_MANUAL_CHECK_LIMIT) > 0 && $urlparam['amount'] >= trim(MODULE_PAYMENT_NOVALNET_MANUAL_CHECK_LIMIT)) { // If condition true then order proceed on-hold transaction
                $urlparam['on_hold'] = 1;
            }
            // The create payment ref params send to novalnet server
            if(in_array( MODULE_PAYMENT_NOVALNET_CC_SHOP_TYPE, array( 'ONECLICK', 'ZEROAMOUNT'))) {
				$urlparam['create_payment_ref'] = 1;
			}
            // Get the vendor configuration details
            $vendor_details = NovalnetHelper::getVendorDetails($this->code);
            // To process the zero amount booking  and send the parms to novalnet server
            if (MODULE_PAYMENT_NOVALNET_CC_SHOP_TYPE == 'ZEROAMOUNT') {
				if($vendor_details['tariff_type'] == 2) {
					unset($vendor_details['tariff_type'],$urlparam['on_hold']);
					$urlparam['amount']                                   = 0;
					$_SESSION['novalnet'][$this->code]['serialize_data']  = serialize($urlparam);
					$_SESSION['novalnet'][$this->code]['reference_transaction'] = 1;
				}else {
					unset($urlparam['create_payment_ref']);
				}
            }
            $urlparam['nn_it']         = 'iframe';
			$urlparam['pan_hash']      = $_SESSION['novalnet'][$this->code]['nn_cc_pan_hash'];
			$urlparam['unique_id']     = $_SESSION['novalnet'][$this->code]['nn_cc_unique_id'];
			// Payment call request send to the novalnet server
			$response                  = NovalnetHelper::doPaymentCurlCall('https://payport.novalnet.de/paygate.jsp', $urlparam);
            parse_str($response, $payment_response);
            if ($payment_response['status'] == '100') { //Payment success
				NovalnetHelper::getPostValues($this->code, $urlparam, $payment_response);
            } else { // Payment failed
				unset($_SESSION['novalnet'][$this->code]['reference_transaction']);
                $stauts_failed = NovalnetHelper::getServerResponse($payment_response);
                $payment_error_return = 'payment_error=' . $this->code . '&error_message=' . utf8_decode($stauts_failed);
                xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
            }
        } else if (MODULE_PAYMENT_NOVALNET_CC_SHOP_TYPE == 'ONECLICK' && $_SESSION['novalnet'][$this->code]['novalnet_ccchange_account'] == '1' && !empty($_SESSION['novalnet'][$this->code]['nn_payment_ref_tid'])) { // To process the one click shopping with reference transaction
            $urlparam                = NovalnetHelper::getCommonParms($param_inputs); // Get the novalnet common payment params
            if (trim(MODULE_PAYMENT_NOVALNET_MANUAL_CHECK_LIMIT) > 0 && $urlparam['amount'] >= trim(MODULE_PAYMENT_NOVALNET_MANUAL_CHECK_LIMIT)) { // If condition true then order proceed on-hold transaction
                $urlparam['on_hold'] = 1;
            }
            $urlparam['payment_ref'] = $_SESSION['novalnet'][$this->code]['nn_payment_ref_tid'];
            $_SESSION['novalnet'][$this->code]['reference_transaction'] = 1;
            // Payment call request send to the novalnet server
            $response                = NovalnetHelper::doPaymentCurlCall('https://payport.novalnet.de/paygate.jsp', $urlparam);
            parse_str($response, $payment_response);
            if ($payment_response['status'] == '100') { // Payment success
				// To store the server response in session and form novalnet transaction details in novalnet table
				NovalnetHelper::getPostValues($this->code, $urlparam, $payment_response);
            } else { // Payment failed
				unset($_SESSION['novalnet'][$this->code]['reference_transaction']);
                $stauts_failed = NovalnetHelper::getServerResponse($payment_response);
                $payment_error_return = 'payment_error=' . $this->code . '&error_message=' . utf8_decode($stauts_failed);
                // Redirect to checkout page
                xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
            }
        }
    }

    /**
     * Core Function : after_process()
     *
     * Send params to Novalnet server (Note : if the payment uses curl request)
     * @param none
     * @return void
     */
    function after_process() {
        global $insert_id;
        // Update the order status in shop
        NovalnetHelper::updateOrderStatus($insert_id, $this->code); // Update the order status
        NovalnetHelper::doSecondCallProcess(array(
            'payment_type'  => $this->code,
            'order_no' => $insert_id
        )); // Perform paygate second call for transaction confirmations / order_no update
    }

    /**
     * Core Function : get_error()
     *
     * Show validation / error message
     * @return array
     */
    function get_error() {
		// Get error message from shop
        $error_message = (isset($_GET['payment_message']) ? $_GET['payment_message'] : $_GET['error_message']);
        // Show the Error message in front end
        return array(
			'title' => $this->code,
			'error' => $error_message
        );
    }

    /**
     *
     * Core Function : check()
     *
     * Checks for payment installation status
     * @return boolean
     */
    function check() {
        if (!isset($this->_check)) {
            $check_query  = xtc_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_NOVALNET_CC_ALLOWED'");
            $this->_check = xtc_db_num_rows($check_query);
        }
        return $this->_check;
    }

    /**
     *
     * Core Function : install()
     *
     * Payment module installation
     * @return boolean
     */
    function install() {
        $label = "font-weight:normal;font-family:Roboto,Arial,sans-serif;font-size:13px;line-height:1.42857";
		$input_field = "height:30px;border:1px solid #ccc;";
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . "
        (configuration_key, configuration_value, configuration_group_id, sort_order,set_function, use_function, date_added)
        VALUES
        ('MODULE_PAYMENT_NOVALNET_CC_ALIAS', 'NN_CC', '6', '0', '', '', now()),
        ('MODULE_PAYMENT_NOVALNET_CC_ALLOWED', '', '6', '1', '', '', now()),
        ('MODULE_PAYMENT_NOVALNET_CC_ENABLE_MODULE','False', '6', '2', 'xtc_mod_select_option(array(\'True\' => MODULE_PAYMENT_NOVALNET_TRUE,\'False\' => MODULE_PAYMENT_NOVALNET_FALSE),\'MODULE_PAYMENT_NOVALNET_CC_ENABLE_MODULE\'," . MODULE_PAYMENT_NOVALNET_CC_ENABLE_MODULE . ",' , '',now()),
        ('MODULE_PAYMENT_NOVALNET_CC_TEST_MODE','False', '6', '3', 'xtc_mod_select_option(array(\'True\' => MODULE_PAYMENT_NOVALNET_TRUE,\'False\' => MODULE_PAYMENT_NOVALNET_FALSE),\'MODULE_PAYMENT_NOVALNET_CC_TEST_MODE\'," . MODULE_PAYMENT_NOVALNET_CC_TEST_MODE . ",' , '', now()),
        ('MODULE_PAYMENT_NOVALNET_CC_3D_SECURE','False', '6', '4', 'xtc_mod_select_option(array(\'True\' => MODULE_PAYMENT_NOVALNET_TRUE,\'False\' => MODULE_PAYMENT_NOVALNET_FALSE,),\'MODULE_PAYMENT_NOVALNET_CC_3D_SECURE\'," . MODULE_PAYMENT_NOVALNET_CC_3D_SECURE . ",' ,'',now()),
        ('MODULE_PAYMENT_NOVALNET_CC_AMEX_ACCEPT','False', '6', '5', 'xtc_mod_select_option(array(\'True\' => MODULE_PAYMENT_NOVALNET_TRUE,\'False\' => MODULE_PAYMENT_NOVALNET_FALSE,),\'MODULE_PAYMENT_NOVALNET_CC_AMEX_ACCEPT\'," . MODULE_PAYMENT_NOVALNET_CC_AMEX_ACCEPT . ",' ,'',now()),
        ('MODULE_PAYMENT_NOVALNET_CC_MAESTRO_ACCEPT','False', '6', '6', 'xtc_mod_select_option(array(\'True\' => MODULE_PAYMENT_NOVALNET_TRUE,\'False\' => MODULE_PAYMENT_NOVALNET_FALSE,),\'MODULE_PAYMENT_NOVALNET_CC_MAESTRO_ACCEPT\'," . MODULE_PAYMENT_NOVALNET_CC_MAESTRO_ACCEPT . ",' ,'',now()),
        ('MODULE_PAYMENT_NOVALNET_CC_CARTASI_ACCEPT','False', '6', '7', 'xtc_mod_select_option(array(\'True\' => MODULE_PAYMENT_NOVALNET_TRUE,\'False\' => MODULE_PAYMENT_NOVALNET_FALSE,),\'MODULE_PAYMENT_NOVALNET_CC_CARTASI_ACCEPT\'," . MODULE_PAYMENT_NOVALNET_CC_CARTASI_ACCEPT . ",' ,'',now()),
        ('MODULE_PAYMENT_NOVALNET_CC_SHOP_TYPE','False', '6', '8', 'xtc_mod_select_option(array(\'False\' => MODULE_PAYMENT_NOVALNET_OPTION_NONE,\'ONECLICK\' => MODULE_PAYMENT_NOVALNET_CC_ONE_CLICK,\'ZEROAMOUNT\' => MODULE_PAYMENT_NOVALNET_CC_ZERO_AMOUNT,),\'MODULE_PAYMENT_NOVALNET_CC_SHOP_TYPE\'," . MODULE_PAYMENT_NOVALNET_CC_SHOP_TYPE . ",' ,'',now()),
        ('MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_HOLDER', '', '6', '10','',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_HOLDER_INPUT', '', '6', '10','',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_NUMBER', '', '6', '10','',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_NUMBER_INPUT', '', '6', '10','',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_EXPIRY_DATE', '', '6', '10','',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_EXPIRY_DATE_INPUT', '', '6', '10','',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_CVC', '', '6', '10','',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_CVC_INPUT', '', '6', '10','',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_CC_CSS_STANDARD_STYLE', '.$label.', '6', '10','',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_CC_CSS_STANDARD_STYLE_INPUT', '.$input_field.', '6', '10','',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_CC_CSS_TEXT', '.$label.', '6', '10','',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_CC_VISIBILITY_BY_AMOUNT', '', '6', '10','',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_CC_ENDCUSTOMER_INFO', '', '6', '11','',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_CC_SORT_ORDER', '0', '6', '12', '',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_CC_ORDER_STATUS', '0',  '6', '13', 'xtc_cfg_pull_down_order_statuses(', 'xtc_get_order_status_name', now()),
        ('MODULE_PAYMENT_NOVALNET_CC_PAYMENT_ZONE', '0', '6', '14', 'xtc_cfg_pull_down_zone_classes(', 'xtc_get_zone_class_title',now()),
        ('MODULE_PAYMENT_NOVALNET_CC_TRANS_REFERENCE1', '', '6', '15', '',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_CC_TRANS_REFERENCE2', '', '6', '16', '',  '', now())");
    }

   /**
     * Core Function : remove()
     *
     * Payment module uninstallation
     * @return boolean
     */
    function remove() {
		xtc_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", array_merge($this->keys(),array('MODULE_PAYMENT_NOVALNET_CC_ALIAS'))) . "')");
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
			$alias_menu = array_merge($alias_menu,array('MODULE_PAYMENT_NOVALNET_CC_ALIAS'));
		}
	    $return_key = array_merge(array(
			'MODULE_PAYMENT_NOVALNET_CC_ALLOWED',
            'MODULE_PAYMENT_NOVALNET_CC_ENABLE_MODULE',
            'MODULE_PAYMENT_NOVALNET_CC_TEST_MODE',
            'MODULE_PAYMENT_NOVALNET_CC_3D_SECURE',
            'MODULE_PAYMENT_NOVALNET_CC_AMEX_ACCEPT',
            'MODULE_PAYMENT_NOVALNET_CC_MAESTRO_ACCEPT',
            'MODULE_PAYMENT_NOVALNET_CC_CARTASI_ACCEPT',
            'MODULE_PAYMENT_NOVALNET_CC_SHOP_TYPE',
            'MODULE_PAYMENT_NOVALNET_CC_VISIBILITY_BY_AMOUNT',
            'MODULE_PAYMENT_NOVALNET_CC_ENDCUSTOMER_INFO',
            'MODULE_PAYMENT_NOVALNET_CC_SORT_ORDER',
            'MODULE_PAYMENT_NOVALNET_CC_ORDER_STATUS',
            'MODULE_PAYMENT_NOVALNET_CC_PAYMENT_ZONE',
            'MODULE_PAYMENT_NOVALNET_CC_TRANS_REFERENCE1',
            'MODULE_PAYMENT_NOVALNET_CC_TRANS_REFERENCE2',
			'MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_HOLDER',
            'MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_HOLDER_INPUT',
            'MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_NUMBER',
            'MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_NUMBER_INPUT',
            'MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_EXPIRY_DATE',
            'MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_EXPIRY_DATE_INPUT',
            'MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_CVC',
            'MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_CVC_INPUT',
            'MODULE_PAYMENT_NOVALNET_CC_CSS_STANDARD_STYLE',
            'MODULE_PAYMENT_NOVALNET_CC_CSS_STANDARD_STYLE_INPUT',
            'MODULE_PAYMENT_NOVALNET_CC_CSS_TEXT'
        ),$alias_menu);
         foreach ($return_key as $key => $value) {
            if( ($gx_version >= '3.1.1.0' && $value == 'MODULE_PAYMENT_NOVALNET_CC_ALIAS')) {
				unset($return_key[$key]);
                break;
			}
        }
         return $return_key;
    }

    /**
     * To get Iframe content in checkout page
     *
     */
    function getIframecontent() {
		// Get the client ip
        $clientip   = xtc_get_ip_address();
        $remoteIp   = NovalnetHelper::getIpAddress($clientip);
        // Get the server ip
        $serverIp   = NovalnetHelper::getIpAddress($_SERVER['SERVER_ADDR']);
        // To form encode merchant api, remote ip , server ip for generate iframe
		$api_key  = base64_encode( trim(MODULE_PAYMENT_NOVALNET_PUBLIC_KEY) . '&' . $remoteIp . '&' . $serverIp );
		// Get the language for load iframe
		$lang = ((isset($_SESSION['language'])) && $_SESSION['language'] == 'english') ? 'en' : 'de';
		$iframe_path = 'https://secure.novalnet.de/cc?signature='. $api_key . '&ln=' .$lang ;
		return '<iframe scrolling="off" id="nnIframe" style ="margin: 0% 0% 0% -2%;background-color: transparent;background-image:none;border:none;" width="481px" src="'.$iframe_path.'" onload="getIframeForm()" frameBorder="0"></iframe>'.
		xtc_draw_hidden_field('', DIR_WS_CATALOG, 'id="nn_root_cc_catalog"').
		xtc_draw_hidden_field('nn_pan_hash', '', 'id="nn_pan_hash"').
		xtc_draw_hidden_field('nn_cc_uniqueid', '', 'id="nn_cc_uniqueid"')
		.xtc_draw_hidden_field('', trim(MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_HOLDER), 'id="nn_css_card_holder"').xtc_draw_hidden_field('', trim(MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_HOLDER_INPUT), 'id="nn_css_holder_input"').	xtc_draw_hidden_field('', trim(MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_NUMBER), 'id="nn_css_card_no"').xtc_draw_hidden_field('', trim(MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_NUMBER_INPUT), 'id="nn_css_card_no_input"').xtc_draw_hidden_field('', trim(MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_EXPIRY_DATE), 'id="nn_css_expiry_date"').xtc_draw_hidden_field('', trim(MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_EXPIRY_DATE_INPUT), 'id="nn_css_expiry_date_input"').xtc_draw_hidden_field('', trim(MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_CVC), 'id="nn_css_cvc"').xtc_draw_hidden_field('', trim(MODULE_PAYMENT_NOVALNET_CC_CSS_CARD_CVC_INPUT), 'id="nn_css_cvc_input"').xtc_draw_hidden_field('', trim(MODULE_PAYMENT_NOVALNET_CC_CSS_STANDARD_STYLE), 'id="nn_css_standard"').xtc_draw_hidden_field('', trim(MODULE_PAYMENT_NOVALNET_CC_CSS_STANDARD_STYLE_INPUT), 'id="nn_css_standard_input"').
		xtc_draw_hidden_field('', trim(MODULE_PAYMENT_NOVALNET_CC_CSS_TEXT), 'id="nn_css_text"').xtc_draw_hidden_field('', trim(MODULE_PAYMENT_NOVALNET_CC_SHOP_TYPE), 'id="nn_form_mode"')	.xtc_draw_hidden_field('', trim(defined('MODULE_PAYMENT_NOVALNET_CC_IFRAME_CVC_HINT') ? MODULE_PAYMENT_NOVALNET_CC_IFRAME_CVC_HINT : '' ), 'id="nn_iframe_cvc_hint"').xtc_draw_hidden_field('', defined('MODULE_PAYMENT_NOVALNET_CC_IFRAME_ERROR') ? trim(MODULE_PAYMENT_NOVALNET_CC_IFRAME_ERROR) : '', 'id="nn_iframe_error"').xtc_draw_hidden_field('', defined('MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_HOLDER') ? trim(MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_HOLDER) : '', 'id="nn_iframe_holder_label"').xtc_draw_hidden_field('', defined('MODULE_PAYMENT_NOVALNET_CC_IFRAME_HOLDER_INPUT_TEXT')? trim(MODULE_PAYMENT_NOVALNET_CC_IFRAME_HOLDER_INPUT_TEXT) : '', 'id="nn_iframe_holder_input"').xtc_draw_hidden_field('', defined('MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_NO') ? trim(MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_NO) : '', 'id="nn_iframe_number_label"').xtc_draw_hidden_field('', defined('MODULE_PAYMENT_NOVALNET_CC_IFRAME_NUMBER_INPUT_TEXT') ? trim(MODULE_PAYMENT_NOVALNET_CC_IFRAME_NUMBER_INPUT_TEXT) : '', 'id="nn_iframe_number_input"').xtc_draw_hidden_field('', defined('MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_VALID_DATE') ? trim(MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_VALID_DATE): '', 'id="nn_iframe_expire_label"').xtc_draw_hidden_field('',defined('MODULE_PAYMENT_NOVALNET_CC_IFRAME_EXPIRYDATE_INPUT_TEXT') ? trim(MODULE_PAYMENT_NOVALNET_CC_IFRAME_EXPIRYDATE_INPUT_TEXT) : '', 'id="nn_iframe_expire_input"').xtc_draw_hidden_field('', defined('MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_CVC') ? trim(MODULE_PAYMENT_NOVALNET_CC_FORM_CARD_CVC) :'', 'id="nn_iframe_cvc_label"').xtc_draw_hidden_field('', defined('MODULE_PAYMENT_NOVALNET_CC_IFRAME_CVC_INPUT_TEXT') ? trim(MODULE_PAYMENT_NOVALNET_CC_IFRAME_CVC_INPUT_TEXT) : '', 'id="nn_iframe_cvc_input"');
	}
}

