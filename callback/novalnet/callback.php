<?php
/**
 * Novalnet payment module
 * 
 * This file is used for real time processing of
 * Novalnet transaction of customers.
 *
 * Copyright (c) Novalnet
 *
 * Released under the GNU General Public License
 * This free contribution made by request.
 * 
 * If you have found this file useful a small
 * recommendation as well as a comment on merchant form
 * would be greatly appreciated.
 *
 * File: callback.php
 *
 */

chdir('../../');
require_once('includes/application_top.php');
include(DIR_FS_CATALOG . 'release_info.php');
global $gx_version;
if ($gx_version >= 'v3.1.1.0') {
	include_once(DIR_FS_CATALOG . 'vendor/true/punycode/src/Punycode.php');
}
include(DIR_FS_INC . 'xtc_format_price_order.inc.php');
require_once(DIR_FS_INC . 'xtc_php_mail.inc.php');
require_once (DIR_FS_CATALOG . 'gm/inc/gm_save_order.inc.php');
include_once(DIR_FS_CATALOG . 'includes/external/novalnet/NovalnetHelper.class.php');
$callback_parameter = array_map('trim', $_REQUEST); // Assign callback parameters
new novalnet_vendor_script($callback_parameter); // Novalnet callback class object
class novalnet_vendor_script {

    // Payment types of Level 0 - Initial level payments
    protected $payments = array('CREDITCARD', 'INVOICE_START', 'DIRECT_DEBIT_SEPA', 'GUARANTEED_INVOICE', 'PAYPAL', 'ONLINE_TRANSFER', 'IDEAL', 'EPS', 'GIROPAY', 'PRZELEWY24', 'GUARANTEED_DIRECT_DEBIT_SEPA','CASHPAYMENT');

    // Payment types of Level 1 - Chargeback payments
    protected $chargebacks_payments = array('PRZELEWY24_REFUND', 'RETURN_DEBIT_SEPA', 'CREDITCARD_BOOKBACK', 'PAYPAL_BOOKBACK', 'CREDITCARD_CHARGEBACK', 'REFUND_BY_BANK_TRANSFER_EU', 'REVERSAL','CASHPAYMENT_REFUND');

    // Payment types of Level 2 - Credit entry and collection payments
    protected $collection_payments = array('ONLINE_TRANSFER_CREDIT', 'CASHPAYMENT_CREDIT', 'INVOICE_CREDIT', 'CREDIT_ENTRY_CREDITCARD', 'CREDIT_ENTRY_SEPA', 'DEBT_COLLECTION_SEPA', 'DEBT_COLLECTION_CREDITCARD');

    // Payment type of subscription
    protected $subscription_payment = array('SUBSCRIPTION_STOP');

    // Payment types Group */
    protected $payment_groups = array(
		'novalnet_cc'         => array('CREDITCARD', 'CREDITCARD_BOOKBACK', 'CREDITCARD_CHARGEBACK', 'CREDIT_ENTRY_CREDITCARD', 'SUBSCRIPTION_STOP', 'DEBT_COLLECTION_CREDITCARD'),
		'novalnet_sepa'       => array('DIRECT_DEBIT_SEPA', 'REFUND_BY_BANK_TRANSFER_EU','RETURN_DEBIT_SEPA', 'SUBSCRIPTION_STOP', 'DEBT_COLLECTION_SEPA', 'CREDIT_ENTRY_SEPA', 'GUARANTEED_DIRECT_DEBIT_SEPA'),
		'novalnet_ideal'      => array('IDEAL', 'REFUND_BY_BANK_TRANSFER_EU', 'REVERSAL','ONLINE_TRANSFER_CREDIT'),
		'novalnet_instantbank'=> array('ONLINE_TRANSFER', 'ONLINE_TRANSFER_CREDIT', 'REFUND_BY_BANK_TRANSFER_EU', 'REVERSAL'),
		'novalnet_paypal'     => array('PAYPAL', 'SUBSCRIPTION_STOP', 'PAYPAL_BOOKBACK','REFUND_BY_BANK_TRANSFER_EU'),
		'novalnet_prepayment' => array('INVOICE_START', 'INVOICE_CREDIT', 'SUBSCRIPTION_STOP'),
		'novalnet_cashpayment' => array('CASHPAYMENT', 'CASHPAYMENT_CREDIT', 'CASHPAYMENT_REFUND'),
		'novalnet_invoice'    => array('INVOICE_START', 'INVOICE_CREDIT', 'SUBSCRIPTION_STOP', 'GUARANTEED_INVOICE'),
		'novalnet_eps'        => array('EPS','REFUND_BY_BANK_TRANSFER_EU','ONLINE_TRANSFER_CREDIT'),
		'novalnet_giropay'    => array('GIROPAY','REFUND_BY_BANK_TRANSFER_EU','ONLINE_TRANSFER_CREDIT'),
		'novalnet_przelewy24' => array('PRZELEWY24', 'PRZELEWY24_REFUND'));
    protected $callback_params = array();
    protected $order_reference = array();
    protected $params_required = array();
    protected $affiliated_params = array();
    protected $process_testmode;

    // Novalnet IP address is a fixed value. DO NOT CHANGE!!!!!
    protected $ip_allowed = array('195.143.189.210', '195.143.189.214');
    
    function __construct($capture_parameter) {
        $this->process_testmode  = (MODULE_PAYMENT_NOVALNET_CALLBACK_TEST_MODE == 'True');
        $this->validate_ip_address(); // Validate IP address
        $this->params_required   = array('vendor_id', 'tid', 'payment_type', 'status', 'tid_status');
        $this->affiliated_params = array('vendor_id', 'vendor_authcode', 'product_id', 'aff_id', 'aff_accesskey', 'aff_authcode');
        if (isset($capture_parameter['subs_billing']) && $capture_parameter['subs_billing'] == 1) { // Add signup_tid parameter as mandatory for subscription
            array_push($this->params_required, 'signup_tid');
        } else if (in_array($capture_parameter['payment_type'], array_merge($this->chargebacks_payments, array('CASHPAYMENT_CREDIT','INVOICE_CREDIT')))) { // Add tid_payment parameter as mandatory for collection/chargeback payments
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
        } else if ((($this->callback_params['payment_type'] == 'SUBSCRIPTION_STOP')) || (!empty($this->callback_params['subs_billing']) && $this->callback_params['status'] != 100 && $this->callback_params['payment_type'] != 'SUBSCRIPTION_STOP')) { // Cancel the subscription process
            $this->cancel_subscription();
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
		$client_ip = NovalnetHelper::getIpAddress(xtc_get_ip_address());
        if (!in_array($client_ip, $this->ip_allowed) && !$this->process_testmode) { // Check IP address
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
			if (!in_array($capture_parameter['payment_type'], array_merge($this->payments, $this->chargebacks_payments, $this->collection_payments, $this->subscription_payment))) { // Validate payment type
            $this->display_message('Novalnet callback received. Payment type (' . $capture_parameter['payment_type'] . ') is mismatched!');
			}
			// Assign original transaction id
			$capture_parameter['shop_tid'] = (!empty($capture_parameter['signup_tid'])) ? $capture_parameter['signup_tid'] : (in_array($capture_parameter['payment_type'], array_merge($this->chargebacks_payments, array('CASHPAYMENT_CREDIT','INVOICE_CREDIT'))) ? $capture_parameter['tid_payment'] : (!empty($capture_parameter['tid']) ? $capture_parameter['tid'] : ''));
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
		// To create recurring order subscription transaction
        if ($this->callback_params['subs_billing'] == 1 && $this->callback_params['status'] == 100 && in_array($this->callback_params['tid_status'], array( 85, 91,90, 98, 99, 100))) {
			$callback_comments = sprintf(MODULE_PAYMENT_NOVALNET_CALLBACK_RECURRING_COMMENTS, $this->callback_params['shop_tid'], $this->formatted_amount, date("Y-m-d", strtotime(date("Y-m-d")) . ' days'), date("H:i:s"), $this->callback_params['tid']);
			$charging_date = !empty($this->callback_params['next_subs_cycle']) ? $this->callback_params['next_subs_cycle'] : (!empty($this->callback_params['paid_until']) ? $this->callback_params['paid_until'] : '');
            $callback_comments .= sprintf(MODULE_PAYMENT_NOVALNET_CALLBACK_CHARGING_DATE_COMMENTS, $charging_date). PHP_EOL . sprintf(MODULE_PAYMENT_NOVALNET_CALLBACK_REFERENCE_TID_COMMENTS, $this->callback_params['shop_tid']). PHP_EOL;
            $this->create_new_order($this->order_reference['order_no'], $callback_comments, (array) $this->transaction_details);
        } else if ($this->callback_params['subs_billing'] == 1 && $this->callback_params['status'] != 100) { // Subscription cancel when recurring order status other than 100
            $this->cancel_subscription();
        } else if (in_array($this->callback_params['payment_type'], array('PAYPAL', 'PRZELEWY24')) && $this->callback_params['tid_status'] == 100 && $this->callback_params['status'] == 100) { // To process paypal and przelewy24 payment pending status
            if ($this->order_reference['order_paid_amount'] == 0) { // when paypal and przelewy24 payment in pending status only
                //Update callback order status due to full payment
                xtc_db_perform(TABLE_ORDERS, array(
                    'orders_status' => constant('MODULE_PAYMENT_NOVALNET_'.($this->callback_params['payment_type']).'_ORDER_STATUS')
                ), 'update', 'orders_id="' . $this->order_reference['order_no'] . '"');
                $callback_comments = PHP_EOL . sprintf(html_entity_decode(MODULE_PAYMENT_NOVALNET_CALLBACK_UPDATE_COMMENTS), $this->callback_params['tid'], $this->formatted_amount, date("Y-m-d", strtotime(date("Y-m-d")) . ' days'), date("H:i:s")) . PHP_EOL;
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
        } else if ($this->callback_params['payment_type'] == 'PRZELEWY24' && ($this->callback_params['status'] != 100 || !in_array($this->callback_params['tid_status'], array(100,86)))) { // Cancel the przelewy24 payment when orignal transaction status other than 100
            //Update callback order status due to full payment
            xtc_db_perform(TABLE_ORDERS, array(
                'orders_status' => MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED
            ), 'update', 'orders_id="' . $this->order_reference['order_no'] . '"');
            // To form the server status message
            $server_error = NovalnetHelper::getServerResponse($this->callback_params);
            // To form the callback comments
            $callback_comments = PHP_EOL . 'Novalnet Callbackscript received. ' . sprintf(MODULE_PAYMENT_NOVALNET_PRZELEWY_CANCEL_ORDER_MESSAGE, $server_error) . PHP_EOL;
            // Update callback comments in order status history table
            $this->update_callback_comments(array(
                'order_no'              => $this->order_reference['order_no'],
                'comments'              => $callback_comments,
                'orders_status_id'      => MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED,
                'callback_total_amount' => $this->order_reference['order_paid_amount'],
                'nnCaptureParams'       => $this->callback_params,
                'order_total'           => $this->order_reference['order_amount']
            ));
        } else {
			// To display the message
            $this->display_message('Novalnet Callbackscript received. Payment type (' . $this->callback_params['payment_type'] . ') is not applicable for this process!');
        }
    }

    /**
     * Payment types of Level 1 - Chargeback payments processing
     */
    function first_level_process() {
		// To get bookback comments or charge back comments
        $callback_comments = in_array($this->callback_params['payment_type'], array('PAYPAL_BOOKBACK', 'REFUND_BY_BANK_TRANSFER_EU','CREDITCARD_BOOKBACK', 'PRZELEWY24_REFUND','CASHPAYMENT_REFUND')) ? sprintf(PHP_EOL.MODULE_PAYMENT_NOVALNET_CALLBACK_BOOKBACK_COMMENTS, $this->callback_params['tid_payment'], $this->formatted_amount, date("Y-m-d"), date("H:i:s"), $this->callback_params['tid']) . PHP_EOL : PHP_EOL . sprintf(MODULE_PAYMENT_NOVALNET_CALLBACK_CHARGEBACK_COMMENTS, $this->callback_params['tid_payment'], $this->formatted_amount, date("Y-m-d", strtotime(date("Y-m-d")) . ' days'), date("H:i:s"), $this->callback_params['tid']) . PHP_EOL;
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
        if (in_array($this->callback_params['payment_type'], array('CASHPAYMENT_CREDIT','INVOICE_CREDIT'))) {
			if ($this->order_reference['order_paid_amount'] < $this->order_reference['order_amount']) {
				$callback_comments       = PHP_EOL . sprintf(MODULE_PAYMENT_INVOICE_CREDIT_COMMENTS, $this->callback_params['shop_tid'], $this->formatted_amount, date("Y-m-d", strtotime(date("Y-m-d")) . ' days'), date("H:i:s"), $this->callback_params['tid']) . PHP_EOL;
                $callback_status_id = $this->order_reference['order_current_status']; // partial payment paid after order status updated in shop
                if ($this->order_reference['order_amount'] <= ($this->order_reference['order_paid_amount'] + $this->callback_params['amount'])) {
                    //Full payment paid after order status updated in shop
                    $callback_status_id = constant('MODULE_PAYMENT_'.strtoupper($this->order_reference['payment_type']).'_CALLBACK_ORDER_STATUS');
                    $test_mode_text     = ($this->callback_params['test_mode'] == 1) ? 'Test order' : '';
                    list($test_order_msg, $tid_details) = array(
                        $test_mode_text,
                        MODULE_PAYMENT_NOVALNET_TRANSACTION_DETAILS . PHP_EOL . MODULE_PAYMENT_NOVALNET_TRANSACTION_ID
                    );
                    $nn_comments = PHP_EOL . $tid_details . $this->callback_params['shop_tid'] . PHP_EOL . $test_order_msg . PHP_EOL;
                    //Update callback order status and comments
                    xtc_db_perform(TABLE_ORDERS, array(
                        'comments'      => $nn_comments,
                        'orders_status' => $callback_status_id,
                    ), 'update', 'orders_id="' . $this->order_reference['order_no'] . '"');
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
            $this->display_message('Novalnet callback received. Callback Script executed already. Refer Order:' . $this->order_reference['order_no']);
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
			if (!empty($transaction_details['order_no'])) { // Check db value
				$get_shop_payment_name = xtc_db_fetch_array(xtc_db_query('SELECT payment_method FROM '.TABLE_ORDERS.' WHERE orders_id = '.xtc_db_input($transaction_details['order_no'])));
				$transaction_details['tid'] = $this->callback_params['shop_tid'];
				$transaction_details['payment_type'] = $get_shop_payment_name['payment_method'] ;
				list($transaction_details['order_current_status'], $transaction_details['nn_order_lang']) = $this->get_order_current_status($transaction_details['order_no']);
				NovalnetHelper::loadLocaleContents($transaction_details['nn_order_lang']);
				// To get paid amount information from  shop
				$transaction_details['order_paid_amount'] = 0;
				// To proceed the payment type level 0 and 2
				if (in_array($this->get_payment_type_level(), array(0, 2))) { 
					// Get paid callback amount from db
					$transaction_details['order_paid_amount'] = ((isset($transaction_details['callback_total_amount'])) ? $transaction_details['callback_total_amount'] : 0);
				}
				// To validate the payment type for original transaction
				if (!isset($transaction_details['payment_type']) || !in_array($this->callback_params['payment_type'], $this->payment_groups[$transaction_details['payment_type']])) {				
					$this->display_message('Novalnet callback received. Payment Type [' . $this->callback_params['payment_type'] . '] is not valid.');
				}
				// Check order no for the current transaction
				if (!empty($this->callback_params['order_no']) && $this->callback_params['order_no'] != $transaction_details['order_no']) { 
					$this->display_message('Novalnet callback received. Order Number is not valid.');
				}
				return $transaction_details;
			} else { 
				// If order number is not available in Novalnet table, throw this message
				$this->display_message('Transaction mapping failed');
			}
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
     * @return boolean
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
     */
    function display_message($message) {
        echo utf8_decode($message);
        exit;
    }

    /**
     * Creation of order for recurring process
     *
     * @param integer $order_no
     * @param string $callback_comments
     * @param array $transaction_details
     */
    function create_new_order($order_no, $callback_comments, $transaction_details) {
        $order_details = xtc_db_fetch_array(xtc_db_query("SELECT*FROM " . TABLE_ORDERS . " WHERE orders_id = " . xtc_db_input($order_no)));
        unset($order_details['orders_id']);
        $order_details['date_purchased'] = $order_details['last_modified'] = date("Y-m-d H:i:s");
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
        $test_mode               = !empty($this->callback_params['test_mode']) ? MODULE_PAYMENT_NOVALNET_TEST_ORDER_MESSAGE . PHP_EOL : '';
        $novalnet_order_comments = MODULE_PAYMENT_NOVALNET_TRANSACTION_DETAILS . PHP_EOL . MODULE_PAYMENT_NOVALNET_TRANSACTION_ID . ' ' . $this->callback_params['tid'] . PHP_EOL . $test_mode;
        list($order_comments, $bank_details) = (in_array($transaction_details['payment_type'], array('novalnet_invoice', 'novalnet_prepayment'))) ? $this->form_novalnet_comments() : array($novalnet_order_comments, '');
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
                'comments'      => $order_comments . $callback_comments
            ), 'update', 'orders_id="' . $order_no . '"');
            xtc_db_perform(TABLE_ORDERS_STATUS_HISTORY, array(
            'orders_id'         => $order_id,
            'orders_status_id'  => $new_order_status,
            'date_added'        => date("Y-m-d H:i:s"),
            'customer_notified' => 1,
            'comments'          => $order_comments . $callback_comments
			));
			xtc_db_query('UPDATE ' . TABLE_ORDERS_TOTAL . ' SET value = "' . ($this->callback_params['amount']) . '", text = "<b>' . xtc_format_price_order(($this->callback_params['amount'] / 100), 1, $this->callback_params['currency']) . '</b>" WHERE orders_id = "' . $order_id . '" AND (class = "ot_total" OR class = "ot_subtotal")');
        }
        xtc_db_perform('novalnet_subscription_detail', array(
            'order_no'           => $order_id,
            'subs_id'            => $transaction_details['subs_id'],
            'tid'                => $transaction_details['tid'],
            'signup_date'        => date("Y-m-d H:i:s"),
            'termination_reason' => '',
            'termination_at'     => ''
        ));
        self::send_notify_mail(array(
                'comments' => '<br>' . $callback_comments,
                'order_no' => $order_id,
           ),true);
    }

    /**
     * To process cancel the subscription
     *
     */
    function cancel_subscription() {
		// Get the subscription cancel message from server response
        $subs_termination_reason = ($this->callback_params['payment_type'] == 'SUBSCRIPTION_STOP' && $this->callback_params['termination_reason'] != '') ? $this->callback_params['termination_reason'] : NovalnetHelper::getServerResponse($this->callback_params);
        // Update the subscription reason in novalnet table
        $this->update_subscription_reason(array(
            'termination_reason' => $subs_termination_reason,
            'termination_at'     => date("Y-m-d H:i:s"),
            'tid'                => $this->callback_params['shop_tid']
        ));
        $callback_comments = PHP_EOL . sprintf(MODULE_PAYMENT_NOVALNET_CALLBACK_SUBS_STOP_COMMENTS, $this->callback_params['shop_tid'], date("Y-m-d", strtotime(date("Y-m-d")) . ' days'), date("H:i:s"));
        $callback_comments .= PHP_EOL . MODULE_PAYMENT_NOVALNET_SUBS_CANCEL_REASON_MESSAGE . $subs_termination_reason;
        $db_query = xtc_db_query('SELECT order_no from novalnet_subscription_detail WHERE tid = "' . $this->callback_params['shop_tid'] . '"');
        while ($row = xtc_db_fetch_array($db_query)) {
			$order_no= $row['order_no'];
            xtc_db_perform(TABLE_ORDERS, array(
                'orders_status'  => MODULE_PAYMENT_NOVALNET_SUBSCRIPTION_CANCEL,
            ), 'update', 'orders_id="' . $row['order_no'] . '"');
            xtc_db_perform(TABLE_ORDERS_STATUS_HISTORY, array(
				'orders_id'        => $row['order_no'],
				'orders_status_id' => MODULE_PAYMENT_NOVALNET_SUBSCRIPTION_CANCEL,
				'date_added'       => date("Y-m-d H:i:s"),
				'customer_notified'=> 1,
				'comments'         => $callback_comments
			));
        }
        $parent_order_db_query = xtc_db_fetch_array(xtc_db_query('SELECT order_no from novalnet_subscription_detail WHERE tid = "' . $this->callback_params['shop_tid'] . '"'));
        // Send notification mail to Merchant
        $this->send_notify_mail(array(
            'comments'      => $callback_comments,
            'order_no'      => $parent_order_db_query['order_no']
		));
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
     * update subscription details in novalnet_subscription_detail table
     *
     * @param array $datas
     */
    function update_subscription_reason($datas) {
		// Update the transaction details in novalnet tables
        xtc_db_perform('novalnet_subscription_detail', array(
            'termination_reason' => $datas['termination_reason'],
            'termination_at'     => $datas['termination_at']
        ), 'update', "tid = '" . $datas['tid'] . "'");
    }

    /**
     * Get the bank details for invoice & prepayment
     *
     * @return string
     */
    function form_novalnet_comments() {
        $novalnet_comments = PHP_EOL . MODULE_PAYMENT_NOVALNET_TRANSACTION_DETAILS . PHP_EOL . MODULE_PAYMENT_NOVALNET_TRANSACTION_ID . $this->callback_params['tid'] . PHP_EOL;
        $novalnet_comments .= !empty($this->callback_params['test_mode']) ? MODULE_PAYMENT_NOVALNET_TEST_ORDER_MESSAGE . PHP_EOL : '';
        $novalnet_comments .= MODULE_PAYMENT_NOVALNET_INVOICE_COMMENTS_PARAGRAPH . PHP_EOL;
        $novalnet_comments .= MODULE_PAYMENT_NOVALNET_DUE_DATE . date("m.d.Y", strtotime(!empty($this->callback_params['due_date']) ? $this->callback_params['due_date'] : '')) . PHP_EOL;
        $novalnet_comments .= MODULE_PAYMENT_NOVALNET_ACCOUNT_HOLDER . ': NOVALNET AG' . PHP_EOL;
        $novalnet_comments .= MODULE_PAYMENT_NOVALNET_IBAN.': '. (!empty($this->callback_params['invoice_iban']) ? $this->callback_params['invoice_iban'] : '') . PHP_EOL;
        $novalnet_comments .= 'BIC: ' . (!empty($this->callback_params['invoice_bic']) ? $this->callback_params['invoice_bic'] : '') . PHP_EOL;
        $novalnet_comments .= MODULE_PAYMENT_NOVALNET_BANK . (!empty($this->callback_params['invoice_bankname']) ? trim($this->callback_params['invoice_bankname']) : '') . ' ' . (!empty($this->callback_params['invoice_bankplace']) ? trim($this->callback_params['invoice_bankplace']) : '') . PHP_EOL;
        $novalnet_comments .= MODULE_PAYMENT_NOVALNET_AMOUNT . xtc_format_price_order(($this->callback_params['amount'] / 100), 1, $this->callback_params['currency']) . PHP_EOL;
        $bank_details = array(
            'tid'            => $this->callback_params['tid'],
            'account_holder' => 'NOVALNET AG',
            'amount'         => $this->callback_params['amount'] * 100,
            'currency'       => $this->callback_params['currency'],
            'bank_iban'      => $this->callback_params['invoice_iban'],
            'bank_bic'       => $this->callback_params['invoice_bic'],
            'due_date'       => $this->callback_params['due_date'],
            'bank_name'      => utf8_decode($this->callback_params['invoice_bankname']),
            'bank_city'      => utf8_decode($this->callback_params['invoice_bankplace'])

        );
        return array($novalnet_comments, $bank_details);
    }


    /**
     * Send notification mail to Merchant
     *
     * @param array $datas
     * @param boolean $$create_new_order
     */
    function send_notify_mail($datas,$create_new_order = false) {
		$datas['comments'] = str_replace(PHP_EOL, '<br/>', $datas['comments']);
        if (MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_SEND == 'True') { // Check whether the notification mail option is enabled or not
			// Assign email to address
            $email_to  = ((MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_TO != '') ? MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_TO : STORE_OWNER_EMAIL_ADDRESS);
            // Assign bcc in email
            $email_bcc = ((MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_BCC != '') ? MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_BCC : '');
			// Send mail
			// GET MAIL CONTENTS ARRAY
			$order = new order($datas['order_no']);

			// GET WITHDRAWAL
			$coo_shop_content_control = MainFactory::create_object('ShopContentContentControl');
			$t_mail_attachment_array = array();
			if($create_new_order) {
				if (gm_get_conf('ATTACH_CONDITIONS_OF_USE_IN_ORDER_CONFIRMATION') == 1)
				{
					$coo_shop_content_control->set_content_group('3');
					$t_mail_attachment_array[] = $coo_shop_content_control->get_file();
				}
			
				if(gm_get_conf('ATTACH_WITHDRAWAL_INFO_IN_ORDER_CONFIRMATION') == '1')
				{
					$coo_shop_content_control->set_content_group(gm_get_conf('GM_WITHDRAWAL_CONTENT_ID'));
					$t_mail_attachment_array[] = $coo_shop_content_control->get_file();
				}

				if(gm_get_conf('ATTACH_WITHDRAWAL_FORM_IN_ORDER_CONFIRMATION') == '1')
				{
					$coo_shop_content_control->set_content_group(gm_get_conf('GM_WITHDRAWAL_CONTENT_ID'));
					$coo_shop_content_control->set_withdrawal_form('1');
					$t_mail_attachment_array[] = $coo_shop_content_control->get_file();
				}
			} else {
				$order->info['comments'] = $datas['comments'];
			}
			$t_shop_content_query = xtc_db_query("SELECT
												content_title,
												content_heading,
												content_text,
												content_file
												FROM " . TABLE_CONTENT_MANAGER . "
												WHERE content_group='" . (int)gm_get_conf('GM_WITHDRAWAL_CONTENT_ID') . "' " . $group_check . "
												AND languages_id='" . $_SESSION['languages_id'] . "'");
			$t_shop_content_data = xtc_db_fetch_array($t_shop_content_query);
			$t_withdrawal = html_entity_decode_wrapper(trim(strip_tags($t_shop_content_data['content_text'])));
			// GET AGB
			$t_shop_content_query = xtc_db_query("SELECT
												content_title,
												content_heading,
												content_text,
												content_file
												FROM " . TABLE_CONTENT_MANAGER . "
												WHERE content_group='3' " . $group_check . "
												AND languages_id='" . $_SESSION['languages_id'] . "'");
			$t_shop_content_data = xtc_db_fetch_array($t_shop_content_query);
			$t_agb = html_entity_decode_wrapper(trim(strip_tags($t_shop_content_data['content_text'])));
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
			$coo_send_order_content_view->set_('credit_covers', $_SESSION['credit_covers']);
			$coo_send_order_content_view->set_('language', $_SESSION['language']);
			$coo_send_order_content_view->set_('language_id', $_SESSION['languages_id']);
			$coo_send_order_content_view->set_('language_code', $_SESSION['language_code']);
			$coo_send_order_content_view->set_('withdrawal', $t_withdrawal);
			$coo_send_order_content_view->set_('agb', $t_agb);
			$coo_send_order_content_view->set_('mail_logo', $t_mail_logo);
			if($datas['order_no'] == '' ){
				$coo_send_order_content_view->set_content_data('gm_logo_mail', $t_mail_logo);
				$t_mail_content_array['html'] = fetch_email_template($coo_send_order_content_view, 'novalnet_vendor_activation_mail', 'html', '', $_SESSION['languages_id'], $_SESSION['language']);
				$t_content_mail = $t_mail_content_array['html'];
				$t_mail_content_array['txt']      = fetch_email_template($coo_send_order_content_view, 'novalnet_vendor_activation_mail', 'txt', '', $_SESSION['languages_id'], $_SESSION['language']);
				$t_txt_mail = $t_mail_content_array['txt'];
			} else {
				$t_mail_content_array = $coo_send_order_content_view->get_mail_content_array();
				// GET HTML MAIL CONTENT
				$t_content_mail = $t_mail_content_array['html'];
				// GET TXT MAIL CONTENT
				$t_txt_mail = $t_mail_content_array['txt'];
			}
			$t_subject = 'Novalnet Callback script notification';
			$order_subject = str_replace('{$nr}', $order_id, $t_subject);
			$order_subject = str_replace('{$date}', utf8_encode_wrapper(strftime(DATE_FORMAT_LONG)), $order_subject);
			$order_subject = str_replace('{$lastname}', $order->customer['lastname'], $order_subject);
			$order_subject = str_replace('{$firstname}', $order->customer['firstname'], $order_subject);
			// send mail to admin
			// BOF GM_MOD:
			if(SEND_EMAILS == 'true')
			{
				// get the sender mail adress. e.g. Host Europe has problems with the customer mail adress.
				$from_email_address = $order->customer['email_address'];
				if(SEND_EMAIL_BY_BILLING_ADRESS == 'SHOP_OWNER') {
					$from_email_address = EMAIL_FROM;
				}
				xtc_php_mail($from_email_address,
							$order->customer['firstname'].' '.$order->customer['lastname'],
							$email_to,
							STORE_OWNER,
							$email_bcc,
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
			if (SEND_EMAILS == 'true' && $create_new_order)
			{
				$gm_mail_status = xtc_php_mail(EMAIL_BILLING_ADDRESS,
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
			if($gm_mail_status == false) {
				$gm_send_order_status = 0;
			} else {
				$gm_send_order_status = 1;
			}
			gm_save_order($order_id, $t_content_mail, $t_txt_mail, $gm_send_order_status);
        }
        // Display callback message
        $this->display_message($datas['comments']);
    }
}

