<?php
/**
 * Novalnet payment module
 *
 * This file is used for real time processing of
 * Novalnet transaction of customers.
 *
 * @category   PHP
 * @package    NovalnetGateway
 * @author     Novalnet AG
 * @copyright  Copyright by Novalnet
 * @license    https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * File: callback.php
 *
 */
chdir('../../');
if(isset($_REQUEST['currency'])){
$currency = $_REQUEST['currency'];
unset($_POST['currency']);
}
require_once('includes/application_top.php');
include(DIR_FS_CATALOG . 'release_info.php');
global $gx_version;
if ($gx_version >= 'v3.1.1.0') {
    include_once(DIR_FS_CATALOG . 'vendor/true/punycode/src/Punycode.php');
}
include(DIR_FS_INC . 'xtc_format_price_order.inc.php');
require_once(DIR_FS_INC . 'xtc_php_mail.inc.php');
require_once (DIR_FS_CATALOG . 'gm/inc/gm_save_order.inc.php');
include_once(DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php');
$_REQUEST['currency'] = $currency;
$callback_parameter = array_map('trim', $_REQUEST); // Assign callback parameters
new novalnet_vendor_script($callback_parameter); // Novalnet callback class object
class novalnet_vendor_script {

    // Payment types of Level 0 - Initial level payments
    protected $payments = array('CREDITCARD', 'INVOICE_START', 'DIRECT_DEBIT_SEPA', 'GUARANTEED_INVOICE', 'PAYPAL', 'ONLINE_TRANSFER', 'IDEAL', 'EPS', 'GIROPAY', 'PRZELEWY24', 'GUARANTEED_DIRECT_DEBIT_SEPA','CASHPAYMENT');

    // Payment types of Level 1 - Chargeback payments
    protected $chargebacks_payments = array('PRZELEWY24_REFUND', 'RETURN_DEBIT_SEPA', 'CREDITCARD_BOOKBACK', 'PAYPAL_BOOKBACK', 'CREDITCARD_CHARGEBACK', 'REFUND_BY_BANK_TRANSFER_EU', 'REVERSAL','CASHPAYMENT_REFUND','GUARANTEED_SEPA_BOOKBACK','GUARANTEED_INVOICE_BOOKBACK');

    // Payment types of Level 2 - Credit entry and collection payments
    protected $collection_payments = array('ONLINE_TRANSFER_CREDIT', 'CASHPAYMENT_CREDIT', 'INVOICE_CREDIT', 'CREDIT_ENTRY_CREDITCARD', 'CREDIT_ENTRY_SEPA', 'DEBT_COLLECTION_SEPA', 'DEBT_COLLECTION_CREDITCARD','CREDIT_ENTRY_DE','DEBT_COLLECTION_DE');


    // Payment types Group */
    protected $payment_groups = array(
        'novalnet_cc'         => array('CREDITCARD', 'CREDITCARD_BOOKBACK', 'CREDITCARD_CHARGEBACK', 'CREDIT_ENTRY_CREDITCARD', 'DEBT_COLLECTION_CREDITCARD'),
        'novalnet_sepa'       => array('DIRECT_DEBIT_SEPA', 'REFUND_BY_BANK_TRANSFER_EU','RETURN_DEBIT_SEPA', 'DEBT_COLLECTION_SEPA', 'CREDIT_ENTRY_SEPA', 'GUARANTEED_DIRECT_DEBIT_SEPA','GUARANTEED_SEPA_BOOKBACK'),
        'novalnet_ideal'      => array('IDEAL', 'REFUND_BY_BANK_TRANSFER_EU', 'REVERSAL','ONLINE_TRANSFER_CREDIT','CREDIT_ENTRY_DE','DEBT_COLLECTION_DE'),
        'novalnet_instantbank'=> array('ONLINE_TRANSFER', 'ONLINE_TRANSFER_CREDIT', 'REFUND_BY_BANK_TRANSFER_EU', 'REVERSAL','CREDIT_ENTRY_DE','DEBT_COLLECTION_DE'),
        'novalnet_paypal'     => array('PAYPAL',  'PAYPAL_BOOKBACK'),
        'novalnet_prepayment' => array('INVOICE_START', 'INVOICE_CREDIT', 'REFUND_BY_BANK_TRANSFER_EU'),
        'novalnet_cashpayment' => array('CASHPAYMENT', 'CASHPAYMENT_CREDIT', 'CASHPAYMENT_REFUND'),
        'novalnet_invoice'    => array('INVOICE_START', 'INVOICE_CREDIT',  'GUARANTEED_INVOICE','GUARANTEED_INVOICE_BOOKBACK','REFUND_BY_BANK_TRANSFER_EU','CREDIT_ENTRY_DE','DEBT_COLLECTION_DE'),
        'novalnet_eps'        => array('EPS','REFUND_BY_BANK_TRANSFER_EU','ONLINE_TRANSFER_CREDIT','CREDIT_ENTRY_DE','DEBT_COLLECTION_DE'),
        'novalnet_giropay'    => array('GIROPAY','REFUND_BY_BANK_TRANSFER_EU','ONLINE_TRANSFER_CREDIT','CREDIT_ENTRY_DE','DEBT_COLLECTION_DE'),
        'novalnet_przelewy24' => array('PRZELEWY24', 'PRZELEWY24_REFUND'));
    protected $callback_params = array();
    protected $order_reference = array();
    protected $params_required = array();
    protected $affiliated_params = array();
    protected $process_testmode;
    
    /**
     * @var Mail ID to be notify to technic
     */
    protected $technic_notify_mail = 'technic@novalnet.de';

    /**
     * Core Function : Constructor()
     *
     */
    function __construct($capture_parameter) {
        $this->process_testmode  = (MODULE_PAYMENT_NOVALNET_CALLBACK_TEST_MODE == 'True');
        $this->validate_ip_address(); // Validate IP address
        $this->params_required   = array('vendor_id', 'tid', 'payment_type', 'status', 'tid_status');
        $this->affiliated_params = array('vendor_id', 'vendor_authcode', 'product_id', 'aff_id', 'aff_accesskey', 'aff_authcode');
        if (in_array($capture_parameter['payment_type'], array_merge($this->chargebacks_payments, $this->collection_payments))) { // Add tid_payment parameter as mandatory for collection/chargeback payments
            array_push($this->params_required, 'tid_payment');
        }
        $this->callback_params  = $this->validate_capture_params($capture_parameter);
        $this->formatted_amount = xtc_format_price_order(($this->callback_params['amount'] / 100), 1, $this->callback_params['currency']);

        $this->order_reference     = $this->get_order_reference(); // Get the order reference of given callback request
        $this->transaction_details = NovalnetHelper::getNovalnetTransDetails($this->order_reference['order_no']);
        $payment_type_level = $this->get_payment_type_level();
        // level 0 payments - Initial payments
        if ($payment_type_level === 0) {
            $this->zero_level_process();
        } else if ($payment_type_level === 1 && $this->callback_params['status'] == '100' && $this->callback_params['tid_status'] == '100') { // level 1 payments - Type of charge backs
            $this->first_level_process();
        } else if ($payment_type_level === 2 && $this->callback_params['status'] == '100' && $this->callback_params['tid_status'] == '100') { // level 2 payments - Type of credit entry
            $this->second_level_process();
        }  else if ($this->callback_params['payment_type'] == 'TRANSACTION_CANCELLATION' && in_array($this->order_reference['current_order_previous_status'], array('75','91','99'))) {
           
             //Update novalnet transaction table gateway status
                $param = array('gateway_status'  => $this->callback_params['tid_status']);
                xtc_db_perform('novalnet_transaction_detail', $param, "update", "tid='" . $this->callback_params['shop_tid'] . "'");
           
              //Update callback order status due to full payment
            xtc_db_perform(TABLE_ORDERS, array(
                'orders_status' => MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED
            ), 'update', 'orders_id="' . $this->order_reference['order_no'] . '"');
            // To form the callback comments
            $callback_comments = PHP_EOL . sprintf(MODULE_PAYMENT_GUARANTEE_PAYMENT_CANCELLED_MESSAGE,date(DATE_FORMAT, strtotime(date('d.m.Y'))),date('H:i:s')) . PHP_EOL;
            // Update callback comments in order status history table
            $this->update_callback_comments(array(
                'order_no'              => $this->order_reference['order_no'],
                'comments'              => $callback_comments,
                'orders_status_id'      => MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED,
                'callback_total_amount' => $this->order_reference['order_paid_amount'],
                'nnCaptureParams'       => $this->callback_params,
                'order_total'           => $this->order_reference['order_amount']
            ));
        } else { // To validate the status and tid status
            $status       = ($this->callback_params['status'] != 100) ? 'status' : 'tid_status';
            $status_value = $this->callback_params['status'] != 100 ? $this->callback_params['status'] : $this->callback_params['tid_status'];
            $this->display_message('Novalnet callback received. ' . $status . ' (' . $status_value . ') is not valid: Only 100 is allowed');
        }
    }

    /*
     * Validate IP address
     *
     */
    function validate_ip_address() {
        $real_host_ip = gethostbyname('pay-nn.de');
        if(empty($real_host_ip))
        {
            $this->display_message('Novalnet HOST IP missing');
        }

        $client_ip = NovalnetHelper::getIpAddress(xtc_get_ip_address());

        if ($client_ip != $real_host_ip  && !$this->process_testmode) { // Check IP address
            $this->display_message('Novalnet callback received. Unauthorised access from the IP' . $client_ip);
        }
    }

    /**
     * Validate the parameters
     *
     * @param array $capture_parameter
     * @return array
     */
    function validate_capture_params($capture_parameter) {
        if (empty($capture_parameter)) { // Check whether the callback parameters are empty or not
            $this->display_message('Novalnet callback received. No params passed over!');
        }
        foreach (!empty($capture_parameter['vendor_activation']) ? $this->affiliated_params : $this->params_required as $values) {
            if (empty($capture_parameter[$values])) { // Check required parameters
                $this->display_message('Required param (' . $values . ') missing!');
            } else if (empty($capture_parameter['vendor_activation']) && in_array($values, array('tid', 'tid_payment', 'signup_tid')) && !preg_match('/^\d{17}$/', $capture_parameter[$values])) { // Validate TID
                $this->display_message('Novalnet callback received. Invalid TID [' . $values . '] for Order.');
            }
        }
        if (!empty($capture_parameter['vendor_activation'])) {
            $this->update_aff_account_activation($capture_parameter);
        } else {
            if (!in_array($capture_parameter['payment_type'], array_merge($this->payments, $this->chargebacks_payments, $this->collection_payments)) && $capture_parameter['payment_type'] != 'TRANSACTION_CANCELLATION') { // Validate payment type
            $this->display_message('Novalnet callback received. Payment type (' . $capture_parameter['payment_type'] . ') is mismatched!');
            }
            // Assign original transaction id
            $capture_parameter['shop_tid'] = $capture_parameter['tid'];
            if (!empty($capture_parameter ['payment_type']) && in_array($capture_parameter ['payment_type'], array_merge( $this->chargebacks_payments, $this->collection_payments))) {
                $capture_parameter['shop_tid'] = $capture_parameter['tid_payment'];
            } 
        }
        return $capture_parameter;
    }

    /*
     * Get given payment_type level for process
     *
     * @return integer
     */
    function get_payment_type_level() {
        return in_array($this->callback_params['payment_type'], $this->payments) ? 0 : (in_array($this->callback_params['payment_type'], $this->chargebacks_payments) ? 1 : (in_array($this->callback_params['payment_type'], $this->collection_payments) ? 2 : false));
    }

    /**
     * Payment types of Level 0 - Initial level payments processing
     */
    function zero_level_process() {
        if (in_array($this->callback_params['payment_type'], array('PRZELEWY24')) && $this->callback_params['tid_status'] == 100 && $this->callback_params['status'] == 100) { // To process paypal and przelewy24 payment pending status
            if ($this->order_reference['order_paid_amount'] == 0) { // when paypal and przelewy24 payment in pending status only
                //Update callback order status due to full payment
                xtc_db_perform(TABLE_ORDERS, array(
                    'orders_status' => constant('MODULE_PAYMENT_NOVALNET_'.($this->callback_params['payment_type']).'_ORDER_STATUS')
                ), 'update', 'orders_id="' . $this->order_reference['order_no'] . '"');
                $callback_comments = PHP_EOL . sprintf(html_entity_decode(MODULE_PAYMENT_NOVALNET_CALLBACK_UPDATE_COMMENTS), $this->callback_params['tid'], $this->formatted_amount, date("Y-m-d", strtotime(date("Y-m-d")) . ' days'), date("H:i:s")) . PHP_EOL;
                $param = array('gateway_status'  => $this->callback_params['tid_status']);
                xtc_db_perform('novalnet_transaction_detail', $param, "update", "tid='" . $this->callback_params['shop_tid'] . "'");
                // Update callback comments in order status history table
                $this->update_callback_comments(array(
                    'order_no'              => $this->order_reference['order_no'],
                    'comments'              => $callback_comments,
                    'orders_status_id'      => constant('MODULE_PAYMENT_NOVALNET_'.($this->callback_params['payment_type']).'_ORDER_STATUS'),
                    'callback_total_amount' => $this->order_reference['order_paid_amount'],
                    'nnCaptureParams'       => $this->callback_params,
                    'order_total'           => $this->order_reference['order_amount']
                ));
            }
            // Show callback message
            $this->display_message('Novalnet Callbackscript received. Order already Paid');
        }else if ($this->callback_params['payment_type'] == 'PRZELEWY24' && ($this->callback_params['status'] != 100 || !in_array($this->callback_params['tid_status'], array(100,86)))) { // Cancel the przelewy24 payment when orignal transaction status other than 100
            //Update callback order status due to full payment
            xtc_db_perform(TABLE_ORDERS, array(
                'orders_status' => MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED
            ), 'update', 'orders_id="' . $this->order_reference['order_no'] . '"');
            // To form the server status message
            $server_error = NovalnetHelper::getServerResponse($this->callback_params);
            // To form the callback comments
            $callback_comments = PHP_EOL . 'Novalnet Callbackscript received. ' . sprintf(MODULE_PAYMENT_NOVALNET_CANCEL_ORDER_MESSAGE, $server_error) . PHP_EOL;
            // Update callback comments in order status history table
            $this->update_callback_comments(array(
                'order_no'              => $this->order_reference['order_no'],
                'comments'              => $callback_comments,
                'orders_status_id'      => MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED,
                'callback_total_amount' => $this->order_reference['order_paid_amount'],
                'nnCaptureParams'       => $this->callback_params,
                'order_total'           => $this->order_reference['order_amount']
            ));
        }else if(in_array($this->callback_params['payment_type'],array('GUARANTEED_INVOICE','GUARANTEED_DIRECT_DEBIT_SEPA','INVOICE_START','DIRECT_DEBIT_SEPA')) && in_array($this->callback_params['tid_status'], array(91,99,100)) && $this->callback_params['status'] == 100 && in_array($this->order_reference['current_order_previous_status'] ,array(75,91,99))){
                $param = array();
                $callback_comments = $transaction_comments =  $transactionCommentsForm = '';
                $test_mode_text     = ($this->callback_params['test_mode'] == 1) ? MODULE_PAYMENT_NOVALNET_TEST_ORDER_MESSAGE : '';
                $next_charge_transactionCommentsForm ='';
               
                if(in_array($this->callback_params['payment_type'],array('GUARANTEED_INVOICE','GUARANTEED_DIRECT_DEBIT_SEPA'))){
                    if(in_array($this->callback_params['payment_type'],array('GUARANTEED_INVOICE')) && $this->callback_params['tid_status'] == '100'  ){
						$transaction_comments .= MODULE_PAYMENT_NOVALNET_TRANSACTION_ID;
						$nn_comments = PHP_EOL . $transaction_comments . $this->callback_params['shop_tid'] . PHP_EOL . $test_mode_text.PHP_EOL;
					}
                }
				if( $next_charge_transactionCommentsForm != '')
					$nn_comments = PHP_EOL . $next_charge_transactionCommentsForm.PHP_EOL;
					
                if( in_array($this->callback_params['tid_status'],array(99,91)) && $this->order_reference['current_order_previous_status'] == 75){
					
                    $order_status = constant('MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_COMPLETE');
                    $callback_comments .= PHP_EOL. sprintf(MODULE_PAYMENT_GUARANTEE_PAYMENT_PENDING_TO_HOLD_MESSAGE, $this->callback_params['shop_tid'],date(DATE_FORMAT, strtotime(date('d.m.Y'))), date('H:i:s'));
                    
                    if($this->callback_params['tid_status'] == 91){
						//new
						$serialize_data = unserialize($this->order_reference['current_order_payment_details']);
                        list($transactionCommentsForm, $bank_details)  = NovalnetHelper::formInvoicePrepaymentComments(array(
                            'invoice_account_holder'   => $serialize_data['account_holder'],
                            'invoice_bankname'         => $serialize_data['bank_name'],
                            'invoice_bankplace'        => $serialize_data['bank_city'],
                            'amount'                   => sprintf("%.2f", ($serialize_data['amount'] / 100)),
                            'currency'                 => $serialize_data['currency'],
                            'tid'                      => $serialize_data['tid'],
                            'invoice_iban'             => $serialize_data['bank_iban'],
                            'invoice_bic'              => $serialize_data['bank_bic'],
                            'due_date'                 => $this->callback_params['due_date'],
                            'tid_status'               => $this->callback_params['tid_status']
                            ));
                        $vendor_details = array('product'=>$this->callback_params['product_id'],
                                                'order_no'=> $this->order_reference['order_no'],
                                                'tid'=> $this->callback_params['shop_tid'],
                        );
						
                    $transactionCommentsForm .= NovalnetHelper::formInvoicePrepaymentPaymentReference($this->order_reference['current_order_payment_ref'],$this->order_reference['payment_type'],$vendor_details);
                        $param['payment_details'] = serialize($serialize_data);
                        NovalnetHelper::guarantee_mail(array(
                            'comments' => '<br>' . $callback_comments.PHP_EOL. $nn_comments .PHP_EOL.$transactionCommentsForm,
                            'order_no' => $this->order_reference['order_no'],
                        ));
				}
                   
                } else if($this->callback_params['tid_status'] == 100 && in_array( $this->order_reference['current_order_previous_status'], array(75,91,99))){
                    if((($this->callback_params['payment_type'] == 'GUARANTEED_INVOICE') &&  $this->order_reference['current_order_previous_status'] == 75)){
                        $order_status =  constant('MODULE_PAYMENT_NOVALNET_INVOICE_CALLBACK_ORDER_STATUS');
                    } elseif((($this->callback_params['payment_type'] == 'GUARANTEED_DIRECT_DEBIT_SEPA') &&  $this->order_reference['current_order_previous_status'] == 75)) {
                        $order_status =constant('MODULE_PAYMENT_NOVALNET_SEPA_ORDER_STATUS');
                    }else{
                        $order_status = constant('MODULE_PAYMENT_' .strtoupper($this->order_reference['payment_type']). '_ORDER_STATUS');
                    }
                    $callback_comments .= PHP_EOL.sprintf(MODULE_PAYMENT_NOVALNET_GUARANTEE_TRANS_CONFIRM_SUCCESSFUL_MESSAGE, date(DATE_FORMAT, strtotime(date('d.m.Y'))),date('H:i:s')).PHP_EOL;

                    if(in_array($this->callback_params['payment_type'],array('GUARANTEED_INVOICE','INVOICE_START')) && in_array($this->order_reference['current_order_previous_status'], array(75, 91))){

                        $serialize_data = unserialize($this->order_reference['current_order_payment_details']);
                        list($transactionCommentsForm, $bank_details)  = NovalnetHelper::formInvoicePrepaymentComments(array(
                            'invoice_account_holder'   => $serialize_data['account_holder'],
                            'invoice_bankname'         => $serialize_data['bank_name'],
                            'invoice_bankplace'        => $serialize_data['bank_city'],
                            'amount'                   => sprintf("%.2f", ($serialize_data['amount'] / 100)),
                            'currency'                 => $serialize_data['currency'],
                            'tid'                      => $serialize_data['tid'],
                            'invoice_iban'             => $serialize_data['bank_iban'],
                            'invoice_bic'              => $serialize_data['bank_bic'],
                            'due_date'                 => $this->callback_params['due_date'],
                            'tid_status'               => $this->callback_params['tid_status']
                            ));
                        $vendor_details = array('product'=>$this->callback_params['product_id'],
                                                'order_no'=> $this->order_reference['order_no'],
                                                'tid'=> $this->callback_params['shop_tid'],
                        );
                        $transactionCommentsForm .= NovalnetHelper::formInvoicePrepaymentPaymentReference($this->order_reference['current_order_payment_ref'],$this->order_reference['payment_type'],$vendor_details);
                        $param['payment_details'] = serialize($serialize_data);
                        NovalnetHelper::guarantee_mail(array(
                            'comments' => '<br>' . $callback_comments.PHP_EOL. $nn_comments .PHP_EOL.$transactionCommentsForm,
                            'order_no' => $this->order_reference['order_no'],
                        ));
                    }
                }
                $param ['gateway_status'] = $this->callback_params['tid_status'];
                xtc_db_perform('novalnet_transaction_detail', $param, "update", "tid='" . $this->callback_params['shop_tid'] . "'");
                // Update the order status in shop
                xtc_db_perform(TABLE_ORDERS, array(
                    'orders_status' => $order_status
                ), 'update', 'orders_id="' . $this->order_reference['order_no'] . '"');
                xtc_db_perform(TABLE_ORDERS_STATUS_HISTORY, array(
                    'orders_id'        => $this->order_reference['order_no'],
                    'orders_status_id' => $order_status,
                    'date_added'       => date("Y-m-d H:i:s"),
                    'customer_notified'=> 1,
                    'comments'         => $callback_comments. $nn_comments.$transactionCommentsForm 
                ));
                // Send notification mail to Merchant
                $this->send_notify_mail(array(
                    'comments'      => $callback_comments,
                    'order_no'      => $this->order_reference['order_no'],
                ));
        }else if(in_array($this->callback_params['payment_type'],array('GUARANTEED_INVOICE','GUARANTEED_DIRECT_DEBIT_SEPA')) && $this->callback_params['tid_status'] != 100 && $this->callback_params['status'] != 100 && in_array($this->order_reference['current_order_previous_status'], array(75,91,99))){
                //Update callback order status due to full payment
                xtc_db_perform(TABLE_ORDERS, array(
                    'orders_status' => MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED
                ), 'update', 'orders_id="' . $this->order_reference['order_no'] . '"');
                // To form the server status message
                $callback_comments = '';
                if(in_array($this->callback_params['payment_type'],array('GUARANTEED_INVOICE','GUARANTEED_DIRECT_DEBIT_SEPA'))){
                    $callback_comments .= MODULE_PAYMENT_NOVALNET_MENTION_PAYMENT_CATEGORY.PHP_EOL;
                }
                $test_mode_text     = ($this->callback_params['test_mode'] == 1) ? MODULE_PAYMENT_NOVALNET_TEST_ORDER_MESSAGE : '';
                $callback_comments .= MODULE_PAYMENT_NOVALNET_TRANSACTION_ID.$this->callback_params['shop_tid'].PHP_EOL.$test_mode_text.PHP_EOL;
                $server_error = NovalnetHelper::getServerResponse($this->callback_params);
                $callback_comments .= PHP_EOL.sprintf(MODULE_PAYMENT_GUARANTEE_PAYMENT_CANCELLED_MESSAGE, $this->callback_params['shop_tid'],date(DATE_FORMAT, strtotime(date('d.m.Y'))), date('H:i:s')) . PHP_EOL;
                $param ['gateway_status'] = $this->callback_params['tid_status'];
                xtc_db_perform('novalnet_transaction_detail', $param, "update", "tid='" . $this->callback_params['shop_tid'] . "'");

                // Update callback comments in order status history table
                $this->update_callback_comments(array(
                    'order_no'              => $this->order_reference['order_no'],
                    'comments'              => $callback_comments,
                    'orders_status_id'      => MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED,
                    'callback_total_amount' => $this->order_reference['order_paid_amount'],
                    'nnCaptureParams'       => $this->callback_params,
                    'order_total'           => $this->order_reference['order_amount']
                ));
        }else if (in_array($this->callback_params['payment_type'],array('PAYPAL','CREDITCARD')) && in_array($this->order_reference['current_order_previous_status'], array(85,98,90))){
                //Update callback order status from hold to confirm
                xtc_db_perform(TABLE_ORDERS, array(
                    'orders_status' => constant('MODULE_PAYMENT_'.strtoupper($this->order_reference['payment_type']).'_ORDER_STATUS')
                ), 'update', 'orders_id="' . $this->order_reference['order_no'] . '"');
                
                $callback_comments = PHP_EOL . sprintf(MODULE_PAYMENT_NOVALNET_GUARANTEE_TRANS_CONFIRM_SUCCESSFUL_MESSAGE, date(DATE_FORMAT, strtotime(date('d.m.Y'))),date('H:i:s')) . PHP_EOL;
                $param = array('gateway_status'  => $this->callback_params['tid_status']);
                xtc_db_perform('novalnet_transaction_detail', $param, "update", "tid='" . $this->callback_params['shop_tid'] . "'");
                // Update callback comments in order status history table
                $this->update_callback_comments(array(
                    'order_no'              => $this->order_reference['order_no'],
                    'comments'              => $callback_comments,
                    'orders_status_id'      => constant('MODULE_PAYMENT_NOVALNET_'.($this->callback_params['payment_type']).'_ORDER_STATUS'),
                    'callback_total_amount' => $this->order_reference['order_paid_amount'],
                    'nnCaptureParams'       => $this->callback_params,
                    'order_total'           => $this->order_reference['order_amount']
                ));
         
		}else{
            // To display the message
            $this->display_message('Novalnet Callbackscript received. Payment type (' . $this->callback_params['payment_type'] . ') is not applicable for this process!');
        }
    }

    /**
     * Payment types of Level 1 - Chargeback payments processing
     */
    function first_level_process() {
        // To get bookback comments or charge back comments
        $callback_comments = in_array($this->callback_params['payment_type'], array('PAYPAL_BOOKBACK', 'REFUND_BY_BANK_TRANSFER_EU','CREDITCARD_BOOKBACK', 'PRZELEWY24_REFUND','CASHPAYMENT_REFUND','GUARANTEED_INVOICE_BOOKBACK','GUARANTEED_SEPA_BOOKBACK')) ? sprintf(PHP_EOL.MODULE_PAYMENT_NOVALNET_CALLBACK_BOOKBACK_COMMENTS, $this->callback_params['tid_payment'], $this->formatted_amount, date("Y-m-d"), date("H:i:s"), $this->callback_params['tid']) . PHP_EOL : PHP_EOL . sprintf(MODULE_PAYMENT_NOVALNET_CALLBACK_CHARGEBACK_COMMENTS, $this->callback_params['tid_payment'], $this->formatted_amount, date("Y-m-d", strtotime(date("Y-m-d")) . ' days'), date("H:i:s"), $this->callback_params['tid']) . PHP_EOL;
        //Update the comments , order id and status id in Novalnet table
        $this->update_callback_comments(array(
            'order_no'          => $this->order_reference['order_no'],
            'comments'          => $callback_comments,
            'orders_status_id'  => $this->order_reference['order_current_status'],
            'nnCaptureParams'   => $this->callback_params,
            'order_total'       => $this->order_reference['order_amount']
        ));
    }

    /**
     * Payment types of Level 2 - Credit entry and collection payments processing
     */
    function second_level_process() {
        // To proceed the collection payment process
        if (in_array($this->callback_params['payment_type'], array('CASHPAYMENT_CREDIT','INVOICE_CREDIT','ONLINE_TRANSFER_CREDIT'))) {
            $callback_secound_level_payment_execute = false;
            if ($this->order_reference['order_paid_amount'] < $this->order_reference['order_amount']) {
                $callback_secound_level_payment_execute = true;
                $callback_status_id = $this->order_reference['order_current_status']; // partial payment paid after order status updated in shop
                if ($this->order_reference['order_amount'] <= ($this->order_reference['order_paid_amount'] + $this->callback_params['amount'])) {
                    //Full payment paid after order status updated in shop
                    if($this->callback_params['payment_type'] == 'ONLINE_TRANSFER_CREDIT') {
                        $callback_status_id = $this->order_reference['order_current_status'];
                    }else {
						$callback_status_id = constant('MODULE_PAYMENT_'.strtoupper($this->order_reference['payment_type']).'_CALLBACK_ORDER_STATUS');
					}
                    $test_mode_text     = ($this->callback_params['test_mode'] == 1) ? 'Test order' : '';
                    list($test_order_msg, $tid_details) = array(
                        $test_mode_text,
                        MODULE_PAYMENT_NOVALNET_TRANSACTION_ID
                    );
                    $nn_comments = PHP_EOL . $tid_details . $this->callback_params['shop_tid'] . PHP_EOL . $test_order_msg . PHP_EOL;
                    //Update callback order status and comments
                    xtc_db_perform(TABLE_ORDERS, array(
                        'comments'      => $nn_comments,
                        'orders_status' => $callback_status_id,
                    ), 'update', 'orders_id="' . $this->order_reference['order_no'] . '"');
                }
            }
            // To display the message
            if(!$callback_secound_level_payment_execute)
                $this->display_message('Novalnet callback received. Callback Script executed already. Refer Order:' . $this->order_reference['order_no']);
        }else {
            $callback_secound_level_payment_execute = true;
            $callback_status_id = $this->order_reference['order_current_status'];
        }
        if($callback_secound_level_payment_execute){
			$callback_comments       = PHP_EOL . sprintf(MODULE_PAYMENT_INVOICE_CREDIT_COMMENTS, $this->callback_params['shop_tid'], $this->formatted_amount, date("Y-m-d", strtotime(date("Y-m-d")) . ' days'), date("H:i:s"), $this->callback_params['tid']) . PHP_EOL;
			if($this->callback_params['payment_type'] == 'ONLINE_TRANSFER_CREDIT') {
				$callback_comments  .= MODULE_PAYMENT_ONLINE_TRANSFER_CREDIT_COMMENTS;
			}
            // Update callback comments in order status history table
            $this->update_callback_comments(array(
                'order_no'              => $this->order_reference['order_no'],
                'comments'              => $callback_comments,
                'orders_status_id'      => $callback_status_id,
                'nnCaptureParams'       => $this->callback_params,
                'callback_total_amount' => $this->order_reference['order_paid_amount'],
                'order_total'           => $this->order_reference['order_amount']
            ));
        }
        // To display the message
        $this->display_message('Novalnet Callbackscript received. Payment type (' . $this->callback_params['payment_type'] . ') is not applicable for this process!');
    }

    /*
     * Get order reference from the novalnet_callback_history table
     *
     * @return array
     */
     function get_order_reference() {
        $transaction_details  = xtc_db_fetch_array(xtc_db_query("SELECT payment_type,order_amount, order_no, sum(callback_amount) AS callback_total_amount  FROM novalnet_callback_history WHERE original_tid = " . xtc_db_input($this->callback_params['shop_tid']))); // Get transaction details from Novalnet tables
        $transaction_info  = xtc_db_fetch_array(xtc_db_query("SELECT gateway_status,payment_details,payment_ref  FROM novalnet_transaction_detail WHERE tid = " . xtc_db_input($this->callback_params['shop_tid']))); // Get transaction details from Novalnet tables
        $transaction_details['current_order_previous_status'] = $transaction_info['gateway_status'];
        $transaction_details['current_order_payment_details'] = $transaction_info['payment_details'];
        $transaction_details['current_order_payment_ref'] = $transaction_info['payment_ref'];
        $get_order_no = ($transaction_details['order_no'] != '') ? $transaction_details['order_no'] : $this->callback_params['order_no'];
        $get_shop_payment_details = xtc_db_fetch_array(xtc_db_query('SELECT payment_method,customers_id,language,orders_status FROM '.TABLE_ORDERS.' WHERE orders_id = '.xtc_db_input($get_order_no)));
        list($transaction_details['order_current_status'], $transaction_details['nn_order_lang']) = $this->get_order_current_status($get_order_no);
        NovalnetHelper::loadLocaleContents($transaction_details['nn_order_lang']);
            if (!empty($transaction_details['order_no'])) { // Check db value
                if (empty($get_shop_payment_details['payment_method']) || strpos($get_shop_payment_details['payment_method'], 'novalnet')) {
                    list($subject, $message) = $this->build_notification_mail();
                    // Send E-mail, if transaction not found
                    $this->send_notify_mail(array(
                        'comments'      => $message,
                        'order_no'      => $transaction_details['order_no'],
                    ),false,true,$subject);
                }
                $transaction_details['tid'] = $this->callback_params['shop_tid'];
                $transaction_details['payment_type'] = $get_shop_payment_details['payment_method'] ;
                // To get paid amount information from  shop
                $transaction_details['order_paid_amount'] = 0;
                // To proceed the payment type level 0 and 2
                if (in_array($this->get_payment_type_level(), array(0, 2))) {
                    // Get paid callback amount from db
                    $transaction_details['order_paid_amount'] = ((isset($transaction_details['callback_total_amount'])) ? $transaction_details['callback_total_amount'] : 0);
                }
                // To validate the payment type for original transaction
                if ((!isset($transaction_details['payment_type']) || !in_array($this->callback_params['payment_type'], $this->payment_groups[$transaction_details['payment_type']])) && $this->callback_params['payment_type'] != 'TRANSACTION_CANCELLATION' ) {
                    $this->display_message('Novalnet callback received. Payment Type [' . $this->callback_params['payment_type'] . '] is not valid.');
                }
                // Check order no for the current transaction
                if (!empty($this->callback_params['order_no']) && $this->callback_params['order_no'] != $transaction_details['order_no']) {
                    $this->display_message('Novalnet callback received. Order Number is not valid.');
                }
                return $transaction_details;
            } else if(empty($transaction_details['order_no']) && !empty($this->callback_params['order_no'])) {
                // Handle communication failure.
                $this->handle_communication_failure($get_shop_payment_details);
            } else {
                // If order number is not available in Novalnet table, throw this message
                $this->display_message('Transaction mapping failed');
            }
    }
    /*
     * Update the communication failure
     *
     * @param $order_table_details array
     * @return array
     */
   function handle_communication_failure($order_table_details){ 
        if (!empty($order_table_details['payment_method']) && strpos($order_table_details['payment_method'], 'novalnet') !== false) {
            // Form payment transactions comments
            $transaction_comments = PHP_EOL . MODULE_PAYMENT_NOVALNET_TRANSACTION_ID . $this->callback_params['tid'] . ((int)(!empty($this->callback_params['test_mode']) || constant('MODULE_PAYMENT_' . strtoupper($order_table_details['payment_method']) . '_TEST_MODE') == 'True') ? PHP_EOL . MODULE_PAYMENT_NOVALNET_TEST_ORDER_MESSAGE . PHP_EOL : '');
            
            if ((!isset($order_table_details['payment_method']) || !in_array($this->callback_params['payment_type'], $this->payment_groups[$order_table_details['payment_method']])) && $this->callback_params['payment_type'] != 'TRANSACTION_CANCELLATION') {
                    $this->display_message('Novalnet callback received. Payment Type [' . $this->callback_params['payment_type'] . '] is not valid.');
            }
			// Get vendor and authcode details.
			$vendor_id        = trim(MODULE_PAYMENT_NOVALNET_VENDOR_ID);
			$vendor_authcode = trim(MODULE_PAYMENT_NOVALNET_AUTHCODE);
			if ($vendor_id != $this->callback_params['vendor_id']) {
			$affiliate = xtc_db_query('SELECT aff_authcode FROM  '.DB_PREFIX.'novalnet_aff_account_detail WHERE aff_id = "'.xtc_db_input($this->callback_params['vendor_id']).'"');
				if(!empty($affiliate['aff_authcode'])) {
					$vendor_id        = $this->callback_params['vendor_id'];
					$vendor_authcode = $affiliate['aff_authcode'];
				}
			}
			// Get tariff details.
			$tariff_details   = explode('-', MODULE_PAYMENT_NOVALNET_TARIFF_ID);
			$tariff           = $tariff_details[1];
			$datas = array(
				'payment_type'=>$order_table_details['payment_method'],
				'order_no'=> $this->callback_params['order_no']
			);
			NovalnetHelper::testMailNotification($datas,$this->callback_params);
			xtc_db_perform('novalnet_transaction_detail', array(
			'tid'                   => $this->callback_params['shop_tid'],
			'vendor'                => $vendor_id,
			'product'               => MODULE_PAYMENT_NOVALNET_PRODUCT_ID,
			'tariff_id'             => $tariff,
			'auth_code'             => $vendor_authcode,
			'payment_id'            => $this->callback_params['payment_id'],
			'payment_type'          => $order_table_details['payment_method'],
			'amount'                => $this->callback_params['amount'],
			'currency'              => $this->callback_params['currency'],
			'gateway_status'        => $this->callback_params['tid_status'],
			'order_no'              => $this->callback_params['order_no'],
			'novalnet_order_date'   => date('Y-m-d H:i:s'),
			'test_mode'             => (int)(!empty($this->callback_params['test_mode']) || constant('MODULE_PAYMENT_' . strtoupper($order_table_details['payment_method']) . '_TEST_MODE') == 'True') ? '1' :'0',
			'payment_details'       => '',
			'customer_id'           => $order_table_details['customer_id'],
			'reference_transaction' => '',
			'payment_ref'           => '',
			'next_payment_date'     => '',
			'process_key'           => '',
			'refund_amount'         => ''), "insert");
		xtc_db_perform('novalnet_callback_history', array(
			 'callback_datetime'    => date('Y-m-d H:i:s'),
			 'payment_type'         => $order_table_details['payment_method'],
			 'original_tid'         => $this->callback_params['shop_tid'],
			 'callback_tid'         => '',
			 'order_amount'         => $this->callback_params['amount'],
			 'callback_amount'      => (in_array($order_table_details['payment_method'], array('novalnet_invoice', 'novalnet_prepayment','novalnet_cashpayment')) || in_array($this->callback_params['tid_status'], array(85,86,90)) || !in_array($this->callback_params['tid_status'], array(100))) ? '0' : $this->callback_params['amount'],
			 'order_no'             => $this->callback_params['order_no'],
			), "insert");
			
            // Check for success tarnsaction.
            if(!empty($this->callback_params['tid_status']) && in_array($this->callback_params['tid_status'], array('100','99','98','91','90','85','86'))) {
                // Set paypal pending status.
                if(($this->callback_params['payment_type'] == 'PAYPAL' && in_array($this->callback_params['tid_status'],array('90','85'))) || ($this->callback_params['payment_type'] == 'PRZELEWY24' && $this->callback_params['tid_status'] == '86')) {
                    $order_status = constant('MODULE_PAYMENT_' . strtoupper($order_table_details['payment_method']) . '_PENDING_ORDER_STATUS');
                } else {
                    $order_status = constant('MODULE_PAYMENT_' . strtoupper($order_table_details['payment_method']) . '_ORDER_STATUS');
                }
            }else {
                $test_order_message = !empty($this->callback_params['test_mode']) ? MODULE_PAYMENT_NOVALNET_TEST_ORDER_MESSAGE . PHP_EOL : '';
                $order_comments = MODULE_PAYMENT_NOVALNET_TRANSACTION_ID . $this->callback_params['shop_tid'].PHP_EOL.$test_order_message;
                $transaction_status_failed = NovalnetHelper::getServerResponse($this->callback_params);
                $transaction_comments = sprintf(MODULE_PAYMENT_NOVALNET_CANCEL_ORDER_MESSAGE,$transaction_status_failed);
                $cancel_order_comments = $order_comments.PHP_EOL.$transaction_comments;
                xtc_db_perform(TABLE_ORDERS, array(
                    'orders_status' => '99',
                    'comments'      => $cancel_order_comments
                ), "update", "orders_id='".$this->callback_params['order_no']."'");
                xtc_db_perform(TABLE_ORDERS_STATUS_HISTORY, array(
                    'orders_status_id' => '99',
                    'comments'         => $cancel_order_comments
                ), "update", "orders_id='".$this->callback_params['order_no']."'");
                $this->send_notify_mail(array(
                        'comments'      => $cancel_order_comments,
                        'order_no'      => $this->callback_params['order_no'],
                ),false,true);
                $this->display_message($cancel_order_comments,$this->callback_params['order_no']);
            }
            // Update the comments.
            if($this->callback_params['payment_type'] != 'ONLINE_TRANSFER_CREDIT') {
                // Update Novalnet transaction comments and status.
                xtc_db_query("UPDATE ".TABLE_ORDERS." SET orders_status='".$order_status."', comments='".$transaction_comments."' WHERE orders_id='".xtc_db_input($this->callback_params['order_no'])."'");

                xtc_db_query("UPDATE ".TABLE_ORDERS_STATUS_HISTORY." SET orders_status_id='".$order_status."', comments='".$transaction_comments."' WHERE orders_id='".xtc_db_input($this->callback_params['order_no'])."'");
                $_SESSION['language'] = $order_table_details['language']; 
                $language_id = xtc_db_fetch_array(xtc_db_query("SELECT languages_id FROM " . TABLE_LANGUAGES . " WHERE directory = '" . $order_table_details['language'] ."'"));
                $this->recurring_mail(array(
                    'comments' => '<br>' . $transaction_comments,
                    'order_no' => $this->callback_params['order_no'],
                    'language' => $order_table_details['language'],
                    'language_id' => $language_id['languages_id'],
                ));
                unset($_SESSION['language']);
                $this->display_message('Novalnet Callback Script executed successfully, Transaction details are updated');
            }
        } else {
            list($subject, $message) = $this->build_notification_mail();
            // Send E-mail, if transaction not found
            $this->send_notify_mail(array(
                'comments'      => $message,
                'order_no'      => $this->callback_params['order_no'],
            ),false,true,$subject);
            $this->display_message($message,$this->callback_params['order_no']);
        }
    }

    /**
     * Build the Notification Message
     *
     * @return array
     */
    function build_notification_mail() {

        $subject = sprintf(' Critical error on shop system %s order not found for TID: %s',STORE_NAME, $this->callback_params['shop_tid']);
        $message = 'Dear Technic team,'.PHP_EOL.PHP_EOL.'&emsp;&emsp;Please evaluate this transaction and contact our payment module team at Novalnet'.PHP_EOL.PHP_EOL;
        $params = array(
        'vendor_id' => 'Merchant ID', 'product_id' => 'Project ID', 'tid' => 'TID', 'tid_payment' => 'TID Payment', 'tid_status' => 'TID status', 'order_no' => 'Order no', 'payment_type' => 'Payment type', 'email' => 'E-mail');
        foreach($params as $key => $value) {
            if (!empty($this->callback_params[$key])) {
                $message .= "$value: " . $this->callback_params[$key] . PHP_EOL;
            }
        }
        $message .= PHP_EOL.'Regards,'.PHP_EOL.'Novalnet Team';
        return array($subject, $message);
    }
    /**
     * Get orders details from the orders table
     *
     * @param integer $order_id
     * @return array
     */
    function get_order_current_status($order_id) {
        // To get the order status from database
        $order_details = xtc_db_fetch_array(xtc_db_query("select orders_status, language from " . TABLE_ORDERS . " where orders_id = " . xtc_db_input($order_id)));
        return array(
            $order_details['orders_status'],
            $order_details['language']
        );
    }

    /*
     * Update the transaction details in Novalnet table
     *
     * @param array $datas
     *
     */
    function update_callback_comments($datas) {
        // To update order details in shop
        xtc_db_perform(TABLE_ORDERS_STATUS_HISTORY, array(
            'orders_id'        => $datas['order_no'],
            'orders_status_id' => $datas['orders_status_id'],
            'date_added'       => date("Y-m-d H:i:s"),
            'customer_notified'=> 1,
            'comments'         => !empty($datas['comments']) ? $datas['comments'] : ''
        ));
        // To update order details in novalnet tables
        if ($this->get_payment_type_level() != 1 || ($datas['payment_type'] == 'PRZELEWY24' && $datas['tid_status'] != 86)) {
            $this->log_callback_process($datas);
        }
        // Send notification mail to Merchant
        $this->send_notify_mail(array(
            'comments'      => $datas['comments'],
            'order_no'      => $datas['order_no'],
        ));
    }

    /**
     * Display the error message
     *
     * @param string $message
     * @param int $order_no
     */
    function display_message($message,$order_no = null) {
        $message = 'message='.$message;
        if($order_no != '')
            $message .= '&order_no='.$order_no;
        echo utf8_decode($message);
        exit;
    }

    /**
     * Creation of order for recurring process
     *
     * @param integer $order_no
     * @param string $callback_comments
     * @param array $transaction_details
     * @param string $callback_comments_recurring
     */
    function create_new_order($order_no, $callback_comments, $transaction_details,$callback_comments_recurring) {
        $order_details = xtc_db_fetch_array(xtc_db_query("SELECT*FROM " . TABLE_ORDERS . " WHERE orders_id = " . xtc_db_input($order_no)));
        unset($order_details['orders_id']);
        $order_details['date_purchased'] = $order_details['last_modified'] = date("Y-m-d H:i:s");
        $customer_dbvalue = xtc_db_fetch_array(xtc_db_query("SELECT customers_email_address, customers_cid, customers_gender, customers_dob, customers_fax,customers_telephone FROM " . TABLE_CUSTOMERS . " WHERE customers_id=".$order_details['customers_id']));
        $addressbook_dbvalue = xtc_db_fetch_array(xtc_db_query("SELECT entry_firstname,entry_lastname,entry_gender, entry_company, entry_street_address,entry_house_number,entry_suburb,entry_city,entry_postcode,entry_state,entry_country_id FROM " . TABLE_ADDRESS_BOOK . " WHERE customers_id=".$order_details['customers_id']));
        $updated_country = xtc_db_fetch_array(xtc_db_query("select countries_name from " . TABLE_COUNTRIES . " where countries_id = '" . (int)$addressbook_dbvalue['entry_country_id'] . "'"));
        $update_column = array('firstname','lastname','gender','company','street_address','house_number','suburb','city','postcode','state');
        foreach($update_column as $update_value){
            $order_details['customers_'.$update_value] = $addressbook_dbvalue['entry_'.$update_value];
            $order_details['delivery_'.$update_value] = $addressbook_dbvalue['entry_'.$update_value];
            $order_details['billing_'.$update_value] = $addressbook_dbvalue['entry_'.$update_value];
        }
        $order_details['customers_country'] = $order_details['delivery_country'] =  $order_details['billing_country'] = $updated_country['countries_name'];
        $order_details['billing_name'] = $order_details['delivery_name'] = $order_details['customers_name'] = $addressbook_dbvalue['entry_firstname'].$addressbook_dbvalue['entry_lastname'];
        $order_details['customers_telephone'] = $customer_dbvalue['customers_telephone'];
        $order_details['customers_email_address'] = $customer_dbvalue['customers_email_address'];
        xtc_db_perform(TABLE_ORDERS, $order_details, 'insert');
        $order_id       = xtc_db_insert_id();
        $order_total_qry = xtc_db_query("SELECT title, text, value, class, sort_order FROM " . TABLE_ORDERS_TOTAL . " where orders_id = " . xtc_db_input($order_no));
        while ($order_total_array = xtc_db_fetch_array($order_total_qry)) {
            $order_total_array['orders_id'] = $order_id;
            xtc_db_perform(TABLE_ORDERS_TOTAL, $order_total_array);
        }
        $order_products_qry = xtc_db_query("SELECT * FROM " . TABLE_ORDERS_PRODUCTS . " where orders_id = " . xtc_db_input($order_no));
        while ($order_products_array = xtc_db_fetch_array($order_products_qry)) {
            unset($order_products_array['orders_id']);
            $order_products_id = $order_products_array['orders_products_id'];
            unset($order_products_array['orders_products_id']);
            $order_products_array['orders_id'] = $order_id;
            xtc_db_perform(TABLE_ORDERS_PRODUCTS, $order_products_array);
            $orders_products_id  = xtc_db_insert_id();
            $products_qry        = xtc_db_query("select products_quantity, products_ordered from " . TABLE_PRODUCTS . " where products_id = " . xtc_db_input($order_products_array['products_id']));
            $products_array      = xtc_db_fetch_array($products_qry);
            $products_quantity   = $products_array['products_quantity'] - $order_products_array['products_quantity'];
            $products_ordered    = $products_array['products_ordered'] + $order_products_array['products_quantity'];
            ($products_quantity < 1) ? xtc_db_query("update " . TABLE_PRODUCTS . " set products_quantity = '" . $products_quantity . "', products_ordered = '" . $products_ordered . "', products_status = '0' where products_id = '" . $order_products_array['products_id'] . "'") : xtc_db_query("update " . TABLE_PRODUCTS . " set products_quantity = '" . $products_quantity . "', products_ordered = '" . $products_ordered . "' where products_id = '" . $order_products_array['products_id'] . "'");
            $order_products_attr_qry = xtc_db_query("SELECT products_options, products_options_values, options_values_price, price_prefix FROM " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " where orders_id = " . xtc_db_input($order_id) . " AND orders_products_id=" . xtc_db_input($order_products_id));
            while ($order_products_attr_array = xtc_db_fetch_array($order_products_attr_qry)) {
                $order_products_attr_array['orders_id']          = $order_id;
                $order_products_attr_array['orders_products_id'] = $order_products_id;
                xtc_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $order_products_attr_array);
            }
            if (xtc_db_num_rows(xtc_db_query('SHOW TABLES LIKE "' . TABLE_ORDERS_PRODUCTS_DOWNLOAD . '"'))) {
                $order_products_downQry = xtc_db_query("SELECT * FROM " . TABLE_ORDERS_PRODUCTS_DOWNLOAD . " WHERE orders_id = " . xtc_db_input($order_id) . " AND orders_products_id=" . xtc_db_input($order_products_id));
                while ($order_products_down_array = xtc_db_fetch_array($order_products_downQry)) {
                    $order_products_down_array['orders_id']          = $order_id;
                    $order_products_down_array['orders_products_id'] = $orders_products_id;
                    xtc_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $order_products_down_array);
                }
            }
        }
        $new_order_status = ($this->callback_params['payment_type'] == 'PAYPAL' && in_array($this->callback_params['tid_status'], array( 85,90 ))) ? MODULE_PAYMENT_NOVALNET_PAYPAL_PENDING_ORDER_STATUS : constant('MODULE_PAYMENT_' . strtoupper($transaction_details['payment_type']) . '_ORDER_STATUS');
        xtc_db_perform(TABLE_ORDERS, array(
            'orders_status' => $new_order_status
        ), 'update', 'orders_id="' . $order_id . '"');
        $guarantee_order_comments = '';
        if(in_array($this->callback_params['payment_type'],array('GUARANTEED_INVOICE','GUARANTEED_DIRECT_DEBIT_SEPA'))){
            $guarantee_order_comments  .= MODULE_PAYMENT_NOVALNET_MENTION_PAYMENT_CATEGORY;
        }
        $test_mode               = !empty($this->callback_params['test_mode']) ? MODULE_PAYMENT_NOVALNET_TEST_ORDER_MESSAGE . PHP_EOL : '';
        $novalnet_order_comments = MODULE_PAYMENT_NOVALNET_TRANSACTION_ID . ' ' . $this->callback_params['tid'] . PHP_EOL . $test_mode.$charging_date_comments.PHP_EOL;
        list($order_comments, $bank_details) = (in_array($transaction_details['payment_type'], array('novalnet_invoice', 'novalnet_prepayment'))) ? $this->form_novalnet_comments($callback_comments) : array($novalnet_order_comments, '');

        // To create a new order
        NovalnetHelper::logInitialTransaction(array_merge(array(
            'recurring_tid'   => $this->callback_params['tid'],
            'callback_amount' => $this->callback_params['amount'],
            'recurring_amount' => $this->callback_params['amount'],
            'tid_status'      => $this->callback_params['tid_status'],
            'new_order_no'    => $order_id,
            'invoice_payment_details' => !empty($bank_details) ?serialize($bank_details) : '',
        ), $transaction_details));
        // Update the transaction information in shop
        if (!empty($order_comments)) {
            xtc_db_perform(TABLE_ORDERS, array(
                'orders_status' => $new_order_status,
                'comments'      => $guarantee_order_comments.$order_comments
            ), 'update', 'orders_id="' . $order_no . '"');
            xtc_db_perform(TABLE_ORDERS_STATUS_HISTORY, array(
            'orders_id'         => $order_id,
            'orders_status_id'  => $new_order_status,
            'date_added'        => date("Y-m-d H:i:s"),
            'customer_notified' => 1,
            'comments'          => $guarantee_order_comments.$order_comments
            ));
        }

        $this->recurring_mail(array(
                'order_no' => $order_no,
        ));
        self::send_notify_mail(array(
                'comments' => '<br>' .$callback_comments_recurring. $callback_comments,
                'order_no' => $order_id,
           ),true,false);
    }

    /**
     * To Form recurring order mail
     *
     * @param $datas array
     * @param $guarantee_bankdetails boolean
     *
     */
   function recurring_mail($datas,$guarantee_bankdetails=false){
            // GET MAIL CONTENTS ARRAY
            $order = new order($datas['order_no']);
            if($guarantee_bankdetails){
                $order->info['comments'] = $datas['comments'];
            }
            // GET WITHDRAWAL
            $coo_shop_content_control = MainFactory::create_object('ShopContentContentControl');
            // GET E-MAIL LOGO
            $t_mail_logo = '';
            $t_logo_mail = MainFactory::create_object('GMLogoManager', array("gm_logo_mail"));
            if($t_logo_mail->logo_use == '1')
            {
                $t_mail_logo = $t_logo_mail->get_logo();
            }

            $coo_send_order_content_view = MainFactory::create_object('SendOrderContentView');
            $coo_send_order_content_view->set_('order', $order);
            if($datas['order_no'] != ''){
                $coo_send_order_content_view->set_('order_id', $datas['order_no']);
            }
            if($datas['language'] != ''){
				$coo_send_order_content_view->set_('language', $datas['language']);
				$coo_send_order_content_view->set_('language_id', $datas['language_id']);
            }
            $coo_send_order_content_view->set_('mail_logo', $t_mail_logo);
            $t_mail_content_array = $coo_send_order_content_view->get_mail_content_array();
            // GET HTML MAIL CONTENT
            $t_content_mail = $t_mail_content_array['html'];
            // GET TXT MAIL CONTENT
            $t_txt_mail = $t_mail_content_array['txt'];
            $order_subject = sprintf(MODULE_PAYMENT_NOVALNET_ORDER_MAIL_SUBJECT,$datas['order_no'],date('l'),date('d.F Y'));
            
            // send mail to admin
            // BOF GM_MOD:
            if(SEND_EMAILS == 'true')
            {
                // get the sender mail adress. e.g. Host Europe has problems with the customer mail adress.
                $from_email_address = $order->customer['email_address'];
                if(SEND_EMAIL_BY_BILLING_ADRESS == 'SHOP_OWNER') {
                    $email_to = EMAIL_FROM;
                }
                    xtc_php_mail($from_email_address,
                                $order->customer['firstname'].' '.$order->customer['lastname'],
                                EMAIL_BILLING_ADDRESS,
                                STORE_NAME,
                                EMAIL_BILLING_FORWARDING_STRING,
                                $order->customer['email_address'],
                                $order->customer['firstname'].' '.$order->customer['lastname'],
                                $t_mail_attachment_array,
                                '',
                                $order_subject,
                                $t_content_mail,
                                $t_txt_mail
                   );
            }
            // send mail to customer
            // BOF GM_MOD:
            if (SEND_EMAILS == 'true')
            {
                xtc_php_mail(EMAIL_BILLING_ADDRESS,
                                                EMAIL_BILLING_NAME,
                                                $order->customer['email_address'],
                                                $order->customer['firstname'].' '.$order->customer['lastname'],
                                                '',
                                                EMAIL_BILLING_REPLY_ADDRESS,
                                                EMAIL_BILLING_REPLY_ADDRESS_NAME,
                                                $t_mail_attachment_array,
                                                '',
                                                $order_subject,
                                                $t_content_mail,
                                                $t_txt_mail
                );
            }
    }
    


    /*
     * Update affiliate account activation details in novalnet_aff_account_detail table
     *
     *  @param $datas array
     */
    function update_aff_account_activation($datas) {
        // Update the affiliate account in Novalnet affiliate table
        xtc_db_perform('novalnet_aff_account_detail', array(
            'vendor_id'       => $datas['vendor_id'],
            'vendor_authcode' => $datas['vendor_authcode'],
            'product_id'      => $datas['product_id'],
            'product_url'     => $datas['product_url'],
            'aff_accesskey'   => $datas['aff_accesskey'],
            'activation_date' => (($datas['activation_date'] != '') ? date("Y-m-d H:i:s", strtotime($datas['activation_date'])) : date("Y-m-d H:i:s")),
            'aff_id'          => $datas['aff_id'],
            'aff_authcode'    => $datas['aff_authcode']
        ), 'insert');
        // Send the notification mail to merchant
        $this->send_notify_mail(array(
            'comments' => 'Novalnet callback script executed successfully with Novalnet account activation information.',
            'order_no' => '',
        ));
    }

    /**
     * Log callback process in novalnet_callback_history table
     *
     * @param array $datas
     */
    function log_callback_process($datas) {
        // Update the transaction details in novalnet table
        xtc_db_perform('novalnet_callback_history', array(
            'payment_type'      => $datas['nnCaptureParams']['payment_type'],
            'original_tid'      => $datas['nnCaptureParams']['shop_tid'],
            'callback_tid'      => $datas['nnCaptureParams']['tid'],
            'order_amount'      => $datas['order_total'],
            'callback_amount'   => $datas['nnCaptureParams']['amount'],
            'order_no'          => $datas['order_no'],
            'callback_datetime' => date("Y-m-d H:i:s")
        ), 'insert');
        if (!empty($datas['nnCaptureParams']['amount'])) {
            xtc_db_perform('novalnet_transaction_detail', array(
                'gateway_status'  => $datas['nnCaptureParams']['tid_status'],
            ), 'update', "order_no = '" . xtc_db_input($datas['order_no']) . "'");
        }
    }
    

    /**
     * Get the bank details for invoice & prepayment
     *
     * @param $charging_date_comments string
     * @return array
     */
    function form_novalnet_comments($charging_date_comments) {
        $novalnet_comments = PHP_EOL . MODULE_PAYMENT_NOVALNET_TRANSACTION_ID . $this->callback_params['tid'] . PHP_EOL;
        $novalnet_comments .= !empty($this->callback_params['test_mode']) ? MODULE_PAYMENT_NOVALNET_TEST_ORDER_MESSAGE . PHP_EOL : '';
        $novalnet_comments .= $charging_date_comments.PHP_EOL;
        $novalnet_comments .= MODULE_PAYMENT_NOVALNET_INVOICE_COMMENTS_PARAGRAPH . PHP_EOL;
        $novalnet_comments .= MODULE_PAYMENT_NOVALNET_DUE_DATE . date("d.m.Y", strtotime(!empty($this->callback_params['due_date']) ? $this->callback_params['due_date'] : '')) . PHP_EOL;
        $novalnet_comments .= MODULE_PAYMENT_NOVALNET_ACCOUNT_HOLDER . ': '.$this->callback_params['invoice_account_holder'] . PHP_EOL;
        $novalnet_comments .= MODULE_PAYMENT_NOVALNET_IBAN.': '. (!empty($this->callback_params['invoice_iban']) ? $this->callback_params['invoice_iban'] : '') . PHP_EOL;
        $novalnet_comments .= 'BIC: ' . (!empty($this->callback_params['invoice_bic']) ? $this->callback_params['invoice_bic'] : '') . PHP_EOL;
        $novalnet_comments .= MODULE_PAYMENT_NOVALNET_BANK . (!empty($this->callback_params['invoice_bankname']) ? trim($this->callback_params['invoice_bankname']) : ''). PHP_EOL;
        $novalnet_comments .= MODULE_PAYMENT_NOVALNET_AMOUNT . xtc_format_price_order(($this->callback_params['amount'] / 100), 1, $this->callback_params['currency']) . PHP_EOL;
        $bank_details = array(
            'tid'            => $this->callback_params['tid'],
            'account_holder' => $this->callback_params['invoice_account_holder'],
            'amount'         => $this->callback_params['amount'] * 100,
            'currency'       => $this->callback_params['currency'],
            'bank_iban'      => $this->callback_params['invoice_iban'],
            'bank_bic'       => $this->callback_params['invoice_bic'],
            'due_date'       => $this->callback_params['due_date'],
            'bank_name'      => utf8_decode($this->callback_params['invoice_bankname']),
        );
        return array($novalnet_comments, $bank_details);
    }


   /**
     * Send notification mail to Merchant
     *
     * @param array $datas
     * @param boolean $$create_new_order
     * @param string $$subject
     * @param boolean $$order_fail
     */
    function send_notify_mail($datas,$create_new_order = false,$order_fail=false,$subject='') {
         $datas['comments'] = str_replace(PHP_EOL, '<br/>', $datas['comments']);
        if (MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_SEND == 'True') { // Check whether the notification mail option is enabled or not
            // Assign email to address
            $email_to  = ($order_fail) ? $this->technic_notify_mail : ((MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_TO != '') ? MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_TO : STORE_OWNER_EMAIL_ADDRESS);
            // Assign bcc in email
            $email_bcc = ((MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_BCC != '') ? MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_BCC : '');
            $order_subject = ($subject == '') ? 'Novalnet Callback Script Access Report '.STORE_NAME  : $subject .STORE_NAME;
            // Send mail
            xtc_php_mail(EMAIL_FROM, STORE_NAME, $email_to, STORE_OWNER, $email_bcc, '', '', '', '', $order_subject, $datas['comments'], '');
        }
        if(!$order_fail){
            // Display callback message
             $this->display_message($datas['comments'],$datas['order_no']);
        }
    }
}

