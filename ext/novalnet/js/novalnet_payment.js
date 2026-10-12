/**
 * Novalnet payment module
 *
 * This script loads the form and gets the server's pan hash
 *
 * @author    Novalnet AG
 * @copyright Copyright (c) Novalnet
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link      https://www.novalnet.de
 *
 * Script: novalnet_payment.min.js
 */
window.onload = function () {
    jQuery('input[name="payment"]').click(
        function () {
            if (jQuery(this).val() == 'novalnet_cc') {
                NovalnetUtility.setCreditCardFormHeight();
                if (document.querySelector('#novalnet_iframe')) {
                    document.querySelector('#novalnet_iframe').parentElement.parentElement.classList.remove('col-md-8');
                    document.querySelector('#novalnet_iframe').parentElement.parentElement.classList.add('col-md-12');
                }
            } else if (jQuery(this).val() == 'novalnet_instalment_sepa') {
                var payment = jQuery(this).val();
                if ($('.' + payment + '_saved_acc:checked') != undefined && $('.' + payment + '_saved_acc:checked') != null && $('.' + payment + '_saved_acc:checked').length == 1 && $('.' + payment + '_saved_acc:checked').val() != 'new') {
                    document.querySelector('#' + payment + '_dob').parentElement.parentElement.style.marginTop = '-15px';
                    document.querySelector('.novalnet_instalment_text').style.marginTop = '-20px';
                }
            }
        }
    );

    novalnet_payment = {
        /**
         * Initiate Credit card Iframe process 
         */
        init: function () {
            novalnet_payment.loadIframe();
            novalnet_payment.addListener();
        },
        loadIframe: function () {
            var cc_form_details = (jQuery('#nn_cc_iframe_data').length > 0 && jQuery('#nn_cc_iframe_data').val()) ? JSON.parse(jQuery('#nn_cc_iframe_data').val()) : null;

            if (typeof cc_form_details === 'undefined' || cc_form_details === null) {
                return; // stop execution if cc_form_details doesn't exist.
            }

            NovalnetUtility.setClientKey(cc_form_details.clientKey);
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
                        if (undefined != data['error_message']) {
                            alert(data['error_message']);
                            novalnet_payment.enableSubmitButton();
                            return false;
                        }
                    },
                    on_show_overlay: function (data) {
                        document.getElementById('novalnet_iframe').classList.add("novalnet-challenge-window-overlay");
                    },
                    on_hide_overlay: function (data) {
                        document.getElementById("novalnet_iframe").classList.remove("novalnet-challenge-window-overlay");
                    }
                },
                iframe: cc_form_details.iframe,
                customer: cc_form_details.customer,
                transaction: cc_form_details.transaction,
                custom: cc_form_details.custom
            }
            NovalnetUtility.createCreditCardForm(configurationObject);
        },
        addListener: function () {
            // bic hide/show
            ['novalnet_sepa', 'novalnet_guarantee_sepa', 'novalnet_instalment_sepa'].forEach(
                (payment) => {
                    const iban = document.getElementById(payment + '_iban');
                    if (iban !== undefined && iban !== null) {
                        ['click', 'keydown', 'keyup'].forEach(
                            (evt) => {
                                iban.addEventListener(
                                    evt, (event) => {
                                        var result = event.target.value;
                                        if (result != undefined && result != null) {
                                            result = result.toUpperCase();
                                            if (result.match(/(?:CH|MC|SM|GB|GI)/)) {
                                                document.getElementById(payment + '_bic_field').style.display = "block";
                                            } else {
                                                document.getElementById(payment + '_bic_field').style.display = "none";
                                            }
                                        }
                                    }
                                );
                            }
                        );
                    }
                }
            );

            $('.token_delete').click(
                function () {
                    language = document.getElementById('languageID').value;
                    message = (language.length && language == 'de') ? 'Sie möchten das Token löschen?' : 'You want to delete the token?';
                    if (window.confirm(message)) {
                        var data_to_send = { 'action': 'delete_token', 'id': this.id };
                        $.ajax(
                            {
                                type: 'POST',
                                url: 'novalnet_token_delete.php',
                                data: data_to_send,
                                success: function (data) {
                                    location.reload();
                                }
                            }
                        );
                    }
                }
            );

            $('.about_mandate').click(
                function () {
                    $(this).next().next().toggle('slow');
                }
            );

            // token payment form hide/show
            ['novalnet_cc', 'novalnet_sepa', 'novalnet_guarantee_sepa', 'novalnet_instalment_sepa', 'novalnet_direct_debit_ach'].forEach(
                (payment) => {
                    $('.' + payment + '_saved_acc').click(
                        function () {
                            if ($(this).is(':checked')) {
                                $('.' + payment + '_saved_acc').not(this).prop('checked', false);
                            }
                            var payment_name = $(this).attr('class').split("_");
                            payment_name = payment_name[0] + '_' + payment_name[1];

                            if ($(this).attr('id').includes('_new')) {
                                if (payment == 'novalnet_instalment_sepa' || payment == 'novalnet_guarantee_sepa') {
                                    if (document.querySelector('#' + payment + '_dob')) {
                                        document.querySelector('#' + payment + '_dob').parentElement.parentElement.style.marginTop = '0px';
                                    }

                                    if (document.querySelector('.novalnet_instalment_text')) {
                                        document.querySelector('.novalnet_instalment_text').style.marginTop = '0px';
                                    }
                                } else if (payment == 'novalnet_cc') {
                                    NovalnetUtility.setCreditCardFormHeight();
                                }
                                jQuery('#' + payment + '_payment_form').show();
                                jQuery('#' + payment + '_save_card').show();
                            } else {
                                jQuery('#' + payment + '_payment_form').hide();
                                jQuery('#' + payment + '_save_card').hide();
                                document.getElementById(payment + '_iban').value = '';
                                document.getElementById(payment + '_bic_field').style.display = "none";
                                if (payment == 'novalnet_instalment_sepa' || payment == 'novalnet_guarantee_sepa') {
                                    document.querySelector('#' + payment + '_dob').parentElement.parentElement.style.marginTop = '-15px';
                                    document.querySelector('.novalnet_instalment_text').style.marginTop = '-20px';
                                }
                            }
                        }
                    );
                }
            );

            // hide/show instalment tables
            ['novalnet_instalment_sepa', 'novalnet_instalment_invoice'].forEach(
                (payment) => {
                    $('#' + payment + '_cycles').change(
                        function (event) {
                            const duration = event.target.value;
                            const elements = document.querySelectorAll('.' + payment + '_detail');
                            elements.forEach(
                                function (instalmentElement) {
                                    if (instalmentElement.dataset.duration === duration) {
                                        instalmentElement.hidden = false;
                                    } else {
                                        instalmentElement.hidden = 'hidden';
                                    }
                                }
                            );
                        }
                    );
                    $('#' + payment + '_info').click(
                        function (event) {
                            $('#' + payment + '_summary').toggle();
                        }
                    );
                }
            );
        },
        disbableSubmitButton: function () {
            document.querySelector('#checkout_payment input[type="submit"]').setAttribute('disabled', 'disabled');
        },
        enableSubmitButton: function () {
            document.querySelector('#checkout_payment input[type="submit"]').removeAttribute('disabled');
        },
    };

    jQuery(document).ready(
        function () {
            novalnet_payment.init();
            if (jQuery('input[name="payment"]') != undefined) {
                // active the payment form if the payment ID is checked
                if (jQuery('input[name="payment"]:checked').length == 1) {
                    var class_name = jQuery('input[name="payment"]:checked').val();
                    setTimeout(
                        function () {
                            $('.' + class_name).addClass('active');
                        }, 500
                    );
                }

                if ((jQuery('input[name="payment"]').length == 1 && jQuery('input[name="payment"]').val() == 'novalnet_cc') || (jQuery('input[name="payment"]').length > 1 && jQuery('input[name="payment"]:checked').val() == 'novalnet_cc')) {
                    if (document.querySelector('#novalnet_iframe')) {
                        document.querySelector('#novalnet_iframe').parentElement.parentElement.classList.remove('col-md-8');
                        document.querySelector('#novalnet_iframe').parentElement.parentElement.classList.add('col-md-12');
                    }
                }
            }
        }
    );
};

document.getElementById('checkout_payment').addEventListener(
    "submit", function (event) {
        if (jQuery('input[name="payment"]') != undefined) {
            var checkedPayment = (jQuery('input[name="payment"]').length == 1) ? jQuery('input[name="payment"]').val() : jQuery('input[name="payment"]:checked').val();
            if (checkedPayment == 'novalnet_cc') {
                if (jQuery('#novalnet_iframe').is(":visible") && jQuery('#nn_pan_hash').val() != undefined && jQuery('#nn_pan_hash').val() == '') {
                    novalnet_payment.disbableSubmitButton();
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    if (document.querySelector('input[name="payment"]:checked')) {
                        document.querySelector('input[name="payment"]:checked').scrollIntoView();
                    }
                    NovalnetUtility.getPanHash();
                }
            } else if (['novalnet_guarantee_sepa', 'novalnet_instalment_sepa', 'novalnet_guarantee_invoice', 'novalnet_instalment_invoice'].includes(checkedPayment)) {
                if (jQuery('#' + checkedPayment + '_dob').val() != undefined && (jQuery('#' + checkedPayment + '_dob').val() == '' || !NovalnetUtility.validateDateFormat(jQuery('#' + checkedPayment + '_dob').val()))) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    alert($('#guarantee_dob_error').val());
                }
            }
        }
    }
);
