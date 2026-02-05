<?php
/**
 * Novalnet payment module
 *
 * This script is used to append payment transaction comments.
 *
 * @author    Novalnet AG
 * @copyright Copyright (c) Novalnet
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link      https://www.novalnet.de
 *
 * Script : NovalnetSendOrderThemeContentView.inc.php
 */

require_once DIR_FS_CATALOG . 'inc/get_transfer_charge_text.inc.php'; // Required in older shop versions.

/**
 * Class NovalnetSendOrderThemeContentView
 */
class NovalnetSendOrderThemeContentView extends NovalnetSendOrderThemeContentView_parent
{
    /**
     * @return array
     */
    public function get_mail_content_array()
    {
        global $gx_version;

        if (strpos($this->order->info['payment_method'], 'novalnet') !== false) {
            $comments = &$this->order->info['comments'];
            // Novalnet code begins
            if (!empty($_SESSION['novalnet']['nn_response']) && isset($_SESSION['novalnet']['nn_response']['transaction'])) {
                $transaction = $_SESSION['novalnet']['nn_response']['transaction'];
                $payment_type = $transaction['payment_type'];
                $status = $transaction['status'] ? $transaction['status'] : '';

                if (in_array($payment_type, ['INVOICE', 'PREPAYMENT']) || ($payment_type === 'GUARANTEED_INVOICE' && $status !== 'PENDING')
                ) {
                    include_once DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php';
                    $helper    = new NovalnetHelper();
                    $response = $helper->sendTransactionUpdate($this->order->info['orders_id']);

                    if (!empty($comments) && !preg_match('/BNR-' . MODULE_PAYMENT_NOVALNET_PROJECT_ID . '-/', $comments)) {
                        $comments .= sprintf(MODULE_PAYMENT_NOVALNET_PAYMENT_REFERENCE, 2, $response['transaction']['invoice_ref']) . PHP_EOL;
                        if (!empty($_SESSION['novalnet']['nn_response']['transaction']['bank_details']['qr_image'])) {
                            $comments .= PHP_EOL . MODULE_PAYMENT_NOVALNET_PAYMENT_QR_CODE_REFERENCE_TEXT;
                            $comments .= PHP_EOL . sprintf(MODULE_PAYMENT_NOVALNET_PAYMENT_QR_CODE_IMAGE, $_SESSION['novalnet']['nn_response']['transaction']['bank_details']['qr_image']);
                        }
                    }
                }

                if($payment_type == 'INSTALMENT_INVOICE' && in_array($_SESSION['novalnet']['nn_response']['transaction']['status'], ['CONFIRMED', 'ON_HOLD']) &&
                    strpos($comments, 'SHOPORDERNUMBER') !== false) {
                    $comments = str_replace('###SHOPORDERNUMBER###', $this->order->info['orders_id'], $comments);
                }
            }

            // Novalnet code ends
            if ($gx_version > 'v4.9.5.0') {
                $this->order->info['comments'] = $comments;
            } else {
                $this->order->info['comments'] = nl2br($comments);
            }
        }

        return parent::get_mail_content_array();
    }
}
