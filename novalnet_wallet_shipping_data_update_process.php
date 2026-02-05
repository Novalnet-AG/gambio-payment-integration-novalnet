<?php
/**
 * Novalnet payment module
 *
 * This script is used for shipping address and method update
 * for wallet payments
 *
 * @author    Novalnet AG
 * @copyright Copyright (c) Novalnet
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link      https://www.novalnet.de
 *
 * Script : novalnet_wallet_shipping_data_update_process.php
 */

require 'includes/application_top.php';
require_once 'includes/classes/order.php';

$post = $_REQUEST;

if ($post['action'] == 'add_to_cart') {
    if (isset($post['products_id']) && isset($post['products_qty'])) { // Add product to cart in product page
        $t_products_properties_combis_id = 0;
        $product_attribute = [];
        $attribute = !empty($post['attribute_info']) ? $post['attribute_info'] : '';
        if (!empty($post['variant_info'])) {
            $product_attribute = array_values($post['variant_info']);
            $coo_properties_control = MainFactory::create_object('PropertiesControl');
            $t_products_properties_combis_id = $coo_properties_control->get_combis_id_by_value_ids_array($post['products_id'], array_values($post['variant_info']));
        }
        $_SESSION['cart'] = new shoppingCart();
        $cart_object = $_SESSION['cart'];
        $cart_quantity = (xtc_remove_non_numeric($post['products_qty']) + $cart_object->get_quantity(xtc_get_uprid($post['products_id'], $product_attribute)));
        $cart_object->add_cart((int)$post['products_id'], $cart_quantity, $attribute, true, $t_products_properties_combis_id);
    }
    $order = new order();
    if (!empty($order->products)) {
        $status = json_encode(['success' => true]);
    } else {
        $status = json_encode(['success' => false]);
    }
    echo $status;
    exit;
}

// Shipping address change
if ($post['action'] == 'novalnet_shipping_address_update') {
    $xtPrice = new xtcPrice($_SESSION['currency'], $_SESSION['customers_status']['customers_status_id']);
    $response = $post['shippingInfo'];
    $received_address = json_decode($response, true);
    $order = new order();

    if (!empty($received_address) && !empty($received_address['countryCode'])) {
        $shipping_country_query = xtc_db_fetch_array(xtc_db_query("SELECT status FROM " . TABLE_COUNTRIES . " WHERE countries_iso_code_2 = '" . $received_address['countryCode']."'"));

        if ($shipping_country_query['status'] == null || $shipping_country_query['status'] == false) {
            $response = json_encode(
                array(
                'amount'           => null,
                'shipping_address' => [],
                'article_details'  => []
                ),
                true
            );
            echo $response;
            exit;
        }
    }

    if (!empty($received_address['countryCode'])) {
        $delivery_zone_query = xtc_db_fetch_array(xtc_db_query("SELECT countries_id, countries_iso_code_3 FROM " . TABLE_COUNTRIES . " WHERE countries_iso_code_2 = '" . $received_address['countryCode']."'"));
        $zone_query = xtc_db_fetch_array(xtc_db_query("SELECT zone_id FROM " . TABLE_ZONES. " WHERE zone_country_id = '".$delivery_zone_query['countries_id']."' and zone_code = '" . $received_address['administrativeArea']."'"));

        $order->delivery['country']['id']           = $delivery_zone_query['countries_id'];
        $order->delivery['country']['iso_code_2'] = $received_address['countryCode'];// assign country code in order object
        $order->delivery['country']['iso_code_3'] = $delivery_zone_query['countries_iso_code_3'];
        $order->delivery['country_id']             = $delivery_zone_query['countries_id'];
        $order->delivery['country']['title']      = '';
        $order->delivery['zone_id']                  = !empty($zone_query) ? $zone_query['zone_id'] : 0;

        if (!empty($_SESSION['customer_id']) && !empty($delivery_zone_query)) {
            if (!empty($zone_query)) {
                xtc_db_query("UPDATE ". TABLE_ADDRESS_BOOK ." SET entry_country_id = '" . $delivery_zone_query['countries_id'] . "', entry_zone_id = '" . $zone_query['zone_id'] . "' WHERE customers_id = '".xtc_db_input($_SESSION['customer_id'])."'");
            } else {
                xtc_db_query("UPDATE ". TABLE_ADDRESS_BOOK ." SET entry_country_id = '" . $delivery_zone_query['countries_id'] . "' WHERE customers_id = '".xtc_db_input($_SESSION['customer_id'])."'");
            }
        }

        $_SESSION['sendto'] = $_SESSION['customer_country_id'] = $delivery_zone_query['countries_id'];
        $_SESSION['delivery_zone'] = $received_address['countryCode'];

        $GLOBALS['total_weight'] = $_SESSION['cart']->show_weight();
        $GLOBALS['total_count']  = $_SESSION['cart']->count_contents();

        $article_details = [];
        $shipping_content = [];
        $total = 0;

        if (!empty($order->products)) {
            $order->info['subtotal'] = 0;
            $order->info['total'] = 0;
            $order->info['tax'] = 0;
            $order->info['tax_groups'] = [];
        }

        if (MODULE_ORDER_TOTAL_SHIPPING_STATUS == 'true') {
            include_once 'includes/classes/shipping.php';
            $shipping_obj = new shipping();

            // Load all enabled shipping modules
            $quotes = $shipping_obj->quote();

            $count = 1;
            $shipping_tax_amount = 0;
            $discount_price = 0;

            // Add article
            foreach ($order->products as $key => $product) {
                $tax_removed_amount = $xtPrice->xtcRemoveTax($product['price'], $product['tax']);
                $tax_rate = xtc_get_tax_rate($product['tax_class_id'], $delivery_zone_query['countries_id'], isset($zone_query['zone_id']) ? $zone_query['zone_id'] : null);
                $product_price = $xtPrice->xtcAddTax($tax_removed_amount, $tax_rate, false);

                if ($_SESSION['customers_status']['customers_status_show_price_tax'] != '0') {
                    if ($_SESSION['customers_status']['customers_status_ot_discount_flag'] == '1' && $_SESSION['customers_status']['customers_status_ot_discount'] != '0.00') {
                        $discount_price = ($xtPrice->calcTax($product['price'], $tax_rate) * round($_SESSION['customers_status']['customers_status_ot_discount'])) / 100;
                        $tax_value = (($xtPrice->calcTax($product['price'], $tax_rate)) - $discount_price) * $product['qty'];
                    } else {
                        $tax_value = ($product_price - $tax_removed_amount) * $product['qty'];
                    }

                    $updated_product = array(
                        'tax_class_id'             => $product['tax_class_id'],
                        'tax'                     => xtc_get_tax_rate($product['tax_class_id'], $delivery_zone_query['countries_id'], isset($zone_query['zone_id']) ? $zone_query['zone_id'] : null),
                        'tax_description'         => xtc_get_tax_description($product['tax_class_id'], $delivery_zone_query['countries_id'], isset($zone_query['zone_id']) ? $zone_query['zone_id'] : null),
                        'price'                 => $product_price,
                        'price_formated'         => $xtPrice->xtcFormat($product_price, true),
                        'final_price'             => $product_price * $product['qty'],
                        'final_price_formated'  => $xtPrice->xtcFormat($product_price * $product['qty'], true),
                    );
                } else {
                    if ($_SESSION['customers_status']['customers_status_ot_discount_flag'] == '1' && $_SESSION['customers_status']['customers_status_ot_discount'] != '0.00') {
                        $discount_price = ($xtPrice->calcTax($product['price'], $tax_rate) * round($_SESSION['customers_status']['customers_status_ot_discount'])) / 100;
                        $tax_value = (($xtPrice->calcTax($product['price'], $tax_rate)) - $discount_price) * $product['qty'];
                    } else {
                        $tax_value = ($xtPrice->calcTax($product['price'], $tax_rate)) * $product['qty'];
                    }

                    $updated_product = array(
                        'tax_class_id'             => $product['tax_class_id'],
                        'tax'                     => xtc_get_tax_rate($product['tax_class_id'], $delivery_zone_query['countries_id'], $zone_query['zone_id']),
                        'tax_description'         => xtc_get_tax_description($product['tax_class_id'], $delivery_zone_query['countries_id'], $zone_query['zone_id']),
                        'price'                 => $product['price'],
                        'price_formated'         => $xtPrice->xtcFormat($product['price'], true),
                        'final_price'             => $product['price'] * $product['qty'],
                        'final_price_formated'     => $xtPrice->xtcFormat($product['price'] * $product['qty'], true),
                    );
                }

                $product = array_merge($product, $updated_product);
                $order->products[$key] = $product;
                if ($tax_rate != 0) {
                    $order->products[$key]['tax_value'] = $tax_value;
                }

                // tax information needed in OrderTaxInformation class for storing orders_tax_sum_items data
                $_SESSION['customer_cart_tax_info'] = [
                    $product['tax_description'] => [
                        'tax_class_id'              => $product['tax_class_id'],
                        'country_id'                => $delivery_zone_query['countries_id'],
                        'zone_id'                   => isset($zone_query['zone_id']) ? $zone_query['zone_id'] : null
                    ]
                ];

                $order->info['subtotal'] += $order->products[$key]['final_price'];
                if ($tax_rate != 0) {
                    $order->info['tax'] += $order->products[$key]['tax_value'];
                }
                $products_tax_description = $product['tax_description'];

                // Price incl tax
                $tax_key = TAX_ADD_TAX . "$products_tax_description";
                if ($_SESSION['customers_status']['customers_status_show_price_tax'] == '1') {
                    if (!isset($order->info['tax_groups'][$tax_key])) {
                        $order->info['tax_groups'][$tax_key] = 0;
                    }
                    $order->info['tax_groups'][$tax_key] += isset($order->products[$key]['tax_value']) ? round((float)$order->products[$key]['tax_value'], 2) : 0;
                } else { // Price excl tax
                    if (!isset($order->info['tax_groups'][$tax_key])) {
                        $order->info['tax_groups'][$tax_key] = 0;
                    }
                    $order->info['tax_groups'][$tax_key] += (string)((round($order->products[$key]['tax_value'], 2)));
                }
            }

            foreach ($order->products as $product) {
                $product_name = $product['name'] . ' ' . $xtPrice->xtcFormat($product['price'], true) . ' x ' . $product['qty'];
                $article_details[] = array(
                    'label'        => $product_name,
                    'amount'     => (string)(round($product['qty'] * $product['price'], 2) * 100),
                    'type'         => 'SUBTOTAL'
                );

                if ($_SESSION['customers_status']['customers_status_ot_discount_flag'] == '1' && $_SESSION['customers_status']['customers_status_ot_discount'] != '0.00') {
                    $discount_price = round($xtPrice->xtcFormat($order->info['subtotal'], false) / 100 * $_SESSION['customers_status']['customers_status_ot_discount'] * 1, 2);
                    $article_details[] = array(
                        'label'        => $_SESSION['customers_status']['customers_status_discount']. '%' .  'Discount',
                        'amount'     => (string) (round($discount_price, 2) * 100),
                        'type'         => 'SUBTOTAL'
                    );
                }

                if($_SESSION['customers_status']['customers_status_show_price_tax'] != '0') {
                    $total += ($product['qty'] * round($product['price'], 2)) - (round($discount_price, 2));
                } else {
                    $total += (($product['qty'] * round($product['price'], 2))) + (round($order->info['tax'], 2)) - round($discount_price, 2);
                }
            }

            // Add shipping
            foreach ($quotes as $shipping) {
                $title = $shipping['module'];
                if (!isset($shipping['error'])) {
                    foreach ($shipping['methods'] as $methods) {
                        if (!defined('SHOW_SHIPPING_MODULE_TITLE') || SHOW_SHIPPING_MODULE_TITLE == 'shipping_default') {
                            $title .= ' - ' . $methods['title'];
                        }

                        $value = 0;
                        if (isset($methods['cost']) && $methods['cost'] > 0) {
                            if (isset($shipping['tax']) && $shipping['tax'] > 0) {
                                $tax_value = round(($shipping['tax'] / 100) * $methods['cost'], 2);
                                $value = ($methods['cost'] + $tax_value) * $order->info['currency_value'];
                            } else {
                                $value = $methods['cost'] * $order->info['currency_value'];
                            }
                        }

                        $shipping_content[] = array(
                            'label'         => $title,
                            'amount'         => (string) (round($value, 2) * 100),
                            'identifier'     => $shipping['id'] . '_' . $methods['id'],
                            'detail'         => ''
                        );

                        if ($count == 1) {
                            $article_details[] = array(
                                'label'        => $title,
                                'amount'     => (string)(round($value, 2) * 100),
                                'type'         => 'SUBTOTAL'
                            );
                            $total += $value;

                            if (!empty($value)) {
                                $shipping_tax_amount = $xtPrice->xtcGetTax($value, $order->products[0]['tax']);
                            }
                            $order->info['total'] = $order->info['subtotal'] + ($xtPrice->xtcFormat($value, false, 0, true)) - (round($discount_price, 2) * 100);
                        }

                        $count++;
                    }
                }
            }

            $deduction = 0;
            $promotion_tax_amount = 0;

            if (!empty($_SESSION['cc_id'])) { // To add discount
                $coupon = xtc_db_fetch_array(xtc_db_query("SELECT coupon_type, coupon_active, coupon_amount FROM " . TABLE_COUPONS . " WHERE coupon_id = '" . $_SESSION['cc_id']."'"));

                $deduction = 0;
                if (!empty($coupon) && $coupon['coupon_active'] === 'Y' && $coupon['coupon_type'] === 'S' && !empty($shipping_content)) {
                    $deduction = round($coupon['coupon_amount'], 2) + round($shipping_content[0]['amount'] / 100, 2);
                } elseif (!empty($coupon) && $coupon['coupon_active'] === 'Y' && $coupon['coupon_type'] === 'F') {
                    $deduction = round($coupon['coupon_amount'], 2);
                    $deduction = $deduction * $order->info['currency_value'];
                } elseif (!empty($coupon) && $coupon['coupon_active'] === 'Y' && $coupon['coupon_type'] === 'P') {
                    $amount = 0;
                    foreach ($order->products as $products) {
                        $amount += $products['qty'] * $products['price'];
                    }
                    $deduction = round(((round($coupon['coupon_amount'], 2) / 100) * $amount), 2);
                }

                if (!empty($deduction)) {
                    $article_details[] = array('label' => (defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_DISCOUNT_AND_GIFT_VOUCHER_LABEL') ? MODULE_PAYMENT_NOVALNET_GOOGLEPAY_DISCOUNT_AND_GIFT_VOUCHER_LABEL : ($_SESSION['language_code'] == 'de' ? 'Rabatt' : 'Discount')),
                                    'amount' => (string) (round($deduction, 2) * 100),
                                    'type' => 'SUBTOTAL');

                    $total -= round($deduction, 2);
                    $order->info['deduction'] = $deduction;
                }
            }

            if (!empty($deduction)) {
                $promotion_tax_amount = $xtPrice->xtcGetTax($deduction, $order->products[0]['tax']);
            }

            // Price incl tax
            if ($_SESSION['customers_status']['customers_status_show_price_tax'] != '0' && !empty($order->info['tax']) && $order->info['tax'] != 0) {
                $article_details[] = array(
                    'label'        => 'Incl.Tax',
                    'amount'     => (string)((round((($order->info['tax'] + $shipping_tax_amount) - $promotion_tax_amount), 2)) * 100),
                    'type'         => 'SUBTOTAL'
                );
            } elseif (!empty($order->info['tax']) && $order->info['tax'] != 0) {        // Price excl tax
                $article_details[] = array(
                    'label'        => 'Excl.Tax',
                    'amount'     => (string)((round((($order->info['tax'] + $shipping_tax_amount) - $promotion_tax_amount), 2)) * 100),
                    'type'         => 'SUBTOTAL'
                );
            }

            if (!empty($_SESSION['gift_vouchers'])) {
                foreach ($_SESSION['gift_vouchers'] as $voucher) {
                    $amount = $voucher['amount'] * $order->info['currency_value'];
                }
                $article_details[] = array(
                    'label' => (defined('MODULE_PAYMENT_NOVALNET_GIFT_VOUCHER_LABEL') ? MODULE_PAYMENT_NOVALNET_GIFT_VOUCHER_LABEL : ($_SESSION['language_code'] == 'de' ? 'Gutschein' : 'Voucher')),
                    'amount' => (string)(round($amount, 2) * 100),
                    'type' => 'SUBTOTAL'
                );

                $total = $order->info['total'] - round($amount, 2);
            }

            $_SESSION['novalnet']['nn_order'] = $order;
        }
        $total = (string)(round($total, 2) * 100);

        $_SESSION['novalnet']['payment_amount'] = (int)$total / 100;

        $response = json_encode(
            array(
            'amount'           => $total,
            'shipping_address' => $shipping_content,
            'article_details'  => $article_details
            ),
            true
        );
        echo $response;
        exit;
    }
}

// Shpping method change
if($post['action'] == 'novalnet_shipping_method_update') {
    $received_shipping_method = json_decode($post['shippingInfo'], true);

    $GLOBALS['total_weight'] = $_SESSION['cart']->show_weight();
    $GLOBALS['total_count']  = $_SESSION['cart']->count_contents();

    $shipping_obj = new shipping();

    // Load all enabled shipping modules
    $quotes = $shipping_obj->quote();

    $_SESSION['shipping']['id'] = $received_shipping_method['identifier'];
    $_SESSION['shipping']['title'] = $received_shipping_method['label'];
    $shipping_cost = 0;
    if (!empty($quotes)) {
        foreach ($quotes as $shipping_method) {
            $shipping_id = explode('_', $received_shipping_method['identifier']);

            if (is_array($shipping_id) && $shipping_id[0] == $shipping_method['id']) {
                foreach ($shipping_method['methods'] as $method) {
                    $shipping_cost = $_SESSION['shipping']['cost'] = round($method['cost'], 2);
                }
            }
        }
    }

    $order = new order();

    $order->info['shipping_cost'] = $shipping_cost;
    $order->info['shipping_method'] = isset($received_shipping_method['label']) ? $received_shipping_method['label'] : '';
    $order->info['shipping_class']  = isset($received_shipping_method['identifier']) ? $received_shipping_method['identifier'] : '';

    if(!empty($_SESSION['method_update_shipping_amount'])) {
        unset($_SESSION['method_update_shipping_amount']);
    }

    $shipping_tax_amount = 0;
    if (!empty($received_shipping_method['amount'])) {
        $shipping_tax_amount = $xtPrice->xtcGetTax($received_shipping_method['amount'], $order->products[0]['tax']);
    }

    $article_details = [];
    foreach($_SESSION['novalnet']['nn_order']->products as $products) {
        $article_details[] = array(
            'label'        => $products['name']. 'x' .$products['qty'],
            'amount'     => (string)(round($products['qty'] * $products['price'], 2) * 100),
            'type'         => 'SUBTOTAL'
        );
    }

    $discount_price = 0;
    if ($_SESSION['customers_status']['customers_status_ot_discount_flag'] == '1' && $_SESSION['customers_status']['customers_status_ot_discount'] != '0.00') {
        $discount_price = round($xtPrice->xtcFormat($_SESSION['novalnet']['nn_order']->info['subtotal'], false) / 100 * $_SESSION['customers_status']['customers_status_ot_discount'] * 1, 2);
        $article_details[] = array(
            'label'        => $_SESSION['customers_status']['customers_status_discount']. '%' .  'Discount',
            'amount'     => (string) (round($discount_price, 2) * 100),
            'type'         => 'SUBTOTAL'
        );
    }

    if ($_SESSION['customers_status']['customers_status_show_price_tax'] != '0' && ($_SESSION['novalnet']['nn_order']->info['tax'] != 0)) {
        $total = (($_SESSION['novalnet']['nn_order']->info['subtotal'] * 100) - round($discount_price, 2) * 100) + ($received_shipping_method['amount'] * 100);
    } elseif(($_SESSION['novalnet']['nn_order']->info['tax'] != 0)) {
        $total = (($_SESSION['novalnet']['nn_order']->info['subtotal'] * 100) - round($discount_price, 2) * 100) + (string)((round($_SESSION['novalnet']['nn_order']->info['tax'], 2)) * 100) + ($received_shipping_method['amount'] * 100);
    } else {
        $total = (($_SESSION['novalnet']['nn_order']->info['subtotal'] * 100) - round($discount_price, 2) * 100) + ($received_shipping_method['amount'] * 100);
    }

    $_SESSION['method_update_shipping_amount'] = (string)($received_shipping_method['amount'] * 100);

    $article_details[] = array(
        'label'        => $received_shipping_method['label'],
        'amount'     => (string)($received_shipping_method['amount'] * 100),
        'type'         => 'SUBTOTAL');

    if (!empty($_SESSION['novalnet']['nn_order']->info['deduction']) && !empty($_SESSION['cc_id'])) { // To add discount
        $coupon = xtc_db_fetch_array(xtc_db_query("SELECT coupon_type, coupon_active, coupon_amount FROM " . TABLE_COUPONS . " WHERE coupon_id = '" . $_SESSION['cc_id']."'"));
        $deduction = 0;

        if (!empty($coupon) && $coupon['coupon_active'] === 'Y' && $coupon['coupon_type'] === 'S') {
            $deduction = round($coupon['coupon_amount'], 2) + round($received_shipping_method['amount'], 2);
        } elseif (!empty($coupon) && $coupon['coupon_active'] === 'Y' && $coupon['coupon_type'] === 'F') {
            $deduction = round($coupon['coupon_amount'], 2);
            $deduction = $deduction * $order->info['currency_value'];
        } elseif (!empty($coupon) && $coupon['coupon_active'] === 'Y' && $coupon['coupon_type'] === 'P') {
            $amount = 0;
            foreach ($order->products as $products) {
                $amount += $products['qty'] * $products['price'];
            }
            $deduction = round(((round($coupon['coupon_amount'], 2) / 100) * $amount), 2);
        }

        if (!empty($deduction)) {
            $order->info['deduction'] = $_SESSION['novalnet']['nn_order']->info['deduction'] = $deduction;
            $article_details[] = array('label' => (defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_DISCOUNT_AND_GIFT_VOUCHER_LABEL') ? MODULE_PAYMENT_NOVALNET_GOOGLEPAY_DISCOUNT_AND_GIFT_VOUCHER_LABEL : ($_SESSION['language_code'] == 'de' ? 'Rabatt' : 'Discount')),
                            'amount' => (string) (round($deduction, 2) * 100),
                            'type' => 'SUBTOTAL');

            $total -= round($deduction, 2) * 100;
        }
    }

    $deduction_tax_amount = 0;
    if (!empty($deduction)) {
        $deduction_tax_amount = $xtPrice->xtcGetTax($deduction, $order->products[0]['tax']);
    }

    // Price incl tax
    if ($_SESSION['customers_status']['customers_status_show_price_tax'] != '0' && ($_SESSION['novalnet']['nn_order']->info['tax'] != 0)) {
        $article_details[] = array(
            'label'        => 'Incl.Tax',
            'amount'     => (string)((round((($order->info['tax'] + $shipping_tax_amount) - $deduction_tax_amount), 2)) * 100),
            'type'         => 'SUBTOTAL'
        );
    } elseif($_SESSION['novalnet']['nn_order']->info['tax'] != 0) {        // Price excl tax
        $article_details[] = array(
            'label'        => 'Excl.Tax',
            'amount'     => (string)((round((($order->info['tax'] + $shipping_tax_amount) - $deduction_tax_amount), 2)) * 100),
            'type'         => 'SUBTOTAL'
        );
    }

    if (!empty($_SESSION['gift_vouchers'])) {
        foreach ($_SESSION['gift_vouchers'] as $voucher) {
            $amount = $voucher['amount'] * $order->info['currency_value'];
        }
        $article_details[] = array(
            'label' => (defined('MODULE_PAYMENT_NOVALNET_GIFT_VOUCHER_LABEL') ? MODULE_PAYMENT_NOVALNET_GIFT_VOUCHER_LABEL : ($_SESSION['language_code'] == 'de' ? 'Gutschein' : 'Voucher')),
            'amount' => (string)(round($amount, 2) * 100),
            'type' => 'SUBTOTAL'
        );

        $total -= round($amount, 2) * 100;
    }

    $shipping_method_change = array(
        'article_details' => $article_details,
        'amount'          => (string)$total);

    $result = json_encode($shipping_method_change, true);
    echo $result;
    exit;
}
