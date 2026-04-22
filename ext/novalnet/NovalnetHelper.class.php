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
 * Script: NovalnetHelper.class.php
 */
require_once DIR_FS_INC . 'xtc_format_price_order.inc.php';
require_once DIR_FS_INC . 'xtc_validate_email.inc.php';
require_once DIR_FS_INC.'xtc_php_mail.inc.php';

class NovalnetHelper
{
    /**
     * @var string
     */
    protected $endpoint = 'https://payport.novalnet.de/v2/';

    /**
     * @var array
     */
    private $nnRedirectPayments = ['novalnet_paypal', 'novalnet_online_bank_transfer', 'novalnet_ideal', 'novalnet_przelewy24', 'novalnet_eps', 'novalnet_postfinance_card', 'novalnet_postfinance', 'novalnet_bancontact', 'novalnet_trustly', 'novalnet_alipay', 'novalnet_wechatpay', 'novalnet_blik', 'novalnet_twint', 'novalnet_mbway'];

    /**
     * @var array
     */
    private $nnFormTypePayments = ['novalnet_sepa', 'novalnet_guarantee_sepa', 'novalnet_instalment_sepa', 'novalnet_direct_debit_ach', 'novalnet_guarantee_invoice', 'novalnet_instalment_invoice', 'novalnet_cc', 'novalnet_mbway'];

    /**
     * @var array
     */
    private $enforcePayments  = ['novalnet_cc', 'novalnet_googlepay'];

    /**
     * @var array
     */
    private $zeroAmountPayments  = ['novalnet_cc', 'novalnet_sepa', 'novalnet_applepay', 'novalnet_googlepay', 'novalnet_direct_debit_ach'];

    /**
     * NovalnetHelper constructor
     *
     * @param string $lang
     */
    public function __construct($lang = '')
    {
        if (!empty($lang)) {
            foreach (glob(DIR_FS_CATALOG.'lang/'.$lang.'/modules/payment/novalnet*.php') as $filename) {
                include_once $filename;
            }
        } else {
            include_once DIR_FS_CATALOG ."lang/". $_SESSION['language']."/modules/payment/novalnet.php";
        }
    }

    /**
     * Validate the merchant credentials
     */
    public function validateMerchantConfiguration()
    {
        $error_display = $this->checkMerchantCredentials();
        if (isset($_GET['module']) && strpos(MODULE_PAYMENT_INSTALLED, $_GET['module']) && $_GET['module'] == 'novalnet_config' && (!isset($_GET['action']) || $_GET['action'] != 'edit')) {
            if ($error_display) {
                echo $this->displayErrorMessage(defined('MODULE_PAYMENT_NOVALNET_CONFIG_TEXT_TITLE') ? MODULE_PAYMENT_NOVALNET_CONFIG_TEXT_TITLE : '');
            }
        }
    }

    /**
     * Check the merchant credentials are empty
     *
     * @return boolean
     */
    public function checkMerchantCredentials()
    {
        if ((!defined('MODULE_PAYMENT_NOVALNET_SIGNATURE') || MODULE_PAYMENT_NOVALNET_SIGNATURE == '')
            || (!defined('MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY') || MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY == '')
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Display error message
     *
     * @param string $error_payment_name
     *
     * @return string
     */
    protected function displayErrorMessage(string $error_payment_name)
    {
        $_SESSION['error_msg_displayed'] = true;
        return '<div class="message_stack_container" style="display:block"><div class = "alert alert-danger">' . $error_payment_name . '<br/><br/>'. (defined('MODULE_PAYMENT_NOVALNET_VALID_MERCHANT_CREDENTIALS_ERROR') ? MODULE_PAYMENT_NOVALNET_VALID_MERCHANT_CREDENTIALS_ERROR : '') . '<button type="button" class="close" data-dismiss="alert">×</button></div></div>';
    }

    /**
     * Get action URL
     *
     * @param string $action
     *
     * @return string
     */
    public function getActionEndpoint(string $action = '')
    {
        return $this->endpoint . str_replace('_', '/', $action);
    }

    /**
     * Send request to server
     *
     * @param array  $data
     * @param string $paygate_url
     * @param string $access_key
     *
     * @return array
     */
    public function sendRequest(array $data, string $paygate_url, string $access_key = '')
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $paygate_url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->getHeadersParam($access_key ?: MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY));
        $result = curl_exec($curl);
        if (curl_errno($curl)) {
            echo 'Request Error:' . curl_error($curl);
            return $result;
        }
        curl_close($curl);
        $result = json_decode($result, true);
        return $result;
    }

    /**
     * Get request header
     *
     * @param string $access_key
     *
     * @return array
     */
    public function getHeadersParam(string $access_key)
    {
        $encoded_data = base64_encode($access_key);
        $headers = [
            'Content-Type:application/json',
            'Charset:utf-8',
            'Accept:application/json',
            'X-NN-Access-Key:' . $encoded_data
        ];
        return $headers;
    }

    /**
     * Includes the support scripts in payment configuration page
     *
     * @param $payment_code
     */
    public function includeAdminJS($payment_code)
    {
        if (isset($_GET['module']) && $_GET['module'] == $payment_code && isset($_GET['action']) && $_GET['action'] == 'edit') {
            echo '<script src="' . DIR_WS_CATALOG . 'ext/novalnet/js/novalnet.min.js" type="text/javascript" integrity="sha384-usvoNgsBXQbXc+APKOE09arkc4mE/QLNE2S0QbVxSUIspFWLHAkJVnVQ+eu83BRW" ></script>';
            $auth = defined('MODULE_PAYMENT_'. strtoupper($payment_code) . '_AUTHENTICATE') ? constant('MODULE_PAYMENT_'. strtoupper($payment_code) . '_AUTHENTICATE') : '';
            if ($auth) {
                $elementID = $payment_code . '_auth';
                echo '<input type="hidden" id="' . $elementID . '" value= '.$auth.' />';
            }
        }
    }

    /**
     * Check payment is available for the order amount
     *
     * @param string $payment_name
     *
     * @return bool
     */
    public function hidePaymentVisibility(string $payment_name)
    {
        global $order;
        if (!empty($order->info['total'])) {
            $order_amount = $this->getOrderAmount($order->info['total']);
            $visibility_amount = constant('MODULE_PAYMENT_' . strtoupper($payment_name) . '_VISIBILITY_BY_AMOUNT');
            return ($visibility_amount == '' || (int) $visibility_amount <= (int) $order_amount);
        }

        return false;
    }

    /**
     * Get the order total amount and convert it into minimum unit amount (cents in Euro)
     *
     * @param string $order_amount
     *
     * @return int
     */
    public function getOrderAmount(string $order_amount)
    {
        global $order;
        if (($_SESSION['customers_status']['customers_status_show_price_tax'] == '0') && !isset($_SESSION['novalnet']['payment_amount']) && empty($_SESSION['novalnet']['payment_amount'])) {
            $order_amount += (round($order->info['tax'], 2)) ;
        } elseif (!empty($_SESSION['novalnet']['payment_amount'])) {
            $order_amount = $_SESSION['novalnet']['payment_amount'];
        }
        return (sprintf('%0.2f', $order_amount) * 100);
    }

    /**
     * Show payment description and test mode notification to the payments
     *
     * @param string $payment_name
     *
     * @return string
     */
    public function showPaymentDescription(string $payment_code)
    {
        // Payment method description
        $payment_description = '<link rel="stylesheet" type="text/css" href="ext/novalnet/css/novalnet.min.css">';
        $languageCode = (isset($_SESSION['language_code']) && $_SESSION['language_code'] == 'de') ? 'de' : 'en';

        if (in_array($payment_code, $this->getFormTypePayments())) {
            $payment_description .= '<script src="https://cdn.novalnet.de/js/v2/NovalnetUtility.js"></script>
            <script type="text/javascript" src="' . DIR_WS_CATALOG . 'ext/novalnet/js/novalnet_payment.min.js" integrity="sha384-TOfOdn5RuZAU0ENZHiqXb9rQ5GYPPhwO8zOWZc4EFERh4gcx0Adc8cdkNoW0kQYy" ></script>
            <input type="hidden" id="languageID" value= '. $languageCode .' />';
        }

        if (in_array($payment_code, ['novalnet_applepay', 'novalnet_googlepay'])) {
            $payment_description .= '<script src="https://cdn.novalnet.de/js/v3/payment.js"></script>
            <script type="text/javascript" integrity="sha384-WBLQQbJSUhYZ0rxqvy+6tnyCG9Co3Z3eg8GVoaUVWty4LTlEWKL7c/pe9+LjY++h" src="' . DIR_WS_CATALOG . 'ext/novalnet/js/novalnet_wallet.min.js"></script>';
        }
        $payment_description .= '<div class="novalnet-info-box">';
        // Add TestMode Label if the payment in Test Mode.
        if (constant('MODULE_PAYMENT_'. strtoupper($payment_code) . '_TEST_MODE') == 'true') {
            $payment_description .= '<div class="novalnet-test-mode">'.MODULE_PAYMENT_NOVALNET_TESTMODE.'</div>';
        }
        $payment_description .= defined('MODULE_PAYMENT_'.strtoupper($payment_code).'_TEXT_INFO') ? constant('MODULE_PAYMENT_'.strtoupper($payment_code).'_TEXT_INFO') : '';
        $authentication = defined('MODULE_PAYMENT_'.strtoupper($payment_code).'_AUTHENTICATE') ? constant('MODULE_PAYMENT_'.strtoupper($payment_code).'_AUTHENTICATE') : '';

        if (!empty($authentication) && ($authentication == 'zero_amount' || ($payment_code == 'novalnet_direct_debit_ach' && $authentication == 'true'))) {
            $payment_description .= defined('MODULE_PAYMENT_NOVALNET_ZEROAMOUNT_BOOKING_TEXT') ? MODULE_PAYMENT_NOVALNET_ZEROAMOUNT_BOOKING_TEXT : '';
        }

        if (in_array($payment_code, ['novalnet_sepa', 'novalnet_guarantee_sepa', 'novalnet_instalment_sepa'])) {
            $payment_description .= '<br><br>' . constant('MODULE_PAYMENT_' . strtoupper($payment_code) . '_FORM_MANDATE_CONFIRM_TEXT');
            $payment_description .= '<br>' . constant('MODULE_PAYMENT_' . strtoupper($payment_code) . '_ABOUT_MANDATE_TEXT');
        }
        $payment_description .= '</div>';
        return $payment_description;
    }

    /**
     * Update order status in the shop
     *
     * @param string $orderId
     * @param string $orderStatus
     * @param string $message
     */
    public function updateStatus($orderId, $orderStatus, $message)
    {
        $comments = xtc_db_fetch_array(xtc_db_query("SELECT comments FROM ". TABLE_ORDERS ." WHERE orders_id = '$orderId'"));
        xtc_db_perform(TABLE_ORDERS, array('orders_status' => $orderStatus,), "update", "orders_id = '$orderId'");
        xtc_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments, customer_id) values ('".xtc_db_input($orderId)."', '".xtc_db_input($orderStatus)."', '" .date('Y-m-d H:i:s') . "', '1', '".xtc_db_prepare_input($message)."', '0')");
    }

    /**
     * Perform serialize data.
     *
     * @param array $data
     *
     * @return string
     */
    public function serializeData(array $data)
    {
        $result = '{}';

        if (! empty($data)) {
            $result = json_encode($data, JSON_UNESCAPED_SLASHES);
        }
        return $result;
    }

    /**
     * Perform unserialize data.
     *
     * @param string|null $data
     * @param bool        $needAsArray
     *
     * @return array
     */
    public function unserializeData(string $data = null, bool $needAsArray = true): array
    {
        if (empty($data)) {
            return [];
        }
        $result = json_decode($data, $needAsArray, 512, JSON_BIGINT_AS_STRING);

        if (json_last_error() === 0) {
            return $result;
        }
        return $result ? $result : [];
    }

    /**
     * Get customer address
     *
     * @param array $datas
     *
     * @return array
     */
    public function getAddressData($datas)
    {
        $data = [
            'street'    => $datas['street_address'],
            'city'      => $datas['city'],
            'zip'       => $datas['postcode'],
            'state'     => $datas['state'],
            'country_code' => !empty($datas['country']['iso_code_2']) ? $datas['country']['iso_code_2'] :
                (!empty($datas['country_iso_code_2']) ? $datas['country_iso_code_2'] : null),
        ];
        if ($datas['company']) {
            $data['company'] = $datas['company'];
        }
        return array_filter($data);
    }

    /**
     * Get customer IP address
     *
     * @return string
     */
    public function getIPAddress()
    {
        $ip_keys = [ 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR' ];
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
        }
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false;
    }

    /**
     * Get payment method type for payment request
     *
     * @param string $payment_code
     *
     * @return string
     */
    public function getPaymentName(string $payment_code)
    {
        $payment_title = array(
            'novalnet_applepay'               => 'APPLEPAY',
            'novalnet_googlepay'              => 'GOOGLEPAY',
            'novalnet_alipay'                 => 'ALIPAY',
            'novalnet_invoice'                => 'INVOICE',
            'novalnet_prepayment'             => 'PREPAYMENT',
            'novalnet_sepa'                   => 'DIRECT_DEBIT_SEPA',
            'novalnet_cc'                     => 'CREDITCARD',
            'novalnet_ideal'                  => 'IDEAL',
            'novalnet_wechatpay'              => 'WECHATPAY',
            'novalnet_trustly'                => 'TRUSTLY',
            'novalnet_online_bank_transfer'   => 'ONLINE_BANK_TRANSFER',
            'novalnet_eps'                    => 'EPS',
            'novalnet_przelewy24'             => 'PRZELEWY24',
            'novalnet_paypal'                 => 'PAYPAL',
            'novalnet_bancontact'             => 'BANCONTACT',
            'novalnet_multibanco'             => 'MULTIBANCO',
            'novalnet_guarantee_invoice'      => 'GUARANTEED_INVOICE',
            'novalnet_guarantee_sepa'         => 'GUARANTEED_DIRECT_DEBIT_SEPA',
            'novalnet_postfinance'            => 'POSTFINANCE',
            'novalnet_postfinance_card'       => 'POSTFINANCE_CARD',
            'novalnet_instalment_invoice'     => 'INSTALMENT_INVOICE',
            'novalnet_instalment_sepa'        => 'INSTALMENT_DIRECT_DEBIT_SEPA',
            'novalnet_direct_debit_ach'       => 'DIRECT_DEBIT_ACH',
            'novalnet_blik'                   => 'BLIK',
            'novalnet_mbway'                  => 'MBWAY',
            'novalnet_twint'                  => 'TWINT',
        );
        return $payment_title[$payment_code];
    }

    /**
     * Get due date
     *
     * @param $days
     *
     * @return string
     */
    public function getDueDate($days)
    {
        return date("Y-m-d", strtotime('+' . $days . ' days'));
    }

    /**
     * Return the redirect payments
     *
     * @return array
     */
    public function getRedirectPayments()
    {
        return $this->nnRedirectPayments;
    }

    /**
     * Return the form type payments
     *
     * @return array
     */
    public function getFormTypePayments()
    {
        return $this->nnFormTypePayments;
    }

    /**
     * Return the enforce payments
     *
     * @return array
     */
    public function getEnforcePayments()
    {
        return $this->enforcePayments;
    }

    /**
     * Return the zero amount supported payments
     *
     * @return array
     */
    public function getZeroAmountPayments()
    {
        return $this->zeroAmountPayments;
    }

    /**
     * Return the enforce payments
     *
     * @param string $code
     * @param string $suffix
     *
     * @return string
     */
    public function getConstant(string $code, string $suffix)
    {
        $payment_upper = strtoupper($code);
        $constant_string = "MODULE_PAYMENT_{$payment_upper}_{$suffix}";
        return defined($constant_string) ? constant($constant_string) : '';
    }

    /*
     * Format the amount in order format
     *
     * @param string $amount
     * @param array $currency
     *
     * @return string
     */
    public function formattedAmount(string $amount, string $currency)
    {
        return xtc_format_price_order($amount, 1, $currency);
    }

    /*
     * Custom comments for transaction
     *
     * @param string $payment_code
     * @param array $response
     *
     * @return string
     */
    public function formCustomerComments(string $payment_code, array $response)
    {
        if (! empty($response['instalment']['cycle_amount'])) {
            $amountInBiggerCurrencyUnit = $this->formattedAmount($response['instalment']['cycle_amount'] / 100, $response['transaction']['currency']);
        } else {
            $amountInBiggerCurrencyUnit = $this->formattedAmount($response['transaction']['amount'] / 100, $response['transaction']['currency']);
        }

        $comments = MODULE_PAYMENT_NOVALNET_TRANSACTION_ID . $response['transaction']['tid'] . PHP_EOL;

        if (!empty($response['transaction']['test_mode'])) {
            $comments .= MODULE_PAYMENT_NOVALNET_PAYMENT_MODE . PHP_EOL;
        }

        if ($response['transaction']['amount'] == 0) {
            $comments .= MODULE_PAYMENT_NOVALNET_ZEROAMOUNT_BOOKING_MESSAGE . PHP_EOL;
        }

        switch ($payment_code) {
            case 'novalnet_invoice':
            case 'novalnet_guarantee_invoice':
            case 'novalnet_instalment_invoice':
            case 'novalnet_prepayment':
                if ($response['transaction']['status'] === 'PENDING' && in_array($payment_code, ['novalnet_guarantee_invoice', 'novalnet_instalment_invoice'])) {
                    $comments .= MODULE_PAYMENT_NOVALNET_MENTION_GUARANTEE_PAYMENT_PENDING_TEXT . PHP_EOL;
                } elseif (!empty($response['transaction']['bank_details'])) {
                    if (in_array($response['transaction']['status'], [ 'CONFIRMED', 'PENDING' ]) && !empty($response ['transaction']['due_date'])) {
                        $comments .= sprintf(MODULE_PAYMENT_NOVALNET_AMOUNT_TRANSFER_NOTE_DUE_DATE, $amountInBiggerCurrencyUnit, date('d.m.Y', strtotime($response['transaction']['due_date']))) . PHP_EOL;
                    } else {
                        $comments .= sprintf(MODULE_PAYMENT_NOVALNET_AMOUNT_TRANSFER_NOTE, $amountInBiggerCurrencyUnit) . PHP_EOL . PHP_EOL;
                    }

                    foreach ([
                        'account_holder' => MODULE_PAYMENT_NOVALNET_ACCOUNT_HOLDER,
                        'iban'           => MODULE_PAYMENT_NOVALNET_IBAN,
                        'bic'            => MODULE_PAYMENT_NOVALNET_BIC,
                        'bank_name'      => MODULE_PAYMENT_NOVALNET_BANK_NAME,
                        'bank_place'     => MODULE_PAYMENT_NOVALNET_BANK_PLACE,
                    ] as $key => $text) {
                        if (! empty($response ['transaction']['bank_details'][ $key ])) {
                            $comments .= $text . $response ['transaction']['bank_details'][ $key ] . PHP_EOL;
                        }
                    }

                    $comments .= PHP_EOL . MODULE_PAYMENT_NOVALNET_PAYMENT_REFERENCE_TEXT . PHP_EOL. PHP_EOL;
                    $comments .= sprintf(MODULE_PAYMENT_NOVALNET_PAYMENT_REFERENCE, 1, $response['transaction']['tid']) . PHP_EOL;

                    if (!empty($response['transaction']['invoice_ref']) && !preg_match('/BNR-' . MODULE_PAYMENT_NOVALNET_PROJECT_ID . '-/', $comments)) {
                        $comments .= sprintf(MODULE_PAYMENT_NOVALNET_PAYMENT_REFERENCE, 2, $response['transaction']['invoice_ref']) . PHP_EOL;
                        if (!empty($response['transaction']['bank_details']['qr_image'])) {
                            $comments .= PHP_EOL . MODULE_PAYMENT_NOVALNET_PAYMENT_QR_CODE_REFERENCE_TEXT;
                            $comments .= PHP_EOL . sprintf(MODULE_PAYMENT_NOVALNET_PAYMENT_QR_CODE_IMAGE, $response['transaction']['bank_details']['qr_image']);
                        }
                    }

                    if ($payment_code == 'novalnet_instalment_invoice' && !preg_match('/BNR-' . MODULE_PAYMENT_NOVALNET_PROJECT_ID . '-/', $comments)) {
                        if(!empty($response['transaction']['order_no'])) {
                            $comments .= sprintf(MODULE_PAYMENT_NOVALNET_PAYMENT_REFERENCE, 2, ('BNR-' . MODULE_PAYMENT_NOVALNET_PROJECT_ID . '-' . $response['transaction']['order_no'])) . PHP_EOL;
                        } else {
                            $comments .= sprintf(MODULE_PAYMENT_NOVALNET_PAYMENT_REFERENCE, 2, ('BNR-' . MODULE_PAYMENT_NOVALNET_PROJECT_ID . '-###SHOPORDERNUMBER###')) . PHP_EOL;
                        }

                        if (!empty($response['transaction']['bank_details']['qr_image'])) {
                            $comments .= PHP_EOL . MODULE_PAYMENT_NOVALNET_PAYMENT_QR_CODE_REFERENCE_TEXT;
                            $comments .= PHP_EOL . sprintf(MODULE_PAYMENT_NOVALNET_PAYMENT_QR_CODE_IMAGE, $response['transaction']['bank_details']['qr_image']);
                        }
                    }

                    if ($payment_code == 'novalnet_instalment_invoice' && $response['transaction']['status'] == 'CONFIRMED') {
                        $comments .= $this->formInstalmentComments($response, $amountInBiggerCurrencyUnit);
                    }
                }
                break;
            default:
                if ($response['transaction']['status'] === 'PENDING' && in_array($payment_code, ['novalnet_guarantee_sepa', 'novalnet_instalment_sepa'])) {
                    $comments .= MODULE_PAYMENT_NOVALNET_MENTION_GUARANTEE_PAYMENT_PENDING_TEXT . PHP_EOL;
                } elseif ($payment_code == 'novalnet_instalment_sepa' && $response['transaction']['status'] == 'CONFIRMED') {
                    $comments .= PHP_EOL . $this->formInstalmentComments($response, $amountInBiggerCurrencyUnit);
                } elseif ($payment_code == 'novalnet_multibanco' && !empty($response['transaction']['partner_payment_reference'])) {
                    $comments .= PHP_EOL . sprintf(MODULE_PAYMENT_NOVALNET_MULTIBANCO_NOTE, $amountInBiggerCurrencyUnit) . PHP_EOL;
                    $comments .= sprintf(MODULE_PAYMENT_NOVALNET_PARTNER_PAYMENT_REFERENCE, $response['transaction']['partner_payment_reference']) . PHP_EOL;
                    $comments .= sprintf(MODULE_PAYMENT_NOVALNET_PARTNER_SUPPLIER_ID, $response['transaction']['service_supplier_id']);
                }
                break;
        }
        $note = $this->setHtmlEntity($comments, 'decode');
        return $note;
    }

    /**
     * Return the server error text
     *
     * @param  string $str
     * @param  string $type
     * @return string
     */
    public function setHtmlEntity($str, $type = 'encode')
    {
        if (!is_string($str)) {
            throw new \Exception('Invalid encoding specified');
        }
        return ($type == 'encode') ? htmlentities($str) : html_entity_decode($str, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Form instalment detail comments
     *
     * @param array  $response
     * @param string $amount
     *
     * @return string
     */
    public function formInstalmentComments($response, $amount)
    {
        $comments = PHP_EOL . MODULE_PAYMENT_NOVALNET_INSTALMENT_INSTALMENTS_INFO . PHP_EOL;
        $comments .= MODULE_PAYMENT_NOVALNET_INSTALMENT_PROCESSED_INSTALMENTS . $response['instalment']['cycles_executed'] . PHP_EOL;
        $comments .= MODULE_PAYMENT_NOVALNET_INSTALMENT_DUE_INSTALMENTS . (($response['instalment']['pending_cycles']) ? $response['instalment']['pending_cycles'] : '0') . PHP_EOL;

        if (!empty($response['instalment']['next_cycle_date'])) {
            $comments .= MODULE_PAYMENT_NOVALNET_INSTALMENT_NEXT_INSTALMENT_DATE . date('d.m.Y', strtotime($response['instalment']['next_cycle_date'])) . PHP_EOL;
        }

        $comments .= MODULE_PAYMENT_NOVALNET_INSTALMENT_NEXT_INSTALMENT_AMOUNT . $amount . PHP_EOL;

        return $comments;
    }

    /**
     * Update order status and insert the transaction details in the database
     *
     * @param string $order_id
     * @param string $payment_code
     * @param string $comments
     * @param array  $response
     *
     * @return void
     */
    public function updateOrderStatus(string $order_id, string $payment_code, string $comments, array $response)
    {
        global $order;

        if (empty($order) && !empty($order_id)) {
            $order = new order($order_id);
        }

        $customerId = isset($order->customer['ID']) ? $order->customer['ID'] : (isset($order->customer['csID']) ? $order->customer['csID'] : '');
        $customerId = !empty($response['customer']['customer_no']) ? $response['customer']['customer_no'] : $customerId;

        $novalnet_transaction_details = array(
            'order_no'     => $order_id,
            'tid'          => $response['transaction']['tid'],
            'amount'       => $response['transaction']['amount'],
            'currency'     => $response['transaction']['currency'],
            'customer_id'  => $customerId,
            'payment_type' => $response['transaction']['payment_type'],
            'status'       => $response['transaction']['status'],
        );

        if ($response['transaction']['status'] === 'CONFIRMED') {
            $novalnet_transaction_details['paid_amount'] = $response['transaction']['amount'];
        }

        $payment_details = [];

        $payment_status['orders_status'] = $status_update['orders_status_id'] = $this->getOrderStatus($response['transaction']['status'], $payment_code);
        $payment_status['comments'] = $status_update['comments']  = xtc_db_prepare_input($comments);

        if (in_array($response['transaction']['payment_type'], array('INSTALMENT_INVOICE', 'INSTALMENT_DIRECT_DEBIT_SEPA')) && ($response['transaction']['status'] == 'CONFIRMED')) {
            $novalnet_transaction_details['instalment_cycle_details'] = $this->serializeData($this->getInstalmentInformation($response));
        }

        if (!empty($response['transaction']['bank_details'])) {
            $payment_details = $response['transaction']['bank_details'];
        } elseif (!empty($response['transaction']['payment_data']['token'])) {
            $payment_details = $response['transaction']['payment_data'];
            $payment_details['token'] = $response['transaction']['payment_data']['token'];

            $authorize = $this->getConstant($payment_code, 'AUTHENTICATE');
            if (!empty($authorize) && ($authorize == 'zero_amount' || ($payment_code == 'novalnet_direct_debit_ach' && $authorize == 'true'))) {
                $payment_details['zero_amount_booking'] = 1;
                $payment_details['display_token'] = 0;
                if (!empty($_SESSION['novalnet']['novalnet_create_token'])) {
                    $payment_details['display_token'] = 1;
                }
            }
        }

        $novalnet_transaction_details['payment_details'] = !empty($payment_details) ? $this->serializeData($payment_details) : null;
        xtc_db_perform('novalnet_transaction_detail', $novalnet_transaction_details, 'insert');
        xtc_db_perform(TABLE_ORDERS, $payment_status, "update", "orders_id='$order_id'");
        xtc_db_perform(TABLE_ORDERS_STATUS_HISTORY, $status_update, "update", "orders_id='$order_id'");
    }

    /**
     * Send transaction update call to update order_no in Novalnet
     *
     * @param $order_no
     *
     * @return none
     */
    public function sendTransactionUpdate($order_no)
    {
        $params = [
            'transaction' => [
                'tid'       => $_SESSION['novalnet']['nn_response']['transaction']['tid'],
                'order_no'  => $order_no,
            ],
            'custom'      => [
                'lang' => (isset($_SESSION['language_code'])) ? strtoupper($_SESSION['language_code']) : 'DE',
            ]
        ];

        return $this->sendRequest($params, $this->getActionEndpoint('transaction_update'));
    }

    /**
     * Form instalment information.
     *
     * @param array $response
     *
     * @return array
     */
    public function getInstalmentInformation(array $response)
    {
        $instalmentData = $response['instalment'];
        $additionalDetails = [];
        sort($instalmentData['cycle_dates']);
        $futureInstalmentDates = $instalmentData['cycle_dates'];
        foreach (array_keys($futureInstalmentDates) as $cycle) {
            $additionalDetails[$cycle] = [
                'cycle_amount'        => $instalmentData['cycle_amount'],
                'next_instalment_date' => !empty($futureInstalmentDates[$cycle + 1]) ? date('Y-m-d', strtotime($futureInstalmentDates[$cycle + 1])) : '',
                'cycles_executed' => '',
                'due_cycles'     => '',
                'paid_date'      => '',
                'status'        => 'Pending',
                'reference_tid'     => '',
                'refunded_amount' => '0',

            ];

            if ($cycle == count($instalmentData['cycle_dates'])) {
                $amount = abs($response['instalment']['total_amount'] - ($instalmentData['cycle_amount'] * ($cycle - 1)));
                $additionalDetails[$cycle] = array_merge(
                    $additionalDetails[$cycle],
                    [
                        'cycle_amount'    => $amount
                    ]
                );
            }

            if ($cycle == 0) {
                $additionalDetails[$cycle] = array_merge(
                    $additionalDetails[$cycle],
                    [
                        'cycles_executed' => !empty($instalmentData['cycles_executed']) ? $instalmentData['cycles_executed'] : '',
                        'due_cycles'     => !empty($instalmentData['pending_cycles']) ? $instalmentData['pending_cycles'] : '',
                        'paid_date'      => date('Y-m-d'),
                        'status'        => 'Paid',
                        'reference_tid'     => $response['transaction']['tid']
                    ]
                );
            }
        }
        return $additionalDetails;
    }

    /**
     * Update Novalnet instalment cycles
     *
     * @param array  $instalmentDetails
     * @param int    $amount
     * @param string $referenceTid
     *
     * @return array
     */
    public function updateInstalmentCycle(array $instalmentDetails, int $amount, string $referenceTid)
    {
        foreach ($instalmentDetails as $key => $values) {
            if ($values['reference_tid'] == $referenceTid) {
                if (isset($values['instalment_cycle_amount'])) {
                    $instalment_amount = (strpos((string)$values['instalment_cycle_amount'], '.')) ? $values['instalment_cycle_amount'] * 100 : $values['instalment_cycle_amount'];
                    $instalment_amount = $instalment_amount - $amount;
                    $instalmentDetails[$key]['instalment_cycle_amount'] = $instalment_amount;
                    if($instalmentDetails[$key]['instalment_cycle_amount'] <= 0) {
                        $instalmentDetails[$key]['status'] = 'Refunded';
                    }
                } else {
                    $instalmentDetails[$key]['refunded_amount'] = (int) $values['refunded_amount'] + $amount;
                    if ($instalmentDetails[$key]['refunded_amount'] >= $values['cycle_amount']) {
                        $instalmentDetails[$key]['status'] = 'Refunded';
                    }
                }
            }
        }
        return $instalmentDetails;
    }

    /**
     * Update Novalnet instalment cycles
     *
     * @param array       $instalmentDetails
     * @param string|null $cycleType
     *
     * @return array
     */
    public function updateInstalmentCancel(array $instalmentDetails, ?string $cycleType)
    {
        foreach ($instalmentDetails as $key => $values) {
            if (empty($cycleType) || false !== strpos($cycleType, 'ALL_CYCLES')) {
                $instalmentDetails[$key]['refunded_amount'] = !empty($values['reference_tid']) ? $values['cycle_amount'] : 0;
                $instalmentDetails[$key]['status'] = !empty($values['reference_tid']) ? 'Refunded' : 'Canceled';
            } elseif (false !== strpos($cycleType, 'REMAINING_CYCLES') && empty($values['reference_tid'])) {
                $instalmentDetails[$key]['status'] = 'Canceled';
            }
        }

        return $instalmentDetails;
    }

    /**
     * Get shop order status id
     *
     * @param string $status
     * @param string $payment_code
     *
     * @return $order_status_id
     */
    public function getOrderStatus(string $status, string $payment_code)
    {
        $order_status_id = $this->getConstant($payment_code, 'ORDER_STATUS');

        if ($status === 'ON_HOLD') {
            $order_status_id = MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_COMPLETE;
        } elseif (!in_array($payment_code, array('novalnet_invoice', 'novalnet_prepayment', 'novalnet_multibanco')) && $status == 'PENDING') {
            $order_status_id = 1;
        }
        return $order_status_id;
    }

    /**
     * Hadnle temporary created order for the failure transaction
     *
     * @param string $payment_method
     * @param int    $order_id
     * @param array  $response
     */
    public function processTempOrderFail($payment_method, $order_id, $response)
    {
        $status_text = $this->getStatusDesc($response);
        $this->updateTempOrderFail($order_id, $response, $status_text);
        $error = 'payment_error=' . $payment_method . '&error=' . rawurlencode($status_text);
        xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $error, 'SSL', true, false));
    }

    /**
     * Update order status for the temporary created order.
     *
     * @param int    $order_id
     * @param array $response
     * @param string $status_text
     */
    public function updateTempOrderFail($order_id, $response, $status_text)
    {
        $tid = !empty($response['tid']) ? $response['tid'] : (!empty($response['transaction']['tid']) ? $response['transaction']['tid'] : '');
        $note  = (!empty($tid)) ? PHP_EOL . MODULE_PAYMENT_NOVALNET_TRANSACTION_ID . $tid . PHP_EOL : '';
        if (!empty($response['transaction']['test_mode'])) {
            $note .= MODULE_PAYMENT_NOVALNET_PAYMENT_MODE . PHP_EOL;
        }
        $note .= $status_text;
        xtc_db_query('UPDATE '.TABLE_ORDERS.' SET orders_status = ' . MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED . ', comments = "'.xtc_db_prepare_input($note).'" WHERE orders_id= '.$order_id);
        xtc_db_query('UPDATE ' . TABLE_ORDERS_STATUS_HISTORY . ' SET orders_status_id = ' . MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED . ', comments = "'.xtc_db_prepare_input($note).'" WHERE orders_id='.$order_id);
    }

    /**
     * Get the server status text
     *
     * @param array $response
     *
     * @return string
     */
    public function getStatusDesc($response)
    {
        return isset($response['result']['status_text']) ? $response['result']['status_text'] : (!empty($response['status_text']) ? $response['status_text'] : MODULE_PAYMENT_NOVALNET_TRANSACTION_ERROR);
    }

    /**
     * Get the customer details from the database
     *
     * @param string $customerId
     *
     * @return array
     */
    public function getCustomerInfo($customerId)
    {
        $customer = xtc_db_fetch_array(xtc_db_query("SELECT customers_id, customers_cid, customers_gender, customers_dob, customers_fax, customers_vat_id FROM " . TABLE_CUSTOMERS . " WHERE customers_cid = '" . $customerId . "'"));

        if (!empty($customer)) {
            $customer['customers_dob'] = ($customer['customers_dob'] != '0000-00-00 00:00:00') ? date('d.m.Y', strtotime($customer['customers_dob'])) : '';
        }
        return $customer;
    }

    /**
     * Validate the customer birth date.
     *
     * @param string $dob
     *
     * @return boolean
     */
    public function validateBirthDate($dob)
    {
        if (time() < strtotime('+18 years', strtotime($dob)) || time() > strtotime('+100 years', strtotime($dob))) {
            return false;
        }
        return true;
    }

    /**
     * Check the status of the Apple Pay button
     *
     * @return bool
     */
    public function checkApplePayIsAvailable()
    {
        $userAgent     = $_SERVER['HTTP_USER_AGENT'];
        $pattern       = '#(?:Chrome|CriOS|FxiOS|EdgiOS|OPiOS)/(\d+)#';

        if (!stripos($userAgent, "Mac OS") || !stripos($userAgent, "Safari") || preg_match($pattern, $userAgent, $matchedAgent)) {
            return false;
        }
        return true;
    }

    /**
     * Get Novalnet transaction details from novalnet table
     *
     * @param string $order_no
     * @param string $tid
     *
     * @return integer
     */
    public static function getNovalnetTransDetails(string $order_no, string $tid = '')
    {
        $orderDetails = '';
        if (!empty($order_no)) {
            $orderDetails = xtc_db_fetch_array(xtc_db_query("SELECT order_no, tid, amount, currency, customer_id, payment_type, status, payment_details, instalment_cycle_details, refund_amount, paid_amount FROM novalnet_transaction_detail WHERE order_no = '" . xtc_db_input($order_no) . "' ORDER BY ID DESC LIMIT 1"));
        }

        if (empty($orderDetails) && !empty($tid)) {
            $orderDetails = xtc_db_fetch_array(xtc_db_query("SELECT order_no, tid, amount, currency, customer_id, payment_type, status, payment_details, instalment_cycle_details, refund_amount, paid_amount FROM novalnet_transaction_detail WHERE tid = '" . xtc_db_input($tid) . "' ORDER BY ID DESC LIMIT 1"));
        }
        return $orderDetails;
    }

    /**
     * To form guarantee payment order confirmation mail
     *
     * @param $datas
     */
    public function sendOrderUpdateMail($datas)
    {
        // load order from order id
        $order = new order($datas['order_no']);

        // CREATE CONTENTVIEW
        $coo_send_order_content_view = MainFactory::create_object('SendOrderThemeContentView');

        // ASSIGN VARIABLES
        $coo_send_order_content_view->set_('order', $order);

        // load order from order id
        $order = new order($datas['order_no']);
        $customeremail = $order->customer['email_address'];
        $customername  = $order->customer['firstname'].' '.$order->customer['lastname'];

        if (isset($datas['instalment']) && !empty($datas['instalment'])) {
            $subject = sprintf(MODULE_PAYMENT_INSTALMENT_PAYMENT_MAIL_SUBJECT, STORE_NAME, $datas['order_no']);
        } else {
            $subject = sprintf(MODULE_PAYMENT_GUARANTEE_PAYMENT_MAIL_SUBJECT, $datas['order_no'], STORE_NAME);
        }
        $get_mail_content = $this->get_mail_content_array_novalnet($datas['comments'], $datas);
        $html_mail = $get_mail_content['smarty']->fetch(DIR_FS_CATALOG.'lang/' . $get_mail_content['lang'] . '/original_mail_templates/order_mail.html');
        $txt_mail = $get_mail_content['smarty']->fetch(DIR_FS_CATALOG.'lang/' . $get_mail_content['lang'] . '/original_mail_templates/order_mail.txt');
        $from_email_address = EMAIL_BILLING_ADDRESS;
        $from_name = EMAIL_BILLING_NAME;

        if (SEND_EMAILS == 'true') {
            // GET WITHDRAWAL
            $coo_shop_content_control = MainFactory::create_object('ShopContentContentControl');
            $t_mail_attachment_array = array();

            if (isset($datas['communication_failure']) && !empty($datas['communication_failure'])) {
                if (gm_get_conf('ATTACH_CONDITIONS_OF_USE_IN_ORDER_CONFIRMATION') == 1) {
                    $coo_shop_content_control->set_content_group('3');
                    $t_attachment = $coo_shop_content_control->get_file();
                    if($t_attachment !== false) {
                        $t_mail_attachment_array[] = $t_attachment;
                    }
                }

                if (gm_get_conf('ATTACH_PRIVACY_NOTICE_IN_ORDER_CONFIRMATION') == 1) {
                    $coo_shop_content_control->set_content_group('2');
                    $t_attachment = $coo_shop_content_control->get_file();
                    if($t_attachment !== false) {
                        $t_mail_attachment_array[] = $t_attachment;
                    }
                }

                if(gm_get_conf('ATTACH_WITHDRAWAL_INFO_IN_ORDER_CONFIRMATION') == '1') {
                    $coo_shop_content_control->set_content_group(gm_get_conf('GM_WITHDRAWAL_CONTENT_ID'));
                    $t_attachment = $coo_shop_content_control->get_file();
                    if($t_attachment !== false) {
                        $t_mail_attachment_array[] = $t_attachment;
                    }
                }

                if(gm_get_conf('ATTACH_WITHDRAWAL_FORM_IN_ORDER_CONFIRMATION') == '1') {
                    $coo_shop_content_control->set_content_group(gm_get_conf('GM_WITHDRAWAL_CONTENT_ID'));
                    $coo_shop_content_control->set_withdrawal_form('1');
                    $t_attachment = $coo_shop_content_control->get_file();
                    if($t_attachment !== false) {
                        $t_mail_attachment_array[] = $t_attachment;
                    }
                }
            }
            // send mail to customer
            xtc_php_mail($from_email_address, $from_name, $customeremail, $customername, '', EMAIL_BILLING_REPLY_ADDRESS, EMAIL_BILLING_REPLY_ADDRESS_NAME, $t_mail_attachment_array, '', $subject, $html_mail, $txt_mail);
            if (isset($datas['communication_failure']) && !empty($datas['communication_failure'])) {
                // get the sender mail address. e.g. Host Europe has problems with the customer mail address.
                if (SEND_EMAIL_BY_BILLING_ADRESS != 'SHOP_OWNER') {
                    $from_email_address = $customeremail;
                    $from_name = $customername;
                }
                // send mail to admin
                xtc_php_mail($from_email_address, $from_name, EMAIL_BILLING_ADDRESS, STORE_NAME, EMAIL_BILLING_FORWARDING_STRING, $order->customer['email_address'], $order->customer['firstname'].' '.$order->customer['lastname'], $t_mail_attachment_array, '', $subject, $html_mail, $txt_mail);
            }
        }
    }

    /**
     * Get total data
     *
     * @param $oID
     *
     * @return array
     */
    public function getTotalData($oID)
    {
        // get order_total data
        $oder_total_query = "SELECT
                                    title,
                                    text,
                                    class,
                                    value,
                                    sort_order
                                FROM " . TABLE_ORDERS_TOTAL . "
                                WHERE orders_id='" . (int)$oID . "'
                                ORDER BY sort_order ASC";

        $order_total = array();
        $oder_total_query = xtc_db_query($oder_total_query);
        while($oder_total_values = xtc_db_fetch_array($oder_total_query)) {
            $order_total[] = array(
                'TITLE' => $oder_total_values['title'],
                'CLASS' => $oder_total_values['class'],
                'VALUE' => $oder_total_values['value'],
                'TEXT'  => $oder_total_values['text']
            );
            if($oder_total_values['class'] == 'ot_total') {
                $total = $oder_total_values['value'];
            }
        }
        return array('data' => $order_total, 'total' => $total);
    }

    /**
     * Get order data
     *
     * @param $oID
     * @param $currency
     *
     * @return $order_data
     */
    public function getOrderData($oID, $currency)
    {
        include_once DIR_FS_INC . 'xtc_get_attributes_model.inc.php';

        $order_query = "SELECT
                                  op.products_id,
                                  op.orders_products_id,
                                  op.products_model,
                                  op.products_name,
                                  op.checkout_information,
                                  op.final_price,
                                  op.products_shipping_time,
                                  op.products_quantity,
                                  opqu.quantity_unit_id,
                                  opqu.unit_name
                              FROM " . TABLE_ORDERS_PRODUCTS . " op
                              LEFT JOIN orders_products_quantity_units opqu USING (orders_products_id)
                              WHERE op.orders_id = '" . (int)$oID . "'";
        $order_data = array();
        $order_query = xtc_db_query($order_query);
        while($order_data_values = xtc_db_fetch_array($order_query)) {
            $attributes_query = "SELECT
                                  products_options,
                                  products_options_values,
                                  price_prefix,
                                  options_values_price
                                  FROM " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . "
                                  WHERE orders_products_id='" . $order_data_values['orders_products_id'] . "'
                                  AND orders_id='" . (int)$oID . "'";
            $attributes_data = '';
            $attributes_model = '';
            $attributes_query = xtc_db_query($attributes_query);
            while($attributes_data_values = xtc_db_fetch_array($attributes_query)) {
                $attributes_data .= '<br />' . $attributes_data_values['products_options'] . ':' . $attributes_data_values['products_options_values'];
                $attributes_model .= '<br />' . xtc_get_attributes_model($order_data_values['products_id'], $attributes_data_values['products_options_values'], $attributes_data_values['products_options']);
            }

            // properties
            $coo_properties_control = MainFactory::create_object('PropertiesControl');
            $t_properties_array = $coo_properties_control->get_orders_products_properties($order_data_values['orders_products_id']);

            if(ACTIVATE_SHIPPING_STATUS == 'true') {
                $shipping_time = $order_data_values['products_shipping_time'];
            } else {
                $shipping_time = '';
            }
            // BOF GM_MOD GX-Customizer
            include DIR_FS_CATALOG . 'gm/modules/gm_gprint_order.php';
            $order_data[] = array(
                'PRODUCTS_MODEL'            => $order_data_values['products_model'],
                'PRODUCTS_NAME'             => $order_data_values['products_name'],
                'CHECKOUT_INFORMATION'      => $order_data_values['checkout_information'],
                'CHECKOUT_INFORMATION_TEXT' => html_entity_decode_wrapper(strip_tags($order_data_values['checkout_information'])),
                'PRODUCTS_SHIPPING_TIME'    => $shipping_time,
                'PRODUCTS_ATTRIBUTES'       => $attributes_data,
                'PRODUCTS_ATTRIBUTES_MODEL' => $attributes_model,
                'PRODUCTS_PROPERTIES'       => $t_properties_array,
                'PRODUCTS_PRICE'            =>  xtc_format_price_order($order_data_values['final_price'], 1, $currency),
                'PRODUCTS_SINGLE_PRICE'     => xtc_format_price_order($order_data_values['final_price'] / $order_data_values['products_quantity'], 1, $currency),
                'PRODUCTS_QTY'              => gm_prepare_number($order_data_values['products_quantity'], ','),
                'UNIT'                      => $order_data_values['unit_name']
            );
        }
        return $order_data;
    }

    /**
     * To form mail templete as like default mail
     *
     * @param string $comments
     * @param array  $data
     */
    public function get_mail_content_array_novalnet(string $comments, array $data)
    {
        $smarty = new Smarty();
        if(empty($data['order'])) {
            $t_order = new order($data['order_no']);
        } else {
            $t_order = $data['order'];
        }
        $order_lang = xtc_db_fetch_array(xtc_db_query("SELECT currency,language FROM ".TABLE_ORDERS." WHERE orders_id='". xtc_db_input($data['order_no']) ."'"));
        $mail_lang_set =  xtc_db_fetch_array(xtc_db_query("SELECT code FROM ".TABLE_LANGUAGES." WHERE directory='". xtc_db_input($order_lang['language']) ."'"));

        $t_order_id = $data['order_no'];
        $t_language = $order_lang['language'];
        $t_language_id = $mail_lang_set['code'];

        // SET CONTENT DATA
        $smarty->assign('csID', $t_order->customer['csID']);
        $smarty->assign('customer_vat', $t_order->customer['vat_id']);
        $smarty->assign('order_data', $this->getOrderData($t_order_id, $order_lang['currency']));
        $t_order_total = $this->getTotalData($t_order_id);
        $smarty->assign('order_total', $t_order_total['data']);
        $smarty->assign('language', $t_language);
        $smarty->assign('language_id', $t_language_id);
        $smarty->assign('tpl_path', DIR_FS_CATALOG . StaticGXCoreLoader::getThemeControl()->getThemeHtmlPath());
        $smarty->assign('logo_path', HTTP_SERVER . DIR_WS_CATALOG . StaticGXCoreLoader::getThemeControl()->getThemeImagePath());
        $gm_logo_mail = MainFactory::create_object('GMLogoManager', array("gm_logo_mail"));
        if($gm_logo_mail->logo_use == '1') {
            $smarty->assign('gm_logo_mail', $gm_logo_mail->get_logo());
        }
        foreach (['PAYMENT_INFO_HTML', 'PAYMENT_INFO_TXT', 'COD_INFO', 'TS_RATING', 'SHOW_ABANDONMENT_WITHDRAWAL_DOWNLOADS_INFO', 'SHOW_ABANDONMENT_WITHDRAWAL_SERVICES_INFO', 'WITHDRAWAL_LINK', 'PDF_LINK'] as $key) {
            $smarty->assign($key, '');
        }
        $smarty->assign('SHOW_ABANDONMENT_WITHDRAWAL_DOWNLOADS_INFO', '');
        $smarty->assign('oID', $t_order_id);
        $smarty->assign('DATE', xtc_date_long($t_order->info['date_purchased'], !empty($data['order']->info['languages_id']) ? (int) $data['order']->info['languages_id'] : 2));

        $t_payment_method = '';
        if ($t_order->info['payment_method'] != '' && $t_order->info['payment_method'] != 'no_payment') {
            $t_payment_method = PaymentTitleProvider::getStrippedTagsTitle($t_order->info['payment_method']);
            $smarty->assign('PAYMENT_MODUL', $t_order->info['payment_method']);
        }

        $smarty->assign('PAYMENT_METHOD', $t_payment_method);
        $smarty->assign('NAME', $t_order->customer['name']);
        $smarty->assign('GENDER', $t_order->customer['gender']);
        $smarty->assign('COMMENTS', nl2br($comments));
        $smarty->assign('EMAIL', $t_order->customer['email_address']);
        $smarty->assign('PHONE', $t_order->customer['telephone']);

        if (defined('EMAIL_SIGNATURE')) {
            $smarty->assign('EMAIL_SIGNATURE_HTML', nl2br(EMAIL_SIGNATURE));
            $smarty->assign('EMAIL_SIGNATURE_TEXT', EMAIL_SIGNATURE);
        }
        // PREPARE HTML MAIL
        $smarty->assign('address_label_customer', xtc_address_format($t_order->customer['format_id'], $t_order->customer, 1, '', '<br />'));
        $smarty->assign('address_label_shipping', xtc_address_format($t_order->delivery['format_id'], $t_order->delivery, 1, '', '<br />'));
        $smarty->assign('address_label_payment', xtc_address_format($t_order->billing['format_id'], $t_order->billing, 1, '', '<br />'));
        return array( 'lang' =>  $t_language ,'smarty' => $smarty);
    }

    /**
     * Validate customer email
     *
     * @param $emails
     *
     * @return mixed
     */
    public static function validateEmail($emails)
    {
        $email = explode(',', $emails);
        foreach ($email as $value) {
            // Validate E-mail.
            if (!xtc_validate_email($value)) {
                return false;
            }
        }
        return $value;
    }
}
