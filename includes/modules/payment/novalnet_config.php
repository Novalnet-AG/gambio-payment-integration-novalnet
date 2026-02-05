<?php
/**
 * Novalnet payment module
 *
 * This script is used for global configuration
 *
 * @author    Novalnet AG
 * @copyright Copyright (c) Novalnet
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link      https://www.novalnet.de
 *
 * Script: novalnet_config.php
 */
require_once DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php';

class novalnet_config
{
    public $code;

    public $title;

    public $sort_order;

    public $description;

    public $enabled;

    public $helper;

    /**
     * Core Function : Constructor()
     */
    public function __construct()
    {
        $this->code        = 'novalnet_config';
        $this->title       = defined('MODULE_PAYMENT_NOVALNET_CONFIG_TEXT_TITLE') ? MODULE_PAYMENT_NOVALNET_CONFIG_TEXT_TITLE : '';
        $this->description = defined('MODULE_PAYMENT_NOVALNET_CONFIG_TEXT_DESC') ? MODULE_PAYMENT_NOVALNET_CONFIG_TEXT_DESC : '';
        $this->sort_order  = 0;
        $this->enabled     = true;
        $this->helper      = new NovalnetHelper();
    }

    /**
     * Core Function : check()
     *
     * Checks for payment installation status
     *
     * @return boolean
     */
    public function check()
    {
        if (!isset($this->_check)) {
            $check_query  = xtc_db_query("select `value` from `gx_configurations` where `key` = 'configuration/MODULE_PAYMENT_NOVALNET_SIGNATURE'");
            $this->_check = xtc_db_num_rows($check_query);
        }
        return $this->_check;
    }

    /**
     * Core Function : selection()
     *
     * Display checkout form in chekout payment page
     *
     * @return array
     */
    public function selection()
    {
        return false;
    }

    /**
     * Core Function : install()
     *
     * Payment module installation
     */
    public function install()
    {
        $this->checkAdminAccess();
        xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_SIGNATURE', '',  '1',  now())");
        xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY', '',  '2', now())");
        xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_TARIFF_ID', '',  '3', now())");
        xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CLIENT_KEY', '',  '4', now())");
        xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_PROJECT_ID', '', '5', now())");
        xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_COMPLETE', '1', '5', 'order-status', now())");
        xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED', '99',  '6', 'order-status',now())");
        xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CALLBACK_TEST_MODE','false', '7', 'switcher',now())");
        xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CALLBACK_URL', '" . ((defined('ENABLE_SSL_CATALOG') && ENABLE_SSL_CATALOG === true) ? HTTPS_SERVER : HTTP_SERVER . DIR_WS_CATALOG) . 'callback/novalnet/callback.php' ."', '8',now())");
        xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_TO', '', '9',now())");
    }

    /**
     * Core Function : remove()
     *
     * Payment module uninstallation
     */
    public function remove()
    {
        xtc_db_query("delete from `gx_configurations` where `key` in ('" . implode("', '", $this->keys()) . "')");
    }

    /**
     * Core Function : keys()
     *
     * @return array keys to display in payment configuration (Backend)
     * @return array
     */
    public function keys()
    {
        static $error_display_count = 0;
        echo '<input type="hidden" id="nn_language" value="'. strtoupper($_SESSION['language_code']) .'" /> ';
        echo '<input type="hidden" id="nn_key_error" value="'. MODULE_PAYMENT_NOVALNET_CREDENTIALS_ERROR .'" />
			  <input type="hidden" id="nn_webhook_error" value="'. MODULE_PAYMENT_NOVALNET_WEBHOOKURL_ERROR .'" />
			  <input type="hidden" id="nn_webhook_text" value="'. MODULE_PAYMENT_NOVALNET_WEBHOOKURL_CONFIGURE_SUCCESS_TEXT .'" />
			  <input type="hidden" id="nn_webhook_alert" value="'. MODULE_PAYMENT_NOVALNET_WEBHOOKURL_CONFIGURE_ALERT_TEXT .'" />';

        if (isset($_SESSION['error_msg_displayed'])) {
            unset($_SESSION['error_msg_displayed']);
        }

        // Validate the merchant configuration in shop backend
        if (!isset($_SESSION['error_msg_displayed']) && $error_display_count == 0) {
            $this->helper->validateMerchantConfiguration();
            $error_display_count++;
        }
        return array(
            'configuration/MODULE_PAYMENT_NOVALNET_SIGNATURE',
            'configuration/MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY',
            'configuration/MODULE_PAYMENT_NOVALNET_TARIFF_ID',
            'configuration/MODULE_PAYMENT_NOVALNET_CLIENT_KEY',
            'configuration/MODULE_PAYMENT_NOVALNET_PROJECT_ID',
            'configuration/MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_COMPLETE',
            'configuration/MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED',
            'configuration/MODULE_PAYMENT_NOVALNET_CALLBACK_TEST_MODE',
            'configuration/MODULE_PAYMENT_NOVALNET_CALLBACK_URL',
            'configuration/MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_TO',
         );
    }

    /**
     * Check Novalnet column in admin access table
     */
    public function checkAdminAccess()
    {
        $sql_file     = DIR_FS_CATALOG . 'ext/novalnet/sql/db_12_0_0.sql';
        $sql_lines    = file_get_contents($sql_file);
        $sql_linesArr = explode(";", $sql_lines);
        foreach ($sql_linesArr as $sql) {
            if (trim($sql) > '') {
                xtc_db_query($sql);
            }
        }

        // Check whether novalnet table exist in shop
        $nn_check = xtc_db_query('DESC novalnet_transaction_detail');
        $nn_alter_table = true;

        // Alter the novalnet table if already exists in shop
        while ($checks_column = xtc_db_fetch_array($nn_check)) {
            if (in_array($checks_column['Field'], ['gateway_status'])) {
                xtc_db_query('ALTER TABLE novalnet_transaction_detail CHANGE gateway_status status varchar(60) COMMENT "Transaction status"');
            }

            if (in_array($checks_column['Field'], ['callback_amount'])) {
                xtc_db_query('ALTER TABLE novalnet_transaction_detail CHANGE callback_amount paid_amount int(11) DEFAULT NULL COMMENT "Paid amount"');
            }

            // Drop old table columns
            foreach (['vendor', 'product', 'auth_code', 'tariff_id', 'subs_id', 'test_mode', 'novalnet_order_date', 'process_key', 'reference_transaction', 'payment_ref', 'next_payment_date', 'payment_id'] as $column) {
                if (in_array($checks_column['Field'], [$column])) {
                    xtc_db_query('ALTER TABLE `novalnet_transaction_detail` DROP COLUMN ' . $column);
                }
            }

            if (in_array($checks_column['Field'], array('date', 'instalment_cycle_details', 'paid_amount'))) {
                $nn_alter_table = false;
            }
        }

        // Add new columns
        if ($nn_alter_table) {
            xtc_db_query('ALTER TABLE `novalnet_transaction_detail` ADD `date` timestamp DEFAULT CURRENT_TIMESTAMP');
            xtc_db_query('ALTER TABLE `novalnet_transaction_detail` ADD `instalment_cycle_details` text NULL COMMENT "Instalment information used in gateways"');
            xtc_db_query('ALTER TABLE `novalnet_transaction_detail` ADD `paid_amount` int(11) COMMENT "Paid amount"');
        }
    }
}
