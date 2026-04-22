<?php
/**
 * Novalnet payment module
 *
 * This script is used for auto configuration of merchant details
 *
 * @author    Novalnet AG
 * @copyright Copyright (c) Novalnet
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link      https://www.novalnet.de
 *
 * Script: novalnet_auto_config.php
 */
require 'includes/application_top.php';
require_once DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php';

$request = $_REQUEST;
if (!empty($request['signature']) && !empty($request['access_key'])) { // To get values and form request parameters
    $helper = new NovalnetHelper();
    $data = [
        'merchant' => [
            'signature' => $request['signature'],
        ],
        'custom' => [
            'lang' => strtoupper($request['lang'])
        ]
    ];

    $action = 'merchant_details';
    if ($request['action'] == 'webhook') { // For webhook
        $action = 'webhook_configure';
        $data['webhook'] = [
            'url' => $request['webhook_url']
        ];
    }

    // Sending request to Novalnet
    $response  = $helper->sendRequest($data, $helper->getActionEndpoint($action), $request['access_key']);
    echo json_encode($response);
    exit();
}
