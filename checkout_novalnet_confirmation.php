<?php
/**
 * Novalnet payment method module
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
 * Script : checkout_novalnet_confirmation.php
 *
 */
include ('includes/application_top.php');
$GLOBALS['breadcrumb']->add(NAVBAR_TITLE_1_CHECKOUT_CONFIRMATION, xtc_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
$GLOBALS['breadcrumb']->add(NAVBAR_TITLE_2_CHECKOUT_CONFIRMATION);


// create smarty elements
$smarty = new Smarty;
//create payment modules object
new payment($_SESSION['payment']);

// if the customer is not logged on, redirect them to the login page
if (!isset ($_SESSION['customer_id']))
    xtc_redirect(xtc_href_link(FILENAME_LOGIN, '', 'SSL'));

// if there is nothing in the customers cart, redirect them to the shopping cart page
if ($_SESSION['cart']->count_contents() <= 0)
    xtc_redirect(xtc_href_link(FILENAME_SHOPPING_CART));

// avoid hack attempts during the checkout procedure by checking the internal cartID
if (isset ($_SESSION['cart']->cartID) && isset ($_SESSION['cartID'])) {
    if ($_SESSION['cart']->cartID != $_SESSION['cartID'])
        xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
}
// if no shipping method has been selected, redirect the customer to the shipping method selection page
if (!isset ($_SESSION['shipping']))
    xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
    
// page redirect to checkout page if payment params empty 	
if($_SESSION['novalnet'][$_SESSION['payment']]['urlparam'] == ''){
    xtc_redirect(xtc_href_link(FILENAME_SHOPPING_CART));
}
$smarty->assign('language', $_SESSION['language']);
$smarty->caching = 0;
$smarty->assign('NOVALNET_URL',$GLOBALS[$_SESSION['payment']]->form_action_url);
$smarty->assign('NOVALNET_PARAMS', $_SESSION['novalnet'][$_SESSION['payment']]['urlparam']);
$smarty->assign('NOVALNET_DESCRITPTION',MODULE_PAYMENT_NOVALNET_REDIRECT_DESC);
$smarty->assign('logo_path',DIR_WS_CATALOG.'images/icons/payment/nn_loader.gif');
$smarty->caching = 0;
$t_main_content = $smarty->fetch(DIR_FS_CATALOG . 'ext/novalnet/template/novalnet_confirmation.html');
$coo_layout_control = MainFactory::create_object('LayoutContentControl');
$coo_layout_control->set_data('GET', $_GET);
$coo_layout_control->set_data('POST', $_POST);
$coo_layout_control->set_('coo_breadcrumb', $GLOBALS['breadcrumb']);
$coo_layout_control->set_('coo_product', $GLOBALS['product']);
$coo_layout_control->set_('coo_xtc_price', $GLOBALS['xtPrice']);
$coo_layout_control->set_('c_path', $GLOBALS['cPath']);
$coo_layout_control->set_('main_content', $t_main_content);
$coo_layout_control->set_('request_type', $GLOBALS['request_type']);
$coo_layout_control->proceed();
echo $coo_layout_control->get_response();

