jQuery(document).ready(
    function () {
        $(document).on(
            'click', '#nn_instacancel_allcycles, #nn_instacancel_remaincycles, #nn_instalment_cancel', function (event) {
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
            }
        );
    }
);

function zero_amount_validation()
{
    var bookamount = document.getElementById('book_amount').value;
    if (bookamount.trim() == '' || bookamount == 0 || isNaN(bookamount) ) {
        document.getElementById('nn_zero_amount_error').innerHTML=document.getElementsByName("nn_amount_error")[0].value;
        return false;
    }
    if (!confirm(document.getElementsByName("nn_zero_amount_book_confirm")[0].value)) {
        return false;
    }
}

function refund_amount_validation()
{
    var amount = document.getElementById('refund_trans_amount').value;
    if (amount.trim() == '' || amount == 0 || isNaN(amount)) {
        document.getElementById('nn_refund_error').innerHTML= document.getElementsByName("nn_amount_error")[0].value;
        return false;
    }
    
    if (!confirm(document.getElementsByName("nn_refund_amount_confirm")[0].value)) {
        return false;
    } else {
        // Submit the form
        document.forms["novalnet_trans_refund"].submit();
    }
}

function void_capture_status()
{
    if (document.getElementsByName("trans_status")[0].value != "") {
        var display_status =  document.getElementsByName("trans_status")[0].value == "CONFIRM" ? document.getElementsByName("nn_capture_update")[0].value : document.getElementsByName("nn_void_update")[0].value;
        if (!confirm(display_status)) {
            return false;
        }
    }
    return true;
}

