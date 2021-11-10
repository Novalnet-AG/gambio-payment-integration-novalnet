/**
 * Novalnet novalnet_invoice Script
 * By Novalnet (https://www.novalnet.de)
 * Copyright (c) Novalnet
 */

	if (window.addEventListener) {    // For all major browsers, except IE 8 and earlier
		window.addEventListener('load', novalnet_invoice_load);
	} else if (window.attachEvent) { // For IE 8 and earlier versions
		window.attachEvent('onload', novalnet_invoice_load);
	}

/**
 * To performing the page loading and fraud module
 */
	function novalnet_invoice_load() {
		jQuery('#novalnet_invoice_dob').attr("placeholder", "YYYY-MM-DD");
		jQuery( ".shipping_container" ).click(function() {
			jQuery("#novalnet_invoice_callbacknew_pin").prop("checked", false);
		});
		jQuery("#novalnet_invoice_callbackpin").click(function(e) {
			e.stopPropagation();
			(jQuery("#novalnet_invoice_callbacknew_pin").prop("checked")) ? jQuery( "#novalnet_invoice_callbacknew_pin" ).prop("checked","checked") : jQuery( "#novalnet_invoice_callbacknew_pin" ).prop("checked",false) ;
		});
		jQuery("#novalnet_invoice_callbacknew_pin").click(function(e) {
			e.stopPropagation();
			(jQuery("#novalnet_invoice_callbacknew_pin").prop("checked")) ? jQuery( "#novalnet_invoice-callbacknew_pin" ).prop("checked","checked") : jQuery( "#novalnet_invoice-callbacknew_pin" ).prop("checked",false) ;
		});
	}
