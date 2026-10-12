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
class NovalnetOrdersOverviewAjaxController extends NovalnetOrdersOverviewAjaxController_parent
{
    /**
     * Remove the QR code from the order listing page.
     *
     * Overload this method to provide your own data to the rows.
     *
     * @param OrderListItem $orderListItem
     * @param int $latestInvoiceId
     * @param int $latestInvoiceNumber
     *
     * @return array
     */
    protected function _getRowData(OrderListItem $orderListItem, $latestInvoiceId = 0, $latestInvoiceNumber = null)
    {
        $rowData = parent::_getRowData($orderListItem, $latestInvoiceId, $latestInvoiceNumber);
        $order = new order($rowData['id']);
        $payment_name = !empty($order->info['payment_class']) ? $order->info['payment_class'] : $order->info['payment_method'];

        if (strpos($payment_name, 'novalnet') !== false) {
            $rowData['comment'] = strip_tags($rowData['comment']);
        }
        return $rowData;
    }
}
