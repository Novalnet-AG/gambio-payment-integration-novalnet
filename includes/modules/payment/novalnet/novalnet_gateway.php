<?php
/**
 * Novalnet payment module
 *
 * This script contains the parent functions for all the payments
 *
 * @author    Novalnet AG
 * @copyright Copyright (c) Novalnet
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link      https://www.novalnet.de
 *
 * Script: novalnet_gateway.php
 */

defined('GM_HTTP_SERVER') or define('GM_HTTP_SERVER', HTTP_SERVER);
require_once DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php';
require_once DIR_FS_CATALOG . 'ext/novalnet/NovalnetValidator.class.php';
require_once DIR_FS_CATALOG . 'ext/novalnet/NovalnetPaymentRequest.class.php';

class novalnet_gateway
{
    public $code;
    public $title;
    public $description;
    public $enabled;
    public $tmpOrders = false;
    public $sort_order;
    public $info;
    public $helper;
    public $validator;
    public $requestFormatter;
    public $test_mode;
    public $payment_config;

    public function __construct()
    {
        global $order;
        !empty($this->code) or $this->code = 'novalnet';
        $this->helper = new NovalnetHelper();
        $this->validator = new NovalnetValidator();
        $this->requestFormatter = new NovalnetPaymentRequest();
        $constants  = ['title' => 'TEXT_TITLE','description' => 'TEXT_TITLE', 'info' => 'ENDCUSTOMER_INFO', 'sort_order' => 'SORT_ORDER', 'enabled' => 'STATUS', 'test_mode' => 'TEST_MODE'];

        foreach ($constants as $property => $value) {
            $this->$property = $this->helper->getConstant($this->code, $value);
        }

        $this->enabled = $this->enabled === 'true' ? true : false;

        if (is_object($order)) {
            $this->update_status();
        }
    }

    /**
     * Core Function : update_status()
     *
     * check if zone is allowed to see module
     */
    public function update_status()
    {
        global $order;

        if (in_array($this->code, ['novalnet_cashpayment', 'novalnet_giropay', 'novalnet_instantbank'])) {
            return false;
        }

        if (($this->enabled == true) && ((int)constant('MODULE_PAYMENT_' . strtoupper($this->code) . '_PAYMENT_ZONE') > 0)) {
            $check_flag = false;
            $check_query = xtc_db_query("select zone_id from ".TABLE_ZONES_TO_GEO_ZONES." where geo_zone_id = '". constant('MODULE_PAYMENT_' . strtoupper($this->code) . '_PAYMENT_ZONE') ."' and zone_country_id = '".$order->delivery['country']['id']."' order by zone_id");
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
        return false;
    }

    /**
     * Core Function : javascript_validation()
     *
     * Javascript validation takes place
     *
     * @return boolean
     */
    public function javascript_validation()
    {
        return false;
    }

    /**
     * Core Function : pre_confirmation_check()
     *
     * Perform validations for post values
     */
    public function pre_confirmation_check()
    {
        global $order;
        unset($_SESSION['novalnet']);

        if (!in_array($this->code, $this->helper->getFormTypePayments()) && !in_array($this->code, ['novalnet_googlepay', 'novalnet_applepay'])) {
            return;
        }

        if (($this->code == 'novalnet_cc' && !empty($_REQUEST['nn_pan_hash']) && !empty($_REQUEST['nn_uniqueid']))
            || (in_array($this->code, ['novalnet_sepa', 'novalnet_guarantee_sepa', 'novalnet_instalment_sepa']) && !empty($_REQUEST[$this->code . '_iban']) && !empty(trim($_REQUEST[$this->code . '_holder'])))
            || ($this->code == 'novalnet_direct_debit_ach' && !empty($_REQUEST[$this->code . '_account_no']) && !empty($_REQUEST[$this->code . '_routing_no']) && !empty(trim($_REQUEST[$this->code . '_holder'])))
            || (in_array($this->code, ['novalnet_googlepay', 'novalnet_applepay']) && !empty($_REQUEST[$this->code . '_token']))
            || ($this->code == 'novalnet_mbway' && !empty($_REQUEST['novalnet_mbway_mobile_no']))
        ) {
            if ($this->code == 'novalnet_cc') {
                $_SESSION['novalnet']['nn_pan_hash'] = $_REQUEST['nn_pan_hash'];
                $_SESSION['novalnet']['nn_uniqueid'] = $_REQUEST['nn_uniqueid'];
                $_SESSION['novalnet']['nn_do_redirect'] = (bool) $_REQUEST['do_redirect'];
            } elseif (in_array($this->code, ['novalnet_sepa', 'novalnet_guarantee_sepa', 'novalnet_instalment_sepa'])) {
                $_SESSION['novalnet']['nn_holder'] = $_REQUEST[$this->code . '_holder'];
                $_SESSION['novalnet']['nn_iban'] = $_REQUEST[$this->code . '_iban'];
                $_SESSION['novalnet']['nn_bic'] = !empty($_REQUEST[$this->code . '_bic']) ? $_REQUEST[$this->code . '_bic'] : '';
            } elseif ($this->code == 'novalnet_direct_debit_ach') {
                $_SESSION['novalnet']['account_holder'] = $_REQUEST[$this->code . '_holder'];
                $_SESSION['novalnet']['account_no'] = $_REQUEST[$this->code . '_account_no'];
                $_SESSION['novalnet']['routing_no'] = $_REQUEST[$this->code . '_routing_no'];
            } elseif ($this->code == 'novalnet_googlepay' || $this->code == 'novalnet_applepay') {
                $_SESSION['novalnet']['wallet_token'] = $_REQUEST[$this->code . '_token'];
                $_SESSION['novalnet']['nn_do_redirect'] = ($this->code == 'novalnet_googlepay' && $_REQUEST[$this->code . '_do_redirect']  == 'true') ? true : false;
            } elseif ($this->code == 'novalnet_mbway') {
                $_SESSION['novalnet']['novalnet_mbway_mobile_no'] = $_REQUEST['novalnet_mbway_mobile_no'];
            }

            $create_token = defined('MODULE_PAYMENT_'.strtoupper($this->code).'_TOKENIZATION') ? constant('MODULE_PAYMENT_'.strtoupper($this->code).'_TOKENIZATION') == 'true' : '';

            if ($create_token && !empty($_REQUEST[$this->code. '_oneclick'])) {
                $_SESSION['novalnet']['novalnet_create_token'] = $_REQUEST[$this->code. '_oneclick'];
            }
        } elseif (!empty($_REQUEST[$this->code . '_token']) && $_REQUEST[$this->code . '_token'] != 'new') {
            $_SESSION['novalnet']['novalnet_payment_token'] = $_REQUEST[$this->code . '_token'];
        } elseif (!in_array($this->code, ['novalnet_guarantee_invoice', 'novalnet_instalment_invoice'])) {
            $payment_error_return = 'payment_error=' . $this->code . '&error=' . rawurlencode(MODULE_PAYMENT_NOVALNET_VALID_ACCOUNT_CREDENTIALS_ERROR);
            xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
        }

        if (in_array($this->code, ['novalnet_guarantee_invoice', 'novalnet_instalment_invoice', 'novalnet_guarantee_sepa', 'novalnet_instalment_sepa']) && !empty($_REQUEST[$this->code . '_dob'])) {
            // validate the dirth data
            $isValidBirthDate = $this->helper->validateBirthDate($_REQUEST[$this->code . '_dob']);
            if (!$isValidBirthDate) {
                $check_force_sepa = ($this->code == 'novalnet_guarantee_sepa' &&
                    defined('MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_FORCE') && MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_FORCE == 'true' &&
                    defined('MODULE_PAYMENT_NOVALNET_SEPA_STATUS') && MODULE_PAYMENT_NOVALNET_SEPA_STATUS == 'true');
                $check_force_invoice = ($this->code == 'novalnet_guarantee_invoice' &&
                    defined('MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_FORCE') && MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_FORCE == 'true' &&
                    defined('MODULE_PAYMENT_NOVALNET_INVOICE_STATUS') && MODULE_PAYMENT_NOVALNET_INVOICE_STATUS == 'true');

                if ($check_force_sepa || $check_force_invoice) {
                    if ($this->code === 'novalnet_guarantee_invoice') {
                        $this->code = 'novalnet_invoice';
                    } else {
                        $this->code = 'novalnet_sepa';
                    }
                    $order->info['payment_method'] = $order->info['payment_class'] = $_SESSION['payment'] = $this->code;
                    $GLOBALS['order'] = $order;
                    $_SESSION['novalnet']['force_guarantee'] = true;
                } else {
                    $error_msg =  'payment_error=' . rawurlencode($this->code) . '&error=' . rawurlencode(MODULE_PAYMENT_GUARANTEE_PAYMENT_AGE_ERROR_MESSAGE);
                    xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $error_msg, 'SSL', true, false));
                }
            } else {
                $_SESSION['novalnet']['nn_birth_date'] = date('Y-m-d', strtotime($_REQUEST[$this->code . '_dob']));
            }
        }

        if (in_array($this->code, ['novalnet_instalment_invoice', 'novalnet_instalment_sepa']) && !empty($_REQUEST[$this->code . '_cycles'])) {
            $_SESSION['novalnet']['duration'] = $_REQUEST[$this->code . '_cycles'];
        }
    }

    /**
     * Core Function : confirmation ()
     *
     * Displays confirmation page
     */
    public function confirmation()
    {
        global $order;
        if ($_SESSION['customers_status']['customers_status_show_price_tax'] === '0' && !isset($order->info['deduction'])) {
            $_SESSION['novalnet']['payment_amount'] = ($order->info['total'] + (round($order->info['tax'], 2)));
        } else {
            $_SESSION['novalnet']['payment_amount'] = $order->info['total'];
        }
    }

    /**
     * Core Function : process_button()
     *
     * Payments redirects from shop to payment site (Note : if the payment is redirect)
     *
     * @return boolean
     */
    public function process_button()
    {
        return false;
    }

    /**
     * Core Function : before_process()
     *
     * Send params to Novalnet server (Note: if the payment uses curl request)
     */
    public function before_process()
    {
        global $order;
        $post = $_REQUEST;

        if (isset($post['tid'])) {
            $order_id = !empty($_SESSION['novalnet']['tempOID']) ? $_SESSION['novalnet']['tempOID'] : (isset($_SESSION['tmp_oID']) ? $_SESSION['tmp_oID'] : null);
            $generatedHash = $this->requestFormatter->generateCheckSumToken($post);
            if ($post['checksum'] !== $generatedHash) {
                $this->helper->updateTempOrderFail($order_id, $post, $status_text);
                $hash_error = 'payment_error=' . $this->code . '&error=' . rawurlencode('While notifying some data has been changed. The hash check failed');
                xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $hash_error, 'SSL', true, false));
                return;
            }

            $response = $this->requestFormatter->retrieveDetails($post['tid']);

            if ($post['status'] === 'SUCCESS') {
                if ($response['result']['status'] === 'SUCCESS') {
                    $_SESSION['novalnet']['nn_response'] = $response;
                    $order->info['comments'] .= $this->helper->formCustomerComments($this->code, $response);
                    $this->helper->updateOrderStatus($order_id, $this->code, $order->info['comments'], $response);
                } else {
                    $this->helper->processTempOrderFail($this->code, $order_id, $response);
                }
            } else {
                $this->helper->processTempOrderFail($this->code, $order_id, $response);
            }
        } else {
            if (in_array($this->code, $this->helper->getRedirectPayments())) {
                return;
            }

            if (!empty($post['payment_name']) && !empty($post['server_response']) && in_array($post['payment_name'], ['novalnet_googlepay', 'novalnet_applepay'])) {
                $serverResponse = $this->helper->unserializeData($post['server_response'], true);
                if (! empty($serverResponse['transaction']) && ! empty($serverResponse['transaction']['amount'])) {
                    $_SESSION['novalnet']['payment_amount'] = $serverResponse['transaction']['amount'] / 100;
                }
            }

            if (!empty($_SESSION['novalnet']['nn_do_redirect'])) {
                $this->tmpOrders = true;
            } else {
                $response = $this->requestFormatter->getResponseParams($this->code);

                if ($response['result']['status'] === 'SUCCESS') {
                    $_SESSION['novalnet']['nn_response'] = $response;
                    $order->info['comments'] .= $this->helper->formCustomerComments($this->code, $response);
                    $order_id = !empty($_SESSION['novalnet']['tempOID']) ? $_SESSION['novalnet']['tempOID'] : (isset($_SESSION['tmp_oID']) ? $_SESSION['tmp_oID'] : null);
                    if ($this->code == 'novalnet_googlepay' && empty($order) && !empty($order_id)) {
                        $order = new order($order_id);
                    }
                } else {
                    $error = $this->helper->getStatusDesc($response);
                    $payment_error_return = 'payment_error=' . $this->code . '&error=' . rawurlencode($error);
                    if (in_array($this->code, ['novalnet_googlepay', 'novalnet_applepay']) && !empty($_SESSION['novalnet']['express_checkout'])) {
                        $_SESSION['novalnet']['error_message'] = $error;
                        return;
                    }
                    xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
                }
            }
        }
    }

    public function get_error()
    {
        if ($_GET['error']) {
            $error = [
                'title' => $this->code,
                'error' => stripslashes(urldecode($_GET['error']))
            ];
            return $error;
        }
    }

    /**
     * Core Function : after_process()
     *
     * Send params to Novalnet server (Note : if the payment uses curl request)
     */
    public function after_process()
    {
        global $order, $insert_id;
        if (!$this->tmpOrders && !empty($_SESSION['novalnet']['nn_response'])) {
            $order_id = !empty($insert_id) ? $insert_id : (!empty($_SESSION['novalnet']['tempOID']) ? $_SESSION['novalnet']['tempOID'] : $_SESSION['tmp_oID']);
            $response = $this->helper->sendTransactionUpdate($order_id);
            if (!empty($response['transaction']['invoice_ref'])) {
                $_SESSION['novalnet']['nn_response']['transaction']['invoice_ref'] = $response['transaction']['invoice_ref'];
            }

            if($this->code == 'novalnet_instalment_invoice' && in_array($_SESSION['novalnet']['nn_response']['transaction']['status'], ['CONFIRMED', 'ON_HOLD'])) {
                $order->info['comments'] = str_replace('###SHOPORDERNUMBER###', $order_id, $order->info['comments']);
            } else {
                $order->info['comments'] = $this->helper->formCustomerComments($this->code, $_SESSION['novalnet']['nn_response']);
            }

            $this->helper->updateOrderStatus($order_id, $this->code, $order->info['comments'], $_SESSION['novalnet']['nn_response']);
        }
        unset($_SESSION['novalnet']);
    }

    /**
     * Core Function : payment_action()
     *
     * Send params to Novalnet server (Note : if the payment uses curl request)
     */
    public function payment_action()
    {
        global $insert_id;

        if (!empty($_SESSION['novalnet']['txn_secret']) || !empty($_SESSION['novalnet']['tempOID'])) {
            unset($_SESSION['novalnet']['txn_secret'], $_SESSION['novalnet']['tempOID']);
        }

        $response = $this->requestFormatter->getResponseParams($this->code, $insert_id);

        if (!empty($response['result']) && $response['result']['status'] === 'SUCCESS' && !empty($response['result']['redirect_url'])) {
            $_SESSION['novalnet']['txn_secret'] = $response['transaction']['txn_secret'];
            $_SESSION['novalnet']['tempOID'] = $insert_id;

            if ($this->code === 'novalnet_googlepay' && !empty($_SESSION['novalnet']['express_checkout'])) {
                $_SESSION['novalnet']['nn_redirect_url'] = $response['result']['redirect_url'];
                return;
            }
            xtc_redirect($response['result']['redirect_url']);
        } else {
            if (in_array($this->code, ['novalnet_googlepay', 'novalnet_applepay']) && !empty($_SESSION['novalnet']['express_checkout'])) {
                $_SESSION['novalnet']['error_message'] = $this->helper->getStatusDesc($response);
                return;
            }
            $this->helper->processTempOrderFail($this->code, $insert_id, $response);
        }
    }

    /**
     * Core Function : check()
     *
     * @return boolean
     */
    public function check()
    {
        if (!isset($this->_check)) {
            $check_query  = xtc_db_query("SELECT `value` from `gx_configurations` where `key` = 'configuration/MODULE_PAYMENT_". strtoupper($this->code) . "_STATUS'");

            $this->_check = xtc_db_num_rows($check_query);
        }

        return $this->_check;
    }

    /**
     * Core Function : keys()
     *
     * @return array keys to display in payment configuration (Backend)
     */
    public function keys()
    {
        $keys  = [];

        $this->helper->includeAdminJS($this->code);
        $lang = (isset($_SESSION['language_code'])) ? strtoupper($_SESSION['language_code']) : 'DE';
        echo '<input type="hidden" name="lang_code" id="lang_code" value= "'.$lang.'" />';

        if (in_array($this->code, ['novalnet_instalment_sepa', 'novalnet_instalment_invoice', 'novalnet_applepay', 'novalnet_googlepay'])) {
            echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
              <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.4/js/select2.min.js"></script>
                <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.4/css/select2.min.css" rel="stylesheet">';
            echo '<style> ul.select2-selection__rendered{ height: 60px; overflow: scroll !important;} </style>';
            if (in_array($this->code, ['novalnet_instalment_sepa', 'novalnet_instalment_invoice'])) {
                $cycles_display = defined('MODULE_PAYMENT_'. strtoupper($this->code) . '_CYCLE') ? constant('MODULE_PAYMENT_'. strtoupper($this->code) . '_CYCLE') : '';
                echo '<input type="hidden" name="'.$this->code.'_cycle[]" id="'.$this->code.'_cycle" value= "'.$cycles_display.'" />';
            } elseif ($this->code == 'novalnet_applepay') {
                echo "<input type='hidden' value='".$this->getApplepayDisplay()."' id='nn_applepay_display'>";
                $button_display = defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_DISPLAY') != '' ? MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_DISPLAY : '';
                echo '<input type="hidden" name="nn_button_display_page[]" id="nn_applepay_button_display_page" value= "'.$button_display.'" />';
            } else {
                echo "<input type='hidden' value='".$this->getGooglepayDisplay()."' id='nn_googlepay_display'>";
                $button_display = defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_DISPLAY') ? MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_DISPLAY : '';
                echo '<input type="hidden" name="nn_button_display_page[]" id="nn_googlepay_button_display_page" value= "'.$button_display.'" />';
            }
        }

        if (! empty($this->payment_config)) {
            $ckeys = array_keys($this->payment_config);
            foreach ($ckeys as $k) {
                $keys[] = 'configuration/MODULE_PAYMENT_' . strtoupper($this->code) . '_' . $k;
            }
        }
        return $keys;
    }

    public function getApplepayDisplay()
    {
        return json_encode(
            [
            'cartpage'     => defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_DISPLAY_CARTPAGE') ? MODULE_PAYMENT_NOVALNET_APPLEPAY_DISPLAY_CARTPAGE : '',
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
            ]
        );
    }

    public function getGooglepayDisplay()
    {
        return json_encode(
            [
            'cartpage'     => defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_DISPLAY_CARTPAGE') ? MODULE_PAYMENT_NOVALNET_GOOGLEPAY_DISPLAY_CARTPAGE : '',
            'productpage'  => defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_DISPLAY_PRODUCTPAGE') ? MODULE_PAYMENT_NOVALNET_GOOGLEPAY_DISPLAY_PRODUCTPAGE : '',
            'checkoutpage' => defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_DISPLAY_CHECKOUTPAGE') ? MODULE_PAYMENT_NOVALNET_GOOGLEPAY_DISPLAY_CHECKOUTPAGE : '',
            'plain' => defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_PLAIN') ? MODULE_PAYMENT_NOVALNET_GOOGLEPAY_PLAIN : '',
            'buy' => defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUY') ? MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUY : '',
            'donate' => defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_DONATE') ? MODULE_PAYMENT_NOVALNET_GOOGLEPAY_DONATE : '',
            'book' => defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BOOK') ? MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BOOK : '',
            'checkout' => defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_CHECKOUT') ? MODULE_PAYMENT_NOVALNET_GOOGLEPAY_CHECKOUT : '',
            'order' => defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ORDER') ? MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ORDER : '',
            'subscribe' => defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_SUBSCRIBE') ? MODULE_PAYMENT_NOVALNET_GOOGLEPAY_SUBSCRIBE : '',
            'pay' => defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_PAY') ? MODULE_PAYMENT_NOVALNET_GOOGLEPAY_PAY : '',
            'placeholder_text' => defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_PAGES_TEXT') ? MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_PAGES_TEXT : '',
            ]
        );
    }

    public function install()
    {
        $sort_order = 1;
        if (! empty($this->payment_config)) {
            foreach ($this->payment_config as $key => $data) {
                $install_query = "insert into `gx_configurations` (`key`, `value`, `sort_order`, ";
                if (!empty($data['type'])) {
                    $install_query .= "`type`, ";
                }
                $install_query .= "`last_modified`) values ('configuration/MODULE_PAYMENT_" . strtoupper($this->code) . "_" . $key . "', '"
                                  . $data['value'] . "', '" . $sort_order . "', ";

                if (!empty($data['type'])) {
                    $install_query .= "'" . addslashes($data['type']) . "', ";
                }

                $install_query .= "now())";
                xtc_db_query($install_query);
                $sort_order++;
            }
        }
    }

    /**
     * Core Function : remove()
     *
     * Payment module uninstallation
     */
    public function remove()
    {
        xtc_db_query("delete from `gx_configurations` where `key` in ('" . implode("', '", $this->keys()) . "')");
    }

    public function googlepayProduct_page_button()
    {
        return false;
    }

    public function applepayProduct_page_button()
    {
        return false;
    }

    public function googlepayCart_page_button()
    {
        return false;
    }

    public function applepayCart_page_button()
    {
        return false;
    }

}

MainFactory::load_origin_class('novalnet_gateway');
