/**
 * Novalnet payment module
 *
 * This script is used for Instalment Invoice plan tables
 *
 * @author     Novalnet AG
 * @copyright  Copyright (c) Novalnet
 * @license    https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link       https://www.novalnet.de
 *
 * Script: novalnet_instalment_invoice.js
 */

if (window.addEventListener) {
	window.addEventListener("load", instalment_table_invoice);
	window.addEventListener("change", instalment_table_invoice);
} else if (window.attachEvent) {
	window.attachEvent("onload", instalment_table_invoice);
	window.attachEvent("change", instalment_table_invoice);
}

//Show instalment invoice plan details
function instalment_table_invoice() {
	var novalnet_order_cycle_period = $("#novalnet_global_recurring_period_cycles option:selected").val();
	var order_amount = $("#order_amount").val();
	var total_amount = 0, instalment_due = 0, last_instalment_due = 0;
	var nn_dob_placeholder = $('#novalnet_instalment_invoicebirthdate').val();
	$('#novalnet_instalment_invoicebirthdate').attr("placeholder", nn_dob_placeholder);
	total_amount = ( parseFloat( order_amount ) ).toFixed( 2 );
	for (var i=1;i<=novalnet_order_cycle_period;i++) {
		if (novalnet_order_cycle_period != i) {
			split_amount = ( parseFloat( total_amount/novalnet_order_cycle_period ) ).toFixed( 2 );
			instalment_due = parseFloat( instalment_due ) + parseFloat( split_amount );
		} else {
			last_instalment_due = ( parseFloat ( total_amount - instalment_due ) ).toFixed( 2 );
		}
	}

	var number_text = '';
	var currency = $('#nn_installmnet_currency').val();
	var final_due = novalnet_order_cycle_period-1;
	if (novalnet_order_cycle_period == '0') {
		$("#novalnet_instalment_table_invoice thead tr").remove();
	} else {
		$("#novalnet_instalment_table_invoice thead tr").remove();
		$("#novalnet_instalment_table_invoice thead" ).append("<tr><th>" + $('#nn_cycles_frontend').val() + "</th><th>" + $('#nn_amount_frontend').val() + "</th></tr>");
	}
	$("#novalnet_instalment_table_invoice").show();
	$("#novalnet_instalment_table_invoice tbody tr").remove();
	for (var j=0;j<novalnet_order_cycle_period;j++) {
		if ($("#nn_shop_lang" ).val() == 'en') {
			if (j+1 == 1 || j+1 == 21) {
				number_text = j+1+"st "+ $('#nn_installment_frontend').val();
			} else if (j+1 == 2 || j+1 == 22) {
				number_text = j+1+"nd "+ $('#nn_installment_frontend').val();
			} else if (j+1 == 3) {
				number_text = j+1+"rd "+ $('#nn_installment_frontend').val();
			} else {
				number_text = j+1+"th "+ $('#nn_installment_frontend').val();
			}
		} else {
			if (j+1 == 1 || j+1 == 21) {
				number_text = j+1+"st "+ $('#nn_installment_frontend').val();
			} else if (j+1 == 2 || j+1 == 22) {
				number_text = j+1+"nd "+ $('#nn_installment_frontend').val();
			} else if (j+1 == 3) {
				number_text = j+1+"rd "+ $('#nn_installment_frontend').val();
			} else {
				number_text = j+1+"th "+ $('#nn_installment_frontend').val();
			}
		}
		if (final_due != j) {
			$("#novalnet_instalment_table_invoice tbody").append("<tr><td>" + number_text + "</td><td>"+ split_amount + " " + currency + "</td></tr>");
		} else {
			$("#novalnet_instalment_table_invoice tbody").append("<tr><td>" + number_text + "</td><td>"+ last_instalment_due + " " + currency + "</td></tr>");
		}
	}
}
function validateDateFormat(e) {
	if (!NovalnetUtility.validateDateFormat(e.value)) {
		alert($('#nn_instalmentinvoice_birthdate_error').val());
    }
}
