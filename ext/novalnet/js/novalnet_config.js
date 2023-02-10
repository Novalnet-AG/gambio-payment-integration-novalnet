/**
 * Novalnet payment module
 *
 * This script is used for auto configuration of merchant credentials
 * and webhook URL configuration
 *
 * @author     Novalnet AG
 * @copyright  Copyright (c) Novalnet
 * @license    https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link       https://www.novalnet.de
 *
 * Script: novalnet_config.js
 */

if (window.addEventListener) {
	window.addEventListener("load", novalnet_api_load); // For IE browser
} else if (window.attachEvent) {
	window.attachEvent("onload", novalnet_api_load);
}

/**
 * To get the backend field configuration values
 */
function novalnet_api_load() {
	jQuery('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_SIGNATURE]"]').attr('id', 'novalnet_signature');
	jQuery('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_TARIFF_ID]"]').attr('id', 'novalnet_tariff_id');
	jQuery('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_CLIENT_KEY]"]').attr('id', 'novalnet_client_key');
	jQuery('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY]"]').attr('id', 'novalnet_access_key');
	jQuery('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_PROJECT_ID]"]').attr('id','novalnet_product_id');
	jQuery('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_CALLBACK_URL]"]').attr('id','novalnet_webhook_url');

	jQuery('#novalnet_signature, #novalnet_access_key').change(function () {
		if (jQuery('#novalnet_signature').val() != '' && jQuery('#novalnet_access_key').val() != '') {
			get_merchant_details();
			return true;
		} else if (jQuery('#novalnet_signature').val() == '' && jQuery('#novalnet_access_key').val() == '') {
			clear_basic_params();
		}
	}).change();
	jQuery('#webhook_url_button').on('click', function() {
		var webhook_url = jQuery.trim(jQuery('#novalnet_webhook_url').val());
		var regex       = /(http|https):\/\/(\w+:{0,1}\w*)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%!\-\/]))?/;
		if (webhook_url != '' && regex.test(webhook_url)) {
			confirm('Are you sure you want to configure the Webhook URL in Novalnet Admin Portal?');
			configure_webhook();
			return true;
		} else if (!regex.test( webhook_url) || webhook_url === '' || webhook_url === undefined){
			alert('Enter the valid webhook URL');
			return false;
		}
	});
}

/** Get merchant data */
function get_merchant_details() {
		var signature = jQuery.trim(jQuery('#novalnet_signature').val());
		var access_key = jQuery.trim(jQuery('#novalnet_access_key').val());
		var language   = jQuery('#nn_language').val();
		var data_to_send = {'action': 'merchant', 'signature': signature, 'access_key': access_key, 'lang': language};
		do_ajax_call(data_to_send, 'merchant');
		return true;
}

/** Configure webhook URL in Novalnet system */
function configure_webhook() {
	var signature = jQuery.trim(jQuery('#novalnet_signature').val());
	var access_key = jQuery.trim(jQuery('#novalnet_access_key').val());
	var webhook_url = jQuery.trim(jQuery('#novalnet_webhook_url').val());
	var language   = jQuery('#nn_language').val();
	if (signature == '' || access_key == '') {
		alert("Please enter signature and access key");
		clear_basic_params();
		return false;
	}
	var data_to_send = {'action': 'webhook', 'signature': signature, 'access_key': access_key, 'webhook_url': webhook_url, 'lang': language};
	do_ajax_call(data_to_send, 'webhook');
	return true;
}

/** Handle the response */
function process_result(result) {
	var saved_tariff_id = jQuery('#novalnet_tariff_id').val();
	jQuery('#novalnet_tariff_id').replaceWith('<select id="novalnet_tariff_id" name= "configuration[configuration/MODULE_PAYMENT_NOVALNET_TARIFF_ID]" ></select>');
	var tariff = result.merchant.tariff;
	$("#novalnet_client_key").val(result.merchant.client_key);
	$("#novalnet_product_id").val(result.merchant.project);
	if (tariff != undefined) {
		jQuery.each(tariff, function( index, value ) {
			var tariff_val = index;
			jQuery('#novalnet_tariff_id').append(jQuery('<option>', {
				 value: jQuery.trim(tariff_val),
				 text: jQuery.trim(value.name)
			}));
			if (saved_tariff_id != undefined && saved_tariff_id == tariff_val) {
				 jQuery('#novalnet_tariff_id').val(tariff_val);
			}
		});
	} else {
		clear_basic_params();
		alert(result.status_desc);
	}
}

/** Clear basic params */
function clear_basic_params() {
	jQuery('#novalnet_access_key').val('');
	jQuery('#novalnet_client_key').val('');
	jQuery('#novalnet_product_id').val('');
	jQuery('#novalnet_tariff_id').find('option' ).remove();
	jQuery('#novalnet_tariff_id').append(jQuery( '<option>', {
		value: '',
		text : '',
	}));
}

/** AJAX call processing */
function do_ajax_call (data_to_send, action) {
	jQuery.ajax({
		type : 'POST',
		url  : '../novalnet_auto_config.php',
		data : data_to_send,
		success: function(data) {
			var response = JSON.parse(data);
			if (action == 'merchant') {
				process_result(response);
			} else if (action == 'webhook') {
				if (response.result.status_code == 100) {
					alert('Notification / Webhook URL is configured successfully in Novalnet Admin Portal');
				}
				else {
					alert('Webhook URL configured failed. Check the webhook endpoint');
				}
			}
		},
	});
}
