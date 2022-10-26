<?php
/**
 * Novalnet payment module
 *
 * This script is used for shipping address and method update
 * for wallet payments
 *
 * @author     Novalnet AG
 * @copyright  Copyright (c) Novalnet
 * @license    https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link       https://www.novalnet.de
 *
 * Script : novalnet_shipping.php
 */

include ('includes/application_top.php');
require_once('includes/classes/order.php');
require_once ('includes/classes/order_total.php');
require_once('inc/xtc_get_country_name.inc.php');
require_once('inc/xtc_get_tax_rate.inc.php');
include ('includes/filenames.php');
include_once('lang/'. $_SESSION['language'].'/modules/payment/novalnet_applepay.php');
global $xtPrice;
$post = $_REQUEST;
if($post['action'] == 'get_variant_product_amount') {
	$coo_properties_control = MainFactory::create_object('PropertiesControl');
	$amount = 0;
	$combi_id = $coo_properties_control->get_available_combis_ids_by_values($post['products_id'], $post['variant_info']);
	if(empty($_SESSION['cart'])) {
		$_SESSION['cart'] = new shoppingCart();
	}
	$cart_object = $_SESSION['cart'];
	$cart_object->add_cart((int)$post['products_id'], $cart_quantity, isset($post['id'])?$post['id']:'');
	$amount = 0;
	if($combi_id) {
		$variant_data = $coo_properties_control->get_properties_combis_details($combi_id[0], 1);
		foreach ($variant_data as $key => $value) {
			$amount += $value['value_price'];
		}
	}
	$response = json_encode(['amount' => (string)($amount * 100)], true);
	echo $response;
	exit;
}


if ($post['action'] == 'add_virtual_product_in_cart') {
	if (isset($post['products_id']) && isset($post['products_qty'])) { // Add product to cart in product page
		if(empty($_SESSION['cart'])) {
			$_SESSION['cart'] = new shoppingCart();
		}
		$cart_object = $_SESSION['cart'];
		if (isset($post['products_id']) && is_numeric($post['products_id']) && isset($post['products_qty']) && $post['products_qty'] > 0) {
			$cart_quantity = (xtc_remove_non_numeric($post['products_qty']) + $cart_object->get_quantity(xtc_get_uprid($post['products_id'], $post['attribute_info'])));
			$cart_object->add_cart((int)$post['products_id'], $cart_quantity, $post['attribute_info']);

		}
	}
	$order = new order();
	$virtual_articleDetails = [];
	if ($post['get_article_details'] == 1) {
		foreach($order->products as $products) {
			$virtual_articleDetails[] = array(
								    'label'=> $products['name']. ' x ' .$products['qty'],
									 'amount' => (string)(($products['qty'] * $products['price'])*100),
					 				 'type' => 'SUBTOTAL',
								);
		}
		if ($order->info['tax'] != 0) {
			$virtual_articleDetails[] = array(
				'label'		=> 'Tax',
				'amount' 	=> (string)((round($order->info['tax'], 2))*100),
				'type' 		=> 'SUBTOTAL'
			);
		}
	}
	$data = json_encode(['amount' => (string)($order->info['total'] * 100), 'article_details' => $virtual_articleDetails], true);
	echo $data;
	exit;
}

if ($post['action'] == 'updated_amount') {
	require_once(DIR_WS_CLASSES.'order.php');
	$order = new order();
	$articleDetails = [];
	if ($post['get_article_details'] == 1) {
		foreach($order->products as $products) {
			$articleDetails[] = array(
								    'label'=> $products['name']. ' x ' .$products['qty'],
									 'amount' => (string)(($products['qty'] * $products['price'])*100),
					 				 'type' => 'SUBTOTAL',
								);
		}
		if ($order->info['tax'] != 0) {
			$articleDetails[] = array(
				'label'		=> 'Tax',
				'amount' 	=> (string)((round($order->info['tax'], 2))*100),
				'type' 		=> 'SUBTOTAL'
			);
		}
	}
	$response = json_encode(['amount' => (string)($order->info['total'] * 100), 'article_details' => $articleDetails], true);
	echo $response;
	exit;
}

// Shipping address change
if ($post['action'] == 'novalnet_shipping_address_update') {
	$response = $post['shippingInfo'];
	$received_address = json_decode($response, true);
	$coo_properties_control = MainFactory::create_object('PropertiesControl');
	$combi_id = $coo_properties_control->get_available_combis_ids_by_values($post['products_id'], $post['variant_info']);
	if (isset($post['products_id']) && isset($post['products_qty'])) { // Add product to cart in product page
		if(empty($_SESSION['cart'])) {
			$_SESSION['cart'] = new shoppingCart();
		}
		$cart_object = $_SESSION['cart'];
		if (isset($post['products_id']) && is_numeric($post['products_id']) && isset($post['products_qty']) && $post['products_qty'] > 0) {

			$cart_quantity = (xtc_remove_non_numeric($post['products_qty']) + $cart_object->get_quantity(xtc_get_uprid($post['products_id'], isset($post['id']) ? $post['id'] : '')));

			if ($cart_quantity > MAX_PRODUCTS_QTY) {
				$cart_quantity = MAX_PRODUCTS_QTY;
				$messageStack->add_session('global', sprintf(MAX_PROD_QTY_EXCEEDED, xtc_get_products_name($post['products_id'])));
			}

			$cart_object->add_cart((int)$post['products_id'], $cart_quantity, '', true, $combi_id[0]);

		}
	}
	$order = new order();
	$delivery_zone_query = xtc_db_fetch_array(xtc_db_query("SELECT countries_id, countries_iso_code_3 FROM " . TABLE_COUNTRIES . " WHERE countries_iso_code_2 = '" . $received_address['address']['countryCode']."'"));
	$order->delivery['country']['id'] 		  = $delivery_zone_query['countries_id'];
	$order->delivery['country']['iso_code_2'] = $received_address['address']['countryCode'];// assign country code in order object
	$order->delivery['country']['iso_code_3'] = $delivery_zone_query['countries_iso_code_3'];
	$order->delivery['country_id']   		  = $delivery_zone_query['countries_id'];
	$order->delivery['country']['title']      = '';
	$order->delivery['zone_id']    		      = 0;
	$zone_query = xtc_db_fetch_array(xtc_db_query("SELECT zone_id FROM " . TABLE_ZONES. " WHERE zone_country_id = '".$delivery_zone_query['countries_id']."' and zone_code = '" . $received_address['address']['administrativeArea']."'"));

	$_SESSION['delivery_zone'] = $received_address['address']['countryCode'];

	if (!empty($order->products)) {
		$order->info['subtotal'] = 0;
		$order->info['total'] = 0;
		$order->info['tax'] = 0;
		$order->info['tax_groups'] =[];
	}
	if (MODULE_ORDER_TOTAL_SHIPPING_STATUS == 'true') {
		require_once ('includes/classes/shipping.php');
		$shipping_obj = new shipping();
		// Load all enabled shipping modules
		$quotes = $shipping_obj->quote();

		  foreach ($order->products as $key => $products) {
			$tax_removed_amount = $xtPrice->xtcRemoveTax($order->products[$key]['price'], $order->products[$key]['tax']);

			$tax_rate = xtc_get_tax_rate($order->products[$key]['tax_class_id'], $delivery_zone_query['countries_id'], $zone_query['zone_id']);

			$products_price = $xtPrice->xtcAddTax($tax_removed_amount, $tax_rate, false);

			if ($tax_rate != 0) {
				$tax_value = ($products_price- $tax_removed_amount) * $order->products[$key]['qty'];
			}

			$products = array(
							'tax_class_id' => $products['tax_class_id'],
							'tax' => xtc_get_tax_rate($products['tax_class_id'], $delivery_zone_query['countries_id'], $zone_query['zone_id']),
							'tax_description' => xtc_get_tax_description($products['tax_class_id'], $delivery_zone_query['countries_id'], $zone_query['zone_id']),
							'price' => $products_price,
							'price_formated' => $xtPrice->xtcFormat($products_price, true),
							'final_price' => $products_price * $products['qty'],
							'final_price_formated' => $xtPrice->xtcFormat($products_price * $products['qty'], true),
							);
				$order->products[$key]['tax_class_id'] = $products['tax_class_id'];
				$order->products[$key]['tax'] = $products['tax'];
				$order->products[$key]['tax_description'] = $products['tax_description'];
				$order->products[$key]['price'] = $products['price'];
				$order->products[$key]['price_formated'] = $products['price_formated'];
				$order->products[$key]['final_price'] = $products['final_price'];
				$order->products[$key]['final_price_formated'] = $products['final_price_formated'];
				if ($tax_rate != 0) {
					$order->products[$key]['tax_value'] = $tax_value;
				}

				// tax information needed in OrderTaxInformation class for storing orders_tax_sum_items data
				$_SESSION['customer_cart_tax_info'] = [
					$products['tax_description'] => [
						'tax_class_id' => $products['tax_class_id'],
						'country_id'   => $delivery_zone_query['countries_id'],
						'zone_id'      => $zone_query['zone_id']
					]
				];

			$order->info['subtotal'] += $order->products[$key]['final_price'];
			if ($tax_rate != 0) {
				$order->info['tax'] += $order->products[$key]['tax_value'];
			}
			$products_tax_description = $products['tax_description'];

			// Price incl tax
			if ($_SESSION['customers_status']['customers_status_show_price_tax'] == '1') {
				$order->info['tax_groups'][TAX_ADD_TAX . "$products_tax_description"] += (string)((round($order->products[$key]['tax_value'], 2)));
			}

		}

		$articleDetails = [];
		foreach ($order->products as $products) {
			$articleDetails[] = array(
				'label'		=> $products['name']. 'x' .$products['qty'],
				'amount' 	=> (string)(($products['qty'] * $products['price'])*100),
				'type' 		=> 'SUBTOTAL'
			);

			if ($products['discount_allowed'] > 0) {
				$articleDetails[] = array(
					'label'	 	=> 'discount',
					'amount' 	=> (string)(int) ($products['discount_allowed']*100),
					'type' 	 	=> 'SUBTOTAL'
				);
			}
			$total += ($products['qty'] * $products['price']);
		}

		// Price incl tax
		if ($order->info['tax'] != 0) {
			$articleDetails[] = array(
				'label'		=> 'Tax',
				'amount' 	=> (string)((round($order->info['tax'], 2))*100),
				'type' 		=> 'SUBTOTAL'
			);
		}
		if(!empty($_SESSION['initial_shipping_amount'])) {
			unset($_SESSION['initial_shipping_amount']);
		}
		$shipping_content = array ();
		$count = 1;
		for ($i = 0, $n = sizeof($quotes); $i < $n; $i ++) {
			$value = 0;
			$title = $quotes[$i]['module'];
			if (!isset($quotes[$i]['error'])) {
				for ($j = 0, $n2 = sizeof($quotes[$i]['methods']); $j < $n2; $j ++) {
					if (!defined('SHOW_SHIPPING_MODULE_TITLE') || SHOW_SHIPPING_MODULE_TITLE == 'shipping_default') {
						$title .= ' - ' . $quotes[$i]['methods'][$j]['title'];
					}
					if (isset($quotes[$i]['methods'][$j]['cost']) && $quotes[$i]['methods'][$j]['cost'] > 0) {
						if (isset($quotes[$i]['tax']) && $quotes[$i]['tax'] > 0) {
							$value = ($quotes[$i]['methods'][$j]['cost'] * $quotes['tax']);
						} else {
							$value = ($quotes[$i]['methods'][$j]['cost']);
						}
					}
					$shipping_content[] = array(
						'label' 		=> $title,
						'amount' 		=> (string)($value * 100),
						'identifier' 	=> $quotes[$i]['id'].'_'.$quotes[$i]['methods'][$j]['id'],
						'detail' 		=> ''
					);
					if ($count == 1) {
						$articleDetails[] = array(
							'label'		=> $quotes[$i]['module'],
							'amount' 	=> (string)($value * 100),
							'type' 		=> 'SUBTOTAL'
						);
						$total += $value;
					}
					$count++;
				}
			}
			$order->info['total'] = $order->info['subtotal'] + ($xtPrice->xtcFormat($quotes[$i]['methods'][$j]['cost'], false, 0, true));
		}
		
		$_SESSION['nn_order'] = $order;
		$shipping_address_change = array(
			'amount'			=> (string)($total*100),
			'shipping_address' 	=> $shipping_content,
			'article_details' 	=> $articleDetails
		);
		$_SESSION['initial_shipping_amount'] = $shipping_content['amount'];
		$response = json_encode($shipping_address_change, true);
		echo $response;
		exit;
	}
}

// Shpping method change
if($post['action'] == 'novalnet_shipping_method_update') {
	$order = new order();
	$received_shipping_method = json_decode($post['shippingInfo'], true);
	if(!empty($_SESSION['method_update_shipping_amount'])) {
		unset($_SESSION['method_update_shipping_amount']);
	}
	$articleDetails = [];
	foreach($_SESSION['nn_order']->products as $products) {
		$articleDetails[] = array(
			'label'		=> $products['name']. 'x' .$products['qty'],
			'amount' 	=> (string)(($products['qty'] * $products['price'])*100),
			'type' 		=> 'SUBTOTAL'
		);

		// For discuounts
		if($products['discount_allowed'] > 0) {
			$articleDetails[] = array(
				'label'		=> 'discount',
				'amount' 	=> (string) $products['discount_allowed']*100,
				'type' 		=> 'SUBTOTAL'
			);
		}
	}
	// price incl tax

	if ($_SESSION['nn_order']->info['tax'] != 0) {
		$articleDetails[] = array(
			'label'		=> 'Tax',
			'amount' 	=> (string)((round($_SESSION['nn_order']->info['tax'], 2))*100),
			'type' 		=> 'SUBTOTAL'
		);
	}

	$total = ($_SESSION['nn_order']->info['subtotal'] * 100) + ($received_shipping_method['shippingMethod']['amount']*100);
	$_SESSION['method_update_shipping_amount'] = (string)($received_shipping_method['shippingMethod']['amount']*100);
	$articleDetails[] = array(
		'label'		=> $received_shipping_method['shippingMethod']['label'],
		'amount' 	=> (string)($received_shipping_method['shippingMethod']['amount']*100),
		'type' 		=> 'SUBTOTAL');
	$shipping_method_change = array(
		'article_details' 	=> $articleDetails,
		'amount'			=> (string)$total);
	$result = json_encode($shipping_method_change, true);
	echo $result;
	exit;
}
?>
