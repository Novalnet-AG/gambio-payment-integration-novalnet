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
 * Script: adminOrderExtender.php
 */

$orderData = new order($_GET['oID']);

if (!empty($orderData) && !empty($orderData->info['payment_method']) && strpos($orderData->info['payment_method'], 'novalnet') !== false) {
    if (strpos($orderData->info['payment_method'], 'invoice') !== false || $orderData->info['payment_method'] === 'novalnet_prepayment') {
        $newOrdersStatusDataArray = array();

        foreach ($ordersStatusDataArray as $orderStatusRow) {
            foreach ($orderStatusRow['comments'] as $index => $comment) {
                $orderStatusRow['comments'][$index]['text'] = strip_tags($comment['text']);
            }
            $newOrdersStatusDataArray[] = $orderStatusRow;
        }

        if (!empty($newOrdersStatusDataArray)) {
            $ordersStatusDataArray = $newOrdersStatusDataArray;
        }
    }
}
