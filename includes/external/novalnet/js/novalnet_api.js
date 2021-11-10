/**
 * Novalnet novalnet api call Script
 * By Novalnet (https://www.novalnet.de)
 * Copyright (c) Novalnet
 */
	if(typeof(jQuery) == undefined || typeof(jQuery) == 'undefined' || typeof(jQuery) == 'function' ) {
	  var s = document.createElement("script");
	  s.type = "text/javascript";
	  var nn_cc_root = document.getElementById('nn_api_shoproot').value;
	  s.src = nn_cc_root+"includes/external/novalnet/js/jquery.js";
	  document.getElementsByTagName("head")[0].appendChild(s);
	}
	if (window.addEventListener) { // For all major browsers, except IE 8 and earlier
		window.addEventListener("load", novalnet_api_load);
	} else if (window.attachEvent) { // For IE 8 and earlier versions
		window.attachEvent("onload", novalnet_api_load);
	}
/**
 * To get the backend field configuration values
 */
	function novalnet_api_load() {
		jQuery('input[name="configuration[MODULE_PAYMENT_NOVALNET_PUBLIC_KEY]"]').attr('id', 'novalnet_public_key');
		jQuery('input[name="configuration[MODULE_PAYMENT_NOVALNET_VENDOR_ID]"]').attr('id', 'novalnet_vendor_id');
		jQuery('input[name="configuration[MODULE_PAYMENT_NOVALNET_AUTHCODE]"]').attr('id', 'novalnet_auth_code');
		jQuery('input[name="configuration[MODULE_PAYMENT_NOVALNET_PRODUCT_ID]"]').attr('id', 'novalnet_product');
		jQuery('input[name="configuration[MODULE_PAYMENT_NOVALNET_TARIFF_ID]"]').attr('id', 'novalnet_tariff_id');
		jQuery('input[name="configuration[MODULE_PAYMENT_NOVALNET_PAYMENT_ACCESS_KEY]"]').attr('id', 'novalnet_access_key');
		jQuery('#novalnet_vendor_id,#novalnet_auth_code,#novalnet_product,#novalnet_access_key').attr("readonly", true);
		if( jQuery('#novalnet_public_key').val() != '' ) {
			get_merchant_details();
		}
		jQuery('#novalnet_public_key').change(function() {
			get_merchant_details();
		});
		jQuery('#novalnet_public_key').closest('form').submit(function(event) {
		var form = this;
		if (jQuery('#nn_api_config_call').attr('value') == '0') {
			event.preventDefault();
			jQuery(document).ajaxComplete(function() {
				jQuery(form).submit();
			});
		}
			return true;
		});
	}

/**
 * To send the api details to novalnet server and get vendor configuration details
 */
	function get_merchant_details() {
		var server_ip  = jQuery.trim(jQuery('#server_ip').val());
		var remote_ip  = jQuery.trim(jQuery('#remote_ip').val());
		var public_key = jQuery.trim(jQuery('#novalnet_public_key').val());
		var language   = jQuery('#nn_language').val();
		if(public_key == '') {
			null_basic_params();
			return false;
		}
		jQuery('#nn_api_config_call').attr('value', 0);
		var data_to_send = {"system_ip": server_ip , "remote_ip":remote_ip, "api_config_hash": public_key, 'lang': language}
		if ('XDomainRequest' in window && window.XDomainRequest !== null) {
			var xdr = new XDomainRequest();
			xdr.open('POST' , 'https://payport.novalnet.de/autoconfig');
			xdr.onload = function () {
				process_result( data_to_send );
			};
			xdr.send( data_to_send );
		}else{
			jQuery.ajax({
				type : 'POST',
				url  : 'https://payport.novalnet.de/autoconfig',
				data : data_to_send,
				success: function(data) {
					process_result( data );
				}
			});
		}
		return true;
	}

/**
 * To performing unset the vendor configuration details in global configuration
 */
	function null_basic_params() {
		jQuery('#novalnet_vendor_id, #novalnet_auth_code, #novalnet_product, #novalnet_access_key').val('');
		jQuery('#novalnet_tariff_id').find('option' ).remove();
		jQuery('#nn_api_config_call').attr('value', 1);
		jQuery('#novalnet_tariff_id').append(jQuery( '<option>', {
			value: '',
			text : '',
		}));
	}

/**
 * To fetch the vendor configuration details in shop backend
 *
 * @param hahs_string
 */
	function process_result(hash_string) {
		var saved_tariff_id = jQuery('#novalnet_tariff_id').val();
		jQuery('#novalnet_tariff_id').replaceWith('<select id="novalnet_tariff_id" name= "configuration[MODULE_PAYMENT_NOVALNET_TARIFF_ID]" ></select>');
		if (hash_string.tariff_id != undefined) {
			hash_string_tarrif_value = hash_string.tariff_id.split(',');
			hash_string_tarrif_name = hash_string.tariff_name.split(',');
			hash_string_tarrif_type = hash_string.tariff_type.split(',');
			for(var i=0; i< hash_string_tarrif_value.length; i++) {
				var hash_result_name = hash_string_tarrif_name[i].split(':');
				hash_result_name = (hash_result_name[2] != undefined) ? hash_result_name[1] + ':' + hash_result_name[2] : hash_result_name[1];
				var hash_result_val  = hash_string_tarrif_value[i].split(':');
				var hash_result_type = hash_string_tarrif_type[i].split(':');
				var tariff_val 		 = hash_result_type[1] + '-' + hash_result_val[1].trim();
				jQuery( '#novalnet_tariff_id' ).append( jQuery( '<option>', {
					value: jQuery.trim( tariff_val ),
					text : jQuery.trim( hash_result_name )
				}));
				if (saved_tariff_id != undefined && saved_tariff_id == tariff_val) {
					jQuery('#novalnet_tariff_id').val(tariff_val);
				}
			}
			jQuery('#novalnet_vendor_id').val(hash_string.vendor_id);
			jQuery('#novalnet_auth_code').val(hash_string.auth_code);
			jQuery('#novalnet_product').val(hash_string.product_id);
			jQuery('#novalnet_access_key').val(hash_string.access_key);
			jQuery('#nn_api_config_call').attr('value', '1');
		} else {
			null_basic_params();
			alert(hash_string.config_result);
		}
	}
