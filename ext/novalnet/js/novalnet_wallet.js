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
 * Script: novalnet_wallet.min.js
 */
document.addEventListener('DOMContentLoaded', function () {
    ['novalnet_googlepay', 'novalnet_applepay'].forEach((payment) => {
        const button = document.getElementById(`${payment}_wall_button`);
        if (button) {
            const minAmountAttr = (payment === 'novalnet_googlepay') ? 'data-googlepayMinAmount' : 'data-applepayMinAmount';
            const min_amount = button.getAttribute(minAmountAttr);

            if (min_amount !== null) {
                let currentPriceText = document.querySelector('form.product-info div.current-price-container')?.textContent || '';

                let extractedPrice = currentPriceText
                    .replace(/[0-9,.]+ (EUR|€) pro .*/, '')
                    .replace(/.*?([0-9,.]+ (EUR|€)(?!.*(EUR|€).*)).*/, '$1')
                    .replace(/[^0-9]*/g, '');

                let currentPrice = parseInt(extractedPrice);
                const show_wallet = (min_amount === '' || parseInt(min_amount) <= currentPrice);

                if (!show_wallet) {
                    button.style.display = 'none';
                } else {
                    button.style.display = 'block';
                }
            }
        }
    });
});

const targetNode = document.querySelector('form.product-info div.current-price-container');

if (targetNode) {
    const observer = new MutationObserver((mutationsList) => {
        for (const mutation of mutationsList) {
            if (mutation.type === 'childList' || mutation.type === 'characterData') {
                const newText = $(targetNode).text();

                ['novalnet_googlepay', 'novalnet_applepay'].forEach((payment) => {
                    const button = jQuery(`#${payment}_wall_button`);
                    if (button.length) {
                        const minAmountAttr = (payment == 'novalnet_googlepay') ? 'data-googlepayMinAmount' : 'data-applepayMinAmount';
                        const min_amount = button.attr(minAmountAttr);

                        if (typeof min_amount !== 'undefined') {
                            let currentPriceText = $('form.product-info div.current-price-container').text();

                            let extractedPrice = currentPriceText
                                .replace(/[0-9,.]+ (EUR|€) pro .*/, '')
                                .replace(/.*?([0-9,.]+ (EUR|€)(?!.*(EUR|€).*)).*/, '$1')
                                .replace(/[^0-9]*/g, '');

                            let currentPrice = parseInt(extractedPrice);
                            let show_wallet = (min_amount === '' || parseInt(min_amount) <= currentPrice);

                            if (!show_wallet) {
                                button.hide();
                            } else {
                                button.show();
                            }
                        }
                    }
                });
            }
        }
    });

    observer.observe(targetNode, {
        characterData: true,
        subtree: true,
        childList: true,
    });
}

function novalnet_wallet() {
    if (jQuery('#novalnet_googlepay_wall_button') != undefined || jQuery('#novalnet_applepay_wall_button') != undefined) {
        hideCheckoutButton();
        displayWalletButtons();
    }

    jQuery('#checkout_payment').on('click', function (event) {
        hideCheckoutButton();
    });
}

function hideCheckoutButton() {
    if (jQuery('.continue_button').length > 0 && (jQuery('input[name="payment"]:checked').val() == "novalnet_applepay" || jQuery('input[name="payment"]:checked').val() == "novalnet_googlepay")) {
        jQuery('.continue_button').hide();
    } else {
        jQuery('.continue_button').show();
    }
}

function displayWalletButtons() {
    ['novalnet_googlepay', 'novalnet_applepay'].forEach((payment) => {
        if (jQuery('#' + payment + '_wall_button') != undefined && jQuery('#' + payment + '_wall_button').length > 0) {
            if (jQuery('#' + payment + '_wall_button').attr('data-walletpayparams') != undefined) {
                var novalnetPaymentObj = NovalnetPayment().createPaymentObject(),
                    walletConfiguration = JSON.parse(jQuery('#' + payment + '_wall_button').attr('data-walletpayparams'));

                var paymentIntent = {
                    clientKey: walletConfiguration.clientKey,
                    paymentIntent: {
                        merchant: walletConfiguration.merchant,
                        transaction: walletConfiguration.transaction,
                        order: walletConfiguration.order,
                        custom: walletConfiguration.custom,
                        button: walletConfiguration.button,
                        callbacks: {
                            onProcessCompletion: function (processResponseData, bookingResultCallback) {
                                const processResponse = processResponseData.result;

                                if (processResponse.status === 'SUCCESS') {
                                    if (jQuery('#' + payment + '_wall_button').attr('data-pagetype') === 'checkout') {
                                        bookingResultCallback({ status: 'SUCCESS', statusText: '' });
                                        if (payment == 'novalnet_googlepay') {
                                            document.getElementById('novalnet_googlepay_token').value = processResponseData.transaction.token;
                                            document.getElementById('novalnet_googlepay_do_redirect').value = processResponseData.transaction.doRedirect;
                                        } else {
                                            document.getElementById('novalnet_applepay_token').value = processResponseData.transaction.token;
                                        }
                                        jQuery('.continue_button input[type="submit"]').click();
                                    } else {
                                        jQuery.ajax({
                                            type: 'POST',
                                            url: 'novalnet_wallet_payment_process.php',
                                            data: {
                                                server_response: JSON.stringify(processResponseData),
                                                page_type: jQuery('#' + payment + '_wall_button').attr('data-pagetype'),
                                                payment_name: payment
                                            },
                                            success: function (orderResponse) {
                                                const response = orderResponse.length ? JSON.parse(orderResponse) : '';

                                                if (response && response.status === false) {
                                                    bookingResultCallback({ status: "FAILURE", statusText: response.message });
                                                }

                                                if (response.isRedirect === 1 && response.redirect_url) {
                                                    window.location.replace(response.redirect_url);
                                                } else if (response.return_url) {
                                                    window.location.replace(response.return_url);
                                                }
                                                bookingResultCallback({ status: "SUCCESS", statusText: '' });
                                            },
                                            error: function (xhr) {
                                                bookingResultCallback({ status: "FAILURE", statusText: xhr.statusText });
                                            }
                                        });
                                    }
                                } else {
                                    bookingResultCallback({ status: "FAILURE", statusText: '' });
                                }
                            },
                            onShippingContactChange: function (shippingContact, updateDataCallback) {
                                new Promise((resolve, reject) => {
                                    jQuery.ajax({
                                        type: 'POST',
                                        url: 'novalnet_wallet_shipping_data_update_process.php',
                                        data: {
                                            action: 'novalnet_shipping_address_update',
                                            shippingInfo: JSON.stringify(shippingContact)
                                        },
                                        success: resolve,
                                        error: reject
                                    });
                                }).then((response) => {
                                    const result = response.length ? JSON.parse(response) : {};
                                    const updatedInfo = {};

                                    if (result.shipping_address && result.shipping_address.length === 0) {
                                        updatedInfo.methodsNotFound = "There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.";
                                    } else if (result.shipping_address && result.shipping_address.length) {
                                        updatedInfo.amount = result.amount;
                                        updatedInfo.lineItems = result.article_details;
                                        updatedInfo.methods = result.shipping_address;
                                        updatedInfo.defaultIdentifier = result.shipping_address[0].identifier;
                                    }

                                    updateDataCallback(updatedInfo);
                                }).catch((error) => {
                                    console.error('Error updating shipping contact:', error);
                                });
                            },
                            onShippingMethodChange: function (shippingMethod, updatedData) {
                                $.ajax({
                                    type: 'POST',
                                    url: 'novalnet_wallet_shipping_data_update_process.php',
                                    data: {
                                        action: 'novalnet_shipping_method_update', // your action name
                                        shippingInfo: JSON.stringify(shippingMethod)
                                    },
                                    success: function (response) {
                                        var result = JSON.parse(response);
                                        let updatedInfo = {
                                            amount: result.amount,
                                            lineItems: result.article_details,
                                        };
                                        updatedData(updatedInfo);
                                    }
                                });
                            },
                            onPaymentButtonClicked: function (clickResult) {
                                if (jQuery('#' + payment + '_wall_button').attr('data-pagetype') == 'productDetail') {
                                    var parent_div = jQuery('.modifier-group'),
                                        button_display = true;

                                    if ((jQuery('button[name="btn-add-to-cart"]').prop('disabled')) || (jQuery('input[name="btn-add-to-cart"]').prop('disabled'))) {
                                        button_display = false;
                                    } else if ((jQuery('.modifier-group [name^="modifiers[property]["]') != undefined && jQuery('.modifier-group [name^="modifiers[property]["]').length > 0) || (jQuery('.modifier-group [name^="modifiers[attribute]["]') != undefined && jQuery('.modifier-group [name^="modifiers[attribute]["]').length > 0)) {
                                        jQuery('.modifier-group [name^="modifiers[property]["]').each(function () {
                                            if (jQuery(this).val() == null || jQuery(this).val() == 0) {
                                                button_display = false;
                                            }
                                        });
                                    }

                                    if (!button_display) {
                                        clickResult({ status: "FAILURE" });
                                        alert('Please select some product options before adding this product to your cart.');
                                    } else {
                                        var data = {
                                            'action': 'add_to_cart', // your action name
                                            'products_id': jQuery("input[name='products_id']").val(),
                                            'products_qty': jQuery("input[name='products_qty']").val()
                                        };
                                        let modifiers = {}; let attributes = {};
                                        jQuery('.modifier-group [name^="modifiers[property]["]').each(function () {
                                            var key = parseInt(jQuery(this).attr('name').replace(/[^0-9]/g, ''), 10);
                                            var value = $(this).val();
                                            if (key != null && key != 0 && value != null && value != 0) {
                                                modifiers[key] = value;
                                            }
                                        });

                                        jQuery('.modifier-group [name^="modifiers[attribute]["]').each(function () {
                                            var key = parseInt(jQuery(this).attr('name').replace(/[^0-9]/g, ''), 10);
                                            var value = $(this).val();
                                            if (key != null && key != 0 && value != null && value != 0 && $(this).is(':checked')) {
                                                attributes[key] = value;
                                            }
                                        });
                                        data['variant_info'] = modifiers;
                                        data['attribute_info'] = attributes;

                                        $.ajax({
                                            url: 'novalnet_wallet_shipping_data_update_process.php',
                                            type: 'POST',
                                            data: data,
                                            success: function (response) {
                                                var result = JSON.parse(response);
                                                if (result.success == false) {
                                                    clickResult({ status: "FAILURE" });
                                                    alert('Unable to add the product to the shopping cart. Please review the product options before attempting to add it to your cart.');
                                                    return;
                                                } else {
                                                    clickResult({ status: "SUCCESS" });
                                                }
                                            }
                                        });
                                        clickResult({ status: "SUCCESS" });
                                    }
                                } else {
                                    clickResult({ status: "SUCCESS" });
                                }
                            }
                        }
                    }
                };

                // Setting up the payment intent in your object
                novalnetPaymentObj.setPaymentIntent(paymentIntent);

                // Checking for the payment method availability
                novalnetPaymentObj.isPaymentMethodAvailable(function (displayPaymentButton) {
                    if (jQuery('#' + payment + '_wall_button').length <= 0) {
                        return;
                    }

                    if (displayPaymentButton && (!window?.NovalnetCheckout ||
                        (Array.isArray(window?.NovalnetCheckout) && !window?.NovalnetCheckout?.includes('#' + payment + '_wall_button')))) {
                        // Initiating the Payment Request for the Wallet Payment
                        // Check if the platform is NOT macOS and the page type is "checkout"
                        if (navigator.platform.indexOf('Mac') == -1 || $('div[data-pagetype="checkout"]').length === 0) {
                            // Apply the CSS if the conditions are met
                            $('#novalnet_applepay_wall_button apple-pay-button').css({
                                width: '100%',
                                marginBottom: '4px'
                            });
                        }
                        novalnetPaymentObj.addPaymentButton('#' + payment + '_wall_button');
                        window.NovalnetCheckout = window.NovalnetCheckout || [];
                        window.NovalnetCheckout.push('#' + payment + '_wall_button');
                    }
                });
            }
        }
    });
}

window.addEventListener ? window.addEventListener("load", novalnet_wallet) : window.attachEvent && window.attachEvent("onload", novalnet_wallet);
