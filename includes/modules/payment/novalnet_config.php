<?php
/**
 * Novalnet payment module related file
 * This module is used for real time processing of
 * Novalnet transaction of customers.
 *
 * Copyright (c) Novalnet
 *
 * Released under the GNU General Public License
 * This free contribution made by request.
 * If you have found this script useful a small
 * recommendation as well as a comment on merchant form
 * would be greatly appreciated.
 *
 * Script : novalnet_config.php
 *
 */
require_once(DIR_FS_CATALOG . 'includes/external/novalnet/NovalnetHelper.class.php');
class novalnet_config {
	var $code,$title,$description,$sort_order,$enabled;

	/**
     * Core Function : Constructor()
     *
     */
    public function __construct() {
        $this->code        = 'novalnet_config';
        $this->title       = MODULE_PAYMENT_NOVALNET_CONFIG_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_NOVALNET_CONFIG_TEXT_DESCRIPTION;
		$this->sort_order  = 0;
        $this->enabled     = false;
    }

    /**
     * Checks for payment installation status
     *
     * @return boolean
     */
    function check() {
        if (!isset($this->_check)) {
            $check_query  = xtc_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_NOVALNET_PUBLIC_KEY'");
            $this->_check = xtc_db_num_rows($check_query);
        }
        return $this->_check;
    }

    /**
     * Payment module installation
     *
     * @return boolean
     */
    function install() {
	xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . "(configuration_key, configuration_value, configuration_group_id, sort_order,set_function, use_function, date_added) VALUES
        ('MODULE_PAYMENT_NOVALNET_PUBLIC_KEY', '', '6', '1', '', '', now()),
        ('MODULE_PAYMENT_NOVALNET_CONFIG_ALIAS', 'NN_CONFIG', '6', '0', '', '', now()),
        ('MODULE_PAYMENT_NOVALNET_VENDOR_ID', '', '6', '2', '', '', now()),
        ('MODULE_PAYMENT_NOVALNET_AUTHCODE', '', '6', '3', '', '', now()),
        ('MODULE_PAYMENT_NOVALNET_PRODUCT_ID', '', '6', '4', '', '', now()),
        ('MODULE_PAYMENT_NOVALNET_TARIFF_ID', '', '6', '5', '', '', now()),
        ('MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY', '', '6', '6', '', '', now()),
        ('MODULE_PAYMENT_NOVALNET_MANUAL_CHECK_LIMIT', '', '6', '7', '', '', now()),
        ('MODULE_PAYMENT_NOVALNET_TEST_ORDER_EMAIL_NOTIFICATION','False', '6', '8', 'xtc_mod_select_option(array(\'True\' => MODULE_PAYMENT_NOVALNET_TRUE,\'False\' => MODULE_PAYMENT_NOVALNET_FALSE,),\'MODULE_PAYMENT_NOVALNET_TEST_ORDER_EMAIL_NOTIFICATION\'," . MODULE_PAYMENT_NOVALNET_TEST_ORDER_EMAIL_NOTIFICATION . ",','',now()),
        ('MODULE_PAYMENT_NOVALNET_LAST_SUCCESSFULL_PAYMENT_SELECTION','False', '6', '9', 'xtc_mod_select_option(array(\'True\' => MODULE_PAYMENT_NOVALNET_TRUE,\'False\' => MODULE_PAYMENT_NOVALNET_FALSE,),\'MODULE_PAYMENT_NOVALNET_LAST_SUCCESSFULL_PAYMENT_SELECTION\'," . MODULE_PAYMENT_NOVALNET_LAST_SUCCESSFULL_PAYMENT_SELECTION . ",' ,'',now()),
        ('MODULE_PAYMENT_NOVALNET_PROXY', '', '6', '10', '', '', now()),
        ('MODULE_PAYMENT_NOVALNET_GATEWAY_TIMEOUT', '240', '6', '11', '', '', now()),
        ('MODULE_PAYMENT_NOVALNET_REFERRER_ID', '', '6', '12', '', '', now()),
        ('MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY','True', '6', '13', 'xtc_mod_select_option(array(\'True\' => MODULE_PAYMENT_NOVALNET_TRUE,\'False\' => MODULE_PAYMENT_NOVALNET_FALSE,),\'MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY\'," . MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY . ",' ,'',now()),
        ('MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_COMPLETE', '0',  '6', '14', 'xtc_cfg_pull_down_order_statuses(', 'xtc_get_order_status_name', now()),
        ('MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED', '0',  '6', '15', 'xtc_cfg_pull_down_order_statuses(', 'xtc_get_order_status_name',now()),
        ('MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD', '', '6', '16', '', '', now()),
        ('MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD2_AMOUNT', '', '6', '17', '', '', now()),
        ('MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD2', '', '6', '18', '', '', now()),
        ('MODULE_PAYMENT_NOVALNET_SUBSCRIPTION_CANCEL', '0',  '6', '19', 'xtc_cfg_pull_down_order_statuses(', 'xtc_get_order_status_name',now()),
        ('MODULE_PAYMENT_NOVALNET_CALLBACK_DEBUG_MODE','False', '6', '20', 'xtc_mod_select_option(array(\'True\' => MODULE_PAYMENT_NOVALNET_TRUE,\'False\' => MODULE_PAYMENT_NOVALNET_FALSE,),\'MODULE_PAYMENT_NOVALNET_CALLBACK_DEBUG_MODE\'," . MODULE_PAYMENT_NOVALNET_CALLBACK_DEBUG_MODE . ",','',now()),
        ('MODULE_PAYMENT_NOVALNET_CALLBACK_TEST_MODE','False', '6', '21', 'xtc_mod_select_option(array(\'True\' => MODULE_PAYMENT_NOVALNET_TRUE,\'False\' => MODULE_PAYMENT_NOVALNET_FALSE,),\'MODULE_PAYMENT_NOVALNET_CALLBACK_TEST_MODE\'," . MODULE_PAYMENT_NOVALNET_CALLBACK_TEST_MODE . ",','',now()),
        ('MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_SEND','False', '6', '22', 'xtc_mod_select_option(array(\'True\' => MODULE_PAYMENT_NOVALNET_TRUE,\'False\' => MODULE_PAYMENT_NOVALNET_FALSE,),\'MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_SEND\'," . MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_SEND . ",' ,'',now()),
        ('MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_TO', '', '6', '23','','', now()),
        ('MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_BCC', '', '6', '24','','', now()),
        ('MODULE_PAYMENT_NOVALNET_CONFIG_ALLOWED', '', '6', '25', '', '', now()),
        ('MODULE_PAYMENT_NOVALNET_CALLBACK_URL', '" . ((defined('ENABLE_SSL_CATALOG') && ENABLE_SSL_CATALOG === true) ? HTTPS_SERVER : HTTP_SERVER . DIR_WS_CATALOG) . 'callback/novalnet/callback.php' . "','6', '26','','', now())");
        $this->installQuery();
    }

    /**
     * Payment module uninstallation
     *
     * @return boolean
     */
    function remove() {
		xtc_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", array_merge($this->keys(),array('MODULE_PAYMENT_NOVALNET_CONFIG_ALLOWED','MODULE_PAYMENT_NOVALNET_CONFIG_ALIAS'))) . "')");
    }

    /**
     * Return keys to display in payment configuration (Backend)
     *
     * @return boolean
     */
    function keys() {
		global $gx_version; // Get gambio version
		if (strpos(MODULE_PAYMENT_INSTALLED, $this->code) !== false && !$_SESSION['novalnet']['api'] && $_GET['module'] == $this->code) {
			$_SESSION['novalnet']['api'] = true;	
			$server_ip = NovalnetHelper::getIpAddress($_SERVER['SERVER_ADDR']);
			echo '<input type="hidden" id="server_ip" value="' . $server_ip . '" /><input type="hidden" id="nn_api_shoproot" value="' . DIR_WS_CATALOG . '" /><input type="hidden" id="nn_api_config_call" value="1"> <input type="hidden" id="nn_language" value="' . strtoupper($_SESSION['language_code']) . '" /> <script src="' . DIR_WS_CATALOG . 'includes/external/novalnet/js/novalnet_api.js" type="text/javascript"></script>';
		}
		if(!empty($_SESSION['novalnet']['api']) && empty($_REQUEST['action'])) {
			unset($_SESSION['novalnet']['api']);
		}
		if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'edit' && isset($_SESSION['flag'])) {
            unset($_SESSION['flag']);
        }
        // Validate the merchant configuration in shop backend    
        if (!$_SESSION['flag']) {
            NovalnetHelper::validateMerchantConfiguration();
        }
		$alias_menu = array();
		if($gx_version >= '3.1.1.0' ) {
			$alias_menu = array_merge($alias_menu,array('MODULE_PAYMENT_NOVALNET_CONFIG_ALIAS'));
		}
        $return_key = array_merge(array(
            'MODULE_PAYMENT_NOVALNET_PUBLIC_KEY',
            'MODULE_PAYMENT_NOVALNET_VENDOR_ID',
            'MODULE_PAYMENT_NOVALNET_AUTHCODE',
            'MODULE_PAYMENT_NOVALNET_PRODUCT_ID',
            'MODULE_PAYMENT_NOVALNET_TARIFF_ID',
            'MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY',
            'MODULE_PAYMENT_NOVALNET_MANUAL_CHECK_LIMIT',
            'MODULE_PAYMENT_NOVALNET_TEST_ORDER_EMAIL_NOTIFICATION',
            'MODULE_PAYMENT_NOVALNET_LAST_SUCCESSFULL_PAYMENT_SELECTION',
            'MODULE_PAYMENT_NOVALNET_PROXY',
            'MODULE_PAYMENT_NOVALNET_GATEWAY_TIMEOUT',
            'MODULE_PAYMENT_NOVALNET_REFERRER_ID',
            'MODULE_PAYMENT_NOVALNET_PAYMENT_LOGO_DISPLAY',
            'MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_COMPLETE',
            'MODULE_PAYMENT_NOVALNET_ONHOLD_ORDER_CANCELLED',
            'MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD',
            'MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD2_AMOUNT',
            'MODULE_PAYMENT_NOVALNET_TARIFF_PERIOD2',
            'MODULE_PAYMENT_NOVALNET_SUBSCRIPTION_CANCEL',
            'MODULE_PAYMENT_NOVALNET_CALLBACK_DEBUG_MODE',
            'MODULE_PAYMENT_NOVALNET_CALLBACK_TEST_MODE',
            'MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_SEND',
            'MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_TO',
            'MODULE_PAYMENT_NOVALNET_CALLBACK_MAIL_BCC',
            'MODULE_PAYMENT_NOVALNET_CALLBACK_URL'
        ),$alias_menu);
        foreach ($return_key as $key => $value) {
            if( ($gx_version >= '3.1.1.0' && $value == 'MODULE_PAYMENT_NOVALNET_CONFIG_ALIAS')) {
				unset($return_key[$key]);
                break;
			}
        }
         return $return_key;
    }

    /**
     * Installing Novalnet tables
     */
    function installQuery() {
		$novalnet_check      = xtc_db_query('DESC ' . TABLE_ADMIN_ACCESS);
        $novalet_alter_table = false;
        // Check novalnet column in admin access table
        while ($check_column = xtc_db_fetch_array($novalnet_check)) {
            if (in_array($check_column['Field'], array('novalnet_extension_helper','novalnet'))) {
                $novalet_alter_table = true;
                break;
            }
        }
        if (!$novalet_alter_table) {
            xtc_db_query("ALTER TABLE " . TABLE_ADMIN_ACCESS . " ADD novalnet int(1) NOT NULL DEFAULT '1',COMMENT='Novalnet Admin page'");
            xtc_db_query("ALTER TABLE " . TABLE_ADMIN_ACCESS . " ADD novalnet_extension_helper int(1) NOT NULL DEFAULT '1',COMMENT='Novalnet Admin extension'");
		}
		// Import Novalnet package SQL tables
		$sql_file     = DIR_FS_CATALOG . 'includes/external/novalnet/sql/db.sql';
		$sql_lines    = file_get_contents($sql_file);
		$sql_linesArr = explode(";", $sql_lines);
		foreach ($sql_linesArr as $sql) {
			if (trim($sql) > '') {
				xtc_db_query($sql);
			}
		}
		// Check wheather novalnet table exist in shop 
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
    }
}

