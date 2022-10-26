<?php
/**
 * Novalnet payment module
 * This module is used for real time processing of
 * Novalnet transaction of customers.
 *
 * @author     Novalnet AG
 * @copyright  Copyright (c) Novalnet
 * @license    https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link       https://www.novalnet.de
 *
 * Script : novalnet.php
 */

include ('includes/application_top.php');
require_once DIR_FS_INC . 'xtc_validate_email.inc.php';
require_once DIR_FS_INC . 'xtc_random_charcode.inc.php';
require_once DIR_FS_INC . 'xtc_php_mail.inc.php';
global $xtPrice;
$response = $_POST;

// Initialize smarty
$smarty = new Smarty; // To send mail from shop

include ('includes/filenames.php');
include_once('inc/xtc_format_price_order.inc.php');

// Load selected payment module
require_once('includes/classes/payment.php');

$payment_modules = new payment($response['payment_name']);
if (isset($response['payment_name'])) {
	$payment_modules = new payment($response['payment_name']);
} else {
	$payment_modules = new payment($_SESSION['payment']);
}

if ($_GET['status_code'] == 100 && isset($_GET['tid'])) {
	$payment_modules->before_process();
	$payment_modules->after_process();

	require_once (DIR_FS_CATALOG. 'system/classes/orders/SendOrderProcess.inc.php');
	$coo_send_order_process = MainFactory::create_object('SendOrderProcess');
	$coo_send_order_process->set_('order_id',$_SESSION['tmp_oID']);
	$coo_send_order_process->proceed();

	// BOF GM_MOD GX-Customizer
	require(DIR_FS_CATALOG . 'gm/modules/gm_gprint_order.php');

	// Reset shopping cart
	$_SESSION['cart']->reset(true);
	xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));
}
if (empty($_SESSION['nn_order'])) {
	require_once('includes/classes/order.php');
	$order = new order();
} else {
	$order = $_SESSION['nn_order'];
}

if (!isset($_SESSION['customer_id']) || empty($_SESSION['customer_id'])) {
	$country_query = xtc_db_fetch_array(xtc_db_query("SELECT countries_id FROM " . TABLE_COUNTRIES . " WHERE countries_iso_code_2 = '" . $response['variable_name']['response']['order']['billing']['contact']['countryCode']."'"));
	$stateParam = (($response['payment_name'] == 'novalnet_googlepay' && !empty($response['variable_name']['response']['order']['billing']['contact']['administrativeArea'])) ? $response['variable_name']['response']['order']['billing']['contact']['administrativeArea'] : $response['variable_name']['response']['order']['billing']['countryCode']);
	if (empty($stateParam)) {
		$state_query = xtc_db_fetch_array(xtc_db_query("SELECT zone_id FROM " . TABLE_ZONES . " WHERE zone_country_id = '".$country_query['countries_id']."' limit 1"));
	}
	else {
		$state_query = xtc_db_fetch_array(xtc_db_query("SELECT zone_id FROM " . TABLE_ZONES . " WHERE zone_code = '$stateParam' and zone_country_id = '".$country_query['countries_id']."'"));
	}

	$customer_array = array(
		'firstname' 		=> empty($response['variable_name']['response']['order']['shipping']['contact']['firstName']) ? $response['variable_name']['response']['order']['billing']['contact']['firstName'] : $response['variable_name']['response']['order']['shipping']['contact']['firstName'] ,
		'lastname' 			=> empty($response['variable_name']['response']['order']['shipping']['contact']['lastName']) ? $response['variable_name']['response']['order']['billing']['contact']['lastName'] : $response['variable_name']['response']['order']['shipping']['contact']['lastName'] ,
		'street_address' 	=> $response['variable_name']['response']['order']['billing']['contact']['addressLines'] ,
		'company' 	=> '',
		'vat' 	=> '',
		'telephone' 	=> '',
		'fax' 	=> '',
		'city' 				=> $response['variable_name']['response']['order']['billing']['contact']['locality'],
		'state' 			=> $state_query['zone_id'],
		'postcode' 			=> $response['variable_name']['response']['order']['billing']['contact']['postalCode'] ,
		'country' 			=> $country_query['countries_id'],
		'email_address' 	=> ($response['payment_name'] == 'novalnet_googlepay') ? $response['variable_name']['response']['order']['billing']['contact']['email'] : $response['variable_name']['response']['order']['shipping']['contact']['email'],
		);

$inputTransformer = MainFactory::create('CustomerInputToCollectionTransformer');

/** * @var CountryService $countryService */
$countryService     = StaticGXCoreLoader::getService('Country');

$customerCollection = $inputTransformer->getRegistreeCollectionFromInputArray($customer_array, $countryService);

$createAccountProcess = MainFactory::create('CreateAccountProcess',
                                                        StaticGXCoreLoader::getService('CustomerWrite'),
                                                        $countryService);

			$createAccountProcess->proceedRegistree($customerCollection,
                                                    MainFactory::create('GMLogoManager', 'gm_logo_mail'));

}

$received_address = $response['variable_name']['response'];
$_SESSION['shipping']['id'] = $received_address['order']['shipping']['method']['identifier'];
$_SESSION['received_data'] = $response['variable_name']['response'];
$_SESSION['token'] = $response['variable_name']['response']['transaction']['token'];
$order->info['shipping_method'] = $received_address['order']['shipping']['method']['label'];
$order->info['shipping_class']  = $received_address['order']['shipping']['method']['identifier'];
$order->info['shipping_cost']   = (isset($response['payment_name']) && $response['payment_name'] == 'novalnet_applepay') ||  (isset($_SESSION['payment']) && $_SESSION['payment'] == 'novalnet_applepay') ? (!empty($_SESSION['method_update_shipping_amount']) ? number_format($_SESSION['method_update_shipping_amount']/100, 2) : number_format($_SESSION['initial_shipping_amount']/100, 2)) : number_format($received_address['order']['shipping']['method']['amount']/100, 2);
$order->info['payment_method'] = $_SESSION['payment'];
$order->info['payment_class'] = $_SESSION['payment'];
$order->info['subtotal'] = number_format(($received_address['transaction']['amount']/100),2);
$order->info['total'] = $order->info['subtotal'];

$payment_modules->before_process();

require_once('includes/classes/order_total.php');
$order_total_modules = new order_total();

$order_totals = $order_total_modules->process();

// Check if tmp order need to be created when cart page or product page load
if (${$_SESSION['payment']}->tmpOrders) {
	$tmp = true;
	$orders_status_id = ${$_SESSION['payment']}->tmpStatus;
} else {
	$orders_status_id = constant('MODULE_PAYMENT_'.strtoupper($response['payment_name']).'_ORDER_STATUS');
}

if ($_SESSION['customers_status']['customers_status_ot_discount_flag'] == 1) {
	$discount = $_SESSION['customers_status']['customers_status_ot_discount'];
} else {
	$discount = '0.00';
}
if (!empty($response['variable_name']['response']['order']['billing']['contact']['countryCode'])) {
	$billing_countryQuery = xtc_db_fetch_array(xtc_db_query("SELECT countries_name FROM " . TABLE_COUNTRIES . " WHERE countries_iso_code_2 = '" . $response['variable_name']['response']['order']['billing']['contact']['countryCode']."'"));
}
if (!empty($response['variable_name']['response']['order']['shipping']['contact']['countryCode'])) {
	$delivery_countryQuery = xtc_db_fetch_array(xtc_db_query("SELECT countries_name FROM " . TABLE_COUNTRIES . " WHERE countries_iso_code_2 = '" . $response['variable_name']['response']['order']['shipping']['contact']['countryCode']."'"));
}
$sql_data_array = array(
	'customers_id' 					=> $_SESSION['customer_id'],
	'customers_name' 				=> ($received_address['order']['billing']['contact']['firstName'] . ' ' . $received_address['order']['billing']['contact']['lastName']),
	'customers_firstname' 			=> $received_address['order']['shipping']['contact']['firstName'] ,
	'customers_lastname' 			=>  $received_address['order']['shipping']['contact']['lastName'] ,
	'customers_gender' 				=> !empty($order->customer['gender']) ? $order->customer['gender'] : 'o',
	'customers_status' 				=> $_SESSION['customers_status']['customers_status_id'],
	'customers_status_name' 		=> $_SESSION['customers_status']['customers_status_name'],
	'customers_status_image' 		=> $_SESSION['customers_status']['customers_status_image'],
	'customers_status_discount' 	=> $discount,
	'customers_street_address' 		=> $received_address['order']['billing']['contact']['addressLines'] ,
	'customers_suburb' 				=> $order->customer['suburb'],
	'customers_city' 				=> $received_address['order']['billing']['contact']['locality'],
	'customers_postcode' 			=> $received_address['order']['billing']['contact']['postalCode'] ,
	'customers_country' 			=> $received_address['order']['billing']['contact']['country'] ,
	'customers_email_address' 		=> ($response['payment_name'] == 'novalnet_googlepay') ? $received_address['order']['billing']['contact']['email'] : $received_address['order']['shipping']['contact']['email'],
	'customers_address_format_id' 	=> $order->customer['format_id'],
	'delivery_name' 				=> (empty($received_address['order']['shipping']['contact']['firstName']) ? $received_address['order']['billing']['contact']['firstName'] . ' ' . $received_address['order']['billing']['contact']['lastName'] : $received_address['order']['shipping']['contact']['firstName'] . ' ' . $received_address['order']['shipping']['contact']['lastName']),
	'delivery_firstname' 			=> (empty($received_address['order']['shipping']['contact']['firstName']) ? $received_address['order']['billing']['contact']['firstName'] : $received_address['order']['shipping']['contact']['firstName']),
	'delivery_lastname' 			=> (empty($received_address['order']['shipping']['contact']['lastName']) ? $received_address['order']['shipping']['contact']['lastName'] : $received_address['order']['shipping']['contact']['lastName']),
	'delivery_street_address' 		=> (empty($received_address['order']['shipping']['contact']['addressLines']) ? $received_address['order']['billing']['contact']['addressLines'] : $received_address['order']['shipping']['contact']['addressLines']),
	'delivery_city' 				=> (empty($received_address['order']['shipping']['contact']['locality']) ? $received_address['order']['billing']['contact']['locality'] : $received_address['order']['shipping']['contact']['locality']),
	'delivery_postcode' 			=> (empty($received_address['order']['shipping']['contact']['postalCode']) ? $received_address['order']['billing']['contact']['postalCode'] : $received_address['order']['shipping']['contact']['postalCode']),
	'delivery_country' 				=> (empty($received_address['order']['shipping']['contact']['countryCode']) ? $billing_countryQuery['countries_name'] : $delivery_countryQuery['countries_name']),
	'delivery_country_iso_code_2' 	=> (empty($received_address['order']['shipping']['contact']['countryCode']) ? $received_address['order']['billing']['contact']['countryCode'] : $received_address['order']['shipping']['contact']['countryCode']),
	'delivery_address_format_id' 	=> $order->delivery['format_id'],
	'billing_name' 					=> $received_address['order']['billing']['contact']['firstName'] . ' ' . $received_address['order']['billing']['contact']['lastName'],
	'billing_firstname' 			=> $received_address['order']['billing']['contact']['firstName'],
	'billing_lastname' 				=> $received_address['order']['billing']['contact']['lastName'],
	'billing_street_address' 		=> $received_address['order']['billing']['contact']['addressLines'],
	'billing_city' 					=> $received_address['order']['billing']['contact']['locality'],
	'billing_postcode' 				=> $received_address['order']['billing']['contact']['postalCode'],
	'billing_country' 				=> $billing_countryQuery['countries_name'],
	'billing_country_iso_code_2' 	=> $received_address['order']['billing']['contact']['countryCode'],
	'billing_address_format_id' 	=> $order->delivery['format_id'],
	'payment_method' 				=> $order->info['payment_method'],
	'payment_class' 				=> ${$_SESSION['payment']}->title,
	'shipping_method' 				=> $received_address['order']['shipping']['method']['label'],
	'shipping_class' 				=> $received_address['order']['shipping']['method']['identifier'],
	'date_purchased' 				=> 'now()',
	'orders_status' 				=> $orders_status_id,
	'currency' 						=> $order->info['currency'],
	'currency_value' 				=> $order->info['currency_value'],
	'account_type' 					=> $_SESSION['account_type'],
	'conversion_type' 				=> 1,
	'customers_ip' 					=> $_SERVER['REMOTE_ADDR'],
	'language' 						=> $_SESSION['language'],
);
// refID
$refID = '';
if (isset($_SESSION['tracking']['refID'])) {
	$refID = $_SESSION['tracking']['refID'];
} else {
	$campaign_query = xtc_db_query("SELECT cp.campaigns_refID FROM " . TABLE_CUSTOMERS . " c JOIN " . TABLE_CAMPAIGNS . " cp ON cp.campaigns_id = c.refferers_id WHERE c.customers_id = '" . (int)$_SESSION['customer_id']."'");
	$campaign = xtc_db_fetch_array($campaign_query);
	$refID = $campaign['campaigns_refID'];
}

if ($refID != '') {
	$sql_data_array['campaign'] = $refID;
}
// Check if late or direct sale
$customers_logon_query = xtc_db_query("SELECT customers_info_number_of_logons FROM " . TABLE_CUSTOMERS_INFO . " WHERE customers_info_id  = '" . (int)$_SESSION['customer_id'] . "'");	  $customers_logon = xtc_db_fetch_array($customers_logon_query);
if ($customers_logon['customers_info_number_of_logons'] > 1) {
	$sql_data_array['conversion_type'] = 2;
}
xtc_db_perform(TABLE_ORDERS, $sql_data_array);
$insert_id = xtc_db_insert_id();
$_SESSION['tmp_oID'] = $insert_id;

for ($i = 0, $n = sizeof($order_totals); $i < $n; $i ++) {
	$sql_data_array = array(
		'orders_id'  => $insert_id,
		'title' 	 => $order_totals[$i]['title'],
		'text' 		 => $order_totals[$i]['text'],
		'value' 	 => $order_totals[$i]['value'],
		'class' 	 => $order_totals[$i]['code'],
		'sort_order' => $order_totals[$i]['sort_order']
	);
	xtc_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);

}

/* magnalister v1.0.1 */
if (function_exists('magnaExecute')) magnaExecute('magnaInsertOrderDetails', array('oID' => $insert_id), array('order_details.php'));
if (function_exists('magnaExecute')) magnaExecute('magnaInventoryUpdate', array('action' => 'inventoryUpdateOrder'), array('inventoryUpdate.php'));
/* END magnalister */
$customer_notification = (SEND_EMAILS == 'true') ? '1' : '0';
$sql_data_array = array(
	'orders_id' 		=> $insert_id,
	'orders_status_id' 	=> $orders_status_id,
	'date_added' 		=> 'now()',
	'customer_notified' => $customer_notification,
	'comments' 			=> $order->info['comments']
);

xtc_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
$_SESSION['disable_products'] = array();
for ($i = 0, $n = sizeof($order->products); $i < $n; $i ++) {
	// Stock update
	$stock_set = '';
    if (STOCK_LIMITED == 'true') {
		if (DOWNLOAD_ENABLED == 'true' && STOCK_LIMITED_DOWNLOADS == 'false') {
			$add_stock_query_raw = '';
			$products_attributes = $order->products[$i]['attributes'];
			if (is_array($products_attributes)) {
				$add_stock_query_raw .= " AND pa.options_id = '".$products_attributes[0]['option_id']."' AND pa.options_values_id = '".$products_attributes[0]['value_id']."'";
			}
			$stock_query_raw = "SELECT products_quantity, pad.products_attributes_filename FROM " . TABLE_PRODUCTS . " p LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES." pa ON p.products_id = pa.products_id " . $add_stock_query_raw . " LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad ON pa.products_attributes_id = pad.products_attributes_id WHERE p.products_id = '" . xtc_get_prid($order->products[$i]['id']) . "'";
			// Will work with only one option for downloadable products
			// otherwise, we have to build the query dynamically with a loop
			$stock_query = xtc_db_query($stock_query_raw);
		} else {
			$stock_query = xtc_db_query("SELECT products_quantity FROM " . TABLE_PRODUCTS . " WHERE products_id = '" . xtc_get_prid($order->products[$i]['id']) . "'");
		}
		if (xtc_db_num_rows($stock_query) > 0) {
			$stock_values = xtc_db_fetch_array($stock_query);
			// Do not decrement quantities if products_attributes_filename exists
			if ((DOWNLOAD_ENABLED != 'true') || (!$stock_values['products_attributes_filename'])) {
				$stock_left = $stock_values['products_quantity'] - $order->products[$i]['qty'];
			} else {
				$stock_left = $stock_values['products_quantity'];
			}
			$stock_set = " products_quantity = '".$stock_left."', ";
			if (($stock_left < 1) && (STOCK_CHECKOUT_UPDATE_PRODUCTS_STATUS == 'true')) {
				$_SESSION['disable_products'][] = xtc_get_prid($order->products[$i]['id']);
			}
		}
    }
    // Update product
    xtc_db_query("UPDATE " . TABLE_PRODUCTS . " SET " . $stock_set . "products_ordered = products_ordered + " . sprintf('%d', $order->products[$i]['qty']) . " WHERE products_id = '" .xtc_get_prid($order->products[$i]['id']) . "'");
    $sql_data_array = array(
		'orders_id' 			 => $insert_id,
		'products_id' 			 => xtc_get_prid($order->products[$i]['id']),
		'products_model' 		 => $order->products[$i]['model'],
		'products_name' 		 => $order->products[$i]['name'],
		'products_price' 		 => $order->products[$i]['price'],
		'products_shipping_time' => strip_tags($order->products[$i]['shipping_time']),
		'products_discount_made' => $order->products[$i]['discount_allowed'],
		'final_price' 			 => $order->products[$i]['final_price'],
		'products_tax' 			 => $order->products[$i]['tax'],
		'products_quantity' 	 => $order->products[$i]['qty'],
		'allow_tax' 			 => $_SESSION['customers_status']['customers_status_show_price_tax'],
    );

    foreach(auto_include(DIR_FS_CATALOG.'includes/extra/checkout/checkout_process_products/', 'php') as $file) require ($file);
    xtc_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);
    $order_products_id = xtc_db_insert_id();
    // update specials quantity
    $specials_query = xtc_db_query("SELECT `products_id`, `specials_quantity` FROM `". TABLE_SPECIALS ."` WHERE `products_id` = '" . xtc_get_prid($order->products[$i]['id']) . "'" .`SPECIALS_CONDITIONS`);
    if (xtc_db_num_rows($specials_query)) {
		$specials = xtc_db_fetch_array($specials_query);
		if ($specials['specials_quantity'] != 0) {
			$specials_quantity = ($specials['specials_quantity'] - $order->products[$i]['qty']);
			$stock_set = '';
			if ($specials_quantity < 1) {
				$stock_set = " status = '0', ";
			}
			xtc_db_query("UPDATE `" . TABLE_SPECIALS . "` SET " . $stock_set . "`specials_quantity` = '" . $specials_quantity . "' WHERE `products_id` = '" . xtc_get_prid($order->products[$i]['id']) . "' ");
		}
	}
    $order_total_modules->update_credit_account($i); // GV Code ICW ADDED FOR CREDIT CLASS SYSTEM
    if (isset($order->products[$i]['attributes'])) {
		$order->products[$i]['attributes'] = array_values($order->products[$i]['attributes']); // reset keys for $j
		for ($j = 0, $n2 = sizeof($order->products[$i]['attributes']); $j < $n2; $j ++) {
			// Update attribute stock
			$update_attr_stock = false;
			if (STOCK_LIMITED == 'true' && isset($order->products[$i]['attributes'][$j]['value_id']) && isset($order->products[$i]['attributes'][$j]['option_id'])) {
				$update_attr_stock = true;
				if (DOWNLOAD_ENABLED == 'true' && STOCK_LIMITED_DOWNLOADS == 'false') {
					$attr_stock_query = xtc_db_query("SELECT pad.products_attributes_filename FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad ON pa.products_attributes_id = pad.products_attributes_id WHERE pa.products_id = '" . xtc_get_prid($order->products[$i]['id']) . "' AND pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' AND pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "'");
					$update_attr_stock = ((xtc_db_num_rows($attr_stock_query) > 0) ? false : true);
				}
			}
			// Update attribute stock
			if ($update_attr_stock === true) {
				xtc_db_query("UPDATE " . TABLE_PRODUCTS_ATTRIBUTES . " SET attributes_stock = attributes_stock - '" . $order->products[$i]['qty'] . "' WHERE products_id = '" . xtc_get_prid($order->products[$i]['id']) . "' AND options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' AND options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "'");
			}
			// Attributes
			$sql_data_array = array(
				'orders_id' 				=> $insert_id,
				'orders_products_id' 		=> $order_products_id,
				'products_options' 			=> $order->products[$i]['attributes'][$j]['option'],
				'products_options_values' 	=> $order->products[$i]['attributes'][$j]['value'],
				'options_values_price' 		=> $order->products[$i]['attributes'][$j]['price'],
				'price_prefix' 				=> $order->products[$i]['attributes'][$j]['prefix'],
				'options_id' 				=> $order->products[$i]['attributes'][$j]['option_id'],
				'options_values_id' 		=> $order->products[$i]['attributes'][$j]['value_id'],
			);
			foreach(auto_include(DIR_FS_CATALOG.'includes/extra/checkout/checkout_process_attributes/','php') as $file) require ($file);
			xtc_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);
			// Attributes download
			if (DOWNLOAD_ENABLED == 'true') {
				$attributes_dl_query = xtc_db_query("SELECT pad.products_attributes_maxdays, pad.products_attributes_maxcount, pad.products_attributes_filename FROM " . TABLE_PRODUCTS_ATTRIBUTES." pa LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad ON pa.products_attributes_id = pad.products_attributes_id WHERE pa.products_id = '" .$order->products[$i]['id'] . "' AND pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' AND pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "'");
				$attributes_dl_array = xtc_db_fetch_array($attributes_dl_query);
				if (isset($attributes_dl_array['products_attributes_filename']) && xtc_not_null($attributes_dl_array['products_attributes_filename'])) {
					$sql_data_array = array(
						'orders_id' 				=> $insert_id,
						'orders_products_id' 		=> $order_products_id,
						'orders_products_filename' 	=> $attributes_dl_array['products_attributes_filename'],
						'download_maxdays' 			=> $attributes_dl_array['products_attributes_maxdays'],
						'download_count' 			=> $attributes_dl_array['products_attributes_maxcount'],
					);
					foreach(auto_include(DIR_FS_CATALOG.'includes/extra/checkout/checkout_process_download/','php') as $file) require ($file);
					xtc_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $sql_data_array);
				}
			}
		}
    }
}

foreach(auto_include(DIR_FS_CATALOG.'includes/extra/checkout/checkout_process_order/', 'php') as $file) require ($file);

if ($tmp == true) {
    $payment_modules->payment_action();
    $response = json_encode(['redirect_url' => $_SESSION['nn_redirect_url'], 'isRedirect'=>1], true);
    echo $response;
	exit;
}
$payment_modules->after_process();
require_once (DIR_FS_CATALOG. 'system/classes/orders/SendOrderProcess.inc.php');
$coo_send_order_process = MainFactory::create_object('SendOrderProcess');
$coo_send_order_process->set_('order_id', $insert_id);
$coo_send_order_process->proceed();

// BOF GM_MOD GX-Customizer
require(DIR_FS_CATALOG . 'gm/modules/gm_gprint_order.php');

// Reset shopping cart
$_SESSION['cart']->reset(true);
foreach(auto_include(DIR_FS_CATALOG.'includes/extra/checkout/checkout_process_end/', 'php') as $file) require ($file);
unset($_SESSION['nn_order']);
unset($_SESSION['initial_shipping_amount']);
unset($_SESSION['method_update_shipping_amount']);

// unregister session variables used during checkout
unset($_SESSION['sendto']);
unset($_SESSION['delivery_zone']);
unset($_SESSION['billto']);
unset($_SESSION['shipping']);
unset($_SESSION['payment']);
unset($_SESSION['comments']);
unset($_SESSION['last_order']);
unset($_SESSION['tmp_oID']);
unset($_SESSION['cc']);
unset($_SESSION['disable_products']);
// GV Code Start
if (isset($_SESSION['credit_covers'])) {
	unset($_SESSION['credit_covers']);
}
$return_url = xtc_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL');
$response = json_encode(['return_url' => $return_url], true);
echo $response;
exit;
function auto_include($dir, $ext = 'php', $expr = '*', $flags = 0) {
	$dir = rtrim($dir, '/');
	$files = glob("{$dir}/$expr." . $ext, $flags);
	$files = ((false !== $files) ? $files : array());
	natcasesort($files);
	if (function_exists('debugMessage')) {
		debugMessage('auto_include',$files);
	}
	return $files;
}

?>
