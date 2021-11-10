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
 * Script : novalnet_sepa.php
 *
 */
require_once(DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php');
class novalnet_sepa {
    var $code, $title, $public_title, $description, $sort_order, $enabled, $test_mode;

    /**
     * Core Function : Constructor()
     *
     */
    public function __construct() {
        global $order;
        $this->code         = 'novalnet_sepa';
        $this->title        = 'Novalnet ' . MODULE_PAYMENT_NOVALNET_SEPA_TEXT_TITLE;
        $this->public_title = MODULE_PAYMENT_NOVALNET_SEPA_PUBLIC_TITLE;
        $this->sort_order   = 0;
        if (strpos(MODULE_PAYMENT_INSTALLED, $this->code) !== false) {
			$this->sort_order   = defined('MODULE_PAYMENT_NOVALNET_SEPA_SORT_ORDER') && MODULE_PAYMENT_NOVALNET_SEPA_SORT_ORDER != '' ? MODULE_PAYMENT_NOVALNET_SEPA_SORT_ORDER : 0;
            $this->enabled      = (MODULE_PAYMENT_NOVALNET_SEPA_ENABLE_MODULE == 'True');
            $this->fraud_module = ((MODULE_PAYMENT_NOVALNET_SEPA_ENABLE_FRAUDMODULE == 'False') ? false : MODULE_PAYMENT_NOVALNET_SEPA_ENABLE_FRAUDMODULE);
            $this->test_mode    = (MODULE_PAYMENT_NOVALNET_SEPA_TEST_MODE == 'True');// To check the test mode
            NovalnetHelper::getLastSuccessPayment($this->code); // By default last transaction payment select in checkout page
        }
        if (is_object($order))
            $this->update_status();
    }

    /**
     * Core Function : update_status ()
     *
     * check if zone is allowed to see module
     */
    function update_status() {
        global $order;
        if ($_SESSION['shipping']['id'] == 'selfpickup_selfpickup') {
            $this->enabled = false;
        }
        if (($this->enabled == true) && ((int) MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_ZONE > 0)) {
            $check_flag  = false;
            $check_query = xtc_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_ZONE . "' and zone_country_id = '" . $order->delivery['country']['id'] . "' order by zone_id");
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
     * Core Function : selection ()
     *
     * Display checkout form in chekout payment page
     * @return array
     */
    function selection() {
			global $order;
		    if(!empty($order)) {
			// Get order amount
			$order_amount = NovalnetHelper::getPaymentAmount();
			// Get order amount set for fraud module validation
			if(empty($_SESSION['novalnet']['novalnet_sepa']['nn_order_amount'])) {
				$_SESSION['novalnet']['novalnet_sepa']['nn_order_amount'] = $order_amount;
			}
			// Payment hide in checkout page when condition was true
			if (NovalnetHelper::merchantValidate($this->code) || strpos(MODULE_PAYMENT_INSTALLED, 'novalnet_config') === false || !NovalnetHelper::hidePaymentVisibility($this->code, $order_amount)) {
				if (!empty($_SESSION['payment']) && $_SESSION['payment'] == $this->code) {
					unset($_SESSION['payment']);
				}
				return false;
			}
			// Payment hide in checkout page when condition was true if fraud module enable
			if (!empty($_SESSION[$this->code . '_payment_lock_nn']) && isset($_SESSION[$this->code . '_callback_max_time_nn']) && $_SESSION[$this->code . '_callback_max_time_nn'] > time()) {
				return false;
			}
			// Unset the novalnet session variable
			if (isset($_SESSION['payment']) && $_SESSION['payment'] != $this->code && isset($_SESSION['novalnet'][$this->code])) {
				unset($_SESSION['novalnet'][$this->code]);
			}
			// Unset the novalnet tid session variable
			if ((!empty($_SESSION['payment']) && $_SESSION['novalnet'][$this->code]['tid'] != '' && $_SESSION['payment'] != $this->code)) {
				unset($_SESSION['novalnet'][$this->code]['tid']);
			}
			// Unset the fraud module session variable
			if ($_SESSION['novalnet'][$this->code]['tid'] != '' && !empty($_SESSION[$this->code . '_payment_lock_nn']) && isset($_SESSION[$this->code . '_callback_max_time_nn']) && $_SESSION[$this->code . '_callback_max_time_nn'] < time()) {
				unset($_SESSION[$this->code . '_callback_max_time_nn'], $_SESSION[$this->code . '_payment_lock_nn'], $_SESSION['novalnet'][$this->code]['tid']);
			}
			// Get order amount set for fraud module validation
			if(empty($_SESSION['novalnet']['novalnet_sepa']['nn_set_order_amount']) || (empty($_REQUEST['payment']) && !empty($_SESSION['novalnet']['novalnet_sepa']['nn_set_order_amount']) && $order_amount != $_SESSION['novalnet']['novalnet_sepa']['nn_order_amount'])) {
				$_SESSION['novalnet']['novalnet_sepa']['nn_set_order_amount'] = true;
				$_SESSION['novalnet']['novalnet_sepa']['nn_order_amount'] = $order_amount;
			}
			$gurantee_error = $this->proceedToGuranteePayment($order_amount);
			//To check the basic fraud module requirements
			$this->fraud_module_status = NovalnetHelper::setFraudModuleStatus($this->code, $this->fraud_module,$_SESSION['novalnet']['novalnet_sepa']['nn_order_amount']);
			// To check the test mode in shop
			$test_mode                 = $this->test_mode == 1 ? MODULE_PAYMENT_NOVALNET_TEST_MODE_MSG : '';
			// Merchant can show the information for end customer in checkout page
			$endcustomerinfo           = trim(strip_tags(MODULE_PAYMENT_NOVALNET_SEPA_ENDCUSTOMER_INFO)) != '' ? trim(strip_tags(MODULE_PAYMENT_NOVALNET_SEPA_ENDCUSTOMER_INFO)) : '';
			$customer_name             = (!empty($order->customer['firstname']) ? $order->customer['firstname'] : '') . ' ' . (!empty($order->customer['lastname']) ? $order->customer['lastname'] : '');
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
			$selection  = array(
					'id' => $this->code,
					'module' => $title,
					'description' => MODULE_PAYMENT_NOVALNET_SEPA_DESC . '<br>' . $test_mode . '<br>' . $endcustomerinfo.'<br><script src="' . DIR_WS_CATALOG . 'ext/novalnet/js/novalnet_sepa.js' . '" type="text/javascript"></script><div class="novalnet_loader" id="nn_loader" style="display:none"></div> <link rel="stylesheet" type="text/css" href="' . DIR_WS_CATALOG . 'ext/novalnet/css/novalnet.css' . '"><span style="color:red">'.$gurantee_error.'</span>');
			if($shop_version > '3810') {
				if((!defined('MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY')) || MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY == 'True'){
					$selection['logo_url'] = xtc_href_link('images/icons/payment/novalnet_sepa.png', '', 'SSL', false, false, false, true, true);
				}else {
					$selection['logo_url'] = '';
					$selection['logo_alt'] = '';
				}
			}
			// Fraud module pin box showing in checkout page if fraud module enable
			if ($_SESSION['novalnet'][$this->code]['tid'] != '' && in_array($this->fraud_module,array('CALLBACK', 'SMS')) && $this->fraud_module_status && empty($_SESSION['novalnet'][$this->code]['guarantee'])) {
				$selection['fields'][] = array(
					'title' => MODULE_PAYMENT_NOVALNET_FRAUDMODULE_PIN_REQUEST_DESC,
					'field' => '<div>'.xtc_draw_input_field($this->code . '_fraud_pin', '', 'id="' . $this->code . '_' . strtolower($this->fraud_module) . 'pin"AUTOCOMPLETE="off"').'</div>'
				);
				$selection['fields'][] = array(
					'title' => '',
					'field' => xtc_draw_checkbox_field($this->code . '_new_pin', '1', false, 'id="' . $this->code . '_' . strtolower($this->fraud_module) . 'new_pin"') . MODULE_PAYMENT_NOVALNET_FRAUDMODULE_NEW_PIN,
				);
			} else {
				// To show the sepa form fields
				$data                                            = NovalnetHelper::getVendorDetails();
				NovalnetHelper::getAffDetails($data); // Appending affiliate parameters
				// Get the end customer serialize details from database for one click shopping
				$sqlQuerySet                                     = NovalnetHelper::getPaymentDetails($_SESSION['customer_id'], $this->code);
				$payment_details                                 = unserialize($sqlQuerySet['payment_details']);
				$form_show                                       = !empty($_SESSION['novalnet'][$this->code]['novalnet_sepachange_account']) ? '1' : '0';
				$clientip   = xtc_get_ip_address();
				$remoteIp   = NovalnetHelper::getIpAddress($clientip);
				$sepa_fields = xtc_draw_hidden_field('', $data['vendor'], 'id="nn_vendor"').
				xtc_draw_hidden_field('', $data['auth_code'], 'id="nn_auth_code"').
				xtc_draw_hidden_field('', $_SESSION['language_code'], 'id="nn_shop_lang"').
				xtc_draw_hidden_field('nn_sepa_hash','', 'id="nn_sepa_hash"').
				xtc_draw_hidden_field('', NovalnetHelper::getSepaRefillHash($this->code), 'id="nn_sepa_input_panhash"').
				xtc_draw_hidden_field('', '', 'id="nn_sepa_iban"').
				xtc_draw_hidden_field('', '', 'id="nn_sepa_bic"').
				xtc_draw_hidden_field('', $remoteIp, 'id="nn_remote_ip"').
				xtc_draw_hidden_field('novalnet_sepachange_account', $form_show, 'id="novalnet_sepachange_account"').
				xtc_draw_hidden_field('', MODULE_PAYMENT_NOVALNET_SEPA_SELECT_COUNTRY, 'id="nn_sepa_country"').
				xtc_draw_hidden_field('nn_sepa_uniqueid', NovalnetHelper::uniqueRandomString(), 'id="nn_sepa_uniqueid"').
				xtc_draw_hidden_field('', DIR_WS_CATALOG, 'id="nn_root_sepa_catalog"').
				xtc_draw_hidden_field('', MODULE_PAYMENT_NOVALNET_SEPA_MANDATE_CONFIRM_ERROR, 'id="nn_lang_mandate_confirm"').
				xtc_draw_hidden_field('', MODULE_PAYMENT_NOVALNET_VALID_MERCHANT_CREDENTIALS_ERROR, 'id="nn_lang_valid_merchant_credentials"').
				xtc_draw_hidden_field('', MODULE_PAYMENT_NOVALNET_VALID_ACCOUNT_CREDENTIALS_ERROR, 'id="nn_lang_valid_account_details"').
				xtc_draw_hidden_field('', MODULE_PAYMENT_NOVALNET_VALID_ACCOUNT_CREDENTIALS_ERROR, 'id="nn_lang_valid_account_details"').
				xtc_draw_hidden_field('', MODULE_PAYMENT_NOVALNET_SEPA_SHOP_TYPE, 'id="nn_sepa_shopping_type"');
				$oneclick  = (MODULE_PAYMENT_NOVALNET_SEPA_SHOP_TYPE == 'ONECLICK' && !empty($payment_details)) ? '<nobr><span id ="novalnet_sepa_new_acc" style="color:blue"><u><b>' . MODULE_PAYMENT_NOVALNET_SEPA_NEW_ACCOUNT . '</b></u></span></nobr>' : '<input type="hidden" id="payment_ref_details" value=""/>';
				// To show the one click shop fields
				if(MODULE_PAYMENT_NOVALNET_SEPA_SHOP_TYPE == 'ONECLICK') {
					$selection['fields'][] = array('title' =>$oneclick,
												'field' => '');
					$selection['fields'][] = array(
								'title' => '<div class="nn_sepa_ref_details" style="display:none">' . MODULE_PAYMENT_NOVALNET_ACCOUNT_HOLDER . '</div>',
								'field' => '<div class="nn_sepa_ref_details" style="margin: 1% 0% 0% 0%;display:none;">' . $payment_details['bankaccount_holder'] . '</div>'
							);
					$selection['fields'][] = array(
								'title' => '<div class="nn_sepa_ref_details" style="display:none">' . MODULE_PAYMENT_NOVALNET_ACCOUNT_OR_IBAN . '</div>',
								'field' => '<div class="nn_sepa_ref_details" style="margin: 1% 0% 0% 0%;display:none;">' . $payment_details['iban'] . '<input type="hidden" id="nn_payment_ref_tid_sepa" name="nn_payment_ref_tid_sepa" value="' . $payment_details['tid'] . '"/></div>'
							);
					if($payment_details['bic'] != '123456' ) {							
						$selection['fields'][] = array(
									'title' => '<div class="nn_sepa_ref_details" style="display:none">' . MODULE_PAYMENT_NOVALNET_BANKCODE_OR_BIC . '</div>',
									'field' => '<div class="nn_sepa_ref_details" style="margin: 1% 0% 0% 0%;display:none;">' . $payment_details['bic'] . '</div>'
								);
					}
				}
				// To show the Normal sepa form fields
				$selection['fields'][] = array(
							'title' => MODULE_PAYMENT_NOVALNET_ACCOUNT_HOLDER,
							'field' => '<div class="nn_sepa_acc" style="display:block">' . xtc_draw_input_field($this->code . '_account_holder', $customer_name, 'id="' . $this->code . '_account_holder" class="novalnet_sepa_account_holder" AUTOCOMPLETE="off" placeholder="testere" onkeypress="return account_holder_validate(event, true);"') . '</div></nobr>'
				);
				$selection['fields'][] = array(
							'title' => MODULE_PAYMENT_NOVALNET_BANK_COUNTRY,
							'field' => '<div class="nn_sepa_acc" style="display:block">' . xtc_draw_pull_down_menu($this->code . '_bank_country', NovalnetHelper::sepaBankCountry(), $order->billing['country']['iso_code_2'], 'id="' . $this->code . '_bank_country" style="width:100%;"') . '</div>'
				);
				$selection['fields'][] =  array(
							'title' => MODULE_PAYMENT_NOVALNET_ACCOUNT_OR_IBAN,
							'field' => '<div class="nn_sepa_acc" style="display:block">' . xtc_draw_input_field($this->code . '_iban', '', 'id="' . $this->code . '_iban" AUTOCOMPLETE="off" onkeypress="return ibanbic_validate(event, false);"') . '<span id="novalnet_sepa_iban_span"></span>' . '</div>'
				);
				$selection['fields'][] = array(
							'title' => MODULE_PAYMENT_NOVALNET_BANKCODE_OR_BIC,
							'field' => '<div class="nn_sepa_acc" style="display:block">' . xtc_draw_input_field($this->code . '_bic', '', 'id="' . $this->code . '_bic" AUTOCOMPLETE="off" onkeypress="return ibanbic_validate(event, false);"') . '<span id="novalnet_sepa_bic_span"></span>' . '</div><input type="hidden" id="nn_lang_new_account" value="' . MODULE_PAYMENT_NOVALNET_SEPA_NEW_ACCOUNT . '"/><input type="hidden" id="nn_lang_given_account" value="' . MODULE_PAYMENT_NOVALNET_SEPA_GIVEN_ACCOUNT . '"/>'
				);
				// To show the fraud module fields
				if ($_SESSION['novalnet'][$this->code]['tid'] == '' && in_array($this->fraud_module,array('CALLBACK', 'SMS')) && $this->fraud_module_status && empty($_SESSION['novalnet'][$this->code]['guarantee'])) {
					if ($this->fraud_module == 'CALLBACK') {
						$selection['fields'][] = array(
								'title' => MODULE_PAYMENT_NOVALNET_FRAUDMODULE_CALLBACK_INPUT_TITLE,
								'field' => '<div class="nn_sepa_acc">' . xtc_draw_input_field($this->code . '_fraud_tel', $order->customer['telephone'], 'id="' . $this->code . '_' .strtolower($this->fraud_module).'" AUTOCOMPLETE=off'). '</div>'
						);
					} else {
						$selection['fields'][] = array(
								'title' => MODULE_PAYMENT_NOVALNET_FRAUDMODULE_SMS_INPUT_TITLE,
								'field' => "<div class='nn_sepa_acc'>".xtc_draw_input_field($this->code . "_fraud_mobile", '', "id=" . $this->code . "_" .strtolower($this->fraud_module)."pin"." AUTOCOMPLETE=off")."</div>"
						);
					}
				}
				// To show the gurantee payment field
				if (!empty($_SESSION['novalnet'][$this->code]['guarantee'])) {
					$customer_id = (isset($_SESSION['customer_id'])) ? 'customers_id= "' . xtc_db_input($_SESSION['customer_id']) . '"' : 'customers_email_address= "' . xtc_db_input($order->customer['email_address']) . '"';
					$customer_dbvalue  = xtc_db_fetch_array(xtc_db_query("SELECT customers_dob FROM " . TABLE_CUSTOMERS . " WHERE " . $customer_id . " ORDER BY customers_id DESC"));
					$customer_dbvalue['customers_dob'] = !in_array($customer_dbvalue['customers_dob'] ,array('0000-00-00 00:00:00','1000-01-01 00:00:00')) ? date('Y-m-d', strtotime($customer_dbvalue['customers_dob'])) :'';
					$selection['fields'][] = array(
					    'title' => MODULE_PAYMENT_GUARANTEE_FIELD,
					    'field' => '<div>'.xtc_draw_input_field($this->code . '_dob', $customer_dbvalue['customers_dob'], 'id="' . $this->code . '_dob" AUTOCOMPLETE=off').'</div>
						<link rel="stylesheet" href="//code.jquery.com/ui/1.12.0/themes/base/jquery-ui.css">
						<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
						<script src="https://code.jquery.com/ui/1.12.0/jquery-ui.js"></script>
						<script type= text/javascript src="' . DIR_WS_CATALOG . 'ext/novalnet/js/datepicker-'.$_SESSION['language_code'].'.js"></script>
						<script type="text/javascript">
							var j = jQuery.noConflict();
							jQuery(document).ready(function() {
							j("#novalnet_sepa_dob").datepicker({dateFormat: "yy-mm-dd", changeMonth: true, changeYear: true, yearRange: "-100:+0",},j.datepicker.regional[ jQuery("#nn_shop_lang").val()]);
							j("#novalnet_sepa_dob").click(function(){
								j(".ui-datepicker").css("z-index","9999");
							});
						});
						</script><br/>'
					);
				}
				$selection['fields'][] = array(
							'title' => '<div></div>',
							'field' => "<div class='nn_sepa_acc' style='border:0;background:none;'>" . xtc_draw_checkbox_field($this->code . '_mandate_confirm', 1, false, 'id="' . $this->code . '_mandate_confirm"') . '<span>' . MODULE_PAYMENT_NOVALNET_SEPA_FORM_MANDATE_CONFIRM_TEXT . '</span></div>' . $sepa_fields.NovalnetHelper::enableJavascript()
				);
			}
			return $selection;
		    }
    }

    /**
     * Core Function : pre_confirmation_check ()
     *
     * Perform validations for post values
     * @return boolean
     */
    function pre_confirmation_check() {
		global $order;
		$post = $_REQUEST;
		$_SESSION['novalnet'][$this->code . '_newpin']                    = $post[$this->code . '_new_pin'];
        $_SESSION['novalnet'][$this->code]['hash']                        = $post['nn_sepa_hash'];
        $_SESSION['novalnet'][$this->code]['novalnet_sepachange_account'] = !empty($post['novalnet_sepachange_account']) ? $post['novalnet_sepachange_account'] : '0';
        $this->fraud_module_status = NovalnetHelper::setFraudModuleStatus($this->code, $this->fraud_module,$_SESSION['novalnet']['novalnet_sepa']['nn_order_amount']);
        // if javascript not enable in system then show the error message
        if (!empty($post['nn_js_enabled'])) {
			$payment_error_return = 'payment_error=' . $this->code . '&error_message=' . MODULE_PAYMENT_NOVALNET_NO_SCRIPT;
             xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
		}
		//To validate the fraud module pin field
		if ($this->fraud_module_status && !empty($_SESSION['novalnet'][$this->code]['tid']) && $_SESSION['novalnet'][$this->code . '_newpin'] != 1 && empty($_SESSION['novalnet'][$this->code]['guarantee'])) {
            NovalnetHelper::validateUserInputsOnCallback($this->code, $post, $this->fraud_module);
        } else if (empty($_SESSION['novalnet'][$this->code]['tid'])) { // To performing the first call process
			//To validate the sepa form and hash value
			if (MODULE_PAYMENT_NOVALNET_SEPA_SHOP_TYPE != 'ONECLICK' && ((!isset($post['novalnet_sepa_account_holder']) || trim($post['novalnet_sepa_account_holder']) == '') || (!isset($post['nn_sepa_hash']) || $post['nn_sepa_hash'] == '') || (!isset($post['nn_sepa_uniqueid']) || $post['nn_sepa_uniqueid'] == ''))) {
				$payment_error_return = 'payment_error=' . $this->code . '&error_message=' .MODULE_PAYMENT_NOVALNET_VALID_ACCOUNT_CREDENTIALS_ERROR;
                xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
            }
            // To validate the fraud module tel/sms fields
            if ($this->fraud_module_status && empty($_SESSION['novalnet'][$this->code]['guarantee'])) {
				$error_message = NovalnetHelper::validateCallbackFields($post, $this->fraud_module, $this->code);
                if ($error_message != '') {
                    $payment_error_return = 'payment_error=' . $this->code . '&error_message=' . trim($error_message);
                    xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
                }
            }
            $error_message= '';
            //To validate the gurantee payment field
            if (!empty($_SESSION['novalnet'][$this->code]['guarantee']) && !empty($_SESSION['novalnet'][$this->code]['guarantee_error'])) {
				$error_message = MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_ERROR;
			} elseif (!empty($_SESSION['novalnet'][$this->code]['guarantee'])) {
				$current_age = date('Y-m-d',strtotime($post['novalnet_sepa_dob']));
				if ($post['novalnet_sepa_dob'] == '') {
					$error_message = MODULE_PAYMENT_GUARANTEE_PAYMENT_DOB_EMPTY_ERROR_MESSAGE;
				} else if(!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",trim($post['novalnet_sepa_dob']))) {
					$error_message = MODULE_PAYMENT_GUARANTEE_PAYMENT_INVAILD_DOB_ERROR_MESSAGE;
				} else if (trim($post['novalnet_sepa_dob']) != '' ) {
					if(time() < strtotime('+18 years', strtotime($current_age)) && MODULE_PAYMENT_NOVALNET_SEPA_FORCE == 'False') {
						$error_message = MODULE_PAYMENT_GUARANTEE_PAYMENT_AGE_ERROR_MESSAGE;
					} else if (!NovalnetHelper::validateDuedate(trim($post['novalnet_sepa_dob']))) { // Validate dob field 
						$error_message = MODULE_PAYMENT_GUARANTEE_PAYMENT_DOB_ERROR_MESSAGE;
					}
				}
			}
			if($error_message != '' && MODULE_PAYMENT_NOVALNET_SEPA_FORCE == 'False'){
				unset($_SESSION['payment'],$_SESSION['novalnet'][$this->code]['guarantee']);
				xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code . '&error_message=' . $error_message, 'SSL', true, false));
			}
        }
        // To set the session variable for one click shopping process
        if (isset($post['nn_payment_ref_tid_sepa']) && $post['novalnet_sepachange_account'] == 0) {
            $_SESSION['novalnet'][$this->code]['nn_payment_ref_enable']   = true;
            $_SESSION['novalnet'][$this->code]['nn_payment_ref_tid_sepa'] = $post['nn_payment_ref_tid_sepa'];
            return true;
        }
    }

    /**
     * Core Function : confirmation ()
     *
     * Displays confirmation page
     */
    function confirmation() {
		global $order;
		$order_amount = NovalnetHelper::getPaymentAmount();
		// order amount stored in session
        $_SESSION['novalnet'][$this->code]['payment_amount'] = $order_amount ;
        $this->fraud_module_status = NovalnetHelper::setFraudModuleStatus($this->code, $this->fraud_module,$_SESSION['novalnet']['novalnet_sepa']['nn_order_amount']);
        // Validate fraud module process
        if ($this->fraud_module_status && !empty($_SESSION['novalnet'][$this->code]['tid']) && $_SESSION['novalnet'][$this->code . '_newpin'] != 1 && empty($_SESSION['novalnet'][$this->code]['guarantee'])) {
			NovalnetHelper::validateAmountOnCallback($this->code, $this->fraud_module);
        }

    }

    /**
     * Core Function : process_button ()
     *
     * Payments redirects from shop to payment site (Note : if the payment is redirect)
     */
    function process_button() {
        $post = $_REQUEST;
        // To generate the new pin for fraud module
        if (isset($post[$this->code . '_new_pin']) && $post[$this->code . '_new_pin'] == 1) { // Generate the new pin for fraud module
            $new_pin_response     = NovalnetHelper::doCallbackRequest('TRANSMIT_PIN_AGAIN', $this->code);
            $response             = simplexml_load_string($new_pin_response);
            $response             = json_decode(json_encode($response), true);
            $response_text        = isset($response['status_message']) ? $response['status_message'] : (isset($response['pin_status']['status_message']) ? $response['pin_status']['status_message'] : $response['status_text']);
            $payment_error_return = 'payment_error=' . $this->code . '&error_message=' . $response_text;
            xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
        } elseif (isset($_SESSION['novalnet'][$this->code]['payment_amount'])) { // Store the values in session
            $novalnet_order_details            = isset($_SESSION['novalnet'][$this->code]) ? $_SESSION['novalnet'][$this->code] : array();
            $_SESSION['novalnet'][$this->code] = array_merge($novalnet_order_details, $post, array(
                'payment_amount' => $_SESSION['novalnet'][$this->code]['payment_amount']
            ));
        } else { // Payments redirect to checkout page if amount invaild
            $payment_error_return = 'payment_error=' . $this->code . '&error_message=' . MODULE_PAYMENT_NOVALNET_AMOUNT_ERROR_MESSAGE;
            xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
        }
    }

    /**
     * Core Function : before_process ()
     *
     * Send params to Novalnet server (Note : if the payment uses curl request)
     */
    function before_process() {
        global $order;
        $order_amount              = NovalnetHelper::getPaymentAmount();
        $this->fraud_module_status = NovalnetHelper::setFraudModuleStatus($this->code, $this->fraud_module,$_SESSION['novalnet']['novalnet_sepa']['nn_order_amount']);
        $param_inputs              = array_merge((array) $order, $_SESSION['novalnet'][$this->code], array(
            'fraud_module' => $this->fraud_module,
            'fraud_module_status' => $this->fraud_module_status
        ));
        // Fraud module call request send to the novalnet server
        if (!empty($_SESSION['novalnet'][$this->code]['tid']) && $_SESSION['novalnet'][$this->code . '_newpin'] != 1) {
            $callback_response  = NovalnetHelper::doCallbackRequest('PIN_STATUS', $this->code);
            $callback_response  = simplexml_load_string($callback_response);
            $callback_response  = json_decode(json_encode($callback_response), true);
            $_SESSION['novalnet'][$this->code]['gateway_response']['tid_status'] = $callback_response['tid_status'];
            $callback_response_text = isset($callback_response['status_message']) ? $callback_response['status_message'] : (isset($callback_response['pin_status']['status_message']) ? $callback_response['pin_status']['status_message'] : $callback_response['status_text']);
            if ($callback_response['status'] != 100) {
                if ($callback_response['status'] == '0529006') {
                    $_SESSION[$this->code . '_payment_lock_nn']      = true;
                    $_SESSION[$this->code . '_callback_max_time_nn'] = time() + (30 * 60);
                }
                $payment_error_return = 'payment_error=' . $this->code . '&error_message=' . $callback_response_text;
                xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
            }
        } else {
			// Normal payment request send to the novalnet server
            $urlparam = NovalnetHelper::getCommonParms($param_inputs); // Perform real time payment transaction
			// Get the vendor configuration details
            $vendor_details = NovalnetHelper::getVendorDetails();
            // To process on hold product
            if (trim(MODULE_PAYMENT_NOVALNET_SEPA_MANUAL_CHECK_LIMIT) > 0 && $order_amount >= trim(MODULE_PAYMENT_NOVALNET_SEPA_MANUAL_CHECK_LIMIT)) { // If condition true then order proceed on-hold transaction
                $urlparam['on_hold'] = 1;
            }
            // To send the create payment ref param to novalnet server
            if(in_array( MODULE_PAYMENT_NOVALNET_SEPA_SHOP_TYPE, array( 'ONECLICK', 'ZEROAMOUNT'))) {
				$urlparam['create_payment_ref'] = 1;
			}
            // To process the zero amount booking and param send to the novalnet server
            if ((MODULE_PAYMENT_NOVALNET_SEPA_SHOP_TYPE == 'ZEROAMOUNT') && empty($_SESSION['novalnet'][$this->code]['guarantee'])) { // To process zero amount order booking
				if(!in_array($vendor_details['tariff_type'], array( 1, 3, 4 ))) {
					unset($urlparam['on_hold'],$vendor_details['tariff_type']);
					$urlparam['amount']                                    = 0;
					$_SESSION['novalnet'][$this->code]['serialize_data']  = serialize($urlparam);
					$_SESSION['novalnet'][$this->code]['reference_transaction'] = 1;
				}else {
					unset($urlparam['create_payment_ref']);
				}
            }
            $sepa_duedate              = ((trim(MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_DUE_DATE) >= 7) ? trim(MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_DUE_DATE) : 7);
            $urlparam['sepa_due_date'] = (date('Y-m-d', strtotime('+' . $sepa_duedate . ' days')));
			//The gurantee payment param send to the novalnet server
			if (!empty($_SESSION['novalnet'][$this->code]['guarantee']) && !empty($_SESSION['novalnet'][$this->code]['novalnet_sepa_dob']) && (time() > strtotime('+18 years', strtotime(date('Y-m-d',strtotime($_SESSION['novalnet'][$this->code]['novalnet_sepa_dob'])))) && NovalnetHelper::validateDuedate(trim($_SESSION['novalnet'][$this->code]['novalnet_sepa_dob'])))) { // To process guarantee payments 
					if (MODULE_PAYMENT_NOVALNET_SEPA_SHOP_TYPE == 'ZEROAMOUNT') {
						unset($urlparam['create_payment_ref']);
					}
					$urlparam['key']          = 40;
					$urlparam['birth_date']   = (date('Y-m-d', strtotime($_SESSION['novalnet'][$this->code]['novalnet_sepa_dob'])));
					$urlparam['payment_type'] = 'GUARANTEED_DIRECT_DEBIT_SEPA';
            }
            $fraud_module_active = false;
            //Fraud module param send to the novalnet server
            if ($this->fraud_module_status && empty($_SESSION['novalnet'][$this->code]['guarantee'])) { // Allow fraud module param if enabled
				$fraud_module_active = true;
                if ($this->fraud_module == 'CALLBACK') { // if fraud module pin by callback enabled
                    $urlparam['tel']             = trim($_SESSION['novalnet'][$this->code]['novalnet_sepa_fraud_tel']);
                    $urlparam['pin_by_callback'] = '1';
                } else { // if fraud module pin by sms enabled
                    $urlparam['mobile']     = trim($_SESSION['novalnet'][$this->code]['novalnet_sepa_fraud_mobile']);
                    $urlparam['pin_by_sms'] = '1';
                }
            }
            if (!empty($_SESSION['novalnet'][$this->code]['nn_payment_ref_tid_sepa']) && $_SESSION['novalnet'][$this->code]['novalnet_sepachange_account'] == '0'  && MODULE_PAYMENT_NOVALNET_SEPA_SHOP_TYPE == 'ONECLICK' ) { // To process payment ref
                $urlparam['payment_ref'] = $_SESSION['novalnet'][$this->code]['nn_payment_ref_tid_sepa'];
                $_SESSION['novalnet'][$this->code]['reference_transaction'] = 1;
                unset($_SESSION['novalnet'][$this->code]['nn_payment_ref_tid_sepa'],$urlparam['pin_by_callback'],$urlparam['pin_by_sms'],$fraud_module_active,$urlparam['create_payment_ref']);
            } else {
                $urlparam['iban_bic_confirmed']  = 1;
                $urlparam['bank_account_holder'] = $_SESSION['novalnet'][$this->code]['novalnet_sepa_account_holder'];
                $urlparam['sepa_hash']           = $_SESSION['novalnet'][$this->code]['hash'];
                $urlparam['sepa_unique_id']      = $_SESSION['novalnet'][$this->code]['nn_sepa_uniqueid'];
            }
            // Payment call request send	 to the novalnet server
            $response = NovalnetHelper::doPaymentCurlCall('https://payport.novalnet.de/paygate.jsp', $urlparam);
            parse_str($response, $data);
			// sepa form value set in session variable
			if(MODULE_PAYMENT_NOVALNET_SEPA_SHOP_TYPE == 'ONECLICK' && $urlparam['create_payment_ref'] == '1' && !isset($urlparam['payment_ref'])) {
				$_SESSION['novalnet'][$this->code]['serialize_data'] = serialize(array(
					'bankaccount_holder' => $data['bankaccount_holder'],
					'iban' 				 => $data['iban'],
					'bic'  				 => $data['bic'],
					'tid'  				 => $data['tid']
				));
			}
            // Form the novalnet transaction comments and transaction details are stored in session variable
            $form_comments  = NovalnetHelper::checkPaymentStatus($this->code, $data,$urlparam);
            // To process the fraud module on first call
            if ($fraud_module_active) {
				NovalnetHelper::gotoPaymentOnCallback($this->fraud_module, $this->fraud_module_status, $this->code);
			}
        }
        // Novalnet transaction details update the orders
        $order->info['comments'] = !empty($_SESSION['novalnet'][$this->code]['gateway_response']['novalnet_comments']) ? $_SESSION['novalnet'][$this->code]['gateway_response']['novalnet_comments'] : $form_comments['novalnet_comments'];
    }

    /**
     * Core Function : after_process ()
     *
     * Send params to Novalnet server (Note : if the payment uses curl request)
     */
    function after_process() {
        global $insert_id;
        // Update the order status in shop
        NovalnetHelper::updateOrderStatus($insert_id, $this->code);
        // Perform paygate second call for transaction confirmations / order_no update
        NovalnetHelper::doSecondCallProcess(array(
            'payment_type'  => $this->code,
            'order_no' => $insert_id
        ));
    }

    /**
     * Core Function : get_error ()
     *
     * Show validation / error message
     * @return array
     */
    function get_error() {
		// Get error message from shop
        $error_message = (isset($_GET['payment_message']) ? $_GET['payment_message'] : $_GET['error_message']);
        // return the Error message in front end
        return array(
            'title' => $this->code,
            'error' => $error_message
        );
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
     * Core Function : check ()
     *
     * Checks for payment installation status
     * @return boolean
     */
    function check() {
        if (!isset($this->_check)) {
            $check_query  = xtc_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_NOVALNET_SEPA_ALLOWED'");
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
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . "
        (configuration_key, configuration_value, configuration_group_id, sort_order,set_function, use_function, date_added)
        VALUES
		('MODULE_PAYMENT_NOVALNET_SEPA_ALLOWED', '', '6', '1', '', '', now()),
		('MODULE_PAYMENT_NOVALNET_SEPA_ALIAS', 'NN_SEPA', '6', '0', '', '', now()),
        ('MODULE_PAYMENT_NOVALNET_SEPA_ENABLE_MODULE','False', '6', '2', 'xtc_mod_select_option(array(\'True\' => MODULE_PAYMENT_NOVALNET_TRUE,\'False\' => MODULE_PAYMENT_NOVALNET_FALSE),\'MODULE_PAYMENT_NOVALNET_SEPA_ENABLE_MODULE\'," . MODULE_PAYMENT_NOVALNET_SEPA_ENABLE_MODULE . ",' , '',now()),
        ('MODULE_PAYMENT_NOVALNET_SEPA_TEST_MODE','False', '6', '3', 'xtc_mod_select_option(array(\'True\' => MODULE_PAYMENT_NOVALNET_TRUE,\'False\' => MODULE_PAYMENT_NOVALNET_FALSE),\'MODULE_PAYMENT_NOVALNET_SEPA_TEST_MODE\'," . MODULE_PAYMENT_NOVALNET_SEPA_TEST_MODE . ",' , '', now()),
        ('MODULE_PAYMENT_NOVALNET_SEPA_MANUAL_CHECK_LIMIT', '', '6', '7', '', '', now()),
        ('MODULE_PAYMENT_NOVALNET_SEPA_ENABLE_FRAUDMODULE','False', '6', '4', 'xtc_mod_select_option(array(\'False\' => MODULE_PAYMENT_NOVALNET_OPTION_NONE,\'CALLBACK\' => MODULE_PAYMENT_NOVALNET_FRAUD_OPTIONCALLBACK,\'SMS\' => MODULE_PAYMENT_NOVALNET_FRAUD_OPTIONSMS,),\'MODULE_PAYMENT_NOVALNET_SEPA_ENABLE_FRAUDMODULE\'," . MODULE_PAYMENT_NOVALNET_SEPA_ENABLE_FRAUDMODULE . ",' , '', now()),
        ('MODULE_PAYMENT_NOVALNET_SEPA_CALLBACK_LIMIT', '', '6', '5','',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_DUE_DATE', '', '6', '6','',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_SEPA_AUTO_FILL','False', '6', '7', 'xtc_mod_select_option(array(\'True\' => MODULE_PAYMENT_NOVALNET_TRUE,\'False\' => MODULE_PAYMENT_NOVALNET_FALSE),\'MODULE_PAYMENT_NOVALNET_SEPA_AUTO_FILL\'," . MODULE_PAYMENT_NOVALNET_SEPA_AUTO_FILL . ",' ,'',now()),
        ('MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_REFILL','False', '6', '8', 'xtc_mod_select_option(array(\'True\' => MODULE_PAYMENT_NOVALNET_TRUE,\'False\' => MODULE_PAYMENT_NOVALNET_FALSE),\'MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_REFILL\'," . MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_REFILL . ",' ,'',now()),
        ('MODULE_PAYMENT_NOVALNET_SEPA_SHOP_TYPE','False', '6', '9', 'xtc_mod_select_option(array(\'False\' => MODULE_PAYMENT_NOVALNET_OPTION_NONE,\'ONECLICK\' => MODULE_PAYMENT_NOVALNET_SEPA_ONE_CLICK,\'ZEROAMOUNT\' => MODULE_PAYMENT_NOVALNET_SEPA_ZERO_AMOUNT,),\'MODULE_PAYMENT_NOVALNET_SEPA_SHOP_TYPE\'," . MODULE_PAYMENT_NOVALNET_SEPA_SHOP_TYPE . ",' ,'',now()),
        ('MODULE_PAYMENT_NOVALNET_SEPA_VISIBILITY_BY_AMOUNT', '', '6', '10','',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_SEPA_ENDCUSTOMER_INFO', '', '6', '11','',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_SEPA_SORT_ORDER', '0', '6', '12', '',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_SEPA_ORDER_STATUS', '0',  '6', '13', 'xtc_cfg_pull_down_order_statuses(', 'xtc_get_order_status_name', now()),
        ('MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_ZONE', '0', '6', '14', 'xtc_cfg_pull_down_zone_classes(', 'xtc_get_zone_class_title',now()),
        ('MODULE_PAYMENT_NOVALNET_SEPA_TRANS_REFERENCE1', '', '6', '15', '',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_SEPA_TRANS_REFERENCE2', '', '6', '16', '',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE','False', '6', '17', 'xtc_mod_select_option(array(\'True\' => MODULE_PAYMENT_NOVALNET_TRUE,\'False\' => MODULE_PAYMENT_NOVALNET_FALSE),\'MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE\'," . MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE . ",' , '', now()),
        ('MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_MINIMUM_ORDER_AMOUNT', '', '6', '18','',  '', now()),
        ('MODULE_PAYMENT_NOVALNET_SEPA_PENDING_ORDER_STATUS', '0',  '6', '13', 'xtc_cfg_pull_down_order_statuses(', 'xtc_get_order_status_name', now()),
        ('MODULE_PAYMENT_NOVALNET_SEPA_FORCE','True', '6', '20', 'xtc_mod_select_option(array(\'True\' => MODULE_PAYMENT_NOVALNET_TRUE,\'False\' => MODULE_PAYMENT_NOVALNET_FALSE),\'MODULE_PAYMENT_NOVALNET_SEPA_FORCE\'," . MODULE_PAYMENT_NOVALNET_SEPA_FORCE . ",' , '', now())");
    }

    /**
     * Core Function : remove ()
     *
     * Payment module uninstallation
     */
    function remove() {
		xtc_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", array_merge($this->keys(),array('MODULE_PAYMENT_NOVALNET_SEPA_ALIAS'))) . "')");
    }

    /**
     * Core Function : keys ()
     *
     * Return keys to display in payment configuration (Backend)
     */
    function keys() {
		global $gx_version; // Get teh gambio version
		$alias_menu = array();
		if($gx_version >= '3.1.1.0' ) {
			$alias_menu = array_merge($alias_menu,array('MODULE_PAYMENT_NOVALNET_SEPA_ALIAS'));
		}
	    $return_key = array_merge(array(
			'MODULE_PAYMENT_NOVALNET_SEPA_ALLOWED',
            'MODULE_PAYMENT_NOVALNET_SEPA_ENABLE_MODULE',
            'MODULE_PAYMENT_NOVALNET_SEPA_TEST_MODE',
            'MODULE_PAYMENT_NOVALNET_SEPA_MANUAL_CHECK_LIMIT',
            'MODULE_PAYMENT_NOVALNET_SEPA_ENABLE_FRAUDMODULE',
            'MODULE_PAYMENT_NOVALNET_SEPA_CALLBACK_LIMIT',
            'MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_DUE_DATE',
            'MODULE_PAYMENT_NOVALNET_SEPA_AUTO_FILL',
            'MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_REFILL',
            'MODULE_PAYMENT_NOVALNET_SEPA_SHOP_TYPE',
            'MODULE_PAYMENT_NOVALNET_SEPA_VISIBILITY_BY_AMOUNT',
            'MODULE_PAYMENT_NOVALNET_SEPA_ENDCUSTOMER_INFO',
            'MODULE_PAYMENT_NOVALNET_SEPA_SORT_ORDER',
            'MODULE_PAYMENT_NOVALNET_SEPA_ORDER_STATUS',
            'MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_ZONE',
            'MODULE_PAYMENT_NOVALNET_SEPA_TRANS_REFERENCE1',
            'MODULE_PAYMENT_NOVALNET_SEPA_TRANS_REFERENCE2',
            'MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE',
            'MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_MINIMUM_ORDER_AMOUNT',
            'MODULE_PAYMENT_NOVALNET_SEPA_PENDING_ORDER_STATUS',
            'MODULE_PAYMENT_NOVALNET_SEPA_FORCE',
        ),$alias_menu);
        foreach ($return_key as $key => $value) {
            if( ($gx_version >= '3.1.1.0' && $value == 'MODULE_PAYMENT_NOVALNET_SEPA_ALIAS')) {
				unset($return_key[$key]);
                break;
			}
        }
        return $return_key;
    }
    
    /**
     * To Proceed and validate Guarantee payment basic requirements in checkout
     *
     * @param integer $orderamount
     * return string
     */
	function proceedToGuranteePayment($orderamount) {
		global $order;
		// Delivery address
		$delivery_address = array(
			'street_address' => $order->delivery['street_address'],
			'city'           => $order->delivery['city'],
			'postcode'       => $order->delivery['postcode'],
			'country'        => $order->delivery['country']['iso_code_2'],
		);
		// Billing address
		$billing_address = array(
			'street_address' => $order->billing['street_address'],
			'city'           => $order->billing['city'],
			'postcode'       => $order->billing['postcode'],
			'country'        => $order->billing['country']['iso_code_2'],
		);
		$minimum_amount_gurantee    = trim(MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_MINIMUM_ORDER_AMOUNT) != '' ? trim(MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_MINIMUM_ORDER_AMOUNT) : '2000';
		// Check guarantee payment
		if (MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE == 'True') {
			if ($orderamount >= $minimum_amount_gurantee  && in_array(strtoupper($order->billing['country']['iso_code_2']), array('DE', 'AT', 'CH')) && $order->info['currency'] == 'EUR' && $delivery_address === $billing_address) {
			    if (isset($_SESSION['novalnet'][$this->code]['guarantee_error'])) {
				unset($_SESSION['novalnet'][$this->code]['guarantee_error']);
			    }
			    $_SESSION['novalnet'][$this->code]['guarantee'] = TRUE;
			} else if (MODULE_PAYMENT_NOVALNET_SEPA_FORCE == 'True') {
				if (isset($_SESSION['novalnet'][$this->code]['guarantee'])) {
				    unset($_SESSION['novalnet'][$this->code]['guarantee']);
				}
			} else {
			    $_SESSION['novalnet'][$this->code]['guarantee'] = TRUE;
			    $_SESSION['novalnet'][$this->code]['guarantee_error'] = TRUE;
			    return MODULE_PAYMENT_NOVALNET_SEPA_GUARANTEE_ERROR;
			}
		} else {
			if(isset($_SESSION['novalnet'][$this->code]['guarantee'])) {
			    unset($_SESSION['novalnet'][$this->code]['guarantee']);
			}
		}
	}
}

