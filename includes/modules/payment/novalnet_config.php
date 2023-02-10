<?php
/**
 * Novalnet payment module
 *
 * This script is used for global configuration
 *
 * @author     Novalnet AG
 * @copyright  Copyright (c) Novalnet
 * @license    https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link       https://www.novalnet.de
 *
 * Script: novalnet_config.php
 */
require_once(DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php');
class novalnet_config {
	var $code, $title,$sort_order, $description,$enabled;

	/**
	 * Core Function : Constructor()
	 *
	 */
	function __construct() {
		$this->code        = 'novalnet_config';
		$this->title       = defined('MODULE_PAYMENT_NOVALNET_GLOBAL_CONFIG_TEXT_TITLE') ? MODULE_PAYMENT_NOVALNET_GLOBAL_CONFIG_TEXT_TITLE  : '';
		$this->description = defined('MODULE_PAYMENT_NOVALNET_GLOBAL_CONFIG_TEXT_DESC') ? MODULE_PAYMENT_NOVALNET_GLOBAL_CONFIG_TEXT_DESC : '';
		$this->sort_order  = 0;
		$this->enabled     = true;
		$this->novalnet_version = '12.0.3';
	}

	/**
	 *
	 * Core Function : check()
	 *
	 * Checks for payment installation status
	 * @return boolean
	 */
	function check() {
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
	 * @return array
	 */
	function selection() {
		return false;
	}
 
	/**
	 * Core Function : install()
	 *
	 * Payment module installation
	 */
	function install() {
		$this->checkAdminAccess();
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_SIGNATURE', '',  '1',  now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY', '',  '2', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_TARIFF_ID', '',  '3', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CLIENT_KEY', '',  '4', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_PROJECT_ID', '', '5', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_COMPLETE', '1', '4', 'order-status', now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED', '99',  '5', 'order-status',now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `type`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CALLBACK_TEST_MODE','false', '6', 'switcher',now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CALLBACK_URL', '" . ((defined('ENABLE_SSL_CATALOG') && ENABLE_SSL_CATALOG === true) ? HTTPS_SERVER : HTTP_SERVER . DIR_WS_CATALOG) . 'callback/novalnet/callback.php' ."', '7',now())");
		xtc_db_query("insert into `gx_configurations` (`key`, `value`, `sort_order`, `last_modified`) values ('configuration/MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_TO', '', '8',now())");
	}

	/**
	 * Core Function : remove()
	 *
	 * Payment module uninstallation
	 * @return boolean
	 */
	function remove() {
		xtc_db_query("delete from `gx_configurations` where `key` in ('" . implode("', '", $this->keys()) . "')");
	}

	/**
	 * Core Function : keys()
	 *
	 * @return array keys to display in payment configuration (Backend)
	 * @return boolean
	 */
	function keys() {
		static $error_display_count = 0;
		echo '<input type="hidden" id="nn_language" value="'. strtoupper($_SESSION['language_code']) .'" /> ';

		if (isset($_SESSION['error_msg_displayed'])) {
			unset($_SESSION['error_msg_displayed']);
		}

		// Validate the merchant configuration in shop backend
		if (!isset($_SESSION['error_msg_displayed']) && $error_display_count == 0) {
			NovalnetHelper::validateMerchantConfiguration();
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
     *
     */
	function checkAdminAccess() {
		$novalet_alter_table = false;
		if (xtc_db_fetch_array(xtc_db_query("show tables like 'admin_access'"))) {
			$novalnet_check = xtc_db_query('DESC admin_access');
			// Check Novalnet column in admin access table
			while ($check_column = xtc_db_fetch_array($novalnet_check)) {
				if (in_array($check_column['Field'], array('novalnet_extension_helper', 'novalnet'))) {
					$novalet_alter_table = true;
					break;
				}
			}		
			if (!$novalet_alter_table) {
				xtc_db_query('ALTER TABLE admin_access ADD novalnet int(1) NOT NULL DEFAULT "1",COMMENT="Novalnet Admin page"');
				xtc_db_query('ALTER TABLE admin_access ADD novalnet_extension_helper int(1) NOT NULL DEFAULT "1",COMMENT="Novalnet Admin extension"');
			}
		}
		$version_table = xtc_db_query("SHOW TABLES LIKE 'novalnet_version_detail'");
        $table_count = $version_table->num_rows; 
        if($table_count > 0) { // If version table exists
			$novalnet = xtc_db_fetch_array(xtc_db_query("SELECT * FROM novalnet_version_detail"));
			if(version_compare($novalnet['version'], '11.3.0', '<=')){
				// Import Novalnet package SQL tables
				$sql_file     = DIR_FS_CATALOG . 'ext/novalnet/sql/db.sql';
				$sql_lines    = file_get_contents($sql_file);
				$sql_linesArr = explode(";", $sql_lines);
				foreach ($sql_linesArr as $sql) {
					if (trim($sql) > '') {
						xtc_db_query($sql);
					}
				}
				
				xtc_db_perform('novalnet_version_detail',array('version' => $this->novalnet_version), 'update', "version='{$novalnet['version']}'");
				
				// Check whether novalnet table exist in shop 
		        $nn_check      = xtc_db_query('DESC ' . 'novalnet_callback_history');
		        $nn_alter_table = false;
		        while ($checks_column = xtc_db_fetch_array($nn_check)) {
		            if (in_array($checks_column['Field'] ,array('callback_datetime','original_tid','order_amount','callback_amount'))) {
		                $nn_alter_table = true;
		                break;
		            }
		        }
		        // Alter the novalnet table if already exists in shop
		        if (!$nn_alter_table) {
					xtc_db_query('ALTER TABLE novalnet_callback_history CHANGE org_tid original_tid bigint(20) unsigned COMMENT "Original Transaction ID"');
					xtc_db_query('ALTER TABLE novalnet_callback_history CHANGE amount order_amount int(20) COMMENT "Amount in minimum unit of currency. E.g. enter 100 which is equal to 1.00"');
					xtc_db_query('ALTER TABLE novalnet_callback_history CHANGE total_amount callback_amount int(11) COMMENT "Amount in minimum unit of currency. E.g. enter 100 which is equal to 1.00"');
					xtc_db_query('ALTER TABLE novalnet_callback_history CHANGE `date` callback_datetime datetime COMMENT "Callback excute DATE TIME"');
				}
				
				// Check whether novalnet table exist in shop 
		        $nn_check      = xtc_db_query('DESC ' . 'novalnet_transaction_detail');
		        $nn_alter_table = false;
		        while ($checks_column = xtc_db_fetch_array($nn_check)) {
		            if (in_array($checks_column['Field'] ,array('status', 'instalment_cycle_details', 'callback_amount'))) {
		                $nn_alter_table = true;
		                break;
		            }
		        }
		        // Alter the novalnet table if already exists in shop
		        if (!$nn_alter_table) {
					xtc_db_query('ALTER TABLE novalnet_transaction_detail CHANGE gateway_status status varchar(60) COMMENT "Transaction status"');
					xtc_db_query('ALTER TABLE novalnet_transaction_detail ADD instalment_cycle_details text NULL COMMENT "Instalment information used in gateways"');
					xtc_db_query('ALTER TABLE novalnet_transaction_detail ADD callback_amount int(20) NULL COMMENT "Instalment information used in gateways"');
				}
			} else {
				$sql_file     = DIR_FS_CATALOG . 'ext/novalnet/sql/db_12_0_0.sql';
				$sql_lines    = file_get_contents($sql_file);
				$sql_linesArr = explode(";", $sql_lines);
				foreach ($sql_linesArr as $sql) {
					if (trim($sql) > '') {
						xtc_db_query($sql);
					}
				}
			}
		} else {
			$sql_file     = DIR_FS_CATALOG . 'ext/novalnet/sql/db_12_0_0.sql';
			$sql_lines    = file_get_contents($sql_file);
			$sql_linesArr = explode(";", $sql_lines);
			foreach ($sql_linesArr as $sql) {
				if (trim($sql) > '') {
					xtc_db_query($sql);
				}
			}
		}
	}
}
