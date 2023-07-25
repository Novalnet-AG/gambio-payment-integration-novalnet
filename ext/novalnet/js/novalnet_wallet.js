/**
 * Novalnet payment module
 *
 * This script is used for wallet payments
 *
 * @author     Novalnet AG
 * @copyright  Copyright (c) Novalnet
 * @license    https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link       https://www.novalnet.de
 *
 * Script: novalnet_wallet.js
 */

if (window.addEventListener) {
	window.addEventListener("load", show_hide);
}

// Show or hide continue button based on enabling payment method in checkout page
function show_hide() {
	jQuery('#checkout_payment').on('click', function() {
		if((jQuery('input[name="payment"]:checked').val() == "novalnet_applepay") || (jQuery('input[name="payment"]:checked').val() == "novalnet_googlepay")) {
			jQuery('.continue_button').hide();
		} else {
			jQuery('.continue_button').show();
		}
	});
}

// Display Apple pay, Google pay button and payment process on the product page
function novalnet_wallet_product_page() {
	if (jQuery('#novalnet_googlepay_enable').val() == 1) {
		displayWalletButtons('novalnet_googlepay');
	}
	if (jQuery('#novalnet_applepay_enable').val() == 1) {
		displayWalletButtons('novalnet_applepay');
	}
}

// Display Apple pay, Google pay button and payment process on the cart page
function novalnet_wallet_cart_page() {
	if (jQuery('#novalnet_googlepay_enable').val() == 1) {
		displayWalletButtons('novalnet_googlepay');
	}
	if (jQuery('#novalnet_applepay_enable').val() == 1) {
		displayWalletButtons('novalnet_applepay');
	}
}

// Display Apple pay, Google pay button and payment process on the checkout page
function novalnet_checkout_page(wallet) {
	displayWalletButtons(wallet);
}

function displayWalletButtons(payment_code) {
	var wallet_form_details  = jQuery('#'+payment_code+'_data').val();
	wallet_form_details = JSON.parse(wallet_form_details);
	var walletProductDiv = jQuery('#'+payment_code+'_wallet_div'),
	walletProductContainer = jQuery('#'+payment_code+'_wallet_button'),
	payment_name = (payment_code == 'novalnet_googlepay') ? 'googlepay' : 'applepay';
	var instance = payment_code+'Instance';
	 instance = NovalnetPayment().createPaymentObject();
		instance.setPaymentIntent(self.walletPaymentRequest(payment_code, payment_name, wallet_form_details));
		instance.isPaymentMethodAvailable(function(canShowWalletBtn) {
			if (canShowWalletBtn) {
				walletProductContainer.empty();
				instance.addPaymentButton('#'+payment_code+'_wallet_button');
				if (wallet_form_details.current_page == 'product_info') {
					jQuery('#'+payment_code+'_wallet_button').find("apple-pay-button").css({'width': '425px', 'margin-left' : '3%'});
				} else if (wallet_form_details.current_page == 'shopping_cart') {
					jQuery('#'+payment_code+'_wallet_button').find("apple-pay-button").css({'width': '415px', 'margin-left' : '0.5%'});
				}
				walletProductDiv.css('display', 'block');

			} else {
				walletProductDiv.css('display' , 'none');
				if(jQuery('#novalnet_applepay_checkoutdiv') && jQuery('.novalnet_applepay').length >0) {
					jQuery('.novalnet_applepay').hide();
				}
			}
		});
}

/**
 * ApplePay and GooglePay process function
 */
function walletPaymentRequest(payment_code, payment_name, nn_wallet_details) {
	payment_name = payment_name.toUpperCase();
	var button_dimensions = {
			width: "auto",
			cornerRadius: (payment_name == 'APPLEPAY' ? nn_wallet_details.applepay_button_radius : ''),
			height: parseInt(((payment_name == 'GOOGLEPAY') ? (nn_wallet_details.googlepay_button_height) : (nn_wallet_details.applepay_button_height)))
		};
	var nn_button = {
			dimensions: button_dimensions,
			locale: (nn_wallet_details.lang !== undefined) ? (nn_wallet_details.lang.toLowerCase() + '-' + nn_wallet_details.lang) : '',
			type: (payment_name == 'GOOGLEPAY' ? nn_wallet_details.googlepay_button_type : nn_wallet_details.applepay_button_type),
			boxSizing: (payment_name == 'GOOGLEPAY' ? 'fill' : 'border-box'),
	};
	if ((nn_wallet_details.current_page == 'shopping_cart') && (payment_name == 'GOOGLEPAY' || payment_name == 'APPLEPAY')) {
		var nn_label_text = (payment_name == 'GOOGLEPAY') ? jQuery("#nn_googlepay_estotal_label").val() : jQuery("#nn_applepay_estotal_label").val();
	} else {
		var nn_label_text = (payment_name == 'GOOGLEPAY') ? jQuery("#nn_googlepay_total_label").val() : jQuery("#nn_applepay_total_label").val();
	}
	var shipping = ["postalAddress", "phone", "email"];
		if (nn_wallet_details.show_shipping_option == 0 && nn_wallet_details.payment_method == 'APPLEPAY') {
			var shipping = ["email"];
		}
	var configurationObject = {
		clientKey: nn_wallet_details.client_key,
		paymentIntent: {
			transaction: {
				amount: (nn_wallet_details.total_amount !== undefined) ? nn_wallet_details.total_amount : '',
				currency: (nn_wallet_details.currency !== undefined) ? nn_wallet_details.currency : '',
				paymentMethod: payment_name,
				enforce3d: (nn_wallet_details.enforce_3d !== undefined) ? nn_wallet_details.enforce_3d : '',
				environment: (nn_wallet_details.environment !== undefined) ? nn_wallet_details.environment : '',
				setPendingPayment: (nn_wallet_details.current_page == 'checkout_payment' || nn_wallet_details.current_page == 'product_info') ? false : true,
			},
			merchant: {
				countryCode: (nn_wallet_details.country_code !== undefined) ? nn_wallet_details.country_code : '',
				partnerId: (payment_name == 'GOOGLEPAY') ? ((nn_wallet_details.partner_id !== undefined) ? nn_wallet_details.partner_id : '') : '',
				paymentDataPresent: false
			},
			custom: {
				lang: (nn_wallet_details.lang !== undefined) ? (nn_wallet_details.lang.toLowerCase() + '-' + nn_wallet_details.lang) : '',
			},
			order: {
				paymentDataPresent: false,
				merchantName: (nn_wallet_details.seller_name !== undefined) ? nn_wallet_details.seller_name : '',
				lineItems: JSON.parse(jQuery('#nn_article_details').val()),
				billing: {
					requiredFields: (nn_wallet_details.current_page != 'checkout_payment') ? ["postalAddress", "phone", "email"] : ["postalAddress"],
				},
				shipping: {
					requiredFields: (nn_wallet_details.current_page != 'checkout_payment') ? shipping : ["postalAddress", "phone", "email"],
					methodsUpdatedLater: (nn_wallet_details.current_page != 'checkout_payment') ? true : false,
				},
				labelText: nn_label_text,
			},
			button: nn_button,
			callbacks: {
				onProcessCompletion: function (responseData, bookingResult) {
					// Only on success, we proceed further with the booking
					if (responseData.result.status == 'SUCCESS') {
						if (nn_wallet_details.current_page == 'checkout_payment') {
							bookingResult({status: 'SUCCESS', statusText: ''});
							if (payment_name == 'GOOGLEPAY') {
								document.getElementById('nn_google_wallet').value = responseData.transaction.token;
								document.getElementById('nn_wallet_doredirect').value = responseData.transaction.doRedirect;
							} else {
								document.getElementById('nn_wallet').value = responseData.transaction.token;
							}
							var submitEl = jQuery("div.continue_button :submit");
							jQuery(submitEl).click();
						} else {
							var response = {response : responseData};
							var data_to_send = {
								'variable_name': response,
								'payment_name' : (payment_name == 'GOOGLEPAY') ? 'novalnet_googlepay' : 'novalnet_applepay',
								'payment_page' : (nn_wallet_details.current_page !== undefined) ? nn_wallet_details.current_page : '',

							};
							if (nn_wallet_details.shop_version == 1) {
								data_to_send['customer_id']  = nn_wallet_details.customer_id;
								data_to_send['loginUrl']    = nn_wallet_details.login_url;
								data_to_send['shippingPage']    = nn_wallet_details.shipping_page;
							}
							if (nn_wallet_details.customer_id != undefined && nn_wallet_details.customer_id == 0 && nn_wallet_details.current_page == 'product_info') {
								data_to_send['products_qty'] = jQuery("input[name='products_qty']").val();
								data_to_send['products_id']  = jQuery("input[name='products_id']").val();
								data_to_send['product_amount']  = jQuery('#nn_product').val();
							}
							jQuery.ajax({
								type : 'POST',
								url  : 'novalnet_wallet_payment_process.php',
								data : data_to_send,
								success: function (order_response) {
									response = JSON.parse(order_response);
									if (response.isLogin == 1 && response.login_url) {
										window.location.replace(response.login_url);
										window.location.replace(response.shipping_page);
									}
									if (response.isRedirect == 1 && response.redirect_url) {
										window.location.replace(response.redirect_url);
									}
									if (response.return_url) {
										window.location.replace(response.return_url);
									}
									bookingResult({status: "SUCCESS", statusText: ''});
								},
								error: function(xhr){
									bookingResult({status: "ERROR", statusText: xhr.result.statusText});
								}
							});
						}
					}
				},
				onShippingContactChange: function (shippingContact, updatedData) {
					var payload = {address : shippingContact};
					var data_to_send = {
						action: 'novalnet_shipping_address_update', // your action name
						shippingInfo: JSON.stringify(payload),
					};
					if (((payment_name == 'GOOGLEPAY') && jQuery('#novalnet_googlepay_wallet_button')) || ((payment_name == 'APPLEPAY') && jQuery('#novalnet_applepay_wallet_button'))) {
						data_to_send['products_qty'] = jQuery("input[name='products_qty']").val();
						data_to_send['products_id']  = jQuery("input[name='products_id']").val();
						data_to_send['product_amount']  = jQuery('#nn_product').val();
					}
					var modifiers=[];
					$( "[name*='modifiers[property]']" ).each(function(){
						var modifierVal = $(this).val();
						if (modifierVal && modifierVal != 0 && !modifiers.includes(modifierVal)) {
						  modifiers.push(modifierVal);
						}
					});
					data_to_send['variant_info'] = modifiers;
					new Promise(function(resolve, reject) {
						jQuery.ajax({
							type: 'POST',
							url  : 'novalnet_wallet_shipping_data_update_process.php',
							data: data_to_send,
							success: function (response) {
								resolve(response);
							}
						});
					}).then(function(response) {
						var result = JSON.parse(response);
						var updatedInfo = {};
						if ( result.shipping_address.length == 0 ) {
							updatedInfo.methodsNotFound = "There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.";
						} else if( result.shipping_address != undefined && result.shipping_address.length ) {
							updatedInfo.amount = result.amount;
							updatedInfo.lineItems = result.article_details;
							updatedInfo.methods = result.shipping_address;
							updatedInfo.defaultIdentifier = result.shipping_address[0].identifier;
						}
						updatedData(updatedInfo);
					});
				},
				onShippingMethodChange: function (choosenShippingMethod, updatedData) {
					var payload = {shippingMethod : choosenShippingMethod};
					var data_to_send = {
						'action': 'novalnet_shipping_method_update', // your action name
						'shippingInfo': JSON.stringify(payload), // your action name
					};
					$.ajax({
						type: 'POST',
						url  : 'novalnet_wallet_shipping_data_update_process.php',
						data: data_to_send,
						success: function (response) {
							var result = JSON.parse(response);
							var updatedInfo = {
								amount: result.amount,
								lineItems: result.article_details,
							};
							updatedData(updatedInfo);
						}
					});
				},
				onPaymentButtonClicked: function(clickResult) {
					if (nn_wallet_details.current_page == 'product_info' ) {
						if ((jQuery('button[name="btn-add-to-cart"]').prop('disabled')) || (jQuery('input[name="btn-add-to-cart"]').prop('disabled'))) {
							clickResult({status: "FAILURE"});
							alert('Please select some product options before adding this product to your cart.');
						} else if (nn_wallet_details.product_type == 2) {
								var data = {
									'action': 'add_virtual_product_in_cart', // your action name
									'products_id': jQuery("input[name='products_id']").val(),
									'products_qty': jQuery("input[name='products_qty']").val(),
								};
								var modifiers=[];
								$( "[name*='modifiers[attribute]']" ).each(function(){
									if ($(this).prop('checked')==true) {
										var modifierVal = $(this).val();
										if (modifierVal && modifierVal != 0 && !modifiers.includes(modifierVal)) {
										  modifiers.push(modifierVal);
										}

									}
								});
								data['attribute_info'] = modifiers;
								$.ajax({
									url  : 'novalnet_wallet_shipping_data_update_process.php',
									type: 'POST',
									data: data,
									success: function (response) {
										var result = JSON.parse(response);
										if (result.amount != '' && typeof result.amount !== undefined) {
											var product_data = JSON.parse($('#'+payment_code+'_data').val());
											product_data.total_amount = parseInt(result.amount);
											product_data.orig_amount = parseInt(result.amount);
											$('#'+payment_code+'_data').val(JSON.stringify(product_data));
										}
										if (result.article_details != '') {
											$('#nn_article_details').val(JSON.stringify(result.article_details));
										}
									}
								});
							clickResult({status: "SUCCESS"});
						} else {

							clickResult({status: "SUCCESS"});
						}
					} else {
						var data = {'action' : 'updated_amount'};
						$.ajax({
							url  : 'novalnet_wallet_shipping_data_update_process.php',
							type: 'POST',
							data: data,
							success: function (response) {
								var result = JSON.parse(response);
								if(result.amount != '' && typeof result.amount !== undefined) {
									var product_data = JSON.parse($('#'+payment_code+'_data').val());
									product_data.total_amount = parseInt(result.amount);
									product_data.orig_amount = parseInt(result.amount);
									$('#'+payment_code+'_data').val(JSON.stringify(product_data));
								}
							}
						});
						clickResult({status: "SUCCESS"});
					}
                }
			}
		}
	};
	if (!nn_wallet_details.show_shipping_option && nn_wallet_details.payment_method == 'GOOGLEPAY') {
		delete configurationObject.paymentIntent.order.shipping;
	}
	if (nn_wallet_details.current_page == 'checkout_payment') {
		delete configurationObject.paymentIntent.order.billing;
		delete configurationObject.paymentIntent.order.shipping;
	}
	if (payment_name == 'APPLEPAY') {
		delete configurationObject.paymentIntent.merchant.partnerId;
		delete configurationObject.paymentIntent.transaction.enforce3d;
	}
	if (nn_wallet_details.current_page == 'product_info' && nn_wallet_details.show_shipping_option == 0) {
		var product_amount = JSON.parse($('#'+payment_code+'_data').val());
		configurationObject.paymentIntent.transaction.amount = product_amount.total_amount;

		var order_details = JSON.parse($('#nn_article_details').val());
		configurationObject.paymentIntent.order.lineItems = order_details;
	}
	if (nn_wallet_details.current_page == 'shopping_cart' && nn_wallet_details.show_shipping_option == 0) {
		var product_amount = JSON.parse($('#'+payment_code+'_data').val());
		configurationObject.paymentIntent.transaction.amount = product_amount.total_amount;
	}
	return configurationObject;
}

jQuery( document ).ready(function() {
jQuery( document ).ajaxComplete(function(event,xhr,settings) {
	let pattern = /novalnet_wallet_shipping_data_update_process/i;
	let ajax_url = settings.url.match(pattern);

	var payment_data = '';
	if (jQuery('#novalnet_googlepay_enable').val() == 1) {
		payment_data = $('#novalnet_googlepay_data').val();
	}
	if (jQuery('#novalnet_applepay_enable').val() == 1) {
		payment_data = $('#novalnet_applepay_data').val();
	}

	var parsed_data = JSON.parse(payment_data);
	var current_page = parsed_data.current_page;

	if (current_page && current_page == 'shopping_cart') {
		novalnet_wallet_cart_page();
	}

	if (jQuery('#novalnet_googlepay_wallet_div').length > 0  && jQuery('#novalnet_googlepay_wallet_div').is(":visible") == true && ajax_url != 'novalnet_wallet_shipping_data_update_process') {
		var data = {
			'action': 'get_variant_product_amount', // your action name
			'products_id': jQuery("input[name='products_id']").val(),
			'products_qty': jQuery("input[name='products_qty']").val(),
		};
		var modifiers=[];
		$( "[name*='modifiers[property]']" ).each(function(){
			var modifierVal = $(this).val();
			if (modifierVal && modifierVal != 0 && !modifiers.includes(modifierVal)) {
			  modifiers.push(modifierVal);
			}
		});
		data['variant_info'] = modifiers;
		if (modifiers.length > 0) {
			$.ajax({
				url  : 'novalnet_wallet_shipping_data_update_process.php',
				type: 'POST',
				data: data,
				success: function (response) {
					var result = JSON.parse(response);
					if(result.amount != '' && typeof result.amount !== undefined){
						var product_data = JSON.parse($('#novalnet_googlepay_data').val());
						product_data.total_amount = parseInt(result.amount) + parseInt(product_data.orig_amount);
						$('#novalnet_googlepay_data').val(JSON.stringify(product_data));
					}
				}
			});
		}
		else {
			var data = {'action' : 'updated_amount'};
			data['get_article_details'] = 1;
				$.ajax({
					url  : 'novalnet_wallet_shipping_data_update_process.php',
					type: 'POST',
					data: data,
					success: function (response) {
						var result = JSON.parse(response);
						if(result.amount != '' && typeof result.amount !== undefined){
							var product_data = JSON.parse($('#novalnet_googlepay_data').val());
							product_data.total_amount = parseInt(result.amount);
							product_data.orig_amount = parseInt(result.amount);
							$('#novalnet_googlepay_data').val(JSON.stringify(product_data));
						}
						if (result.article_details != '') {
							var articleDetails = JSON.parse($('#nn_article_details').val());
							$('#nn_article_details').val(JSON.stringify(result.article_details));
						}
					}
				});
		}
		return;
	} else if (jQuery('#novalnet_applepay_wallet_div').length > 0  && jQuery('#novalnet_applepay_wallet_div').is(":visible") == true && ajax_url != 'novalnet_wallet_shipping_data_update_process') {
		var data = {
			'action': 'get_variant_product_amount', // your action name
			'products_id': jQuery("input[name='products_id']").val(),
			'products_qty' : jQuery("input[name='products_qty']").val(),
		};
		var modifiers=[];
		$( "[name*='modifiers[property]']" ).each(function() {
			var modifierVal = $(this).val();
			if (modifierVal && modifierVal != 0 && !modifiers.includes(modifierVal)) {
			  modifiers.push(modifierVal);
			}
		});
		data['variant_info'] = modifiers;
		if(modifiers.length > 0) {
			$.ajax({
				url  : 'novalnet_wallet_shipping_data_update_process.php',
				type: 'POST',
				data: data,
				success: function (response) {
					var result = JSON.parse(response);
					if(result.amount != '' && typeof result.amount !== undefined){
						var product_data = JSON.parse($('#novalnet_applepay_data').val());
						product_data.total_amount = parseInt(result.amount) + parseInt(product_data.orig_amount);
						$('#novalnet_applepay_data').val(JSON.stringify(product_data));
					}
				}
			});
		}
		else {
			var data = {'action' : 'updated_amount'};
			data['get_article_details'] = 1;
				$.ajax({
					url  : 'novalnet_wallet_shipping_data_update_process.php',
					type: 'POST',
					data: data,
					success: function (response) {
						var result = JSON.parse(response);
						if (result.amount != '' && typeof result.amount !== undefined) {
							var product_data = JSON.parse($('#novalnet_applepay_data').val());
							product_data.total_amount = parseInt(result.amount);
							product_data.orig_amount = parseInt(result.amount);
							$('#novalnet_applepay_data').val(JSON.stringify(product_data));
						}
						if (result.article_details != '') {
							var articleDetails = JSON.parse($('#nn_article_details').val());
							$('#nn_article_details').val(JSON.stringify(result.article_details));
						}
					}
				});
		}
			return;
	}
});
});
