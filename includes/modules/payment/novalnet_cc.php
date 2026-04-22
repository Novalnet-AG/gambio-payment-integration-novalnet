<?php
/**
 * Novalnet payment module
 * This module is used for real time processing of
 * Novalnet transaction of customers.
 *
 * @author    Novalnet AG
 * @copyright Copyright (c) Novalnet
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link      https://www.novalnet.de
 *
 * Script : novalnet_cc.php
 */
require_once __DIR__ . '/novalnet/novalnet_gateway.php';

class novalnet_cc extends novalnet_gateway
{
    /**
     * Core Function : Constructor()
     */
    public function __construct()
    {
        $this->code           = 'novalnet_cc';
        $this->payment_config = $this->_configuration();

        parent::__construct();
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
        $display_payment = $this->validator->check_novalnet_payment($this->code);

        if (!$display_payment) {
            return false;
        }

        $selection = [
            'id'          => $this->code,
            'module'      => $this->title,
            'description' => $this->helper->showPaymentDescription($this->code) . $this->info,
        ];

        $this->validator->prepareFormFields($this->code, $this->info, $selection);

        return $selection;
    }

    /**
     * Return the payment configurations
     */
    public function _configuration()
    {
        $label = 'font-weight:normal;font-family:Roboto,Arial,sans-serif;font-size:13px;line-height:1.42857';
        $config = [
            'STATUS'                   => [
                'value' => 'false',
                'type'  => 'switcher'
            ],
            'TEST_MODE'                => [
                'value' => 'false',
                'type'  => 'switcher'
            ],
            'ALLOWED' => [
                'value' => '',
                'type'  => 'text'
             ],
            'PAYMENT_ZONE'             => [
                'value' => '0',
                'type'  => 'geo-zone'
            ],
            'VISIBILITY_BY_AMOUNT'     => [
                'value' => ' ',
            ],
            'INLINE_FORM'              => [
                'value' => 'true',
                'type'  => 'switcher'
            ],
            '3D_SECURE_FORCE'          => [
                'value' => 'false',
                'type'  => 'switcher'
            ],
            'TOKENIZATION'             => [
                'value' => 'true',
                'type'  => 'switcher'
            ],
            'AUTHENTICATE'             => [
                'value' => ' ',
            ],
            'MANUAL_CHECK_LIMIT'       => [
                'value' => ' '
            ],
            'ORDER_STATUS'             => [
                'value' => '2',
                'type' => 'order-status'
            ],
            'CSS_STANDARD_STYLE'       => [
                'value' => $label
            ],
            'CSS_STANDARD_STYLE_INPUT' => [
                'value' => 'height:30px;border:1px solid #ccc;'
            ],
            'CSS_TEXT'                 => [
                'value' => $label
            ],
            'SORT_ORDER'               => [
                'value' => '1'
            ],
            'ENDCUSTOMER_INFO'         => [
                'value' => ''
            ],
        ];

        return $config;
    }
}
