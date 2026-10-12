<?php
/**
 * Novalnet payment module
 *
 * This script is used for the order zero-amount booking process.
 *
 * @author    Novalnet AG
 * @copyright Copyright (c) Novalnet
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link      https://www.novalnet.de
 *
 * Script : NovalnetOrderAmountBookingExtender.php
 */

require_once DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php';

class NovalnetOrderAmountBookingExtender extends NovalnetOrderAmountBookingExtender_parent
{
    /**
     * @var array
     */
    public $v_output_buffer;

    /**
     * Overloaded "proceed" method.
     */
    public function proceed()
    {
        $order_id = htmlspecialchars(strip_tags($this->v_data_array['GET']['oID']));
        $transaction_details = NovalnetHelper::getNovalnetTransDetails($order_id);

        // Process Refund Block
        if (!empty($transaction_details) && $this->isZeroAmountBooking($transaction_details)) {
            $txt = MainFactory::create('LanguageTextManager', 'novalnet_payment', $_SESSION['languages_id']);
            $order_total = xtc_db_fetch_array(xtc_db_query("SELECT value FROM " . TABLE_ORDERS_TOTAL . " where class = 'ot_total' AND orders_id = " . xtc_db_input($order_id)));


            $contentView = MainFactory::create('ContentView');
            $contentView->set_template_dir(DIR_FS_CATALOG . 'GXModules/NovalnetPaymentGateway/Novalnet/Admin/Templates/');
            $contentView->set_content_template('novalnet_amount_booking_block.html');
            $contentView->set_flat_assigns(true);
            $contentView->set_caching_enabled(false);
            $contentView->set_content_data('novalnet_order_id', $order_id);
            $contentView->set_content_data('orders_link', xtc_href_link('orders.php'));
            $contentView->set_content_data('transaction_details', $transaction_details);
            $contentView->set_content_data('order_total', $order_total['value'] * 100);
            $contentView->set_content_data('novalnet_extension_js', xtc_catalog_href_link('GXModules/NovalnetPaymentGateway/Novalnet/Admin/Javascript/novalnet_extension.js'));
            $contentView->set_content_data('novalnet_amount_booking_action', xtc_href_link('admin.php', 'do=NovalnetOrderExtension/ProcessNovalnetOrder&action=amountBookingProcess'));

            // Position: below_order_info
            $this->v_output_buffer['below_order_info_heading'] = $txt->get_text('MODULE_PAYMENT_NOVALNET_BOOK_TITLE');
            $this->v_output_buffer['below_order_info']         = $contentView->get_html();

            // Append content
            $this->addContent();
        }

        parent::proceed();
    }

    /**
     * Checks if the transaction is a zero-amount booking.
     */
    private function isZeroAmountBooking($transaction_details)
    {
        return $transaction_details['amount'] == 0 &&
               in_array($transaction_details['payment_type'], ['CREDITCARD', 'DIRECT_DEBIT_SEPA', 'DIRECT_DEBIT_ACH', 'GOOGLEPAY', 'APPLEPAY']) &&
               $transaction_details['status'] === 'CONFIRMED';
    }

}
