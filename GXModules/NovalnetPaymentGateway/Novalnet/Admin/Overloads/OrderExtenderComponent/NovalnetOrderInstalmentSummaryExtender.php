<?php
/**
 * Novalnet payment module
 *
 * This script is used to display a summary of installment payment transaction details.
 * for wallet payments
 *
 * @author    Novalnet AG
 * @copyright Copyright (c) Novalnet
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link      https://www.novalnet.de
 *
 * Script : NovalnetOrderInstalmentSummaryExtender.php
 */

require_once DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php';

class NovalnetOrderInstalmentSummaryExtender extends NovalnetOrderInstalmentSummaryExtender_parent
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
        $helper = new NovalnetHelper();
        $order_id = htmlspecialchars(strip_tags($this->v_data_array['GET']['oID']));
        $transaction_details = NovalnetHelper::getNovalnetTransDetails($order_id);

        if (! empty($transaction_details) && in_array($transaction_details['status'], ['DEACTIVATED', 'CONFIRMED']) &&
            !empty($transaction_details['instalment_cycle_details'])) {
            $instalmentDetails = $helper->unserializeData($transaction_details['instalment_cycle_details']);
            $installmentTableData  = $this->formatSummaryData($instalmentDetails, $transaction_details['currency']);

            // Process Instalment Summary
            if ($this->isInstalmentTransaction($transaction_details) && !empty($instalmentDetails)) {
                $txt = MainFactory::create('LanguageTextManager', 'novalnet_payment', $_SESSION['languages_id']);

                $contentView = MainFactory::create('ContentView');
                $contentView->set_template_dir(DIR_FS_CATALOG . 'GXModules/NovalnetPaymentGateway/Novalnet/Admin/Templates/');
                $contentView->set_content_template('novalnet_instalment_summary_block.html');
                $contentView->set_flat_assigns(true);
                $contentView->set_caching_enabled(false);
                $contentView->set_content_data('novalnet_order_id', $order_id);
                $contentView->set_content_data('orders_link', xtc_href_link('orders.php'));
                $contentView->set_content_data('transaction_details', $transaction_details);
                $contentView->set_content_data('instalment_details', $installmentTableData);
                $contentView->set_content_data('instalmentCancel', $this->canShowInstalmentCancelButton($instalmentDetails));
                $contentView->set_content_data('instalmentCancelallcycles', $this->canShowInstalmentCancelAllCyclesButton($instalmentDetails));
                $contentView->set_content_data('instalmentCancelremaincycles', $this->canShowInstalmentCancelRemainCyclesButton($instalmentDetails));
                $contentView->set_content_data('novalnet_extension_js', xtc_catalog_href_link('GXModules/NovalnetPaymentGateway/Novalnet/Admin/Javascript/novalnet_extension.js'));
                $contentView->set_content_data('novalnet_instalment_cancel_action', xtc_href_link('admin.php', 'do=NovalnetOrderExtension/ProcessNovalnetOrder&action=instalmentCancelProcess'));
                $contentView->set_content_data('novalnet_refund_action', xtc_href_link('admin.php', 'do=NovalnetOrderExtension/ProcessNovalnetOrder&action=refundProcess'));

                // Position: below_order_info
                $this->v_output_buffer['below_order_info_heading'] = $txt->get_text('MODULE_PAYMENT_NOVALNET_INSTALMENT_SUMMARY_BACKEND');
                $this->v_output_buffer['below_order_info']         = $contentView->get_html();

                // Append content
                $this->addContent();
            }
        }

        parent::proceed();
    }

    /**
     * Checks if the transaction involves instalments.
     */
    private function isInstalmentTransaction($transaction_details)
    {
        return in_array(
            $transaction_details['payment_type'],
            [
            'INSTALMENT_INVOICE',
            'INSTALMENT_DIRECT_DEBIT_SEPA',
            'novalnet_instalment_invoice',
            'novalnet_instalment_sepa'
            ]
        );
    }

    /**
     * Checks if the transaction involves instalments.
     */
    private function canShowInstalmentCancelButton($instalment_details)
    {
        $instalment_status = [];
        foreach ($instalment_details as $instalment_details_data) {
            array_push($instalment_status, $instalment_details_data['status']);
        }

        if (in_array('Canceled', $instalment_status)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Checks if the transaction involves instalments.
     */
    private function canShowInstalmentCancelAllCyclesButton($instalment_details)
    {
        $show_all_cycle_cancel = false;
        foreach ($instalment_details as $instalment_details_data) {
            if ($instalment_details_data['status'] == 'Paid') {
                $show_all_cycle_cancel = true;
                break;
            }
        }

        return $show_all_cycle_cancel;
    }

    /**
     * Checks if the transaction involves instalments.
     */
    private function canShowInstalmentCancelRemainCyclesButton($instalment_details)
    {
        $show_remain_cycle_cancel = false;
        foreach ($instalment_details as $instalment_details_data) {
            if (empty($instalment_details_data['reference_tid'])) {
                $show_remain_cycle_cancel = true;
                break;
            }
        }

        return $show_remain_cycle_cancel;
    }

    /**
     * Adds a formatted cycle amount to each instalment detail.
     *
     * @param  array  $instalment_details Array of instalment details
     * @param  string $currency           Currency code
     * @return array Modified instalment details with 'formatted_amount' added
     */
    private function formatSummaryData(array $instalment_details, string $currency): array
    {
        $instalment = [];
        $instalment_key = 1;

        foreach ($instalment_details as $key => $data) {
            $cycle_amount = !empty($data['cycle_amount']) ? $data['cycle_amount'] : (!empty($data['instalment_cycle_amount_orginal_amount']) ? $data['instalment_cycle_amount_orginal_amount'] : 0);

            $old_refunded_amount = !empty($data['instalment_cycle_amount']) ? (strpos($data['instalment_cycle_amount'], '.') !== false ? $data['instalment_cycle_amount'] * 100 : $data['instalment_cycle_amount']) : 0;

            $refunded_amount = !empty($data['refunded_amount']) ? $data['refunded_amount'] : 0;

            $next_instalment_date = !empty($data['next_instalment_date']) ? $data['next_instalment_date'] : (!empty($instalment_details[$key + 1]['date']) ? $instalment_details[$key + 1]['date'] : '-');

            if (!empty($cycle_amount)) {
                $formattedAmount = xtc_format_price_order($cycle_amount / 100, 1, $currency);
            }

            if (!empty($next_instalment_date) && ($next_instalment_date != '-')) {
                $next_instalment_date = date("d.m.Y", strtotime($next_instalment_date));
            }

            $instalment[$instalment_key] = [
                'sno'                  => $instalment_key,
                'reference_tid'        => !empty($data['reference_tid']) ? $data['reference_tid'] : '-',
                'formatted_amount'     => $formattedAmount,
                'cycle_amount'         => $cycle_amount,
                'next_instalment_date' => $next_instalment_date,
                'status'               => $data['status'],
                'show_refund_val'      => !empty($old_refunded_amount) ? $old_refunded_amount : (isset($data['cycle_amount']) ? ($cycle_amount - $refunded_amount) : 0) ,
                'refunded_amount'      => $refunded_amount,
            ];

            $instalment_key++;
        }

        return $instalment;
    }
}
