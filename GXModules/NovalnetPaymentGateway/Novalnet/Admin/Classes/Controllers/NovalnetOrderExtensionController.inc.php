<?php

/**
 * Novalnet payment module
 *
 * This script contains the order extension process for all the payments
 *
 * @author    Novalnet AG
 * @copyright Copyright (c) Novalnet
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link      https://www.novalnet.de
 *
 * Script: NovalnetOrderExtensionController.inc.php
 */

require_once DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php';
require_once DIR_FS_CATALOG . 'ext/novalnet/NovalnetPaymentRequest.class.php';
require_once DIR_FS_CATALOG . 'admin/includes/classes/order.php';

/**
 * Class NovalnetOrderExtensionController
 *
 * This is a Novalnet admin order extension http view controller class.
 */
class NovalnetOrderExtensionController extends AdminHttpViewController
{
    /**
     * @var request
     */
    protected $request;

    /**
     * @var queryParams
     */
    protected $queryParams;

    /**
     * @var NovalnetHelper
     */
    protected $helper;

    /**
     * @var LanguageTextManager
     */
    protected $textManager;

    /**
     * @var array
     */
    protected $transaction;

    /**
     * @var order
     */
    protected $order;

    /**
     * @var messageStack
     */
    protected $messageStack;

    /**
     * Handles the processing of Novalnet orders.
     *
     * This function is triggered to manage the Novalnet payment order lifecycle.
     *
     * @return JsonHttpControllerResponse Returns a JSON-formatted response to the client.
     */
    public function actionProcessNovalnetOrder(): JsonHttpControllerResponse
    {
        $this->request     = $this->_getPostDataCollection()->getArray();
        $this->queryParams = $this->_getQueryParametersCollection()->getArray();
        $this->helper      = new NovalnetHelper();
        $this->textManager = MainFactory::create('LanguageTextManager', 'novalnet_payment');
        $this->transaction = NovalnetHelper::getNovalnetTransDetails($this->request['novalnet_order_id']);
        $this->order       = new order($this->request['novalnet_order_id']);
        $this->messageStack = $GLOBALS['messageStack'];

        // Define supported actions and their corresponding methods
        $actionMethods = [
            'refundProcess'           => 'processRefund',
            'amountBookingProcess'    => 'processAmountBooking',
            'instalmentCancelProcess' => 'processInstalmentCancel',
            'authorizationProcess'    => 'processVoidCapture'
        ];

        $action = $this->queryParams['action'] ? $this->queryParams['action'] : '';
        if (array_key_exists($action, $actionMethods)) {
            return $this->{$actionMethods[$action]}();
        }

        // Handle invalid or missing action
        $this->addErrorAndRedirect(
            $this->textManager->get_text('ERROR_ORDER_ACTION_NOT_FOUND')
        );
    }

    /**
     * To process the refund.
     */
    public function processRefund()
    {
        if (!empty($this->request['nn_refund_confirm']) && in_array($this->transaction['status'], ['CONFIRMED', 'PENDING'])) {
            $refundAmount = $this->request['refund_trans_amount'];
            $data = [
                'transaction' => [
                    'tid'    => !empty($this->request['refund_tid']) ? $this->request['refund_tid'] : $this->transaction['tid'],
                    'amount' => $this->request['refund_trans_amount'],
                ],
                'custom' => [
                    'lang'         => strtoupper($_SESSION['language_code'] ? $_SESSION['language_code'] : 'DE'),
                    'shop_invoked' => 1,
                ],
            ];

            if (!empty($this->request['refund_reason'])) {
                $data['transaction']['reason'] = $this->request['refund_reason'];
            }

            // Send refund request
            $response = $this->helper->sendRequest($data, $this->helper->getActionEndpoint('transaction_refund'));

            if ($response['result']['status'] === 'SUCCESS') {
                $currency = !empty($response['transaction'] ['currency']) ? $response['transaction'] ['currency'] : $response ['transaction'] ['refund'] ['currency'];
                if (!empty($response['transaction']['refund']['amount'])) {
                    $refundedAmountInBiggerUnit = $this->helper->formattedAmount($response['transaction']['refund']['amount'] / 100, $currency);
                } else {
                    $refundedAmountInBiggerUnit = $this->helper->formattedAmount($refundAmount / 100, $currency);
                }
                $message = PHP_EOL . sprintf($this->textManager->get_text('MODULE_PAYMENT_NOVALNET_REFUND_PARENT_TID_MSG'), $this->transaction['tid'], $refundedAmountInBiggerUnit);

                if (!empty($response['transaction']['refund']['tid'])) {
                    $message .= sprintf($this->textManager->get_text('MODULE_PAYMENT_NOVALNET_REFUND_CHILD_TID_MSG'), $response['transaction']['refund']['tid']);
                }

                if (in_array($response['transaction']['payment_type'], ['INSTALMENT_INVOICE', 'INSTALMENT_DIRECT_DEBIT_SEPA'])) {
                    $instalmentDetails = $this->helper->unserializeData($this->transaction['instalment_cycle_details']);
                    $instalmentDetails = $this->helper->updateInstalmentCycle($instalmentDetails, $refundAmount, $this->request['refund_tid']);
                    $updatedInfo['instalment_cycle_details'] = $this->helper->serializeData($instalmentDetails);
                }

                $updatedInfo['refund_amount'] = (int) $this->transaction['refund_amount'] + (int) $refundAmount;
                $orderStatus = ($updatedInfo['refund_amount'] >= $this->transaction['amount']) ? MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED : $this->order->info['orders_status'];

                xtc_db_perform('novalnet_transaction_detail', $updatedInfo, 'update', 'order_no=' . (int) $this->request['novalnet_order_id']);
                $this->helper->updateStatus($this->request['novalnet_order_id'], $orderStatus, PHP_EOL . $message);
                $this->messageStack->add_session(strip_tags($message), 'success');
            } else {
                // Handle failure response
                $this->messageStack->add_session(!empty($response['result']['status_text']) ? $response['result']['status_text'] : (!empty($response['status_text']) ? $response['status_text'] : $this->textManager->get_text('ERROR_ORDER_ACTION_NOT_FOUND')), 'error');
            }
        }

        // Redirect to order edit page
        $this->redirectToOrderPage();
    }

    /**
     * To process installment cancel.
     */
    public function processInstalmentCancel()
    {
        if (!empty($this->request['nn_instacancel_allcycles']) || !empty($this->request['nn_instacancel_remaincycles'])) {
            $cancelType = !empty($this->request['nn_instacancel_allcycles']) ? 'CANCEL_ALL_CYCLES' : 'CANCEL_REMAINING_CYCLES';

            $data = [
                'instalment' => [
                    'tid'         => $this->transaction['tid'],
                    'cancel_type' => $cancelType,
                ],
                'custom' => [
                    'lang'         => !empty($_SESSION['language_code']) ? strtoupper($_SESSION['language_code']) : 'DE',
                    'shop_invoked' => 1,
                ],
            ];
            $response = $this->helper->sendRequest($data, $this->helper->getActionEndpoint('instalment_cancel'));

            if ($response['result']['status'] === 'SUCCESS') {
                $updatedInfo['status'] = ($cancelType === 'CANCEL_ALL_CYCLES') ? 'DEACTIVATED' : 'CONFIRMED';
                $instalmentDetails = $this->helper->unserializeData($this->transaction['instalment_cycle_details']);
                $currency = !empty($response['transaction'] ['currency']) ? $response['transaction'] ['currency'] : (!empty($response['transaction']['refund']['currency']) ? $response['transaction']['refund']['currency'] : $this->transaction['currency']);

                $totalRefundedAmount = 0;
                if (isset($response['transaction']['refund'])) {
                    $refundedAmountInBiggerUnit = $this->helper->formattedAmount($response['transaction']['refund']['amount'] / 100, $currency);
                    $message = PHP_EOL . sprintf($this->textManager->get_text('MODULE_PAYMENT_NOVALNET_INSTALMENT_CANCEL_ALLCYCLES_TEXT'), $this->transaction['tid'], date('Y-m-d H:i:s'));
                    $totalRefundedAmount = $this->transaction['amount'];
                } else {
                    $message = PHP_EOL . sprintf($this->textManager->get_text('MODULE_PAYMENT_NOVALNET_INSTALMENT_CANCEL_REMAINING_CYCLES_TEXT'), $this->transaction['tid'], date('Y-m-d H:i:s'));
                    $totalRefundedAmount = $this->transaction['refund_amount'];
                    foreach ($instalmentDetails as $instalment) {
                        $totalRefundedAmount += empty($instalment['reference_tid']) ? $instalment['cycle_amount'] : 0;
                    }
                }
                $instalmentDetails = $this->helper->updateInstalmentCancel($instalmentDetails, $cancelType);
                $updatedInfo['refund_amount'] = $totalRefundedAmount;
                $updatedInfo['instalment_cycle_details'] = $this->helper->serializeData($instalmentDetails);

                $orderStatus = ($cancelType === 'CANCEL_ALL_CYCLES') ? MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED : $this->order->info['orders_status'];

                xtc_db_perform('novalnet_transaction_detail', $updatedInfo, 'update', 'order_no=' . $this->request['novalnet_order_id']);
                $this->helper->updateStatus($this->request['novalnet_order_id'], $orderStatus, $message);
                $this->messageStack->add_session(strip_tags($message), 'success');
            } else {
                // Handle failure response
                $this->messageStack->add_session(!empty($response['result']['status_text']) ? $response['result']['status_text'] : (!empty($response['status_text']) ? $response['status_text'] : $this->textManager->get_text('ERROR_ORDER_ACTION_NOT_FOUND')), 'error');
            }

            // Redirect to order edit page
            $this->redirectToOrderPage();
        }
    }

    /**
     * To process on-hold transaction.
     */
    public function processVoidCapture()
    {
        if (isset($this->request['nn_manage_confirm']) && !empty($this->request['trans_status'])) {
            $data = [
                'transaction' => [
                    'tid' => $this->transaction['tid']
                ],
                'custom' => [
                    'lang' => isset($_SESSION['language_code']) ? strtoupper($_SESSION['language_code']) : 'DE',
                    'shop_invoked' => 1,
                ]
            ];

            $endpoint = (!empty($this->request['trans_status']) && $this->request['trans_status'] == 'CONFIRM') ? 'transaction_capture' : 'transaction_cancel';
            $response = $this->helper->sendRequest($data, $this->helper->getActionEndpoint($endpoint));

            if ($response['result']['status'] === 'SUCCESS' & !empty($response['transaction']['status'])) {
                $updateInfo['status'] = $response['transaction']['status'];
                $message = '';
                $payment_code = !empty($this->order->info['payment_method']) ? $this->order->info['payment_method'] : $this->order->info['payment_class'];

                if (in_array($response['transaction']['status'], ['CONFIRMED', 'PENDING'])) {
                    if ($response['transaction']['status'] === 'CONFIRMED') {
                        $updateInfo['paid_amount'] = $this->transaction['amount'];
                    }

                    if (!empty($this->transaction['payment_details']) && !empty($response['transaction']['payment_type']) && in_array($response['transaction']['payment_type'], ['INSTALMENT_INVOICE', 'GUARANTEED_INVOICE', 'INVOICE', 'PREPAYMENT'])) {
                        $response['transaction']['bank_details'] = $this->helper->unserializeData($this->transaction['payment_details']);
                    }

                    $message .=  $this->helper->formCustomerComments($payment_code, $response);

                    if (! empty($response['instalment']['cycles_executed']) && !empty($response['transaction']['payment_type']) && in_array($response['transaction']['payment_type'], ['INSTALMENT_INVOICE', 'INSTALMENT_DIRECT_DEBIT_SEPA'])) {
                        $response['transaction']['amount'] = $this->transaction['amount'];
                        $updateInfo['instalment_cycle_details'] = $this->helper->getInstalmentInformation($response);
                        $updateInfo['instalment_cycle_details'] = $this->helper->serializeData($updateInfo['instalment_cycle_details']);
                    }

                    $message .= PHP_EOL . sprintf($this->textManager->get_text('MODULE_PAYMENT_NOVALNET_TRANS_CONFIRM_SUCCESSFUL_MESSAGE'), date('d/m/Y'), date('H:i:s'));

                    // Send order update email
                    $this->helper->sendOrderUpdateMail(
                        [
                            'comments' => '<br>' . $message,
                            'order_no' => $this->request['novalnet_order_id'],
                            'order'    => $this->order
                        ]
                    );
                } else {
                    $message .= PHP_EOL . sprintf($this->textManager->get_text('MODULE_PAYMENT_NOVALNET_TRANS_DEACTIVATED_MESSAGE'), date('d/m/Y'), date('H:i:s'));
                }

                $orderStatus = ($this->request['trans_status'] === 'CONFIRM') ? $this->helper->getOrderStatus($response['transaction']['status'], $payment_code) : MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED;

                // Update database
                xtc_db_perform('novalnet_transaction_detail', $updateInfo, 'update', 'order_no=' . $this->request['novalnet_order_id']);
                $this->helper->updateStatus($this->request['novalnet_order_id'], $orderStatus, $message);
                $this->messageStack->add_session(strip_tags($message), 'success');
            } else {
                // Handle error message
                $this->messageStack->add_session($response['result']['status_text'], 'error');
            }
            $this->redirectToOrderPage();
        } else {
            $this->addErrorAndRedirect(
                $this->textManager->get_text('MODULE_PAYMENT_NOVALNET_SELECT_STATUS_TEXT')
            );
        }
    }

    /**
     * This function is used to process zero amount booking transactions.
     */
    public function processAmountBooking()
    {
        if (!empty($this->request['nn_book_confirm']) && !empty($this->request['book_amount'])) {
            $GLOBALS['order'] = $this->order;
            $GLOBALS['order']->info['total'] = $this->request['book_amount'] / 100;
            $paymentRequest = new NovalnetPaymentRequest();
            $data['merchant']    = $paymentRequest->getMerchantDetails();
            $data['customer']    = $paymentRequest->getCustomerData(!empty($this->order->info['payment_method']) ? $this->order->info['payment_method'] : $this->order->info['payment_class']);
            $data['transaction'] = $paymentRequest->getTransactionDetails(!empty($this->order->info['payment_method']) ? $this->order->info['payment_method'] : $this->order->info['payment_class'], $this->request['novalnet_order_id']);
            $data['custom']      = $paymentRequest->getCustomDetails();

            if (!empty($this->transaction['payment_details'])) {
                $paymentDetails = $this->helper->unserializeData($this->transaction['payment_details']);
                $data['transaction'] = array_merge(
                    $data['transaction'],
                    [
                    'payment_type' => $this->transaction['payment_type'],
                    'amount' => $this->request['book_amount'],
                    'payment_data' => [
                        'token' => $paymentDetails['token']
                    ]]
                );
            }
            $data['custom']['shop_invoked'] = 1;
            // Send request
            $response = $this->helper->sendRequest($data, $this->helper->getActionEndpoint('payment'));

            if ($response['result']['status'] === 'SUCCESS') {
                $bookedAmountInBiggerUnit = $this->helper->formattedAmount($response['transaction'] ['amount'] / 100, $response['transaction'] ['currency']);
                $message = PHP_EOL . PHP_EOL . sprintf($this->textManager->get_text('MODULE_PAYMENT_NOVALNET_TRANS_BOOKED_MESSAGE'), $bookedAmountInBiggerUnit, $response['transaction'] ['tid']);

                // Update transaction details
                $updateInfo = [
                    'amount' => $response['transaction']['amount'],
                    'paid_amount' => $response['transaction']['amount'],
                    'tid'    => $response['transaction']['tid'],
                    'status' => $response['transaction']['status']
                ];
                // Update database
                xtc_db_perform('novalnet_transaction_detail', $updateInfo, 'update', 'order_no=' . $this->request['novalnet_order_id']);
                $this->helper->updateStatus($this->request['novalnet_order_id'], $this->order->info['orders_status'], $message);
                $this->messageStack->add_session(strip_tags($message), 'success');
            } else {
                // Handle error message
                $this->messageStack->add_session($response['result']['status_text'], 'error');
            }
            $this->redirectToOrderPage();
        } else {
            $this->addErrorAndRedirect(
                $this->textManager->get_text('MODULE_PAYMENT_NOVALNET_AMOUNT_ERROR_MESSAGE')
            );
        }
    }

    /**
     * Adds an error message to the session and redirects to the order edit page.
     *
     * @param string $errorMessage Error message to display.
     */
    private function addErrorAndRedirect($errorMessage)
    {
        $this->messageStack->add_session($errorMessage, 'error');
        $this->redirectToOrderPage();
    }

    /**
     * Redirect to order edit page.
     */
    private function redirectToOrderPage()
    {
        xtc_redirect(xtc_href_link('orders.php', 'oID=' . (int)$this->request['novalnet_order_id'] . '&action=edit'));
    }
}
