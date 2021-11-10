/**
 * Novalnet Direct Debit SEPA Script
 * By Novalnet (https://www.novalnet.de)
 * Copyright (c) Novalnet
*/
if (window.addEventListener) {    // For all major browsers, except IE 8 and earlier
    window.addEventListener('load', novalnet_sepa_load);
} else if (window.attachEvent) { // For IE 8 and earlier versions
    window.attachEvent('onload', novalnet_sepa_load);
}

/**
 *  To performing when page is loading and form submiting
 */
function novalnet_sepa_load() {
	 var current_url = new URL($(location).attr("href"));
	 var url = current_url.searchParams.get("payment_error");
		if(url == 'novalnet_sepa'){
		 jQuery('.novalnet_sepa').addClass('active');
	    }
    jQuery('#novalnet_sepa_iban').removeAttr('name');
    jQuery('#novalnet_sepa_bic').removeAttr('name');
    var formid = '';
    if(formid == '') {
        formid = jQuery('#novalnet_sepa_bank_country').closest('form').attr('id');
    }
    jQuery('#novalnet_sepa_dob').attr("placeholder", "YYYY-MM-DD");
    jQuery('form').each(function() {
        jQuery( ".shipping_container" ).on( "click", function() {
            jQuery('#novalnet_sepa_mandate_confirm').prop('checked', false);
            if(jQuery('#novalnet_sepa_mandate_confirm').val() == 1)
                sepa_mandate_unconfirm_process();
        });
        if(jQuery(this).attr('id') == 'checkout_payment') {
            formid = jQuery(this).attr('id');
        }
    });
    jQuery("#"+formid).submit(function () {
        var selected_payment = (jQuery("input[name='payment']").attr('type') == 'hidden') ? jQuery("input[name='payment']").val() : jQuery("input[name='payment']:checked").val();
        if(selected_payment == 'novalnet_sepa' && jQuery( '.nn_sepa_acc' ).css('display') == 'block' && jQuery('#novalnet_sepa_mandate_confirm').is(":checked") == false) {
            alert(jQuery('#nn_lang_mandate_confirm').val());
            return false;
        }
    });
    jQuery( ".shipping_container" ).on( "click", function() {
        jQuery("#novalnet_sepa_new_pin").prop("checked", false);
    });
    jQuery("#novalnet_sepa_fraud_pin").click(function(e) {
        e.stopPropagation();
        (jQuery("#novalnet_sepa_new_pin").prop("checked")) ? jQuery( "#novalnet_sepa_new_pin" ).prop("checked","checked") : jQuery( "#novalnet_sepa_new_pin" ).prop("checked",false) ;
    });
    jQuery("#novalnet_sepa_new_pin").click(function(e) {
        e.stopPropagation();
        (jQuery("#novalnet_sepa_new_pin").prop("checked")) ? jQuery( "#novalnet_sepa_new_pin" ).prop("checked","checked") : jQuery( "#novalnet_sepa_new_pin" ).prop("checked",false) ;
    });
    separefillformcall();
    jQuery('#novalnet_sepa_mandate_confirm').click(function(e) {
        e.stopPropagation();
        if(jQuery('#novalnet_sepa_mandate_confirm').prop('checked')) {
            jQuery( '#novalnet_sepa_mandate_confirm' ).prop("checked","checked");
            if (jQuery( '.nn_sepa_acc' ).css('display') == 'block') {
                sepaibanbiccall();
            }
        } else {
            jQuery( '#novalnet_sepa_mandate_confirm' ).prop("checked",false);
            sepa_mandate_unconfirm_process();
        }
        var selected_payment = (jQuery("input[name='payment']").attr('type') == 'hidden') ? jQuery("input[name='payment']").val() : jQuery("input[name='payment']:checked").val();
        if(jQuery('#novalnet_sepa_mandate_confirm').prop('checked')) {
            jQuery( '#novalnet_sepa_mandate_confirm' ).prop("checked","checked");
        } else {
            jQuery( '#novalnet_sepa_mandate_confirm' ).prop("checked",false);
            sepa_mandate_unconfirm_process();
        }

    });
    var parentClassName = (jQuery( ".nn_sepa_ref_details" ).parent().attr('class') == 'input') ? jQuery( ".nn_sepa_ref_details" ).parent().attr('class') : jQuery( ".nn_sepa_ref_details" ).parent().parent().attr('class') ;
    jQuery('#novalnet_sepa_new_acc').click(function() {
        jQuery('#novalnet_sepa_mandate_confirm').attr('checked', false);
        if (jQuery('.nn_sepa_acc').css('display') == 'none') {
           jQuery('.nn_sepa_acc').show();
           jQuery('.nn_sepa_ref_details' ).hide();
           jQuery('#novalnet_sepa_account_holder').removeAttr('placeholder');
           jQuery('#novalnet_sepa_iban').removeAttr('placeholder');
           jQuery('#novalnet_sepa_bic').removeAttr('placeholder');
           jQuery('#novalnet_sepachange_account' ).val('1');
           jQuery("div").find( ".nn_sepa_ref_details" ).closest('.'+parentClassName).hide();
           jQuery("div").find( ".nn_sepa_acc" ).closest('.'+parentClassName).show();
           jQuery('#novalnet_sepa_new_acc').html('<u><b>'+jQuery('#nn_lang_given_account').val()+'</b></u>');
        } else {
           jQuery("div").find( ".nn_sepa_ref_details" ).closest('.'+parentClassName).show();
           jQuery("div").find( ".nn_sepa_acc" ).closest('.'+parentClassName).hide();
           jQuery('#novalnet_sepa_mandate_confirm').attr('checked', false);
           jQuery('.nn_sepa_acc').hide();
           jQuery('.nn_sepa_ref_details').show();
           jQuery('#novalnet_sepachange_account').val('0');
           jQuery('#novalnet_sepa_new_acc').html('<u><b>'+jQuery('#nn_lang_new_account').val()+'</b></u>');
        }
    });
    if(jQuery( '#nn_sepa_shopping_type' ) != undefined && jQuery( '#nn_sepa_shopping_type' ).val() == 'ONECLICK' && jQuery('#novalnet_sepachange_account') != undefined && jQuery('#novalnet_sepachange_account').val() == 0  && jQuery('#payment_ref_details').val() != '') {
        jQuery("div").find( ".nn_sepa_acc" ).closest('.'+parentClassName ).hide();
        jQuery( '.nn_sepa_ref_details' ).show();
        if(jQuery('.nn_sepa_acc').length > 0) {
            jQuery('.nn_sepa_acc').hide();
        }
        jQuery('#novalnet_sepa_new_acc').html('<u><b>'+jQuery('#nn_lang_new_account').val()+'</b></u>');
    } else {
        jQuery('#payment_ref_details').parent().hide();
        if(jQuery('.nn_sepa_ref_details').length > 0) {
            jQuery("div").find( ".nn_sepa_ref_details" ).closest('.'+parentClassName).hide();
            jQuery("div").find( ".nn_sepa_acc" ).closest('.'+parentClassName).show();
        }
        jQuery('#novalnet_sepa_new_acc').html('<u><b>'+jQuery('#nn_lang_given_account').val()+'</b></u>');
    }
    jQuery( '#novalnet_sepa_iban, #novalnet_sepa_bic, #novalnet_sepa_bank_country, #novalnet_sepa_account_holder' ).on( 'change', function() {
        sepa_mandate_unconfirm_process();
    });
}

/**
 * To unset the sepa form value
 */
function sepa_mandate_unconfirm_process() {
    jQuery('#nn_sepa_hash').val('');
    jQuery('#novalnet_sepa_iban_span').html('');
    jQuery('#novalnet_sepa_bic_span').html('');
    jQuery('#novalnet_sepa_mandate_confirm').attr('checked', false);
    jQuery('novalnet_sepa_mandate_confirm').attr("disabled", false);
}

/**
 * To validate the sepa account holder field
 *
 * @param event
 * @param allowSpace
 * return boolean
 */
function account_holder_validate(event, allowSpace) {
   var keycode = ('which' in event) ? event.which : event.keyCode;
   if (allowSpace == true) { var reg = /[^0-9\[\]\/\\#,+@!^()§$~%'"=:;<>{}\_\|*?`]/g  };
   return (reg.test(String.fromCharCode(keycode)) || keycode == 0 || keycode == 45 ||keycode == 46 || keycode == 8 || (event.ctrlKey == true && keycode == 114) || ( allowSpace == true && keycode == 32))? true : false;
}

/**
 * To validate the input value in sepa form
 *
 * @param input_val
 * return boolean
 */
function validateSpace(input_val) {
    var input = jQuery.trim(input_val.replace(/\b \b/g, ''));
    return jQuery.trim(input.replace(/\s{2,}/g, ''));
}

/**
 *  To remove the special characters in sepa form
 *
 * @param input_val
 * return boolean
 */
function removeUnwantedSpecialCharsSepa(value, req)
{
    if (value != 'undefined' || value != '') {
         value.replace(/^\s+|\s+$/g, '');
        if (req != 'undefined' && req == 'account_holder') {
            return value.replace(/[\/\\|\]\[|#@,+()`'$~%":;*?<>!^{}=_]/g, '');
        }else {
            return value.replace(/[\/\\|\]\[|#@,+()`'$~%.":;*?<>!^{}=_-]/g, '');
        }
    }
}

/**
 * To validate the special characters
 *
 * @param input_val
 * return boolean
 */
function validateSpecialChars(input_val) {
    var re = /[\/\\#,+!^()$~%.":*?<>{}]/g;
    return re.test(input_val);
}

/**
 * To generate the sepa hash
 */
function sepahashrequestcall() {
    var bank_country = "";var account_holder = "";var account_no = "";var nn_sepa_iban = "";var nn_sepa_bic = "";
    var iban = "";var bic = "";var bank_code = "";var nn_sepa_uniqueid = "";
    var nn_vendor    = "";var nn_auth_code = "";var mandate_confirm = 0; var remote_ip = "";
    bank_country     = jQuery('#novalnet_sepa_bank_country').length ? jQuery('#novalnet_sepa_bank_country').val() : '';
    account_holder   = jQuery('#novalnet_sepa_account_holder').length ? jQuery.trim(jQuery('#novalnet_sepa_account_holder').val()) : '';
    iban             = jQuery('#novalnet_sepa_iban').length ? validateSpace(jQuery('#novalnet_sepa_iban').val()) : '';
    bic              = jQuery('#novalnet_sepa_bic').length ? validateSpace(jQuery('#novalnet_sepa_bic').val()) : '';
    nn_sepa_iban     = jQuery('#nn_sepa_iban').length ?validateSpace(jQuery('#nn_sepa_iban').val()): '';
    nn_sepa_bic      = jQuery('#nn_sepa_bic').length ? validateSpace(jQuery('#nn_sepa_bic').val()) : '';
    nn_vendor        = jQuery('#nn_vendor').length ? jQuery('#nn_vendor').val() : '';
    nn_auth_code     = jQuery('#nn_auth_code').length ? jQuery('#nn_auth_code').val() : '';
    nn_sepa_uniqueid =  jQuery('#nn_sepa_uniqueid').length ? jQuery('#nn_sepa_uniqueid').val() : '';
    remote_ip        = jQuery('#nn_remote_ip').length ? jQuery('#nn_remote_ip').val() : '';
    iban = iban.replace(/[^a-z0-9]+/gi, '');
    bic = bic.replace(/[^a-z0-9]+/gi, '');
    if(validateSpecialChars(iban) || validateSpecialChars(bic) || account_holder == '' || iban == '' || nn_sepa_uniqueid == '') {
        alert(jQuery('#nn_lang_valid_account_details').val()); sepa_mandate_unconfirm_process(); return false;
    }
    if (bank_country == '') {
        alert(jQuery('#nn_sepa_country').val()); sepa_mandate_unconfirm_process(); return false;
    }
    if(bank_country != 'DE' && bic == '') {
        alert(jQuery('#nn_lang_valid_account_details').val()); sepa_mandate_unconfirm_process(); return false;
    } else if(bank_country == 'DE' && !isNaN(iban) && bic == '') {
        alert(jQuery('#nn_lang_valid_account_details').val()); sepa_mandate_unconfirm_process(); return false;
    }
    if(bank_country == 'DE' && (bic == ''|| !isNaN(bic)) && isNaN(iban)) {
        bic = '123456';
    }
    if(!isNaN(iban) && !isNaN(bic))  {
        account_no = iban;
        bank_code = bic;
        iban = bic = '';
    }
    if(nn_sepa_iban != '' && nn_sepa_bic != '')  {
        iban = nn_sepa_iban;
        bic = nn_sepa_bic;
    }
    account_holder = removeUnwantedSpecialCharsSepa(account_holder,'account_holder');
    account_no = removeUnwantedSpecialCharsSepa(account_no,'');
    bank_code = removeUnwantedSpecialCharsSepa(bank_code,'');
    var nnurl_val = {
      'account_holder'    : account_holder,
      'bank_account'      : account_no,
      'bank_code'         : bank_code,
      'vendor_id'         : nn_vendor,
      'vendor_authcode'   : nn_auth_code,
      'bank_country'      : bank_country,
      'unique_id'         : nn_sepa_uniqueid,
      'sepa_data_approved': '1',
      'mandate_data_req'  : '1',
      'iban'              : iban,
      'bic'               : bic,
      'remote_ip'         : remote_ip,
    };
    jQuery('#nn_loader').css('display', 'block');
    jQuery('#nn_loader').attr('tabIndex',-1).focus();
    domainRequestSepa(nnurl_val, 'hash_call');
}

/**
 * To generate the iban and bic value
 */
function sepaibanbiccall() {
    var bank_country = "";var account_holder = "";var account_no = "";
    var bank_code    = "";var nn_sepa_uniqueid = "";
    var nn_vendor    = "";var nn_auth_code = ""; var remote_ip = '';
    bank_country     = jQuery('#novalnet_sepa_bank_country').length ? jQuery('#novalnet_sepa_bank_country').val() : '';
    account_holder   = jQuery('#novalnet_sepa_account_holder').length ? jQuery.trim(jQuery('#novalnet_sepa_account_holder').val()) : '';
    account_no       = jQuery('#novalnet_sepa_iban').length ? validateSpace(jQuery('#novalnet_sepa_iban').val()) : '';
    bank_code        = jQuery('#novalnet_sepa_bic').length ? validateSpace(jQuery('#novalnet_sepa_bic').val()) : '';
    nn_vendor        = jQuery('#nn_vendor').length ? jQuery('#nn_vendor').val() :'';
    nn_auth_code     = jQuery('#nn_auth_code').length ? jQuery('#nn_auth_code').val() : '';
    nn_sepa_uniqueid = jQuery('#nn_sepa_uniqueid').length ? jQuery('#nn_sepa_uniqueid').val() : '';
    remote_ip        = jQuery('#nn_remote_ip').length ? jQuery('#nn_remote_ip').val() : '';
    jQuery('#nn_sepa_iban').val('');
    jQuery('#nn_sepa_bic').val('');
    account_no = account_no.replace(/[^a-z0-9]+/gi, '');
    bank_code = bank_code.replace(/[^a-z0-9]+/gi, '');
    if(isNaN(account_no) && isNaN(bank_code))  {
        jQuery('#novalnet_sepa_iban_span').html('');
        jQuery('#novalnet_sepa_bic_span').html('');
        sepahashrequestcall();
        return false;
    }
    if(bank_code == '' && isNaN(account_no)) {
        sepahashrequestcall();
        return false;
    }
    if (isNaN(bank_code) || isNaN(account_no)) {
        alert(jQuery('#nn_lang_valid_account_details').val()); sepa_mandate_unconfirm_process(); return false;
    }
    if(bank_country == '') {
      alert(jQuery('#nn_sepa_country').val()); sepa_mandate_unconfirm_process(); return false;
    }
    if( account_holder == '' || account_no == '' || bank_code == '' || nn_sepa_uniqueid == '') {
        alert(jQuery('#nn_lang_valid_account_details').val()); sepa_mandate_unconfirm_process(); return false;
    }
    account_holder = removeUnwantedSpecialCharsSepa(account_holder,'account_holder');
    account_no = removeUnwantedSpecialCharsSepa(account_no,'');
    bank_code = removeUnwantedSpecialCharsSepa(bank_code,'');
    var nnurl_val = {
      'account_holder' : account_holder,
      'bank_account'   : account_no,
      'bank_code'      : bank_code,
      'vendor_id'      : nn_vendor,
      'vendor_authcode': nn_auth_code,
      'bank_country'   : bank_country,
      'unique_id'      : nn_sepa_uniqueid,
      'get_iban_bic'   : '1',
      'remote_ip'      : remote_ip,
    };
    jQuery('#nn_loader').css('display', 'block');
    jQuery('#nn_loader').attr('tabIndex',-1).focus();
    domainRequestSepa(nnurl_val, 'iban_bic');
}

/**
 * AJAX call for refill sepa form elements
 */
function separefillformcall() {
    var refillpanhash = '';var remote_ip = '';
    refillpanhash =  jQuery('#nn_sepa_input_panhash').length ?jQuery('#nn_sepa_input_panhash').val() : '';
    if(refillpanhash == '' || refillpanhash == undefined) {
        return false;
    }
    var nn_vendor = ""; var nn_auth_code = ""; var nn_uniqueid = "";
    nn_vendor     = jQuery('#nn_vendor').length ? jQuery('#nn_vendor').val() : '';
    nn_auth_code  = jQuery('#nn_auth_code').length ? jQuery('#nn_auth_code').val() : '';
    remote_ip        = jQuery('#nn_remote_ip').length ? jQuery('#nn_remote_ip').val() : '';
    nn_uniqueid   = jQuery('#nn_sepa_uniqueid').length ? jQuery('#nn_sepa_uniqueid').val() : '';
    var nnurl_val = {
        'vendor_id'          : nn_vendor,
        'vendor_authcode'    : nn_auth_code,
        'unique_id'          : nn_uniqueid,
        'sepa_data_approved' : '1',
        'mandate_data_req'   : '1',
        'sepa_hash'          : refillpanhash,
        'remote_ip'          : remote_ip,
    };
    jQuery('#nn_loader').css('display', 'block');
    domainRequestSepa(nnurl_val, 'sepa_refill');
}

/**
 * To validate the iban bic field
 *
 * @param event
 * @param allowSpace
 * return boolean
 */
function ibanbic_validate(event, allowSpace) {
    var keycode = ('which' in event) ? event.which : event.keyCode;
    var reg = /^(?:[A-Za-z0-9]+$)/; 
    if(allowSpace == true)
      var reg = /^(?:[A-Za-z0-9&\s]+$)/;
    if(event.target.id == 'novalnet_sepa_account_holder')
      var reg = /^(?:[A-Za-z-&.\s]+$)/;
    return (reg.test(String.fromCharCode(keycode)) || keycode == 0 || keycode == 8 || (event.ctrlKey == true && keycode == 114) || ( allowSpace == true && keycode == 32))? true : false;
}

/**
 * To performing refil for sepa form
 */
function sepaFormRefill(response) {
    var hash_stringvalue = response.hash_string,
        hash_string = hash_stringvalue.split('&'),
        acc_hold = hash_stringvalue.match('account_holder=(.*)&bank_code'),
            account_holder='',
            array_result = {},
            data_length = hash_string.length;

    var account_holder = ( null != acc_hold && undefined != acc_hold[1] ) ? acc_hold[1] : '';
    for ( var i=0; i < data_length; i++ ) {
        var hash_result_val = hash_string[i].split( '=' );
        array_result[ hash_result_val[0] ] = hash_result_val[1];
    }
    try{
        var holder = decodeURIComponent(escape(account_holder));
    }catch(e) {
        var holder = account_holder;
    }
    jQuery('#novalnet_sepa_account_holder' ).val( holder );
    jQuery('#novalnet_sepa_bank_country' ).val( array_result.bank_country );
    jQuery('#novalnet_sepa_iban' ).val( array_result.iban );
    if ( array_result.bic != '123456' )
        jQuery('#novalnet_sepa_bic' ).val( array_result.bic );
        jQuery('#novalnet_sepa_refill_hash' ).val('');
        jQuery('#nn_loader').css('display', 'none');
}

/**
 * To assign iban bic value in sepa form
 *
 * @param data
 * return boolean
 */
function ibanCallAssign(data) {
    if (data.IBAN == ''|| data.BIC == '') {
      jQuery('#nn_loader').css('display', 'none');
      alert(jQuery('#nn_lang_valid_account_details').val());
      sepa_mandate_unconfirm_process(); return false;
    }
    jQuery('#nn_sepa_iban').val(data.IBAN);
    jQuery('#nn_sepa_bic').val(data.BIC);
    if (data.IBAN != '' && data.BIC != '') {
      jQuery('#novalnet_sepa_iban_span').html('<b>IBAN:</b> '+data.IBAN);
    }
    if (data.BIC != '') {
      jQuery('#novalnet_sepa_bic_span').html('<b>BIC:</b> '+data.BIC);
    }
    sepahashrequestcall();
    return true;
}

/**
 * To performing the server request call
 */
function domainRequestSepa(nnurl_val, ajax_call) {
    var nnurl = 'https://payport.novalnet.de/sepa_iban';
    if(nnurl_val == '') {return false;}
    if ('XDomainRequest' in window && window.XDomainRequest !== null) {
        var xdr = new XDomainRequest(); //Use Microsoft XDR
        xdr.open('POST', nnurl);
        xdr.onload = function () {
            var data = jQuery.parseJSON(this.responseText);
            if(data.hash_result == 'success') {
                if(ajax_call == 'hash_call') {
                    jQuery('#nn_sepa_hash').val(data.sepa_hash);
                    jQuery('#nn_loader').css('display', 'none');
                } else if(ajax_call == 'iban_bic') {
                    ibanCallAssign(data);
                } else {
                    sepaFormRefill(data);
                }
            } else {
                jQuery('#nn_loader').css('display', 'none');
                alert(data.hash_result);
                return false;
            }
        };
        xdr.onerror = function() { return true; };
        xdr.send(jQuery.param(nnurl_val));
    } else {
        var xmlhttp=(window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
        xmlhttp.onreadystatechange=function() {
            if (xmlhttp.readyState==4 && xmlhttp.status==200) {
                var data = JSON.parse(xmlhttp.responseText);
                if(data.hash_result == 'success') {
                    if(ajax_call == 'hash_call') {
                        jQuery('#nn_sepa_hash').val(data.sepa_hash);
                        jQuery('#nn_loader').css('display', 'none');
                    } else if(ajax_call == 'iban_bic') {
                        ibanCallAssign(data);
                    } else {
                        sepaFormRefill(data);
                    }
                } else {
                    jQuery('#nn_loader').css('display', 'none');
                    alert(data.hash_result);
                    sepa_mandate_unconfirm_process();
                    return false;
                }
            }
        }
        xmlhttp.open("POST", nnurl, true);
        xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
        xmlhttp.send(jQuery.param(nnurl_val));
    }
}
