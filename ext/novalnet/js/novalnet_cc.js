
/**
 * Novalnet Credit Card script
 * By Novalnet (https://www.novalnet.de)
 * Copyright (c) Novalnet
 */
 
 if (window.addEventListener) { // For all major browsers, except IE 8 and earlier
		window.addEventListener("load", novalnet_cc_load);
	 } else if (window.attachEvent) { // For IE 8 and earlier versions
		window.attachEvent("onload", novalnet_cc_load);
    }
    
 
/**
 * To performing the page loading and loding iframe
 */
	function novalnet_cc_load() {
		// On button submit or form
		var client_key = document.getElementById('nn_client_key').value;
		NovalnetUtility.setClientKey(client_key);
		var amount_booked = ($('#nn_zero_amount_book').val() == 'true') ? '0' :  $('#nn_total').val();
		//shipping address
		if(jQuery('#nn_shipping').val() == '0'){
				var shipping_address = {
							// Your End-customer's billing street (incl. House no).
							street: $('#nn_shipping_street').val(),
							
							// Your End-customer's billing city.
							city: $('#nn_shipping_city').val(),
							
							// Your End-customer's billing zip.
							zip: $('#nn_shipping_zip').val(),
							
							// Your End-customer's billing country ISO code.
							country_code: $('#nn_shipping_country').val(),
				}
		}else{
			var shipping_address = { "same_as_billing" : '1' }
		}
		
			var configurationObject = {
		
			// You can handle the process here, when specific events occur.
			callback: {
			
				// Called once the pan_hash (temp. token) created successfully.
				on_success: function (data) {
					
					document.getElementById('nn_pan_hash').value = data ['hash'];
					document.getElementById('nn_cc_uniqueid').value = data ['unique_id'];
					document.getElementById('nn_do_redirect').value = data ['do_redirect'];
					var formid = getFormid();
					jQuery('#'+formid).submit();
					return true;
				},
				
				// Called in case of an invalid payment data or incomplete input. 
				on_error:  function (data) {
					if ( undefined !== data['error_message'] ) {
						alert(data['error_message']);
						$("div.continue_button .btn-block").removeAttr("disabled");
						return false;
					}
				},
				
				// Called in case the challenge window Overlay (for 3ds2.0) displays 
				on_show_overlay:  function (data) {
					document.getElementById('nnIframe').classList.add("overlay");
					
				},
				
				// Called in case the Challenge window Overlay (for 3ds2.0) hided
				on_hide_overlay:  function (data) {
					document.getElementById('nnIframe').classList.remove("overlay");
					$("div.continue_button .btn-block").removeAttr("disabled");
				}
			},
			
			// You can customize your Iframe container styel, text etc. 
			iframe: {
			
				// It is mandatory to pass the Iframe ID here.  Based on which the entire process will took place.
				id: "nnIframe",
				
				// Set to 1 to make you Iframe input container more compact (default - 0)
				inline: '0',
				
				
				// Add the style (css) here for either the whole Iframe contanier or for particular label/input field
				style: {
					// The css for the Iframe container
					container: (jQuery('#nn_css_text').val()) ? jQuery('#nn_css_text').val() : '',
					
					// The css for the input field of the Iframe container
					input: (jQuery('#nn_css_standard_input').val()) ? jQuery('#nn_css_standard_input').val() : '',
					
					// The css for the label of the Iframe container
					label: (jQuery('#nn_css_standard').val()) ? jQuery('#nn_css_standard').val() : ''
				},
				
				// You can customize the text of the Iframe container here
				text: {
				
				// The End-customers selected language. The Iframe container will be rendered in this Language.
					lang : $('#nn_lang').val(),
					
					// Basic Error Message
					error: (jQuery('#nn_iframe_error').val()) ? jQuery('#nn_iframe_error').val() : '',
					
					// You can customize the text for the Card Holder here
					card_holder : {
					
						// You have to give the Customized label text for the Card Holder Container here
						label: (jQuery('#nn_iframe_holder_label').val()) ? jQuery('#nn_iframe_holder_label').val() : '',
						
						// You have to give the Customized placeholder text for the Card Holder Container here
						place_holder: (jQuery('#nn_iframe_holder_input').val()) ?jQuery('#nn_iframe_holder_input').val() : '',
						
					},
					card_number : {
					
						// You have to give the Customized label text for the Card Number Container here
						label: (jQuery('#nn_iframe_number_label').val()) ? jQuery('#nn_iframe_number_label').val() : '',
						
						// You have to give the Customized placeholder text for the Card Number Container here
						place_holder: (jQuery('#nn_iframe_number_input').val()) ? jQuery('#nn_iframe_number_input').val() : '',
						
					},
					expiry_date : {
					
						// You have to give the Customized label text for the Expiry Date Container here
						label: (jQuery('#nn_iframe_expire_label').val()) ? jQuery('#nn_iframe_expire_label').val() : '',
						
					},
					cvc : {
					
						// You have to give the Customized label text for the CVC/CVV/CID Container here
						label: (jQuery('#nn_iframe_cvc_label').val()) ? jQuery('#nn_iframe_cvc_label').val() : '',
						
						// You have to give the Customized placeholder text for the CVC/CVV/CID Container here
						place_holder: (jQuery('#nn_iframe_cvc_input').val()) ? jQuery('#nn_iframe_cvc_input').val() : '',
						
					}
				}
			},
			
			// Add Customer data
			customer: {
			
				// Your End-customer's First name which will be prefilled in the Card Holder field
				first_name:  $('#nn_first_name').val(),
				
				// Your End-customer's Last name which will be prefilled in the Card Holder field
				last_name:  $('#nn_last_name').val(),
				
				// Your End-customer's Email ID. 
				email:  $('#nn_email_address').val(),
				
				// Your End-customer's billing address.
				billing: {
				
					// Your End-customer's billing street (incl. House no).
					street: $('#nn_street_address').val(),
					
					// Your End-customer's billing city.
					city: $('#nn_city').val(),
					
					// Your End-customer's billing zip.
					zip: $('#nn_postcode').val(),
					
					// Your End-customer's billing country ISO code.
					country_code: $('#nn_country').val(),
				},
				
				// Set to 1 if the billing and shipping address are same and no need to specify shipping details again here.
				shipping: shipping_address,
			},
			
			// Add transaction data
			transaction: {
			
				// The payable amount that can be charged for the transaction (in minor units), for eg:- Euro in Eurocents (5,22 EUR = 522).
				amount: amount_booked,
				
				// The three-character currency code as defined in ISO-4217.
				currency: $('#nn_currency').val(),
				
				// Set to 1 for the TEST transaction (default - 0).
				test_mode: $('#nn_test_mode').val(),
				
				enforce_3d: $('#enforce_3d').val(),
			},
			custom: {
				
				// Shopper's selected language in shop
				lang: $('#nn_lang').val()
			}
		}
	    NovalnetUtility.createCreditCardForm(configurationObject);
	    NovalnetUtility.setCreditCardFormHeight();
	    
	    
	    /**
 * To performing oneclick shopping
 */
	var parentClassName = (jQuery( ".nn_cc_ref_details" ).parent().attr('class') == 'input') ? jQuery( ".nn_cc_ref_details" ).parent().attr('class') : jQuery( ".nn_cc_ref_details" ).parent().parent().attr('class');
	jQuery('#novalnet_cc_new_acc').click(function() {
		if (jQuery('.nn_cc_acc').css('display') == 'none') {
			jQuery('.nn_cc_acc').css('display', 'block');
			jQuery('.nn_cc_ref_details').css('display', 'none');
			jQuery('#novalnet_ccchange_account').val('0');
			jQuery("div").find( ".nn_cc_ref_details" ).closest('.'+parentClassName).hide();
			jQuery("div").find( ".nn_cc_acc" ).closest('.'+parentClassName).show();
			jQuery('#novalnet_cc_new_acc').html('<u><b>'+jQuery('#nn_lang_cc_given_account').val()+'</b></u>');
		} else {
			jQuery('.nn_cc_ref_details').css('display', 'block');
			jQuery("div").find( ".nn_cc_ref_details" ).closest('.'+parentClassName).show();
			jQuery("div").find( ".nn_cc_acc" ).closest('.'+parentClassName).hide();
			jQuery('.nn_cc_acc').css('display', 'none');
			jQuery('#novalnet_ccchange_account').val('1');
			jQuery('#novalnet_cc_new_acc').html('<u><b>'+jQuery('#nn_lang_cc_new_account').val()+'</b></u>');
		}
	});
	if (jQuery('#novalnet_ccchange_account').val() != undefined) {
		if (jQuery('#novalnet_ccchange_account').val() == 1 || jQuery('#novalnet_ccchange_account').val() == '') {
		  jQuery('.nn_cc_acc').css('display', 'none');
		  jQuery('.nn_cc_ref_details').css('display', 'block');
		  jQuery('.nn_cc_ref_details').css('margin', '2% 0% 0% 0%');
		  jQuery('#novalnet_cc_new_acc').html('<u><b>'+jQuery('#nn_lang_cc_new_account').val()+'</b></u>');
		} else {
		  jQuery("div").find( ".nn_cc_ref_details" ).closest('.'+parentClassName).hide();
		  jQuery('.nn_cc_acc').css('display', 'block');
		  jQuery('.nn_cc_ref_details').css('display', 'none');
		  jQuery('#novalnet_cc_new_acc').html('<u><b>'+jQuery('#nn_lang_cc_given_account').val()+'</b></u>');
		}
	}
	if($( "div" ).hasClass( "checkout-payment-form" )){
		if($('.checkout-payment-form').css('display') == 'none'){
		 $('.checkout-payment-form').css('display','block');
	    }
	 }
	 
	}
 

 /**
 * To performing form submiting
 */
 
	var formid= '';
	formid = getFormid();
	
  
	jQuery('#'+formid).submit(function (e)
	{
		if((jQuery('#novalnet_ccchange_account').val() == '0') ||  (jQuery('#novalnet_ccchange_account').val() == undefined) ){
			if((jQuery( "input[type=radio][name=payment]:checked" ).val() == 'novalnet_cc') || ($('li.novalnet_cc').hasClass('active'))){
			var hash = $('#nn_pan_hash').val();
			if(hash == ''){
				
					e.preventDefault();
					var panhash = NovalnetUtility.getPanHash();
					$("div.continue_button .btn-block").attr("disabled", "disabled");
				  return false;
				}
			}
		}
	});
 

/**
 *  Get formid to sumbit form 
 */ 
	function getFormid() {
			 var formid = '';
			jQuery('form').each(function() {
				if(jQuery(this).attr('id') == 'checkout_payment') {
					formid = jQuery(this).attr('id');
				}
			});
			if(formid == '') {
				formid = jQuery('#nn_root_cc_catalog').closest('form').attr('id');
			 }
			 if(formid == undefined ) {
				jQuery('#nn_root_cc_catalog').closest('form').attr('id', 'checkout_payment');
				formid = jQuery('#nn_root_cc_catalog').closest('form').attr('id');
			}
			return formid;
	}   
    
    
    
    
