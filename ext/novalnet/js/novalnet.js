/**
 * Novalnet payment module
 *
 * This script is used for common utility functionality
 *
 * @author     Novalnet AG
 * @copyright  Copyright (c) Novalnet
 * @license    https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link       https://www.novalnet.de
 *
 * Script: novalnet.js
 */
if (window.addEventListener) {
	window.addEventListener("load", novalnet_load);
} else if (window.attachEvent) {
	window.attachEvent("onload", novalnet_load);
}

function novalnet_load() {
	var urlVars = getUrlVars();
	if (urlVars.module && urlVars.action && urlVars.action == 'edit') {
		var module		= urlVars.module.toUpperCase();
		var module_code	= urlVars.module;
		// To validate applepay and googlepay button height
		jQuery('input[name="configuration[configuration/MODULE_PAYMENT_'+ module +'_BUTTON_HEIGHT]"]').on('keydown',function(e) {
			if (e.keyCode == 8 || e.keyCode == 46 || e.keyCode == 16 || e.keyCode == 20){
				return true;
			}
			if ((e.keyCode >= 48 && e.keyCode <= 57) || (e.keyCode >= 96 && e.keyCode <= 105)) {
				var num = jQuery('input[name="configuration[configuration/MODULE_PAYMENT_'+ module +'_BUTTON_HEIGHT]"]').val();
				if (num == '' && parseInt(String.fromCharCode(e.which)) >=3 && parseInt(String.fromCharCode(e.which)) <= 6) {
					return true;
				} else if (num >=3 && num <=6) {
					if (num == 6 && ((e.keyCode >= 48 && e.keyCode <= 52) || (e.keyCode >= 96 && e.keyCode <= 99))) {
						return true;
					} else if ((num >=3 && num < 6) && ((e.keyCode >= 48 && e.keyCode <= 57) || (e.keyCode >= 96 && e.keyCode <= 105))) {
						return true;
					}
				} else {
					return false;
				}
			}
			return false;
		});
		// To validate applepay button corner radius
		jQuery('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_CORNER_RADIUS]"]').on('keydown',function(e) {
			var radius = jQuery('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_CORNER_RADIUS]"]').val();
			if (e.keyCode == 8 || e.keyCode == 46 || e.keyCode == 16 || e.keyCode == 20) {
				return true;
			}
			if (radius == '') {
				if ((e.keyCode >= 49 && e.keyCode <= 57) || (e.keyCode >= 97 && e.keyCode <= 105)) {
					return true;
				} else {
					return false;
				}

			} else if (radius == 1) {
				if(e.keyCode == 48 || e.keyCode == 96) {
					return true
				} else {
					return false;
				}
			} else {
				return false;
			}
		});		
		var button_display = jQuery('input[name="configuration[configuration/MODULE_PAYMENT_'+ module +'_BUTTON_DISPLAY]"]');
		if ((getUrlVars()["module"] == 'novalnet_applepay') || (getUrlVars()["module"] == 'novalnet_googlepay')) {
			// Replace GooglePay button type field into select fields.
			var googlepay_button_type = {
				'plain' 	:'Plain',
				'buy'       : 'Buy',
				'donate'    : 'Donate',
				'book'      : 'Book',
				'checkout' 	: 'Checkout',
				'order'     : 'Order',
				'subscribe' : 'Subscribe',
				'pay'		: 'Pay',
			};
			var button_type = jQuery('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_TYPE]"]');
			var selected_type = button_type.val();
			jQuery(button_type).replaceWith('<select id="googlepay_button_type" name= "configuration[configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_TYPE]" ></select>');
			appendOptions(googlepay_button_type, selected_type, 'googlepay_button_type');
			
			// Replace GooglePay button theme field into select fields.
			var googlepay_button_theme = {
				'black'				: 'Dark',
				'white'				: 'Light',
				'white-outline'	    : 'Light-Outline',
			};
			var button_theme = jQuery('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_THEME]"]');
			var selected_theme = button_theme.val();
			jQuery(button_theme).replaceWith('<select id="googlepay_button_theme" name= "configuration[configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_THEME]" ></select>');
			appendOptions(googlepay_button_theme, selected_theme, 'googlepay_button_theme');
			
			//  Replace ApplePay button type field into select fields.
			var applepay_button_type = { 
				'plain' 	:'Default',
				'buy'       : 'Buy',
				'donate'    : 'Donate',
				'book'      : 'Book',
				'check-out' : 'Check out',
				'order'     : 'Order',
				'subscribe' : 'Subscribe',
				'pay'		: 'Pay',
				'contribute' : 'Contribute',
				'tip'       : 'Tip',
				'rent'      : 'Rent',
				'reload'    : 'Reload',
				'support'   : 'Support',
			};
			var button_type = jQuery('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_TYPE]"]');
			var selected_type = button_type.val();
			jQuery(button_type).replaceWith('<select id="applepay_button_type" name= "configuration[configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_TYPE]" ></select>');
			appendOptions(applepay_button_type, selected_type, 'applepay_button_type');
			
			// Replace ApplePay button theme field into select fields.
			var applepay_button_theme = {
				'black'				: 'Dark',
				'white'				: 'Light',
				'white-outline'	    : 'Light-Outline',
			};
			var button_theme = jQuery('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_THEME]"]');
			var selected_theme = button_theme.val();
			jQuery(button_theme).replaceWith('<select id="applepay_button_theme" name= "configuration[configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_THEME]" ></select>');
			appendOptions(applepay_button_theme, selected_theme, 'applepay_button_theme');
			// To give multiselect option in applepay and googlepay button display
			jQuery(button_display).replaceWith('<select id="nn_pages" name= "configuration[configuration/MODULE_PAYMENT_'+ module +'_BUTTON_DISPLAY][]" multiple="multiple" style="width:50%;" ><option value="shopping cart page" selected="selected">shopping cart page</option> <option value="product page">product page</option><option value="checkout page">checkout page</option></select>');
			var button_display_page = jQuery('#nn_button_display_page').val();
			var selectedValues = button_display_page.split("|");
			jQuery(document).ready(function() {
				jQuery("#nn_pages").select2( {
					closeOnSelect : false,
					placeholder : "Button display pages",
					allowHtml: true,
					allowClear: true,
					tags: true
				});
				jQuery('#nn_pages').val(selectedValues).trigger('change');
			});		
		}
		
		$('input[name="configuration[configuration/MODULE_PAYMENT_'+ module +'_ENDCUSTOMER_INFO]"]').keyup(function() {
			this.value = this.value.replace(/<(.|\n)*?>/g,'');
		});
		$('input[name="configuration[configuration/MODULE_PAYMENT_'+ module +'_VISIBILITY_BY_AMOUNT]"]').keyup(function() {
			this.value = this.value.replace(/[^0-9]/g,'');
		});
		$('input[name="configuration[configuration/MODULE_PAYMENT_'+ module +'_MANUAL_CHECK_LIMIT]"]').keyup(function() {
			this.value = this.value.replace(/[^0-9]/g,'');
		});
		checkDueDates();
		if ($('#' + module_code + '_auth').val() == 'false') {
			$('input[name="configuration[configuration/MODULE_PAYMENT_' + module + '_MANUAL_CHECK_LIMIT]').hide();
		}
		
		// Replace payment action field into select fields for CC and SEPA payments
		if ((getUrlVars()["module"] == 'novalnet_cc') || (getUrlVars()["module"] == 'novalnet_sepa')){
			var payment_action = '';
			if (jQuery('#lang_code').val() == 'EN') {
				payment_action = {
				''					: '--Select--',
				'authorize'			: 'Authorize',
				'capture'			: 'Capture',
				'zero_amount'	    : 'Authorize with zero amount',
				};
			} else if (jQuery('#lang_code').val() == 'DE') {
				payment_action = {
				''					: '--Wählen Sie --',
				'authorize'			: 'autorisieren',
				'capture'			: 'einziehen',
				'zero_amount'	    : 'Mit Nullbetrag autorisieren',
				};
			}
			var action = jQuery('input[name="configuration[configuration/MODULE_PAYMENT_' + module + '_AUTHENTICATE]"]');
			var selected_action = action.val();
			jQuery(action).replaceWith('<select id="payment_action" name= "configuration[configuration/MODULE_PAYMENT_' + module + '_AUTHENTICATE]" ></select>');
			appendOptions(payment_action, selected_action, 'payment_action');
			
			var limitElem  =  $('input[name="configuration[configuration/MODULE_PAYMENT_' + module + '_MANUAL_CHECK_LIMIT]"');
			limitElem.hide();
			$('#payment_action').click(function() {
				if($('#payment_action').find(":selected").val() == 'authorize') {
					limitElem.show();
				} else {
					limitElem.hide();
				}
			});
		} else {
			$('input:checkbox[name="configuration[configuration/MODULE_PAYMENT_' + module + '_AUTHENTICATE]"]').parent().click(function() {
				var switcher = $('input:checkbox[name="configuration[configuration/MODULE_PAYMENT_' + module + '_AUTHENTICATE]"]').parent();
				var limitElem      =  $('input[name="configuration[configuration/MODULE_PAYMENT_' + module + '_MANUAL_CHECK_LIMIT]"');
				if(switcher[0].classList.contains('checked')) {
					limitElem.show()
				} else {
					limitElem.hide();
				}
			});
		}
		
		
		// Allow minim value greater than 1998
		$('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_VISIBILITY_BY_AMOUNT]"]').blur(function() {
			if ($(this).val()  < 1998) {
				$(this).val('');
			}
		});
		// Hide switch in instalment and guarantee payments
		$("input[name='configuration[configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA]']").parent().find("div").hide();
		$("input[name='configuration[configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA]']").parents("div").first().hide();
		$("input[name='configuration[configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE]']").parent().find("div").hide();
		$("input[name='configuration[configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE]']").parents("div").first().hide();
		$("input[name='configuration[configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_BASIC_REQ]']").parent().find("div").hide();
		$("input[name='configuration[configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_INVOICE_BASIC_REQ]']").parents("div").first().hide();
		$('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_SEPA_BASIC_REQ]').parent().find("div").hide();
		$("input[name='configuration[configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_SEPA_BASIC_REQ]']").parents("div").first().hide();
	}
}

function checkDueDates() {
	$('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_INVOICE_DUE_DATE]"]').blur(function() {
		if ($(this).val()  < 7) {
			$(this).val('');
		}
	});
	$('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_PREPAYMENT_DUE_DATE]"]').blur(function() {
		if (($(this).val()  < 7) || ($(this).val() > 28)) {
			$(this).val('');
		}
	});
	$('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_SEPA_PAYMENT_DUE_DATE]"]').blur(function() {
		if (($(this).val()  < 2) || ($(this).val() > 14)) {
			$(this).val('');
		}
	});
	$('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_DUE_DATE]"]').blur(function() {
		if (($(this).val()  < 2) || ($(this).val() > 14)) {
			$(this).val('');
		}
	});
	$('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_INSTALMENT_SEPA_PAYMENT_DUE_DATE]"]').blur(function() {
		if (($(this).val()  < 2) || ($(this).val() > 14)) {
			$(this).val('');
		}
	});
}
function appendOptions(optionData , selectedValue ,elemID) {
	if($('#'+elemID+' > option').length == 0) {
		jQuery.each(optionData, function(value,text) {
			jQuery('#' + elemID).append(jQuery('<option>', {
				value: jQuery.trim(value),
				text: jQuery.trim(text)
			}));
			if (selectedValue != undefined && selectedValue == value) {
				jQuery('#' + elemID).val(value);
			}
		});
	}
}

function validateDateFormat(e) {
	if (!NovalnetUtility.validateDateFormat(e.value)) {
		alert($('#nn_invoice_birthdate_error').val());
	}
}
function getUrlVars() {
	var vars = {};
	var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
		vars[key] = value;
	});
	return vars;
}
