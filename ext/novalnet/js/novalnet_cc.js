/**
 * Novalnet payment module
 *
 * This script loads the form and gets the server's pan hash
 *
 * @author     Novalnet AG
 * @copyright  Copyright (c) Novalnet
 * @license    https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link       https://www.novalnet.de
 *
 * Script: novalnet_cc.js
 */
window.onload = function() {
	novalnet_cc = {
		/** Initiate Credit card Iframe process */
		init : function() {
			novalnet_cc.load_iframe();
			$('.cc_token').click(function(){
				var data_to_send = {'action': 'delete_token', 'id': this.id};
				 $.ajax({
					type : 'POST', 
		            url  : 'novalnet_token_delete.php',
		            data : data_to_send,
		            success: function(data){
		                location.reload();
		             }
		          });
			});
			
			jQuery('input[name="payment"]').click(function(){
				if(jQuery(this).val() == 'novalnet_cc') {
					NovalnetUtility.setCreditCardFormHeight();
				}
			});

			jQuery('#novalnet_cc_new').click(function() {
				jQuery('#novalnet_iframe').show();
				jQuery('#cc_save_card').show();
				if(jQuery(this).attr('data-reload') == '1'){
					novalnet_cc.load_iframe();
					jQuery(this).attr('data-reload', '0')
				}
			});
			
			jQuery('.novalnet_cc_saved_acc').click(function(){
				jQuery('#novalnet_iframe').hide();
				jQuery('#cc_save_card').hide();
			});
		},
		load_iframe : function(){
			var nn_css_label = jQuery('#nn_css_label').val();
			var nn_css_input = jQuery('#nn_css_input').val();
			var nn_css_text = jQuery('#nn_css_text').val();
			var nn_cc_form_details  = jQuery('#nn_cc_iframe_data').val();
			var nn_cc_details = JSON.parse(nn_cc_form_details);
			var clientKey = (nn_cc_details.client_key !== undefined) ? nn_cc_details.client_key : '';
			var iframe = document.getElementById('novalnet_iframe').contentWindow;
			NovalnetUtility.setClientKey(clientKey);
			var configurationObject = {
				callback: {
					on_success: function (data) {
						jQuery('#nn_pan_hash').val(data['hash']);
						jQuery('#nn_uniqueid').val(data['unique_id']);
						jQuery('#do_redirect').val(data['do_redirect']);
						$("#checkout_payment").submit();
						return true;
					},
					on_error: function (data) {
						if (data['error_message'] !== undefined) {
							alert(data['error_message']);
							return false;
						}
					},
				},
				iframe: {
					id: "novalnet_iframe",
					inline: (nn_cc_details.inline_form !== undefined) ? nn_cc_details.inline_form : '0',
					style: {
						container: (nn_css_text !== undefined) ? nn_css_text : '',
						input:(nn_css_input !== undefined) ? nn_css_input : '' ,
						label: (nn_css_label !== undefined) ? nn_css_label : '' ,
					},
					text: {
						lang: (nn_cc_details.lang !== undefined) ? nn_cc_details.lang : '',
						error: (nn_cc_details.iframe_error !== undefined) ? nn_cc_details.iframe_error : '',
						card_holder: {
							label: (nn_cc_details.iframe_holder_label !== undefined) ? nn_cc_details.iframe_holder_label : '',
							place_holder: (nn_cc_details.iframe_holder_input !== undefined) ? nn_cc_details.iframe_holder_input : '',
							error: (nn_cc_details.iframe_holder_error !== undefined) ? nn_cc_details.iframe_holder_error : '',
						},
						card_number: {
							label: (nn_cc_details.iframe_number_label !== undefined) ? nn_cc_details.iframe_number_label : '',
							place_holder: (nn_cc_details.iframe_number_input !== undefined) ? nn_cc_details.iframe_number_input : '',
							error: (nn_cc_details.iframe_number_error !== undefined) ? nn_cc_details.iframe_number_error : '',
						},
						expiry_date: {
							label: (nn_cc_details.iframe_expire_label !== undefined) ? nn_cc_details.iframe_expire_label : '',
							error: (nn_cc_details.iframe_expire_error !== undefined) ? nn_cc_details.iframe_expire_error : '',
						},
						cvc: {
							label: (nn_cc_details.iframe_cvc_label !== undefined) ? nn_cc_details.iframe_cvc_label : '',
							place_holder: (nn_cc_details.iframe_cvc_input !== undefined) ? nn_cc_details.iframe_cvc_input : '',
							error: (nn_cc_details.iframe_cvc_error !== undefined) ? nn_cc_details.iframe_cvc_error : '',
						}
					}
				},
				customer: {
					first_name: (nn_cc_details.first_name !== undefined) ? nn_cc_details.first_name : '',
					last_name: (nn_cc_details.last_name !== undefined) ? nn_cc_details.last_name : nn_cc_details.first_name,
					email: (nn_cc_details.email_address !== undefined) ? nn_cc_details.email_address : '',
					billing: {
						street: (nn_cc_details.street_address !== undefined) ? nn_cc_details.street_address : '',
						city: (nn_cc_details.city !== undefined) ? nn_cc_details.city : '',
						zip: (nn_cc_details.postcode !== undefined) ? nn_cc_details.postcode : '',
						country_code: (nn_cc_details.country !== undefined) ? nn_cc_details.country : ''
					},
					shipping: {
						"same_as_billing": 1,
						first_name: (nn_cc_details.first_name !== undefined) ? nn_cc_details.first_name : '',
						last_name: (nn_cc_details.last_name !== undefined) ? nn_cc_details.last_name : nn_cc_details.first_name,
						email: (nn_cc_details.email_address !== undefined) ? nn_cc_details.email_address : '',
						street: (nn_cc_details.street_address !== undefined) ? nn_cc_details.street_address : '',
						city: (nn_cc_details.city !== undefined) ? nn_cc_details.city : '',
						zip: (nn_cc_details.postcode !== undefined) ? nn_cc_details.postcode : '',
						country_code: (nn_cc_details.country !== undefined) ? nn_cc_details.country : ''
					},
				},
				transaction: {
				  amount: (nn_cc_details.amount !== undefined) ? nn_cc_details.amount : '',
				  currency: (nn_cc_details.currency !== undefined) ? nn_cc_details.currency : '',
				  test_mode: (nn_cc_details.test_mode !== undefined) ? nn_cc_details.test_mode : '0',
				  enforce_3d: (nn_cc_details.enforce_3d !== undefined) ? nn_cc_details.enforce_3d : '0',
				},
				custom: {
					lang: (nn_cc_details.lang !== undefined) ? nn_cc_details.lang : 'DE'
				}
			}
			NovalnetUtility.createCreditCardForm(configurationObject);
		},
	};
	jQuery(document).ready(function(){
		novalnet_cc.init();
	});
};

document.getElementById('checkout_payment').onsubmit = function(event) {
	if((document.getElementsByName("payment").length > 1 && document.querySelector('input[name="payment"]:checked').value =="novalnet_cc") 
	|| (document.querySelector('input[name="payment"]').value =="novalnet_cc")) {
		if (jQuery('#novalnet_iframe').is(":visible") && jQuery('#nn_pan_hash').val() != undefined && jQuery('#nn_pan_hash').val() == '') {
			event.preventDefault();
			event.stopImmediatePropagation();
			NovalnetUtility.getPanHash();
		}
	}
};
