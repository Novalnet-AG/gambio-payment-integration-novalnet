<?php
/**
 * Novalnet payment module
 *
 * This script is used for auto configuration of merchant details
 *
 * @author     Novalnet AG
 * @copyright  Copyright (c) Novalnet
 * @license    https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link       https://www.novalnet.de
 *
 * Script: novalnet_auto_config.php
 */
include ('includes/application_top.php');
$signature  = $_REQUEST['signature'];
$access_key = $_REQUEST['access_key'];
$action     = $_REQUEST['action'];
if(!empty($signature) && !empty($access_key)) { // To get values and form request parameters
	$data = [];
	$data['merchant'] = [
		'signature' => $signature,
	];
	$data['custom']   = [
		'lang' 		=> strtoupper($_REQUEST['lang'])
	];
	if($action == 'merchant') { // For merchant credentials
		$endpoint = 'https://payport.novalnet.de/v2/merchant/details';
	} elseif($action == 'webhook') { // For webhook
		$endpoint = 'https://payport.novalnet.de/v2/webhook/configure';
		$data['webhook'] = [
			'url' => $_REQUEST['webhook_url']
		];
	}
	$json_data = json_encode($data);
	$response = send_request($json_data, $endpoint, $access_key); // Sending request to Novalnet
	echo $response;
	exit();

}

/**
 * cURL call
 *
 * @param $data
 * @param $url
 *
 * @return $result
 */
function send_request($data, $url, $access_key) {
	$encoded_data = base64_encode($access_key);
	$headers = [
		'Content-Type:application/json',
		'Charset:utf-8',
		'Accept:application/json',
		'X-NN-Access-Key:' . $encoded_data,
	];
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	$result = curl_exec($curl);
	if (curl_errno($curl)) {
		echo 'Request Error:' . curl_error($curl);
		return $result;
	}
	curl_close($curl);
	return $result;
}
?>
