/**
 * Novalnet payment module
 *
 * This script is used for SEPA process
 *
 * @author     Novalnet AG
 * @copyright  Copyright (c) Novalnet
 * @license    https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link       https://www.novalnet.de
 *
 * Script: novalnet_sepa.js
 */

if (window.addEventListener) {
	window.addEventListener("load", novalnet_sepa_load);
} else if (window.attachEvent) {
	window.attachEvent("onload", novalnet_sepa_load);
}

function novalnet_sepa_load() {
	var selected_payment = ($("input[name='payment']").attr('type') == 'hidden') ? $("input[name='payment']").val() : $("input[name='payment']:checked").val();
	$('form').each(function () {
		if ($(this).attr('id') == 'checkout_payment') {
			formid = 'checkout_payment';
		}
	});
	$(document).ready(function() {
		$(".mandate_confirm").hide();
		if ($.inArray( selected_payment, ['novalnet_guarantee_sepa', 'novalnet_sepa' ])){
			$(".about_mandate").mouseover(function() {
				$(".mandate_confirm").show().next().toggle('slow');
			});
			$(".about_mandate").mouseout(function(){
				$(".mandate_confirm").hide();
			});
		}
	});
	handle_oneclick_process();
	$('#'+formid).submit(function (event) {
		if (selected_payment == 'novalnet_sepa') {
			if (undefined !== $( '#novalnet_sepa_iban') ) {
				var iban = NovalnetUtility.formatAlphaNumeric($('#novalnet_sepa_iban').val());
				if (iban === '' ) {
					return false;
				}
			}
		}
	 });

	$('#novalnet_sepa_iban_field').keyup(function (event) {
		$(this).val($(this).val().toUpperCase());
	});

	if ($.inArray( selected_payment, ['novalnet_guarantee_sepa', 'novalnet_sepa' ]) != -1) {
		if ($('#'+ selected_payment + 'birthdate') !== undefined && $('#'+ selected_payment +'birthdate').val() !== undefined) {
			if ($('#'+ selected_payment + 'birthdate').val() === '' || !NovalnetUtility.validateDateFormat($('#'+ selected_payment + 'birthdate').val())) {
				return false;
			}
		}
	}
}

function handle_oneclick_process () {
	$('#novalnet_sepa_new').click(function() {
		var selected_payment = ($("input[name='payment']").attr('type') == 'hidden') ? $("input[name='payment']").val() : $("input[name='payment']:checked").val();
		$('#sepa_save_card').show();
		$('#novalnet_sepa_iban').show();
		if (selected_payment == 'novalnet_guarantee_sepa') {
			$('#iban').show();
			$('#novalnet_sepa_iban_field').show();
			$('#novalnet_sepa_iban_field').parents().eq(1).show();
			$('#novalnet_sepa_onclick').parents().eq(1).show();
		} else {
			$('#iban').show();
			$('#novalnet_sepa_iban_field').show();
			$('#novalnet_sepa_iban_field').parents().eq(1).show();
			$('#novalnet_sepa_onclick').parents().eq(1).show();
		}
	});
	$('.novalnet_sepa_saved_acc').click(function() {
		var selected_payment = ($("input[name='payment']").attr('type') == 'hidden') ? $("input[name='payment']").val() : $("input[name='payment']:checked").val();
		$('#sepa_save_card').hide();
		$('#novalnet_sepa_iban').hide();
		if (selected_payment == 'novalnet_guarantee_sepa') {
			$('#novalnet_sepa_iban_field').parents().eq(1).hide();
			$('#novalnet_sepa_onclick').parents().eq(1).hide();
		} else {
			$('#novalnet_sepa_iban_field').parents().eq(1).hide();
			$('#novalnet_sepa_onclick').parents().eq(1).hide();
		}
	});
	if (($('#novalnet_sepa_new') == true) || ($('#sepa_save_card') == true)) {
	   $('#novalnet_guarantee_sepabirthdate').show();
	}
	$('.token').click(function(){
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
}

function validateDateFormat(e) {
	if (!NovalnetUtility.validateDateFormat(e.value)) {
		alert($('#nn_sepa_birthdate_error').val());
	}
}
