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
 * To performing the page loading 
 */
	function novalnet_invoice_load() {
		var current_url = new URL($(location).attr("href"));
		var url = current_url.searchParams.get("payment_error");
		var shop_lang = jQuery('#nn_shop_lang').val();
		var nn_dob_placeholder = jQuery('#nn_dob_placeholder').val();
		jQuery('#novalnet_invoice_dob').attr("placeholder", nn_dob_placeholder);		
		if($( "div" ).hasClass( "checkout-payment-form" )){
		if($('.checkout-payment-form').css('display') == 'none'){
		 $('.checkout-payment-form').css('display','block');
	    }
	 }
	 if(url == 'novalnet_invoice'){
		 jQuery('.novalnet_invoice').addClass('active');
	    }
	}

