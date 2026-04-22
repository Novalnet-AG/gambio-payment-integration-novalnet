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
 * Script : novalnet_instalment_sepa.php
 */
require_once __DIR__ . '/novalnet/novalnet_gateway.php';

class novalnet_instalment_sepa extends novalnet_gateway
{
    /**
     * Core Function : Constructor()
     */
    public function __construct()
    {
        $this->code           = 'novalnet_instalment_sepa';
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
            'CYCLE'                    => [
                'value' => '2|3|4|5|6|7|8|9|10|11|12',
                'type'  => 'multiselect',
            ],
            'MINIMUM_ORDER_AMOUNT'     => [
                'value' => '1998',
            ],
            'ALLOW_B2B'                => [
                'value' => 'true',
                'type'  => 'switcher',
            ],
            'TOKENIZATION'             => [
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
                'value' => '12',
            ],
            'ENDCUSTOMER_INFO'         => [
                'value' => '',
            ],
        ];

        return $config;
    }
}
