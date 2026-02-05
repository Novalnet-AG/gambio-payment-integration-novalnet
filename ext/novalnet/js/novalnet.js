/**
 * Novalnet payment module
 *
 * This script is used for common utility functionality
 *
 * @author    Novalnet AG
 * @copyright Copyright (c) Novalnet
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link      https://www.novalnet.de
 *
 * Script: novalnet.js
 */

function novalnet_load()
{
    var e = getUrlVars();
    if (e.module && e.action && "edit" == e.action) {
        var n = e.module.toUpperCase(),
            o = e.module;
        
        if (['novalnet_guarantee_invoice', 'novalnet_guarantee_sepa', 'novalnet_instalment_invoice', 'novalnet_instalment_sepa'].includes(o)) {
            var basic_req = jQuery('input[name="configuration[configuration/MODULE_PAYMENT_' + n + '_BASIC_REQ]"]');
            if (basic_req) {
                basic_req.css('display', 'none');
            }
        }

        // Replace payment action field into select fields for CC and SEPA payments
        if (['novalnet_applepay', 'novalnet_googlepay', 'novalnet_cc', 'novalnet_sepa'].includes(o)) {
            var payment_action = '';
            if (jQuery('#lang_code').val() == 'EN') {
                payment_action = {
                    ''                  : '--Select--',
                    'authorize'         : 'Authorize',
                    'capture'           : 'Capture',
                    'zero_amount'       : 'Authorize with zero amount',
                };
            } else if (jQuery('#lang_code').val() == 'DE') {
                payment_action = {
                    ''                  : '--Wählen Sie --',
                    'authorize'         : 'Zahlung autorisieren',
                    'capture'           : 'Zahlung einziehen',
                    'zero_amount'       : 'Mit Nullbetrag autorisieren',
                };
            }
            var action = jQuery('input[name="configuration[configuration/MODULE_PAYMENT_' + n + '_AUTHENTICATE]"]');
            var selected_action = action.val();
            if ($('#payment_action').length === 0) {
                jQuery(action).replaceWith('<select id="payment_action" name= "configuration[configuration/MODULE_PAYMENT_' + n + '_AUTHENTICATE]" ></select>');
                appendOptions(payment_action, selected_action, 'payment_action');
            }
            var limitElem  =  $('input[name="configuration[configuration/MODULE_PAYMENT_' + n + '_MANUAL_CHECK_LIMIT]"');
            limitElem.hide();
            $('#payment_action').click(
                function () {
                    if ($('#payment_action').find(":selected").val() == 'authorize') {
                        limitElem.show();
                    } else {
                        limitElem.hide();
                    }
                }
            );
        } else {
            $('input:checkbox[name="configuration[configuration/MODULE_PAYMENT_' + n + '_AUTHENTICATE]"]').parent().click(
                function () {
                    var switcher = $('input:checkbox[name="configuration[configuration/MODULE_PAYMENT_' + n + '_AUTHENTICATE]"]').parent();
                    var limitElem      =  $('input[name="configuration[configuration/MODULE_PAYMENT_' + n + '_MANUAL_CHECK_LIMIT]"');
                    if (switcher[0].classList.contains('checked')) {
                        limitElem.show()
                    } else {
                        limitElem.hide();
                    }
                }
            );
        }
        
        $('input[name="configuration[configuration/MODULE_PAYMENT_'+ n +'_ENDCUSTOMER_INFO]"]').keyup(
            function () {
                this.value = this.value.replace(/<(.|\n)*?>/g,'');
            }
        );
        $('input[name="configuration[configuration/MODULE_PAYMENT_'+ n +'_VISIBILITY_BY_AMOUNT]"]').keyup(
            function () {
                this.value = this.value.replace(/[^0-9]/g,'');
            }
        );
        $('input[name="configuration[configuration/MODULE_PAYMENT_'+ n +'_MANUAL_CHECK_LIMIT]"]').keyup(
            function () {
                this.value = this.value.replace(/[^0-9]/g,'');
            }
        );
        $('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_INVOICE_DUE_DATE]"]').blur(
            function () {
                if ($(this).val()  < 7) {
                    $(this).val('');
                }
            }
        );
        $('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_PREPAYMENT_DUE_DATE]"]').blur(
            function () {
                if (($(this).val()  < 7) || ($(this).val() > 28)) {
                    $(this).val('');
                }
            }
        );
        $('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_SEPA_DUE_DATE]"]').blur(
            function () {
                if (($(this).val()  < 3) || ($(this).val() > 14)) {
                    $(this).val('');
                }
            }
        );

        if ($('#' + o + '_auth').val() == 'false') {
            $('input[name="configuration[configuration/MODULE_PAYMENT_' + n + '_MANUAL_CHECK_LIMIT]').hide();
        }
        
        // To give multiselect option in instalment cycles dropdown in shop backend
        var cd = jQuery('input[name="configuration[configuration/MODULE_PAYMENT_'+ n +'_CYCLE]"]');
        if (o == 'novalnet_instalment_sepa' || o == 'novalnet_instalment_invoice') {
            jQuery(cd).replaceWith('<select id="nn_cycles" name= "configuration[configuration/MODULE_PAYMENT_'+ n +'_CYCLE][]" multiple="multiple" style="width:110%;" ><option value="2" selected="selected">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option><option value="11">11</option><option value="12">12</option><option value="15">15</option><option value="18">18</option><option value="21">21</option><option value="24">24</option><option value="36">36</option></select>');
            var dc = jQuery('#' + o + '_cycle').val();
            var s = dc.split("|");
            jQuery(document).ready(
                function () {
                    jQuery("#nn_cycles").select2(
                        {
                            closeOnSelect : false,
                            placeholder : "Instalment cycles",
                            allowHtml: true,
                            allowClear: true,
                            tags: true
                        }
                    );
                    jQuery('#nn_cycles').val(s).trigger('change');
                }
            );
        }
        
        if (o == 'novalnet_applepay' || o == 'novalnet_googlepay') {
            // To validate applepay and googlepay button height
            jQuery('input[name="configuration[configuration/MODULE_PAYMENT_'+ n +'_BUTTON_HEIGHT]"]').on(
                'keydown',function (e) {
                    if (e.keyCode == 8 || e.keyCode == 46 || e.keyCode == 16 || e.keyCode == 20) {
                        return true;
                    }
                    if ((e.keyCode >= 48 && e.keyCode <= 57) || (e.keyCode >= 96 && e.keyCode <= 105)) {
                        var num = jQuery('input[name="configuration[configuration/MODULE_PAYMENT_'+ n +'_BUTTON_HEIGHT]"]').val();
                        if (num == '' && e.key >= '3' && e.key <= '6') {
                            return true;
                        } else if (num >= 3 && num <= 6) {
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
                }
            );
            
            // To validate applepay button corner radius
            jQuery('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_CORNER_RADIUS]"]').on(
                'keydown',function (e) {
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
                }
            );
            
            var button_display = jQuery('input[name="configuration[configuration/MODULE_PAYMENT_'+ n +'_BUTTON_DISPLAY]"]');
            
            var nn_googlepay  = JSON.parse(jQuery('#nn_googlepay_display').val());
            var nn_applepay = JSON.parse(jQuery('#nn_applepay_display').val());

            // Replace GooglePay button type field into select fields.
            var googlepay_button_type = {
                'plain'     :  nn_googlepay.plain,
                'buy'       :  nn_googlepay.buy,
                'donate'    :  nn_googlepay.donate,
                'book'      :  nn_googlepay.book,
                'checkout'     :  nn_googlepay.checkout,
                'order'     :  nn_googlepay.order,
                'subscribe' :  nn_googlepay.subscribe,
                'pay'        :  nn_googlepay.pay,
            };
            var button_type = jQuery('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_TYPE]"]');
            var selected_type = button_type.val();
            jQuery(button_type).replaceWith('<select id="googlepay_button_type" name= "configuration[configuration/MODULE_PAYMENT_NOVALNET_GOOGLEPAY_BUTTON_TYPE]" ></select>');
            appendOptions(googlepay_button_type, selected_type, 'googlepay_button_type');

            //  Replace ApplePay button type field into select fields.
            var applepay_button_type = {
                'plain'     : nn_applepay.plain,
                'buy'       : nn_applepay.buy,
                'donate'    : nn_applepay.donate,
                'book'      : nn_applepay.book,
                'check-out' : nn_applepay.checkout,
                'order'     : nn_applepay.order,
                'subscribe' : nn_applepay.subscribe,
                'pay'        : nn_applepay.pay,
                'contribute' : nn_applepay.contribute,
                'tip'       : nn_applepay.tip,
                'rent'      : nn_applepay.rent,
                'reload'    : nn_applepay.reload,
                'support'   : nn_applepay.support,
            };
            var button_type = jQuery('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_TYPE]"]');
            var selected_type = button_type.val();
            jQuery(button_type).replaceWith('<select id="applepay_button_type" name= "configuration[configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_TYPE]" ></select>');
            appendOptions(applepay_button_type, selected_type, 'applepay_button_type');

            // Replace ApplePay button theme field into select fields.
            var applepay_button_theme = {
                'black'                : nn_applepay.dark,
                'white'                : nn_applepay.light,
                'white-outline'        : nn_applepay.lightoutline,
            };
            var button_theme = jQuery('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_THEME]"]');
            var selected_theme = button_theme.val();
            jQuery(button_theme).replaceWith('<select id="applepay_button_theme" name= "configuration[configuration/MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_THEME]" ></select>');
            appendOptions(applepay_button_theme, selected_theme, 'applepay_button_theme');

            // To give multiselect option in applepay and googlepay button display pages
            jQuery(button_display).replaceWith('<select id="nn_pages" name= "configuration[configuration/MODULE_PAYMENT_'+ n +'_BUTTON_DISPLAY][]" multiple="multiple" style="width:50%;" ><option value="shopping cart page" selected="selected">'+nn_googlepay.cartpage+'</option> <option value="product page">'+nn_googlepay.productpage+'</option><option value="checkout page">'+nn_googlepay.checkoutpage+'</option></select>');

            jQuery(button_display).replaceWith('<select id="nn_pages" name= "configuration[configuration/MODULE_PAYMENT_'+ n +'_BUTTON_DISPLAY][]" multiple="multiple" style="width:50%;" ><option value="shopping cart page" selected="selected">'+nn_applepay.cartpage+'</option> <option value="product page">'+nn_applepay.productpage+'</option><option value="checkout page">'+nn_applepay.checkoutpage+'</option></select>');

            var button_display_page = (o === 'novalnet_googlepay')
                ? jQuery('#nn_googlepay_button_display_page').val()
                : jQuery('#nn_applepay_button_display_page').val();

            var selectedValues = button_display_page.split("|");
            jQuery(document).ready(
                function () {
                    jQuery("#nn_pages").select2(
                        {
                            closeOnSelect : false,
                            placeholder : (o == 'novalnet_googlepay') ? nn_googlepay.placeholder_text : nn_applepay.placeholder_text,
                            allowHtml: true,
                            allowClear: true,
                            tags: true
                        }
                    );
                      jQuery('#nn_pages').val(selectedValues).trigger('change');
                }
            );
        }
    }
}

function appendOptions(optionData , selectedValue, elemID)
{
    if ($('#'+elemID+' > option').length == 0) {
        jQuery.each(
            optionData, function (value,text) {
                jQuery('#' + elemID).append(
                    jQuery(
                        '<option>', {
                            value: jQuery.trim(value),
                            text: jQuery.trim(text)
                        }
                    )
                );
                if (selectedValue != undefined && selectedValue == value) {
                    jQuery('#' + elemID).val(value);
                }
            }
        );
    }
}

function getUrlVars()
{
    var e = {};
    return (
        window.location.href.replace(
            /[?&]+([^=&]+)=([^&]*)/gi, function (n, o, t) {
                e[o] = t;
            }
        ),
        e
    );
}

window.addEventListener ? window.addEventListener("load", novalnet_load) : window.attachEvent && window.attachEvent("onload", novalnet_load);
