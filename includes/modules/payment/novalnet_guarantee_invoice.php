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

class novalnet_guarantee_invoice extends novalnet_gateway
{
    /**
     * Core Function : Constructor()
     */
    public function __construct()
    {
        $this->code           = 'novalnet_guarantee_invoice';
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
        $config = [
            'BASIC_REQ'                   => [
                'value' => '',
            ],
            'STATUS'                   => [
                'value' => 'false',
                'type'  => 'switcher',
            ],
            'TEST_MODE'                => [
                'value' => 'false',
                'type'  => 'switcher',
            ],
            'ALLOWED' => [
                'value' => '',
                'type'  => 'text'
            ],
            'FORCE'                    => [
                'value' => 'true',
                'type'  => 'switcher',
            ],
            'MINIMUM_ORDER_AMOUNT'     => [
                'value' => '999',
            ],
            'ALLOW_B2B'                => [
                'value' => 'true',
                'type'  => 'switcher',
            ],
            'PAYMENT_ZONE'             => [
                'value' => '0',
                'type'  => 'geo-zone',
            ],
            'AUTHENTICATE'             => [
                'value' => 'false',
                'type'  => 'switcher',
            ],
            'MANUAL_CHECK_LIMIT'       => [
                'value' => '',
            ],
            'ORDER_STATUS'             => [
                'value' => '2',
                'type' => 'order-status',
            ],
            'SORT_ORDER'               => [
                'value' => '5',
            ],
            'ENDCUSTOMER_INFO'         => [
                'value' => '',
            ],
        ];

        return $config;
    }
}
