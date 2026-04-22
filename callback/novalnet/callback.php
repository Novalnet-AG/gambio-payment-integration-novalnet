<?php
/**
 * Novalnet payment module
 *
 * This script is used for auto configuration of merchant details
 *
 * @author    Novalnet AG
 * @copyright Copyright (c) Novalnet
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link      https://www.novalnet.de
 *
 * Script: novalnet_auto_config.php
 */
chdir('../../');

require_once 'includes/application_top.php';
require_once DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php';

class NovalnetWebhooks
{
    /**
     * Mandatory Parameters
     */
    protected $mandatory = [
        'event'       => [
            'type',
            'checksum',
            'tid'
        ],
        'result'      => [
            'status'
        ],
        'transaction' => [
            'tid',
            'payment_type',
            'status'
        ],
    ];

    /**
     * Request parameters.
     */
    protected $eventData;

    /**
     * Novalnet Transaction Details
     */
    protected $orderReference;

    /**
     * Shop order details
     */
    protected $order;

    /**
     * Received Event type.
     */
    protected $eventType;

    /**
     * Received Event TID.
     */
    protected $eventTid;

    /**
     * @var string
     */
    protected $formattedAmount;

    /**
     * Received Event parent TID.
     */
    protected $parentTid;

    /**
     * @var NovalnetHelper
     */
    protected $helper;

    /**
     * Core Function : Constructor()
     */
    public function __construct()
    {
        try {
            $this->eventData = json_decode(file_get_contents('php://input'), true);
            $this->helper = new NovalnetHelper();
        } catch (Exception $e) {
            $this->displayMessage([ 'message' => 'Received data is not in the JSON format' . $e]);
        }

        $this->authenticateEventData();

        // Set Event data.
        $this->eventType = $this->eventData ['event'] ['type'];
        $this->eventTid  = $this->eventData ['event'] ['tid'];
        $this->parentTid = !empty($this->eventData['event']['parent_tid']) ? $this->eventData['event']['parent_tid'] : $this->eventData['event']['tid'];

        if (! empty($this->eventData ['instalment']['cycle_amount'])) {
            $this->formattedAmount = $this->helper->formattedAmount($this->eventData ['instalment']['cycle_amount'] / 100, $this->eventData ['transaction'] ['currency']);
        } elseif (!empty($this->eventData ['transaction'] ['amount'])) {
            $this->formattedAmount = $this->helper->formattedAmount($this->eventData ['transaction'] ['amount'] / 100, $this->eventData ['transaction'] ['currency']);
        }

        $this->orderReference = $this->getOrderReference();

        if ('SUCCESS' == $this->eventData['result']['status'] && 'FAILURE' != $this->eventData['transaction']['status'] && (!empty($this->orderReference) || $this->eventType == 'PAYMENT')) {
            switch($this->eventType) {
                case 'PAYMENT':
                    $this->displayMessage(['message' => 'Novalnet Callback executed. The Transaction ID already existed']);
                    break;
                case 'TRANSACTION_CAPTURE':
                case 'TRANSACTION_CANCEL':
                    $this->handleTransactionCaptureVoid();
                    break;
                case 'TRANSACTION_REFUND':
                    $this->handleTransactionRefund();
                    break;
                case 'CREDIT':
                    $this->handleTransactionCredit();
                    break;
                case 'CHARGEBACK':
                case 'RETURN_DEBIT':
                case 'REVERSAL':
                    $this->handleChargeback();
                    break;
                case 'INSTALMENT':
                    $this->handleInstalment();
                    break;
                case 'INSTALMENT_CANCEL':
                    $this->handleInstalmentCancel();
                    break;
                case 'TRANSACTION_UPDATE':
                    $this->handleTransactionUpdate();
                    break;
                case 'PAYMENT_REMINDER_1':
                case 'PAYMENT_REMINDER_2':
                    $this->handlePaymentReminder();
                    break;
                case 'SUBMISSION_TO_COLLECTION_AGENCY':
                    $this->handleCollectionSubmission();
                    break;
                default:
                    $message = "The webhook notification has been received for the unhandled EVENT type('".$this->eventType."')";
                    $this->displayMessage(['message' => $message]);
            }
        } elseif ($this->eventData['transaction']['payment_type'] != 'ONLINE_TRANSFER_CREDIT') {
            $message = (!empty($this->eventData['result']['status']) && $this->eventData['result']['status'] != 'SUCCESS') ? 'Novalnet callback received. Status is not valid.' : 'Novalnet callback received. Callback Script executed already.';
            $this->displayMessage(['message' => $message]);
        } else {
            $this->displayMessage(['message' => xtc_db_prepare_input($this->eventData['result']['status_text'])]);
        }
    }

    /**
     * Authenticate server request
     */
    public function authenticateEventData()
    {
        if (gethostbyname('pay-nn.de')) {
            $validIp  = $this->checkWebhookIp(gethostbyname('pay-nn.de'));
            if (empty($validIp) && MODULE_PAYMENT_NOVALNET_CALLBACK_TEST_MODE == 'false') {
                $this->displayMessage(['message' => 'Unauthorised access from the IP ' . $this->helper->getIPAddress()]);
            }
        } else {
            $this->displayMessage([ 'message' => 'Unauthorised access from the IP. Host/recieved IP is empty' ]);
        }
        $this->validateEventData();
        $this->validateCheckSum();
    }

    /**
     * Get user remote ip address
     *
     * @param string $novalnetHostIp
     *
     * @return bool
     */
    public function checkWebhookIp(string $novalnetHostIp)
    {
        $ipKeys = ['HTTP_X_FORWARDED_HOST', 'HTTP_CLIENT_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];

        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                if (in_array($key, ['HTTP_X_FORWARDED_HOST', 'HTTP_X_FORWARDED_FOR'])) {
                    $forwardedIps = (!empty($_SERVER[$key])) ? explode(",", $_SERVER[$key]) : [];
                    if (in_array($novalnetHostIp, $forwardedIps)) {
                        return true;
                    }
                }

                if ($_SERVER[$key] ==  $novalnetHostIp) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Validate event data
     */
    public function validateEventData()
    {
        if (!empty($this->eventData['custom']['shop_invoked'])) {
            $this->displayMessage(['message' => 'Process already handled in the shop.']);
        }

        // Validate request parameters.
        foreach ($this->mandatory as $category => $parameters) {
            if (empty($this->eventData [ $category ])) {
                // Could be a possible manipulation in the notification data.
                $this->displayMessage([ 'message' => "Required parameter category($category) not received" ]);
            } elseif (! empty($parameters)) {
                foreach ($parameters as $parameter) {
                    if (empty($this->eventData [ $category ] [ $parameter ])) {
                        // Could be a possible manipulation in the notification data.
                        $this->displayMessage([ 'message' => "Required parameter($parameter) in the category($category) not received" ]);
                    } elseif (in_array($parameter, [ 'tid', 'parent_tid' ], true) && ! preg_match('/^\d{17}$/', (string) $this->eventData [ $category ] [ $parameter ])) {
                        $this->displayMessage([ 'message' => "Invalid TID received in the category($category) not received $parameter" ]);
                    }
                }
            }
        }
    }

    /**
     * Validate checksum
     */
    public function validateCheckSum()
    {
        $tokenString = $this->eventData ['event'] ['tid'] . $this->eventData ['event'] ['type'] . $this->eventData ['result'] ['status'];

        if (isset($this->eventData ['transaction'] ['amount'])) {
            $tokenString .= $this->eventData ['transaction'] ['amount'];
        }
        if (isset($this->eventData ['transaction'] ['currency'])) {
            $tokenString .= $this->eventData ['transaction'] ['currency'];
        }

        if (defined('MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY') && MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY !== '') {
            $tokenString .= strrev(trim(MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY));
        }

        $generatedChecksum = hash('sha256', $tokenString);

        if ($generatedChecksum !== $this->eventData ['event'] ['checksum']) {
            $this->displayMessage([ 'message' => 'While notifying some data has been changed. The hash check failed']);
        }
    }

    /*
     * Get order reference from the novalnet_callback_history table
     *
     * @return array
     */
    public function getOrderReference()
    {
        $daVal = NovalnetHelper::getNovalnetTransDetails(!empty($this->eventData['transaction']['order_no']) ? $this->eventData['transaction']['order_no'] : '', $this->parentTid);

        if (empty($daVal) && empty($this->eventData['transaction']['order_no'])) {
            $this->displayMessage(['message' => 'Order reference not found in the shop']);
        }

        $ordernumber = !empty($this->eventData['transaction']['order_no']) ? $this->eventData['transaction']['order_no'] : $daVal['order_no'];
        $orderRecord = xtc_db_fetch_array(xtc_db_query("SELECT * FROM " . TABLE_ORDERS . " WHERE orders_id =" . (int)$ordernumber));

        if ((empty($orderRecord)) || (!empty($orderRecord) && empty($orderRecord['orders_id']))) {
            $this->displayMessage(['message' => 'Order not found in the shop']);
        }

        $this->order = new order($ordernumber);
        // If the order in the Novalnet server to the order number in Novalnet database doesn't match, then there is an issue
        if (!empty($this->eventData['transaction']['order_no']) && !empty($this->order) && !empty($this->order->info['orders_id']) && $this->eventData['transaction']['order_no'] != $this->order->info['orders_id']) {
            $this->displayMessage(['message' => 'Order reference not matching for the order number ' . $this->order->info['orders_id']]);
        }

        $order_details = xtc_db_fetch_array(xtc_db_query("SELECT payment_method, payment_class, orders_id, customers_id, orders_status, comments, language FROM ".TABLE_ORDERS." WHERE orders_id = '".$ordernumber."'"));

        if (!empty($order_details) && is_array($order_details)) {
            $this->order->info = array_merge($this->order->info, $order_details);
        }

        // If the order is not found in Novalnet DB and found in core table, it is communication failure
        if (empty($daVal) && !empty($this->order) && (($this->eventType == 'PAYMENT' && (empty($this->order->info['comments']) || strpos($this->order->info['comments'], $this->parentTid) == false)) || $this->eventData['transaction']['payment_type'] == 'ONLINE_TRANSFER_CREDIT')) {
            $this->handleCommunicationFailure();
        }

        return $daVal;
    }

    /*
     * Update the communication failure
     *
     * @return array
     */
    public function handleCommunicationFailure()
    {
        if ($this->eventData['result']['status'] == 'SUCCESS') {
            $message = $this->order->info['comments'] = $this->helper->formCustomerComments(!empty($this->order->info['payment_method']) ? $this->order->info['payment_method'] : $this->order->info['payment_class'], $this->eventData);

            if ($this->eventData['transaction']['payment_type'] === 'ONLINE_TRANSFER_CREDIT') {
                $message .= $this->order->info['comments'] .= PHP_EOL . sprintf(NOVALNET_WEBHOOK_CREDIT_NOTE, $this->parentTid, $this->formattedAmount, gmdate('d-m-Y H:i:s'), $this->parentTid);
            }

            $this->helper->updateOrderStatus($this->order->info['orders_id'], !empty($this->order->info['payment_method']) ? $this->order->info['payment_method'] : $this->order->info['payment_class'], $this->order->info['comments'], $this->eventData);

            // Send order update email
            $this->helper->sendOrderUpdateMail(
                [
                    'communication_failure' => true,
                    'comments' => '<br>' . $message,
                    'order_no' => $this->order->info['orders_id'],
                    'order'    => $this->order
                ]
            );
            $this->displayMessage(['message' => $message]);
        } else {
            $statusText = $this->helper->getStatusDesc($this->eventData);
            $this->helper->updateTempOrderFail($this->order->info['orders_id'], $this->eventData, $statusText);
            $this->displayMessage(['message' => $statusText]);
        }
    }

    /**
     * Handle transaction capture
     */
    public function handleTransactionCaptureVoid()
    {
        if (in_array($this->orderReference['status'], ['ON_HOLD', 'PENDING']) || in_array($this->orderReference['status'], ['98', '99', '91', '85', '90', '86', '75'])) {
            $comments = '';
            $appendComments = true;
            $upsertData = [
                'tid'    => $this->orderReference['tid'],
                'status' => $this->eventData['transaction']['status']
            ];

            if ($this->eventType === 'TRANSACTION_CAPTURE') {
                if ($this->orderReference['payment_type'] == 'INVOICE') {
                    $this->eventData['transaction']['status'] = $upsertData['status'] = 'PENDING';
                }

                if (in_array($this->eventData['transaction']['status'], ['CONFIRMED', 'PENDING'])) {
                    if (!empty($this->orderReference['payment_details']) && !empty($this->orderReference['payment_type']) && in_array($this->orderReference['payment_type'], ['INSTALMENT_INVOICE', 'GUARANTEED_INVOICE', 'INVOICE', 'PREPAYMENT'])) {
                        if (empty($this->eventData['transaction']['bank_details'])) {
                            $this->eventData['transaction']['bank_details'] = $this->helper->unserializeData($this->orderReference['payment_details']);
                        }
                        $appendComments = false;
                        $comments .=  $this->helper->formCustomerComments(!empty($this->order->info['payment_method']) ? $this->order->info['payment_method'] : $this->order->info['payment_class'], $this->eventData);
                    }

                    if ($this->eventData['transaction']['status'] == 'CONFIRMED') {
                        $upsertData['paid_amount'] = $this->orderReference['amount'];
                        if (in_array($this->eventData['transaction']['payment_type'], ['INSTALMENT_INVOICE', 'INSTALMENT_DIRECT_DEBIT_SEPA'])) {
                            $this->eventData['transaction']['amount'] = $this->orderReference['amount'];
                            $upsertData['instalment_cycle_details'] = $this->helper->getInstalmentInformation($this->eventData);
                            $upsertData['instalment_cycle_details'] = $this->helper->serializeData($upsertData['instalment_cycle_details']);
                        }
                    }
                }

                $comments .= PHP_EOL . sprintf(MODULE_PAYMENT_NOVALNET_TRANS_CONFIRM_SUCCESSFUL_MESSAGE, gmdate('d-m-Y'), gmdate('H:i:s'));

                if (!empty($appendComments)) {
                    $comments = $this->order->info['comments'] . $comments;
                }
                // Send order update email
                $this->helper->sendOrderUpdateMail(
                    [
                        'comments' => '<br>' . $comments,
                        'order_no' => $this->order->info['orders_id'],
                        'order'    => $this->order
                    ]
                );
            } else {
                $comments .= sprintf(MODULE_PAYMENT_NOVALNET_TRANS_DEACTIVATED_MESSAGE, gmdate('d-m-Y'), gmdate('H:i:s'));
            }

            $orderStatus = ($this->eventType === 'TRANSACTION_CAPTURE') ? $this->helper->getOrderStatus($this->eventData['transaction']['status'], !empty($this->order->info['payment_method']) ? $this->order->info['payment_method'] : $this->order->info['payment_class']) : MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED;

            // Update database
            xtc_db_perform('novalnet_transaction_detail', $upsertData, 'update', 'order_no=' . $this->order->info['orders_id']);
            $this->helper->updateStatus($this->order->info['orders_id'], $orderStatus, $comments);
            $this->sendWebhookMail($comments);
            $this->displayMessage([ 'message' => xtc_db_prepare_input($comments)]);
        } else {
            // transaction already captured or transaction not been authorized.
            $this->displayMessage([ 'message' => 'Order already processed.']);
        }
    }

    /**
     * Handle transaction refund
     */
    public function handleTransactionRefund()
    {
        $comments = '';

        if (! empty($this->eventData['transaction']['refund']['amount'])) {
            $refundAmount = $this->eventData['transaction']['refund']['amount'];
        } else {
            $refundAmount = (int) $this->orderReference['amount'] - (int) $this->orderReference['refund_amount'];
        }

        if (!empty($refundAmount)) {
            $totalRefundedAmount = (int) $this->orderReference['refund_amount'] + (int) $refundAmount;

            if ($totalRefundedAmount <= $this->orderReference['amount']) {
                $comments .= PHP_EOL . sprintf(MODULE_PAYMENT_NOVALNET_REFUND_PARENT_TID_MSG, $this->parentTid, $this->helper->formattedAmount($refundAmount / 100, $this->eventData['transaction']['currency']));

                if (!empty($this->eventData['transaction']['refund']['tid'])) {
                    $comments .= PHP_EOL . sprintf(MODULE_PAYMENT_NOVALNET_REFUND_CHILD_TID_MSG, $this->eventData['transaction']['refund']['tid']);
                }

                $upsertData = [
                    'tid'           => $this->orderReference['tid'],
                    'status' => $this->eventData['transaction']['status'],
                    'refund_amount' => $totalRefundedAmount
                ];

                if (in_array($this->orderReference['payment_type'], ['INSTALMENT_INVOICE', 'INSTALMENT_DIRECT_DEBIT_SEPA'])) {
                    $instalmentDetails = $this->helper->unserializeData($this->orderReference['instalment_cycle_details']);
                    $instalmentDetails = $this->helper->updateInstalmentCycle($instalmentDetails, $refundAmount, $this->parentTid);
                    $upsertData['instalment_cycle_details'] = $this->helper->serializeData($instalmentDetails);
                }

                $orderStatus = ($upsertData['refund_amount'] >= $this->orderReference['amount']) ? MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED : $this->order->info['orders_status'];

                xtc_db_perform('novalnet_transaction_detail', $upsertData, 'update', 'order_no=' . $this->order->info['orders_id']);
                $this->helper->updateStatus($this->order->info['orders_id'], $orderStatus, $comments);
                $this->sendWebhookMail($comments);
                $this->displayMessage([ 'message' => xtc_db_prepare_input($comments)]);
            } else {
                $this->displayMessage([ 'message' => 'Already full amount refunded for this TID']);
            }
        } else {
            $this->displayMessage([ 'message' => 'Already full amount refunded for this TID']);
        }
    }

    /**
     * Handle chargeback
     */
    public function handleTransactionCredit()
    {
        $callbackComments = '';

        if ($this->eventData['transaction']['payment_type'] === 'ONLINE_TRANSFER_CREDIT') {
            $callbackComments = PHP_EOL . sprintf(NOVALNET_WEBHOOK_CREDIT_NOTE, $this->parentTid, $this->formattedAmount, gmdate('d-m-Y H:i:s'), $this->parentTid);
        } else {
            $callbackComments = PHP_EOL . sprintf(NOVALNET_WEBHOOK_CREDIT_NOTE, $this->parentTid, $this->formattedAmount, gmdate('d-m-Y H:i:s'), $this->eventTid);
        }

        $upsertData['status'] = $this->eventData['transaction']['status'];

        $orderStatus = $this->helper->getOrderStatus($this->eventData['transaction']['status'], !empty($this->order->info['payment_method']) ? $this->order->info['payment_method'] : $this->order->info['payment_class']);

        if (in_array($this->eventData['transaction']['payment_type'], ['INVOICE_CREDIT', 'CASHPAYMENT_CREDIT', 'MULTIBANCO_CREDIT', 'ONLINE_TRANSFER_CREDIT'])) {
            $paidAmount = (int) $this->orderReference['paid_amount'] + (int) $this->eventData['transaction']['amount'];

            // Calculate including refunded amount.
            $amountToBePaid = (int) $this->orderReference['amount'] - (int) $this->orderReference['refund_amount'];

            $upsertData['paid_amount'] = $paidAmount;

            if ($paidAmount >= $amountToBePaid || $this->eventData['transaction']['payment_type'] == 'ONLINE_TRANSFER_CREDIT') {
                $orderStatus = constant('MODULE_PAYMENT_'. strtoupper(!empty($this->order->info['payment_method']) ? $this->order->info['payment_method'] : $this->order->info['payment_class']) .'_CALLBACK_ORDER_STATUS');
            }
        }
        xtc_db_perform('novalnet_transaction_detail', $upsertData, 'update', 'order_no=' . $this->order->info['orders_id']);
        $this->helper->updateStatus($this->order->info['orders_id'], $orderStatus, $callbackComments);
        $this->sendWebhookMail($callbackComments);
        $this->displayMessage([ 'message' => xtc_db_prepare_input($callbackComments)]);
    }

    /**
     * Handle chargeback
     */
    public function handleChargeback()
    {
        if ($this->orderReference['status'] == 'CONFIRMED' && !empty($this->eventData ['transaction'] ['amount'])) {
            $comments = PHP_EOL . sprintf(NOVALNET_WEBHOOK_CHARGEBACK_NOTE, $this->parentTid, $this->formattedAmount, gmdate('d.m.Y'), gmdate('H:i:s'), $this->eventTid);
            $orderStatus = $this->helper->getOrderStatus($this->eventData['transaction']['status'], !empty($this->order->info['payment_method']) ? $this->order->info['payment_method'] : $this->order->info['payment_class']);
            $this->helper->updateStatus($this->order->info['orders_id'], $orderStatus, $comments);
            $this->sendWebhookMail($comments);
            $this->displayMessage([ 'message' => xtc_db_prepare_input($comments)]);
        }
    }

    /**
     * Handle instalment
     */
    public function handleInstalment()
    {
        $comments = '';
        if (in_array($this->eventData['transaction']['payment_type'], ['INSTALMENT_INVOICE', 'INSTALMENT_DIRECT_DEBIT_SEPA']) && $this->orderReference['status'] == 'CONFIRMED') {
            $comments .=  $this->helper->formCustomerComments(!empty($this->order->info['payment_method']) ? $this->order->info['payment_method'] : $this->order->info['payment_class'], $this->eventData);
        }

        $comments .= PHP_EOL . sprintf(NOVALNET_WEBHOOK_NEW_INSTALMENT_NOTE, $this->parentTid, $this->eventTid, $this->formattedAmount, gmdate('d-m-Y'));
        $orderStatus = $this->helper->getOrderStatus($this->eventData['transaction']['status'], !empty($this->order->info['payment_method']) ? $this->order->info['payment_method'] : $this->order->info['payment_class']);

        xtc_db_perform('novalnet_transaction_detail', ['instalment_cycle_details' => $this->updateInstalmentInfo()], 'update', 'order_no=' . $this->order->info['orders_id']);
        $this->helper->updateStatus($this->order->info['orders_id'], $orderStatus, $comments);

        // Send order update email
        $this->helper->sendOrderUpdateMail(
            [
                'instalment' => true,
                'comments' => '<br>' . $comments,
                'order_no' => $this->order->info['orders_id'],
                'order'    => $this->order
            ]
        );

        $this->sendWebhookMail($comments);
        $this->displayMessage([ 'message' => xtc_db_prepare_input($comments)]);
    }

    /**
     * Handle instalment cancel
     */
    public function handleInstalmentCancel()
    {
        $callbackComments = '';
        if ($this->orderReference['status'] == 'CONFIRMED') {
            $cancelType = !empty($this->eventData['instalment']['cancel_type']) ? $this->eventData['instalment']['cancel_type'] : 'ALL_CYCLES';
            $updatedInfo['status'] = ($cancelType === 'ALL_CYCLES') ? 'DEACTIVATED' : 'CONFIRMED';
            $instalmentDetails = $this->helper->unserializeData($this->orderReference['instalment_cycle_details']);
            if (isset($this->eventData['transaction']['refund'])) {
                $totalRefundedAmount = $this->orderReference['amount'];
                $refundedAmountInBiggerUnit = $this->helper->formattedAmount($totalRefundedAmount / 100, $this->orderReference['currency']);
                $callbackComments .= PHP_EOL . sprintf(MODULE_PAYMENT_NOVALNET_INSTALMENT_CANCEL_ALLCYCLES_TEXT, $this->orderReference['tid'], date('Y-m-d H:i:s'));
            } else {
                $callbackComments = PHP_EOL . sprintf(MODULE_PAYMENT_NOVALNET_INSTALMENT_CANCEL_REMAINING_CYCLES_TEXT, $this->orderReference['tid'], date('Y-m-d H:i:s'));
                $totalRefundedAmount = $this->orderReference['refund_amount'];
                foreach ($instalmentDetails as $instalment) {
                    $totalRefundedAmount += empty($instalment['reference_tid']) ? $instalment['cycle_amount'] : 0;
                }
            }

            $instalmentDetails = $this->helper->updateInstalmentCancel($instalmentDetails, $cancelType);
            $updatedInfo['refund_amount'] = $totalRefundedAmount;
            $updatedInfo['instalment_cycle_details'] = $this->helper->serializeData($instalmentDetails);

            $orderStatus = ($cancelType === 'ALL_CYCLES') ? MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED : $this->order->info['orders_status'];

            xtc_db_perform('novalnet_transaction_detail', $updatedInfo, 'update', 'order_no=' . $this->order->info['orders_id']);
            $this->helper->updateStatus($this->order->info['orders_id'], $orderStatus, $callbackComments);
            $this->sendWebhookMail($callbackComments);
            $this->displayMessage([ 'message' => xtc_db_prepare_input($callbackComments)]);
        } else {
            $this->displayMessage([ 'message' => 'Novalnet callback received. Callback Script executed already.']);
        }
    }

    /**
     * Handle transaction update
     */
    public function handleTransactionUpdate()
    {
        if (in_array($this->eventData['transaction']['status'], array('PENDING', 'ON_HOLD', 'CONFIRMED', 'DEACTIVATED'))) {
            $callbackComments = '';
            $orderStatus = $this->order->info['orders_status'];
            $updatedInfo = ['status' => $this->eventData ['transaction']['status']];

            if (!empty($this->eventData['transaction']['update_type']) && in_array($this->eventData['transaction']['update_type'], ['DUE_DATE', 'AMOUNT_DUE_DATE'])) {
                $updatedInfo['amount'] = $this->eventData['transaction']['amount'];

                $dueDate = date('d/m/Y', strtotime($this->eventData['transaction']['due_date']));
                $callbackComments .= PHP_EOL . sprintf(NOVALNET_WEBHOOK_TRANSACTION_UPDATE_NOTE_DUE_DATE, $this->eventTid, $this->formattedAmount, $dueDate);
            } elseif ($this->eventData['transaction']['update_type'] === 'STATUS') {
                if (in_array($this->eventData['transaction']['payment_type'], ['GUARANTEED_INVOICE', 'INSTALMENT_INVOICE', 'INVOICE', 'GUARANTEED_DIRECT_DEBIT_SEPA', 'INSTALMENT_DIRECT_DEBIT_SEPA'])) {
                    if (!empty($this->orderReference['payment_details'])) {
                        $this->eventData['transaction']['bank_details'] = $this->helper->unserializeData($this->orderReference['payment_details']);
                    }
                    $callbackComments = $this->helper->formCustomerComments(!empty($this->order->info['payment_method']) ? $this->order->info['payment_method'] : $this->order->info['payment_class'], $this->eventData);

                    if (in_array($this->eventData['transaction']['payment_type'], ['INSTALMENT_INVOICE', 'INSTALMENT_DIRECT_DEBIT_SEPA']) && $this->eventData['transaction']['status'] == 'CONFIRMED') {
                        $updatedInfo['instalment_cycle_details'] = $this->helper->serializeData($this->helper->getInstalmentInformation($this->eventData));
                    }
                }

                if ($this->eventData['transaction']['status'] === 'DEACTIVATED') {
                    $callbackComments .= PHP_EOL . sprintf(MODULE_PAYMENT_NOVALNET_TRANS_DEACTIVATED_MESSAGE, gmdate('d.m.Y'), gmdate('H:i:s'));
                    $orderStatus = MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED;
                } elseif (in_array($this->orderReference['status'], ['PENDING', 'ON_HOLD']) || in_array($this->orderReference['status'], ['75', '86', '90', '100'])) {
                    $updatedInfo['amount'] = $this->eventData['transaction']['amount'];

                    if ($this->eventData['transaction']['status'] === 'ON_HOLD') {
                        $callbackComments .= PHP_EOL . sprintf(NOVALNET_WEBHOOK_TRANSACTION_UPDATE_TO_ON_HOLD, $this->eventTid, date('d/m/Y H:i:s'));
                        $orderStatus = MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_COMPLETE;
                        // Payment not yet completed, set transaction status to "AUTHORIZE"
                    } elseif ($this->eventData['transaction']['status'] === 'CONFIRMED') {
                        $callbackComments .= PHP_EOL . PHP_EOL . sprintf(NOVALNET_WEBHOOK_TRANSACTION_UPDATE_NOTE, $this->eventTid, $this->formattedAmount, date('d/m/Y H:i:s'));
                        $updatedInfo['paid_amount'] = $this->eventData['transaction']['amount'];
                        $orderStatus = constant('MODULE_PAYMENT_' . strtoupper($this->order->info['payment_method']) . '_ORDER_STATUS');
                    }
                }
            } else {
                if (!empty($this->eventData['transaction']['amount'])) {
                    $updatedInfo['amount'] = $this->eventData['transaction']['amount'];
                }
                $callbackComments .= PHP_EOL . PHP_EOL . sprintf(NOVALNET_WEBHOOK_TRANSACTION_UPDATE_NOTE, $this->eventTid, $this->formattedAmount, date('d/m/Y H:i:s'));
            }

            xtc_db_perform('novalnet_transaction_detail', $updatedInfo, 'update', 'order_no=' . $this->order->info['orders_id']);
            $this->helper->updateStatus($this->order->info['orders_id'], $orderStatus, $callbackComments);
            $this->sendWebhookMail($callbackComments);

            if (in_array($this->orderReference['payment_type'], ['INVOICE', 'GUARANTEED_INVOICE', 'GUARANTEED_DIRECT_DEBIT_SEPA', 'INSTALMENT_INVOICE', 'INSTALMENT_DIRECT_DEBIT_SEPA']) && in_array($this->eventData['transaction']['status'], ['CONFIRMED', 'PENDING', 'ON_HOLD'])) {
                // Send order update email
                $this->helper->sendOrderUpdateMail(
                    [
                        'comments' => '<br>' . $callbackComments,
                        'order_no' => $this->order->info['orders_id'],
                        'order'    => $this->order
                    ]
                );
            }
            $this->displayMessage([ 'message' => xtc_db_prepare_input($callbackComments)]);
        } else {
            $this->displayMessage([ 'message' => 'Novalnet callback received. Callback Script executed already.']);
        }
    }

    /**
     * Handle Payment Reminder
     */
    public function handlePaymentReminder()
    {
        $comments = PHP_EOL . sprintf(NOVALNET_PAYMENT_REMINDER_NOTE, explode('_', $this->eventType)[2]);
        $this->helper->updateStatus($this->order->info['orders_id'], $this->order->info['orders_status'], $comments);
        $this->sendWebhookMail($comments);
        $this->displayMessage([ 'message' => xtc_db_prepare_input($comments)]);
    }

    /**
     * Handle Collection Agency Submission
     */
    public function handleCollectionSubmission()
    {
        $comments = PHP_EOL.sprintf(NOVALNET_COLLECTION_SUBMISSION_NOTE, $this->eventData['collection']['reference']);
        $this->helper->updateStatus($this->order->info['orders_id'], $this->order->info['orders_status'], $comments);
        $this->sendWebhookMail($comments);
        $this->displayMessage([ 'message' => xtc_db_prepare_input($comments)]);
    }

    /**
     * Form the instalment data into serialize
     *
     * @return string
     */
    public function updateInstalmentInfo(): string
    {
        $configurationDetails = $this->helper->unserializeData($this->orderReference['instalment_cycle_details']);
        $instalmentData       = $this->eventData['instalment'];
        $cycleExecuted        = (int) $instalmentData['cycles_executed'] - 1;
        $configurationDetails[$cycleExecuted] = [
            'cycle_amount'         => $instalmentData['cycle_amount'],
            'next_instalment_date' => !empty($configurationDetails[$cycleExecuted]['next_instalment_date']) ? $configurationDetails[$cycleExecuted]['next_instalment_date'] : '',
            'cycles_executed' => $instalmentData['cycles_executed'],
            'due_cycles'      => $instalmentData['pending_cycles'],
            'paid_date'       => date('Y-m-d'),
            'status'          => 'Paid',
            'reference_tid'   => (string) $this->eventData['transaction']['tid'],
            'refunded_amount' => 0
        ];
        return $this->helper->serializeData($configurationDetails);
    }

    /**
     * Print the Webhook messages.
     *
     * @param $message
     *
     * @return void
     */
    public function displayMessage($message)
    {
        echo json_encode($message);
        exit;
    }

    /**
     * Send notification mail to Merchant
     *
     * @param $comments
     */
    public function sendWebhookMail($comments)
    {
        $message = xtc_db_prepare_input($comments);
        // Assign email to address
        $email = $this->helper->validateEmail(MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_TO);
        if (!empty($email)) {
            $order_subject = 'Novalnet Callback Script Access Report - '.STORE_NAME;
            // Send mail
            xtc_php_mail(EMAIL_FROM, STORE_NAME, $email, STORE_OWNER, '', '', '', '', '', $order_subject, $message, '');
        }
    }
}
new NovalnetWebhooks();
