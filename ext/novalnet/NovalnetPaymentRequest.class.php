<?php
/**
 * Novalnet payment module
 *
 * This script contains the helper function for all the payments
 *
 * @author    Novalnet AG
 * @copyright Copyright (c) Novalnet
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link      https://www.novalnet.de
 *
 * Script: NovalnetPaymentRequest.class.php
 */
require_once DIR_FS_INC . 'xtc_format_price_order.inc.php';
require_once DIR_FS_INC . 'xtc_validate_email.inc.php';
require_once DIR_FS_INC.'xtc_php_mail.inc.php';

require_once DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php';

class NovalnetPaymentRequest
{
    /**
     * @var NovalnetHelper
     */
    public $helper;

    /**
     * NovalnetHelper constructor
     */
    public function __construct()
    {
        $this->helper = new NovalnetHelper();
    }

    /*
     * Form server parameters
     *
     * @param string $code
     * @param string $order_no
     *
     * @return array
     */
    public function getResponseParams(string $code, string $order_no = '')
    {
        $data = [];

        $data['merchant']    = $this->getMerchantDetails();
        $data['customer']    = $this->getCustomerData($code);
        $data['transaction'] = $this->getTransactionDetails($code, $order_no);
        $data['custom']      = $this->getCustomDetails();

        if (in_array($code, array('novalnet_instalment_invoice','novalnet_instalment_sepa'))) {
            $data['instalment'] = $this->getInstallmentDetails();
        } elseif ($code == 'novalnet_paypal') {
            $this->paypalSheetDetails($data);
        }

        if (in_array($code, $this->helper->getZeroAmountPayments())) {
            $authorize = $this->helper->getConstant($code, 'AUTHENTICATE');
            if ($authorize == 'zero_amount' || ($code == 'novalnet_direct_debit_ach' && $authorize == 'true')) {
                $data['transaction']['amount'] = 0;
            }
        }

        $url = $this->helper->getActionEndpoint('payment');

        $authorize = $this->helper->getConstant($code, 'AUTHENTICATE');
        $manualCheckLimit = (int) $this->helper->getConstant($code, 'MANUAL_CHECK_LIMIT');

        if (!empty($authorize) && ($authorize == 'authorize' || $authorize == 'true') && $data['transaction']['amount'] >= $manualCheckLimit && $data['transaction']['amount'] > 0) {
            $url = $this->helper->getActionEndpoint('authorize');
        }

        if (!empty($_SESSION['novalnet']['force_guarantee'])) {
            if ($code == 'novalnet_guarantee_sepa') {
                $data['transaction']['payment_type'] = 'DIRECT_DEBIT_SEPA';
            } elseif ($code == 'novalnet_guarantee_invoice') {
                $data['transaction']['payment_type'] = 'INVOICE';
            }
        }

        return $this->helper->sendRequest($data, $url);
    }

    /*
     * Form merchant parameters
     *
     * @return array
     */
    public function getMerchantDetails()
    {
        return[
            'signature' => MODULE_PAYMENT_NOVALNET_SIGNATURE,
            'tariff'    => MODULE_PAYMENT_NOVALNET_TARIFF_ID
        ];
    }

    /*
     * Form customer parameters
     *
     * @param string $code
     *
     * @return array
     */
    public function getCustomerData(string $code)
    {
        global $order;
        $billing = $shipping = [];
        $order_billing    = $order->billing;
        $order_shipping   = $order->delivery;

        //Need to payment check only for wallet
        if (isset($_SESSION['received_data']) && !empty($_SESSION['received_data'])
            && in_array($code, ['novalnet_googlepay', 'novalnet_applepay'])
        ) {
            $billing      = $_SESSION['received_data']['order']['billing']['contact'];
            if (!empty($_SESSION['received_data']['order']['shipping'])) {
                $shipping     = $_SESSION['received_data']['order']['shipping']['contact'];
            }
            unset($_SESSION['received_data']);
        }

        $customerData = [
            'gender'      => !empty($order_billing['gender']) ? $order_billing['gender'] : 'u',
            'first_name'  => (!empty($billing) && !empty($billing['firstName'])) ? $billing['firstName'] : $order_billing['firstname'],
            'last_name'   => (!empty($billing) && !empty($billing['lastName'])) ? $billing['lastName'] : $order_billing['lastname'],
            'email'       => (!empty($billing) && !empty($billing['email'])) ? $billing['email'] :
                ((!empty($shipping) && !empty($shipping['email'])) ? $shipping['email'] : $order->customer['email_address']),
            'customer_ip' => $this->helper->getIPAddress(),
            'customer_no' => $order->customer['csID'] ? $order->customer['csID'] : $_SESSION['customer_id'],
            'tel'         => $order->customer['telephone'],
            'mobile'      => $order->customer['telephone'],
            'billing'     => [
                'street'            => (!empty($billing) && !empty($billing['addressLines'])) ? $billing['addressLines'] : $order_billing['street_address'],
                'city'              => (!empty($billing) && !empty($billing['locality'])) ? $billing['locality'] : $order_billing['city'],
                'zip'               => (!empty($billing) && !empty($billing['postalCode'])) ? $billing['postalCode'] : $order_billing['postcode'],
                'country_code'      => (!empty($billing) && !empty($billing['countryCode'])) ? $billing['countryCode'] :
                    ((!empty($order_billing['country']) && !empty($order_billing['country']['iso_code_2'])) ? $order_billing['country']['iso_code_2'] :
                    (!empty($order_billing['country_iso_code_2']) ? $order_billing['country_iso_code_2'] : null))
            ]
        ];

        if (!empty($shipping) || !empty($order_shipping)) {
            $customerData['shipping'] = [
                'street'        => (!empty($shipping) && !empty($shipping['addressLines'])) ? $shipping['addressLines'] : $order_shipping['street_address'],
                'city'          => (!empty($shipping) && !empty($shipping['locality'])) ? $shipping['locality'] : $order_shipping['city'],
                'zip'           => (!empty($shipping) && !empty($shipping['postalCode'])) ? $shipping['postalCode'] : $order_shipping['postcode'],
                'country_code'  => (!empty($shipping) && !empty($shipping['countryCode'])) ? $shipping['countryCode'] :
                        (!empty($order_shipping['country']) && !empty($order_shipping['country']['iso_code_2']) ? $order_shipping['country']['iso_code_2'] :
                        (!empty($order_shipping['country_iso_code_2']) ? $order_shipping['country_iso_code_2'] : null))
            ];
        }

        if (!empty($order_billing['company'])) {
            $customerData['billing']['company'] = $order_billing['company'];
        }

        if (!empty($order_shipping['company'])) {
            $customerData['shipping']['company'] = $order_shipping['company'];
        }

        if (!empty($_SESSION['novalnet']['nn_birth_date'])) {
            $customerData['birth_date'] = date('Y-m-d', strtotime($_SESSION['novalnet']['nn_birth_date']));
            unset($customerData['billing']['company']);
        }

        if ($code == 'novalnet_mbway' && ! empty($_SESSION['novalnet']['novalnet_mbway_mobile_no'])) {
            $customerData['customer']['mobile'] = $_SESSION['novalnet']['novalnet_mbway_mobile_no'];
        }

        $billing  = $customerData['billing'];
        $shipping = !empty($customerData['shipping']) ? $customerData['shipping'] : $customerData['billing'];

        if ($billing['street'] == $shipping['street']
            && $billing['city'] == $shipping['city']
            && $billing['zip'] == $shipping['zip']
            && $billing['country_code'] == $shipping['country_code']
        ) {
            unset($customerData['shipping']);
            $customerData['shipping']['same_as_billing'] = 1;
        }

        return array_filter($customerData);
    }

    /*
     * Form transaction details
     *
     * @param string $code
     * @param string $order_no
     *
     * @return array
     */
    public function getTransactionDetails($code, $order_no = '')
    {
        global $order, $gx_version;
        $due_date = (int) $this->helper->getConstant($code, 'DUE_DATE');
        $version = require DIR_FS_CATALOG . 'release_info.php';

        $language_code = $_SESSION['language_code'] ? $_SESSION['language_code'] : 'de';

        $transactionData = [
            'test_mode'      => ($this->helper->getConstant($code, 'TEST_MODE') == 'true') ? 1 : 0,
            'payment_type'   => $this->helper->getPaymentName($code),
            'amount'         => $this->helper->getOrderAmount(!empty($_SESSION['novalnet']['payment_amount']) ? $_SESSION['novalnet']['payment_amount'] : $order->info['total']),
            'currency'       => $order->info['currency'],
            'system_name'    => 'Gambio',
            'system_version' => $version . '-NN12.4.0',
            'system_url'     => (defined('ENABLE_SSL') ? (ENABLE_SSL == true ? HTTPS_SERVER : HTTP_SERVER . DIR_WS_CATALOG) : (HTTPS_CATALOG_SERVER . DIR_WS_CATALOG)),
            'system_ip'      => $_SERVER['SERVER_ADDR'],
            'hook_url'       => ((defined('ENABLE_SSL_CATALOG') && ENABLE_SSL_CATALOG === true) ? HTTPS_SERVER : HTTP_SERVER . DIR_WS_CATALOG) . 'callback/novalnet/callback.php?language=' . $language_code,
        ];

        if (!empty($order_no)) {
            $transactionData['order_no' ] = $order_no;
        }

        if (!empty($due_date) && is_numeric($due_date)) {
            $transactionData['due_date'] = $this->helper->getDueDate($due_date);
        }

        if (in_array($code, $this->helper->getRedirectPayments()) || (in_array($code, $this->helper->getEnforcePayments()) && !empty($_SESSION['novalnet']['nn_do_redirect']))) {
            $redirect_url = ((isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI'];
            $transactionData['return_url'] = $transactionData['error_return_url'] = $redirect_url;

            if (($code === 'novalnet_cc' && defined('MODULE_PAYMENT_NOVALNET_CC_3D_SECURE_FORCE') && constant('MODULE_PAYMENT_NOVALNET_CC_3D_SECURE_FORCE') == 'true') || ($code === 'novalnet_googlepay' && defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ENFORCE_3D_SECCURE') && constant('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ENFORCE_3D_SECCURE') == 'true')) {
                $transactionData['enforce_3d'] = 1;
            }
        }

        if (in_array($code, $this->helper->getFormTypePayments()) || $code === 'novalnet_applepay' || $code === 'novalnet_googlepay') {
            $this->getPaymentDetails($transactionData, $code);

            if (!empty($_SESSION['novalnet']['novalnet_create_token'])) {
                $transactionData['create_token'] = 1;
            }
        }

        return $transactionData;
    }

    /*
     * Form payment related details
     *
     * @param string $code
     * @param string $order_no
     *
     * @return array
     */
    public function getPaymentDetails(&$transactionData, $code)
    {
        $tokenization  = (bool) $this->helper->getConstant($code, 'TOKENIZATION');

        if ($tokenization && !empty($_SESSION['novalnet']['novalnet_payment_token']) && $_SESSION['novalnet']['novalnet_payment_token'] != 'new') {
            $transactionData['payment_data'] = ['token' => $_SESSION['novalnet']['novalnet_payment_token']];
        } elseif (!empty($_SESSION['novalnet'])) {
            if (in_array($code, array('novalnet_sepa', 'novalnet_guarantee_sepa', 'novalnet_instalment_sepa'))) {
                $transactionData['payment_data']['account_holder'] = $_SESSION['novalnet']['nn_holder'];
                $transactionData['payment_data']['iban'] = strtoupper($_SESSION['novalnet']['nn_iban']);
                if (!empty($_SESSION['novalnet']['nn_bic']) && preg_match("/(?:CH|MC|SM|GB)/", $transactionData['payment_data']['iban'])) {
                    $transactionData['payment_data']['bic'] = strtoupper($_SESSION['novalnet']['nn_bic']);
                }
            } elseif ($code === 'novalnet_cc' && !empty($_SESSION['novalnet']['nn_pan_hash']) && !empty($_SESSION['novalnet']['nn_uniqueid'])) {
                $transactionData['payment_data']['pan_hash']  = $_SESSION['novalnet']['nn_pan_hash'];
                $transactionData['payment_data']['unique_id'] = $_SESSION['novalnet']['nn_uniqueid'];
            } elseif (($code === 'novalnet_applepay' || $code === 'novalnet_googlepay') && !empty($_SESSION['novalnet']['wallet_token'])) {
                $transactionData['payment_data']['wallet_token'] = $_SESSION['novalnet']['wallet_token'];
                if (!empty($_SESSION['tmp_oID'])) {
                    $transactionData['order_no'] = $_SESSION['tmp_oID'];
                }
            } elseif ($code === 'novalnet_direct_debit_ach' && !empty($_SESSION['novalnet']['account_no']) && !empty($_SESSION['novalnet']['routing_no'])) {
                $transactionData['payment_data'] = [
                    'account_holder' => $_SESSION['novalnet']['account_holder'],
                    'account_number' => $_SESSION['novalnet']['account_no'],
                    'routing_number' => $_SESSION['novalnet']['routing_no']
                ];
            }
        }
    }

    /*
     * Form custom details
     *
     * @return array
     */
    public function getCustomDetails()
    {
        $customDetails =  [
            'lang'      => (isset($_SESSION['language_code'])) ? strtoupper($_SESSION['language_code']) : 'DE',
        ];

        if (!empty($_SESSION['tmp_oID'])) {
            $customDetails['input1'] = 'temporary_order_id';
            $customDetails['inputval1'] = $_SESSION['tmp_oID'];
        }

        return $customDetails;
    }

    /*
     * Form instalment details
     *
     *  @return array
     */
    public function getInstallmentDetails()
    {
        // Instalment payments params
        return [
            'interval' => '1m',
            'cycles'    => $_SESSION['novalnet']['duration'],
        ];
    }

    /**
     * Built paypal lineItems to show in paypal page.
     *
     * @param array $data
     */
    public function paypalSheetDetails(&$data)
    {
        global $order, $insert_id;

        $voucher_amount = xtc_db_fetch_array(xtc_db_query("select value from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . $insert_id . "' and class = 'ot_gv'"));

        $attributes = '';

        foreach ($order->products as $products) {
            if (isset($products['attributes'])) {
                foreach ($products['attributes'] as $value) {
                    $attributes .= $value['option'] . ':' . $value['value'] . ', ';
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

        if (!empty($voucher_amount)) {
            $amount = $voucher_amount['value'];

            $data['cart_info']['line_items'][] = array(
                'name'        => (defined('MODULE_PAYMENT_NOVALNET_GIFT_VOUCHER_LABEL') ? MODULE_PAYMENT_NOVALNET_GIFT_VOUCHER_LABEL : ''),
                'price'       => -(string)(round($amount, 2) * 100),
                'quantity'    => 1,
            );
        }


        if (!empty($order->info['deduction'])) {
            $data['cart_info']['line_items'][] = array(
                'name'        => (defined('MODULE_PAYMENT_NOVALNET_GIFT_DISCOUNT_LABEL') ? MODULE_PAYMENT_NOVALNET_GIFT_DISCOUNT_LABEL : ''),
                'price'       =>  -(string)(round($order->info['deduction'], 2) * 100),
                'quantity'    => 1,
            );
        }
        $data['cart_info']['items_tax_price'] = (string) (round((float) $order->info['tax'] * 100));
        $data['cart_info']['items_shipping_price'] = (string) (round((float) ($order->info['shipping_cost'] * $order->info['currency_value']) * 100));

        return $data;
    }

    /**
     * Generate Check Sum Token
     *
     * @param array $response
     *
     * @return string
     */
    public function generateCheckSumToken($response)
    {
        $generatedChecksum = '';
        if (!empty($response['tid']) && !empty($_SESSION['novalnet']['txn_secret']) && !empty($response['status'])) {
            $tokenString = $response['tid'] . $_SESSION['novalnet']['txn_secret'] . $response['status']. strrev(trim(MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY));
            $generatedChecksum = hash('sha256', $tokenString);
        }
        return $generatedChecksum;
    }

    /*
     * Transaction retrieve call
     *
     * @param string $tid
     *
     * @return array
     */
    public function retrieveDetails($tid)
    {
        $data = [
            'transaction' => [
                'tid'  => $tid,
            ],
            'custom' => [
                'lang' => (isset($_SESSION['language_code'])) ? strtoupper($_SESSION['language_code']) : 'DE',
            ]
        ];
        $result = $this->helper->sendRequest($data, $this->helper->getActionEndpoint('transaction_details'));
        return $result;
    }
}
