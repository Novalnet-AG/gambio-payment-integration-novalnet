<?php
/**
 * Novalnet payment module
 *
 * This script is used to display the wallet payment option on the shopping cart page.
 *
 * @author    Novalnet AG
 * @copyright Copyright (c) Novalnet
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link      https://www.novalnet.de
 *
 * Script : NovalnetShoppingCartThemeContentView.inc.php
 */

class NovalnetShoppingCartThemeContentView extends NovalnetShoppingCartThemeContentView_parent
{
    public function prepare_data()
    {
        parent::prepare_data();
        $order = new order();

        $_SESSION['novalnet']['nn_order'] = $order;

        include_once DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php';
        include_once DIR_FS_CATALOG . 'ext/novalnet/NovalnetValidator.class.php';
        include_once DIR_FS_CATALOG . 'includes/modules/payment/novalnet_applepay.php';
        include_once DIR_FS_CATALOG.'includes/modules/payment/novalnet_googlepay.php';

        $applepay  = new novalnet_applepay();
        $googlepay = new novalnet_googlepay();
        $helper    = new NovalnetHelper();
        $validator = new NovalnetValidator();

        if ($helper->checkMerchantCredentials() || strpos(MODULE_PAYMENT_INSTALLED, 'novalnet_config') === false) {
            return;
        }

        $this->set_content_data('NOVALNET_WALLET_SCRIPT', DIR_WS_CATALOG . 'ext/novalnet/js/novalnet_wallet.min.js');

        // Applepay
        $display_page = defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_DISPLAY') ? constant('MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_DISPLAY') : '';
        if ($applepay->enabled == true && strpos($display_page, 'shopping cart page') !== false && $helper->checkApplePayIsAvailable()) {
            $walletParams = $validator->getWalletParams('novalnet_applepay', 'shopping_cart');
            $show_applepay = $helper->hidePaymentVisibility('novalnet_applepay') ? true : false;
            $this->set_content_data('NOVALNET_APPLEPAY_AVAILABLE', $show_applepay);
            $this->set_content_data('NOVALNET_APPLEPAY_PARAMETERS', htmlentities($walletParams));
        }

        // Googlepay
        $display_page = defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_DISPLAY') ? constant('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_DISPLAY') : array();
        if ($googlepay->enabled == true && strpos($display_page, 'shopping cart page') !== false) {
            $walletParams = $validator->getWalletParams('novalnet_googlepay', 'shopping_cart');
            $show_googlepay = $helper->hidePaymentVisibility('novalnet_googlepay') ? true : false;
            $this->set_content_data('NOVALNET_GOOGLEPAY_AVAILABLE', $show_googlepay);
            $this->set_content_data('NOVALNET_GOOGLEPAY_PARAMETERS', htmlentities($walletParams));
        }

        foreach (['NOVALNET_GOOGLEPAY', 'NOVALNET_APPLEPAY'] as $payment) {
            $paymentZone = defined('MODULE_PAYMENT_'. $payment .'_PAYMENT_ZONE') ? trim(constant('MODULE_PAYMENT_'. $payment .'_PAYMENT_ZONE')) : '';
            if (!empty($paymentZone) && !empty($order->delivery['country']['id'])) {
                $check_query = xtc_db_query("select zone_id from ".TABLE_ZONES_TO_GEO_ZONES." where geo_zone_id = '". constant('MODULE_PAYMENT_'. $payment .'_PAYMENT_ZONE') ."' and zone_country_id = '".$order->delivery['country']['id']."' order by zone_id");
                $check_flag = false;
                while ($check = xtc_db_fetch_array($check_query)) {
                    if ($check['zone_id'] < 1) {
                        $check_flag = true;
                        break;
                    } elseif ($check['zone_id'] == $order->delivery['zone_id']) {
                        $check_flag = true;
                        break;
                    }
                }

                if ($check_flag == false) {
                    $this->set_content_data($payment . '_AVAILABLE', false);
                }
            }
        }
    }

}
