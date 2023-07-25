/**
 * Novalnet payment module
 *
 * This script is used for post process functionality
 *
 * @author     Novalnet AG
 * @copyright  Copyright (c) Novalnet
 * @license    https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link       https://www.novalnet.de
 *
 * Script: novalnet_extension.js
 */

jQuery(document).ready(function () {
    let elements = jQuery(".refundBtns"),
        nn_instalment_refund_function = function () {
            let cycle = this.getAttribute("data-instalment"),
                refund_id = jQuery('#instalment_refund_'+ cycle);

            if (refund_id.style.display == "none") {
                refund_id.style.display = "block";
            } else {
                refund_id.style.display = "none";
            }
        };

    for (let i = 0; i < elements.length; i++) {
        elements[i].addEventListener('click', nn_instalment_refund_function, false);
    }
    $(document).on('click', '#nn_instacancel_allcycles, #nn_instacancel_remaincycles, #nn_instalment_cancel', function (event) {
        if ($("#novalnet_instalment_cancel").css({"display": "none"})) {
            $("#novalnet_instalment_cancel").css({"display": "inline-flex"});
            $("#nn_instalment_cancel").css({"display": "none"});
        } else {
            $("#novalnet_instalment_cancel").css({"display": "none"});
            $("#nn_instalment_cancel").css({"display": "block"});
        }
        if (this.id == 'nn_instacancel_allcycles') {
            if (!confirm(jQuery("[name=nn_insta_allcycles]").val())) {
                return false;
            }
        } else if (this.id == 'nn_instacancel_remaincycles') {
            if (!confirm(jQuery("[name=nn_insta_remainingcycles]").val())) {
                return false;
            }
        }
    });
});

function void_capture_status() {
	if (document.getElementsByName("trans_status")[0].value == "") {
		var msg = document.getElementsByName("nn_select_status")[0].value;
		if (!confirm(document.getElementsByName("nn_select_status")[0].value)) {
			return false;
		}
	} else {
		var display_status =  document.getElementsByName("trans_status")[0].value == "CONFIRM" ? document.getElementsByName("nn_capture_update")[0].value : document.getElementsByName("nn_void_update")[0].value;
		if (!confirm(display_status)) {
			return false;
		}
	}
	return true;
}

function remove_void_capture_error_message() {
	document.getElementById('nn_void_capture_error').innerHTML='';
}

function refund_amount_validation() {
	if (document.getElementById('refund_tid') != null) {
		var refund_ref = document.getElementById('refund_tid').value;
		refund_ref = refund_ref.trim();
		var re = /[\/\\#,+!^()$~%.":*?<>{}]/g;
		if (re.test(refund_ref)) {
			document.getElementById('nn_refund_error').innerHTML=document.getElementsByName("nn_valid_account")[0].value;
			return false;
		}
	}
	else {
		var amount = document.getElementById('refund_trans_amount').value;
		if (amount.trim() == '' || amount == 0 || isNaN(amount)) {
			document.getElementById('nn_refund_error').innerHTML= document.getElementsByName("nn_amount_error")[0].value;
			return false;
		}
	}
	if (!confirm(document.getElementsByName("nn_refund_amount_confirm")[0].value)) {
		return false;
	}
}

function zero_amount_validation() {
	var bookamount = document.getElementById('book_amount').value;
	if (bookamount.trim() == '' || bookamount == 0 || isNaN(bookamount) ) {
		document.getElementById('nn_zero_amount_error').innerHTML=document.getElementsByName("nn_amount_error")[0].value;
		return false;
	}
	if (!confirm(document.getElementsByName("nn_zero_amount_book_confirm")[0].value)) {
		return false;
	}
}
