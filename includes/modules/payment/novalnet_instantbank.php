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
 * Script : novalnet_instantbank.php
 */
require_once __DIR__ . '/novalnet/novalnet_gateway.php';

class novalnet_instantbank extends novalnet_gateway
{
    /**
     * Core Function : Constructor()
     */
    public function __construct()
    {
        $this->code           = 'novalnet_instantbank';
        $this->tmpOrders      = true;
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
        return false;
    }

    public function _configuration()
    {
        $config = [
            'STATUS' => [
                'value' => 'false',
                'type'  => 'switcher',
            ]
        ];

        return $config;
    }
}
