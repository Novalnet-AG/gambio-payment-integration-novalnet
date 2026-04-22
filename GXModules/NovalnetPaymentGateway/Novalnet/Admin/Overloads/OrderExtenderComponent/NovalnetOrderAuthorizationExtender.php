<?php
/**
 * Novalnet payment module
 *
 * This script is used for the order capture or cancellation process
 *
 * @author    Novalnet AG
 * @copyright Copyright (c) Novalnet
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link      https://www.novalnet.de
 *
 * Script : NovalnetOrderAuthorizationExtender.php
 */

require_once DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php';

class NovalnetOrderAuthorizationExtender extends NovalnetOrderAuthorizationExtender_parent
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
        if (!empty($transaction_details) && $transaction_details['status'] == 'ON_HOLD') {
            $txt = MainFactory::create('LanguageTextManager', 'novalnet_payment', $_SESSION['languages_id']);

            $contentView = MainFactory::create('ContentView');
            $contentView->set_template_dir(DIR_FS_CATALOG . 'GXModules/NovalnetPaymentGateway/Novalnet/Admin/Templates/');
            $contentView->set_content_template('novalnet_authorization_block.html');
            $contentView->set_flat_assigns(true);
            $contentView->set_caching_enabled(false);
            $contentView->set_content_data('novalnet_order_id', $order_id);
            $contentView->set_content_data('orders_link', xtc_href_link('orders.php'));
            $contentView->set_content_data('transaction_details', $transaction_details);
            $contentView->set_content_data('novalnet_extension_js', xtc_catalog_href_link('GXModules/NovalnetPaymentGateway/Novalnet/Admin/Javascript/novalnet_extension.js'));
            $contentView->set_content_data('novalnet_authorization_action', xtc_href_link('admin.php', 'do=NovalnetOrderExtension/ProcessNovalnetOrder&action=authorizationProcess'));

            // Position: below_order_info
            $this->v_output_buffer['below_order_info_heading'] = $txt->get_text('MODULE_PAYMENT_NOVALNET_TRANS_CONFIRM_TITLE');
            $this->v_output_buffer['below_order_info']         = $contentView->get_html();

            // Append content
            $this->addContent();
        }

        parent::proceed();
    }
}
