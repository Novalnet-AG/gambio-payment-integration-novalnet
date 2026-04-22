<?php
/**
 * Novalnet payment module
 *
 * This script contains the helper function for all the payments
 *
 * @author    Novalnet AG
 * @copyright Copyright (c) Novalnet
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link      https://www.novalnet.de
 *
 * Script: NovalnetValidator.class.php
 */

require_once DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php';

class NovalnetValidator
{
    /**
     * @var string
     */
    protected $helper;

    /**
     * NovalnetValidator constructor
     */
    public function __construct()
    {
        $this->helper = new NovalnetHelper();
    }

    /**
     * Check the payment condition whether the payment method needs to display or not
     *
     * @param string $payment
     *
     * @return boolean
     */
    public function check_novalnet_payment(string $payment)
    {
        global $order;

        if (!isset($order)) {
            return false;
        }

        if ($this->helper->checkMerchantCredentials() || strpos(MODULE_PAYMENT_INSTALLED, 'novalnet_config') === false || (!in_array($payment, ['novalnet_instalment_invoice', 'novalnet_instalment_sepa', 'novalnet_guarantee_invoice', 'novalnet_guarantee_sepa']) && !$this->helper->hidePaymentVisibility($payment)) || !$this->checkGuarantee($payment)) {
            if (!empty($_SESSION['payment']) && $_SESSION['payment'] == $payment) {
                unset($_SESSION['payment']);
            }
            return false;
        } elseif (in_array($payment, ['novalnet_applepay', 'novalnet_googlepay'])) {
            $payment_upper = strtoupper($payment);
            $constant_name = "MODULE_PAYMENT_{$payment_upper}_BUTTON_DISPLAY";

            $display_page = [];
            if (defined($constant_name) && !empty(constant($constant_name))) {
                $display_page = explode('|', constant($constant_name));
            }

            $is_applepay_unavailable = ($payment === 'novalnet_applepay' && !$this->helper->checkApplePayIsAvailable());

            if (!in_array('checkout page', $display_page) || $is_applepay_unavailable) {
                if (!empty($_SESSION['payment']) && $_SESSION['payment'] === $payment) {
                    unset($_SESSION['payment']);
                }
                return false;
            }
        }

        return true;
    }

    /**
     * Check guarantee and normal payments
     *
     * @param string $code
     *
     * @return boolean
     */
    public function checkGuarantee(string $code)
    {
        global $order;

        if (!in_array($code, ['novalnet_invoice', 'novalnet_guarantee_invoice', 'novalnet_sepa', 'novalnet_guarantee_sepa', 'novalnet_instalment_sepa', 'novalnet_instalment_invoice'])) {
            return true;
        }

        $payment_upper = strtoupper($code);
        $payment_last_name = strtoupper(end(explode('_', $code)));
        $payment_status = (defined("MODULE_PAYMENT_NOVALNET_GUARANTEE_{$payment_last_name}_STATUS") && constant("MODULE_PAYMENT_NOVALNET_GUARANTEE_{$payment_last_name}_STATUS") == 'true') ? true : false;
        $allowB2b = (defined("MODULE_PAYMENT_{$payment_upper}_ALLOW_B2B") && constant("MODULE_PAYMENT_{$payment_upper}_ALLOW_B2B") == 'true') ? true : false;
        $minimumAmount = 0;
        if (in_array($code, ['novalnet_instalment_sepa', 'novalnet_instalment_invoice'])) {
            $minimumAmount = (defined("MODULE_PAYMENT_{$payment_upper}_MINIMUM_ORDER_AMOUNT") && constant("MODULE_PAYMENT_{$payment_upper}_MINIMUM_ORDER_AMOUNT") >= '1998') ? constant("MODULE_PAYMENT_{$payment_upper}_MINIMUM_ORDER_AMOUNT") : '1998';
        } elseif (in_array($code, ['novalnet_invoice', 'novalnet_guarantee_invoice'])) {
            $minimumAmount = (defined("MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_MINIMUM_ORDER_AMOUNT") && constant("MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_MINIMUM_ORDER_AMOUNT") >= '999') ? constant("MODULE_PAYMENT_NOVALNET_GUARANTEE_INVOICE_MINIMUM_ORDER_AMOUNT") : '999';
        } elseif (in_array($code, ['novalnet_sepa', 'novalnet_guarantee_sepa'])) {
            $minimumAmount = (defined("MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_MINIMUM_ORDER_AMOUNT") && constant("MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_MINIMUM_ORDER_AMOUNT") >= '999') ? constant("MODULE_PAYMENT_NOVALNET_GUARANTEE_SEPA_MINIMUM_ORDER_AMOUNT") : '999';
        }

        $basketAmount = $order->info['total'] * 100;

        if (empty($payment_status) && in_array($code, ['novalnet_invoice' ,'novalnet_sepa'])) {
            return true;
        } elseif (isset($order)) {
            $countriesList  = ['AT','DE','CH'];
            if (!empty($order->billing['company']) && !empty($allowB2b) != false) {
                $countriesList  = ['AT', 'BE', 'BG', 'CY', 'CZ', 'CH', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK', 'XI', 'CH'];
            }

            $billingAddress = $this->helper->getAddressData($order->billing);
            $shippingAddress = $this->helper->getAddressData($order->delivery);
            $countryCode    = strtoupper($order->billing['country']['iso_code_2']);

            $guaranteeCheck = ($billingAddress == $shippingAddress) && in_array($countryCode, $countriesList) && ($order->info['currency'] == 'EUR') && ($basketAmount >= $minimumAmount);

            if (in_array($code, ['novalnet_instalment_sepa', 'novalnet_instalment_invoice'])) {
                $cycles = (defined("MODULE_PAYMENT_{$payment_upper}_CYCLE") && !empty(constant("MODULE_PAYMENT_{$payment_upper}_CYCLE"))) ? array_map('trim', explode('|', constant("MODULE_PAYMENT_{$payment_upper}_CYCLE"))) : [];
                $count = 0;
                foreach ($cycles as $values) {
                    if (($basketAmount / $values) >= 999) {
                        $count++;
                    }
                }

                if ($guaranteeCheck && $count > 0) {
                    return true;
                }
            } elseif (!in_array($code, ['novalnet_instalment_sepa', 'novalnet_instalment_invoice'])) {
                $forcePayment = (defined("MODULE_PAYMENT_NOVALNET_GUARANTEE_{$payment_last_name}_FORCE") && constant("MODULE_PAYMENT_NOVALNET_GUARANTEE_{$payment_last_name}_FORCE") == 'true') ? true : false;
                if (($guaranteeCheck && !in_array($code, ['novalnet_invoice' ,'novalnet_sepa'])) || (!$guaranteeCheck && in_array($code, ['novalnet_invoice' ,'novalnet_sepa']) && !empty($forcePayment))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Payment form details
     *
     * @param string $payment_code
     * @param string $info
     * @param array  $selection
     */
    public function prepareFormFields(string $payment_code, string $info, &$selection)
    {
        global $order;

        if (!isset($order)) {
            return $selection;
        }

        if (!empty($order->customer)) {
            $customerData = $this->helper->getCustomerInfo($order->customer['csID']);
        }
        $tokenization = defined('MODULE_PAYMENT_'.strtoupper($payment_code).'_TOKENIZATION') ? constant('MODULE_PAYMENT_'.strtoupper($payment_code).'_TOKENIZATION') == 'true' : '';
        $display_form = 'block;';

        // Show one click card if exists
        if ($tokenization) {
            $saved_card_details = $this->fetchPaymentDetails($payment_code);
            $saved_cards = $temp = [];

            foreach ($saved_card_details as $item) {
                if (isset($item['zero_amount_booking']) && isset($item['display_token']) && $item['display_token'] == 0) {
                    continue;
                }

                // Create a unique key excluding 'id'
                $key = md5(json_encode(array_diff_key($item, ['id' => ''])));

                if (!isset($temp[$key])) {
                    $temp[$key] = true;
                    $saved_cards[] = $item;
                }
            }

            if (!empty($saved_cards)) {
                $display_form = 'none;';
                foreach ($saved_cards as $key => $value) {
                    $checked = $key == 0 ? 'checked' : '';
                    if ($payment_code == 'novalnet_cc') {
                        $icon = xtc_href_link('/images/icons/payment/novalnet_cc_' . strtolower($value['card_brand']) . '.png', '', 'SSL', false, false, false, true, true);
                        $brand = "<img src='$icon' alt='" . $value['card_brand']. "'/>";

                        $card_info = sprintf(MODULE_PAYMENT_NOVALNET_CC_TOKEN_TEXT, $brand, substr($value['card_number'], -4), $value['card_expiry_month'], substr($value['card_expiry_year'], -2));
                    } elseif (in_array($payment_code, ['novalnet_sepa', 'novalnet_guarantee_sepa', 'novalnet_instalment_sepa'])) {
                        $card_info = 'IBAN ' . $value['iban'];
                    } elseif ($payment_code == 'novalnet_direct_debit_ach') {
                        $card_info = sprintf((ACCOUNT_NO_ACH . " " . '%s'), $value['account_number']) . " " . sprintf((ROUTING_NO_ACH . " " . '%s'), $value['routing_number']);
                    }

                    $oneclick = '<nobr>
                        <input type="checkbox" name="' . $payment_code . '_token" class="' . $payment_code . '_saved_acc" id="' . $payment_code . $value['id'] . '" value="' . $value['token'] . '" ' . $checked . ' onclick/>&nbsp;&nbsp;&nbsp;' . $card_info . '&nbsp;&nbsp;
                        <a href="javascript:void(0);" id="' . $value['id'] . '" class="token_delete">' .
                        (defined('MODULE_PAYMENT_NOVALNET_REMOVE_DUPLICATE_TOKEN') ? MODULE_PAYMENT_NOVALNET_REMOVE_DUPLICATE_TOKEN : '') .
                        '</a></nobr>';
                    $selection['fields'][] = [ 'title' =>   $oneclick ];
                }

                $selection['fields'][] = [ 'title' => '<nobr><input type="checkbox" name="' . $payment_code . '_token" class="' . $payment_code . '_saved_acc" id="' . $payment_code . '_new" data-reload="1" value="new" onclick/>&nbsp;&nbsp;&nbsp;' . constant('MODULE_PAYMENT_' . strtoupper($payment_code) . '_NEW_ACCOUNT_DETAILS') . '</nobr>'];
            }
        }

        if ($payment_code == 'novalnet_cc') {
            $selection['fields'][] = [
                'title' => '<div id="'. "{$payment_code}_payment_form" . '" style="display: '. $display_form . '"><iframe frameborder="0" id="novalnet_iframe" scrolling="no"></iframe>
                    <input type="hidden" id="nn_pan_hash" name="nn_pan_hash" value="" />
                    <input type="hidden" id="nn_uniqueid" name="nn_uniqueid" value="" />
                    <input type="hidden" id="do_redirect" name="do_redirect" value="" /> 
                    <input type="hidden" value="'. htmlentities($this->renderIframe()) .'" id="nn_cc_iframe_data"></div>'
            ];
        } elseif (in_array($payment_code, ['novalnet_sepa', 'novalnet_guarantee_sepa', 'novalnet_instalment_sepa'])) {
            $iban_field_attributes = 'id="' . $payment_code . '_iban" autocomplete="off" placeholder="DE00 0000 0000 0000 0000 00" onkeypress="return NovalnetUtility.formatIban(event);" onchange="return NovalnetUtility.formatIban(event);"';

            $selection['fields'][] = ['title' => sprintf('<div id="'. "{$payment_code}_payment_form" . '" style="display: '. $display_form . '">'. MODULE_PAYMENT_NOVALNET_ACCOUNT_HOLDER. ' *<br>' . xtc_draw_input_field($payment_code . '_holder', $order->customer['firstname'] . ' ' . $order->customer['lastname'], 'id="'.$payment_code.'_holder" autocomplete="off" placeholder ="'.MODULE_PAYMENT_NOVALNET_ACCOUNT_HOLDER.'"'). '<br><br>' .'<span id="iban">'. MODULE_PAYMENT_NOVALNET_SEPA_IBAN .'</span>%s</div>', xtc_draw_input_field("{$payment_code}_iban", '', $iban_field_attributes)) . sprintf(
                '<div class="nn-bic-field" id="' . $payment_code . "_bic_field" . '" style="display: none;"><br><span id="bic">BIC *</span>%s</div>',
                xtc_draw_input_field("{$payment_code}_bic", '', 'id="' . $payment_code . '_bic" autocomplete="off" placeholder="XXXX XX XX XXX" onkeypress="return NovalnetUtility.formatBic(event);" onchange="return NovalnetUtility.formatBic(event);"')
            ), 'field' => ''];
        } elseif ($payment_code == 'novalnet_direct_debit_ach') {
            $selection['fields'][] = [
                'title' => '<div id="'.$payment_code.'_payment_form" style="display: '. $display_form . '">'. MODULE_PAYMENT_NOVALNET_ACCOUNT_HOLDER.' *<br>'. xtc_draw_input_field($payment_code . '_holder', $order->customer['firstname'] . ' ' . $order->customer['lastname'], 'id="'.$payment_code.'_holder" autocomplete="off" placeholder ="'.MODULE_PAYMENT_NOVALNET_ACCOUNT_HOLDER.'"'). '<br><br>' .
                MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ACCOUNT_NO.'<br>'. xtc_draw_input_field($payment_code . '_account_no', '', 'id="'.$payment_code.'_account_no" autocomplete="off" placeholder ="'.MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ACCOUNT_NO.'" oninput="validateNumericInput(this)" '). '<br><br>' .
                '<nobr>' . MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ROUTING_NO .'</nobr>'.'<br>'. xtc_draw_input_field($payment_code . '_routing_no', '', 'id="'.$payment_code.'_routing_no" autocomplete="off" placeholder ="'.MODULE_PAYMENT_NOVALNET_DIRECT_DEBIT_ACH_ROUTING_NO.'" oninput="validateNumericInput(this)" ').
                '</div>',
                'field' => '',
            ];
        } elseif ($payment_code == 'novalnet_mbway') {
            $selection['fields'][] = [
                'title' => sprintf(
                    '<nobr><span>%s</span></nobr>%s',
                    htmlspecialchars(MODULE_PAYMENT_NOVALNET_MBWAY_MOBILE_NO),
                    xtc_draw_input_field(
                        'novalnet_mbway_mobile_no',
                        '',
                        'id="novalnet_mbway_mobile_no" 
                         autocomplete="off"
                         style="width:170%;"
                         onkeypress="return NovalnetUtility.allowDigits(event)" onchange="return NovalnetUtility.allowDigits(event)"'
                    )
                )
            ];
        }

        $allowB2b = (defined('MODULE_PAYMENT_' . strtoupper($payment_code) . '_ALLOW_B2B') && constant('MODULE_PAYMENT_' . strtoupper($payment_code) . '_ALLOW_B2B') == 'true') ? true : false;
        if (in_array($payment_code, ['novalnet_guarantee_invoice', 'novalnet_instalment_invoice', 'novalnet_guarantee_sepa', 'novalnet_instalment_sepa']) && (empty($allowB2b) || ($allowB2b && empty($order->billing['company'])))) {
            $selection['fields'][] = [
                'title' => '<input type="hidden" name="guarantee_dob_error" id="guarantee_dob_error" value="' . MODULE_PAYMENT_NOVALNET_GUARANTEE_DOB_ERROR_MSG . '">' . MODULE_PAYMENT_GUARANTEE_DOB_FIELD . xtc_draw_input_field("{$payment_code}_dob", ((isset($customerData['customers_dob']) && $this->helper->validateBirthDate($customerData['customers_dob'])) ? $customerData['customers_dob'] : ''), 'id="' . "{$payment_code}_dob" . '" placeholder="' . MODULE_PAYMENT_NOVALNET_GUARANTEE_DOB_FORMAT . '" autocomplete="OFF" maxlength="10" onkeydown="return NovalnetUtility.isNumericBirthdate(this,event)"'),
                'field' => '',
            ];
        }

        if ($tokenization && $_SESSION['customers_status']['customers_status_id'] != 1) {
            $title = '<nobr><div class="novalnet_save_card" id="' . $payment_code . '_save_card" style="display:' . $display_form . '">' . xtc_draw_checkbox_field($payment_code . '_oneclick', 1, false, '') . '  ' . MODULE_PAYMENT_NOVALNET_SEPA_SAVE_CARD_DETAILS . '</div></nobr>';
            if ($payment_code == 'novalnet_cc') {
                $title = '<nobr><div class="novalnet_save_card" id="' . $payment_code . '_save_card" style="display:' . $display_form . '">' . xtc_draw_checkbox_field($payment_code . '_oneclick', 1, false, '') . '  ' . MODULE_PAYMENT_NOVALNET_CC_SAVE_CARD_DETAILS . '</div></nobr>';
            }
            $selection['fields'][] = [
                'title' => $title
            ];
        }

        if (in_array($payment_code, ['novalnet_instalment_sepa', 'novalnet_instalment_invoice'])) {
            $order_amount = isset($order->info) ? $this->helper->getOrderAmount($order->info['total']) : 0;
            $instalment_cycles = defined('MODULE_PAYMENT_' . strtoupper($payment_code) . '_CYCLE') ? array_map('trim', explode('|', constant('MODULE_PAYMENT_' . strtoupper($payment_code) . '_CYCLE'))) : [];

            // Prepare instalment fields
            $selection['fields'][] = [
                'title' => sprintf(
                    '<nobr class="novalnet_instalment_text">'.MODULE_PAYMENT_NOVALNET_INSTALLMENT_TEXT.'</nobr>',
                    xtc_format_price_order($order_amount / 100, 1, $order->info['currency'])
                )
            ];

            if (!empty($instalment_cycles)) {
                $instalment_info = '<select class="form-control" name="' . $payment_code . '_cycles" id="' . $payment_code . '_cycles">';
                $instalment_table = '<div id="'.$payment_code.'_summary" class="'.$payment_code.'_summary">';
                sort($instalment_cycles);
                $count = 0;
                foreach ($instalment_cycles as $cycle) {
                    if (($order_amount / $cycle) >= 999) {
                        $cycle_amount = number_format($order->info['total'] / $cycle, 2);
                        $cycle_amount = xtc_format_price_order($cycle_amount, 1, $order->info['currency']);
                        $instalment_info .=  '<option value='. $cycle .'>'.sprintf(MODULE_PAYMENT_NOVALNET_INSTALLMENT_PER_MONTH_CYCLE, $cycle) . $cycle_amount . ' '. MODULE_PAYMENT_NOVALNET_INSTALLMENT_PER_MONTH_FRONTEND .'</option>';
                        $hidden = ($count != 0) ? 'hidden="hidden"' : '';
                        $instalment_table .= '<div class="'.$payment_code.'_detail" data-duration="'. $cycle .'" '.$hidden.'><table class="novalnetinstalment-table">';
                        $instalment_table .= '<thead><tr><th>'. MODULE_PAYMENT_NOVALNET_INSTALLMENT_CYCLES_FRONTEND .'</th><th>'. MODULE_PAYMENT_NOVALNET_INSTALLMENT_AMOUNT_FRONTEND .'</th></tr></thead><tbody>';
                        for ($length = 1; $length <= $cycle; $length++) {
                            if ($length == $cycle) {
                                $cycle_amount = $order->info['total'] - (number_format($order->info['total'] / $cycle, 2) * ($length - 1));
                                $cycle_amount = xtc_format_price_order($cycle_amount, 1, $order->info['currency']);
                                $instalment_table .= '<tr><td>'. $length .'</td><td>'. $cycle_amount .'</td></tr>';
                                continue;
                            }

                            $instalment_table .= '<tr><td>'. $length .'</td><td>'. $cycle_amount .'</td></tr>';
                        }
                        $instalment_table .= '</tbody></table></div>';
                        $count++;
                    }
                }
                $instalment_table .= '</div>';
                $instalment_info .= '</select>' . '<a id="'.$payment_code.'_info" class="'.$payment_code.'_info">' . MODULE_PAYMENT_NOVALNET_INSTALLMENT_SUMMARY_TITLE . '</a>';
                // Instalment cycles selection and hidden fields
                $selection['fields'][] = [
                    'field' => $instalment_info . $instalment_table
                ];
            }
        }
    }

    /**
     * Prepare the parameters required for wallet payments
     *
     * @param string $paymentCode
     * @param array  $selection
     */
    public function prepareWalletButton(string $paymentCode, &$selection)
    {
        global $order;

        if (isset($order)) {
            $hiddenFields = '<input type="hidden" id="'.$paymentCode.'_token" name="'.$paymentCode.'_token" value="" />';

            if ($paymentCode == 'novalnet_googlepay') {
                $hiddenFields .= '<input type="hidden" id="'.$paymentCode.'_do_redirect" name="'.$paymentCode.'_do_redirect" value="" />';
            }

            $selection['fields'][] = [
                'title' => $hiddenFields . '<div id="'. "{$paymentCode}_wall_button" . '" data-pageType="checkout" data-paymentName="' .$paymentCode. '" data-walletPayParams="'. htmlentities($this->getWalletParams($paymentCode, 'checkout')) .'">
                    </div>'
            ];
        }
    }

    /**
     * Form the wallet paramaters
     *
     * @param string $payment_code
     * @param string $payment_type
     *
     * @return array
     */
    public function getWalletParams($payment_code, $payment_type)
    {
        global $xtPrice, $order;

        $order = ($payment_type != 'product_detail') ? $order : new order();
        $totalPrice = (string)(round($order->info['total'], 2) * 100);
        $languageCode = (isset($_SESSION['language_code']) && $_SESSION['language_code'] == 'de') ? 'de-DE' : 'en-GB';

        if ($payment_type == 'product_detail') {
            $price = $xtPrice->xtcGetPrice($GLOBALS['product']->data['products_id'], true, 1, $GLOBALS['product']->data['products_tax_class_id'], $GLOBALS['product']->data['products_price'], 1, 0, true, true, '', true);
            $totalPrice = (string)(round($price['plain'], 2) * 100);
        }

        // Retrieve customer data, country, and country code in one query
        $customer_data = xtc_db_fetch_array(
            xtc_db_query(
                "SELECT 
                            c.customer_id, 
                            ab.entry_country_id, 
                            co.countries_iso_code_2 
                          FROM admin_access_users c 
                          LEFT JOIN " . TABLE_ADDRESS_BOOK . " ab ON c.customer_id = ab.customers_id 
                          LEFT JOIN " . TABLE_COUNTRIES . " co ON ab.entry_country_id = co.countries_id 
                          LIMIT 1"
            )
        );

        $data = [];

        $data['clientKey'] = MODULE_PAYMENT_NOVALNET_CLIENT_KEY;

        $data['merchant'] = [
            'countryCode' => !empty($customer_data) ? $customer_data['countries_iso_code_2'] : 'DE',
            'partnerId'   => ($payment_code == 'novalnet_googlepay' && defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_MERCHANT_ID')) ? constant('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_MERCHANT_ID') : ''
        ];

        $data['transaction'] = [
            'amount' => $totalPrice,
            'currency' => $order->info['currency'],
            'paymentMethod' => $payment_code == 'novalnet_googlepay' ? 'GOOGLEPAY' : 'APPLEPAY',
            'environment' => (defined('MODULE_PAYMENT_'.strtoupper($payment_code).'_TEST_MODE') && constant('MODULE_PAYMENT_'.strtoupper($payment_code).'_TEST_MODE') == 'true') ? 'SANDBOX' : 'PRODUCTION',
            'enforce3d' => ($payment_code == 'novalnet_googlepay' && defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ENFORCE_3D_SECCURE') && constant('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_ENFORCE_3D_SECCURE') == 'true') ? true : false
        ];

        $needs_shipping = false;

        if (!empty($GLOBALS['product']) && !empty($GLOBALS['product']->data) && $payment_type == 'product_detail') {
            $is_virtual_product = $this->has_product_virtual_tax_class($GLOBALS['product']->data['products_id']);
            if ($GLOBALS['product']->data['product_type'] == 1 && empty($is_virtual_product)) {
                $needs_shipping = true;
            }
        } elseif(!empty($order->products) && $order->content_type != 'virtual' && $order->content_type != 'virtual_weight') {
            foreach ($order->products as $products) {
                if ($products['product_type'] == 1) {
                    $needs_shipping = true;
                    break;
                }
            }
        }

        if($payment_type == 'product_detail') {
            $qty = isset($GLOBALS['product']->data['qty']) ? $GLOBALS['product']->data['qty'] : 1;
            $product_name = $GLOBALS['product']->data['products_name'] . ' (' . xtc_format_price_order($totalPrice / 100, 1, $_SESSION['currency']) . ' x ' . $qty . ')';
            $articleDetails[] = array('label' => $product_name, 'amount' => $totalPrice * $qty, 'type' => 'SUBTOTAL');
        } elseif (!empty($order->products)) {
            $articleDetails = $this->getCartItems($payment_code, $order->products, $needs_shipping);
        }

        $data['order'] = [
            'paymentDataPresent' => false,
            'merchantName' => (defined('MODULE_PAYMENT_'.strtoupper($payment_code).'_BUSINESS_NAME') && constant('MODULE_PAYMENT_'.strtoupper($payment_code).'_BUSINESS_NAME') != '') ? constant('MODULE_PAYMENT_'.strtoupper($payment_code).'_BUSINESS_NAME') : STORE_NAME,
            'lineItems' => !empty($articleDetails) ? $articleDetails : []
        ];

        $data['transaction']['setPendingPayment'] = ($payment_type == 'product_detail' && $needs_shipping == false) ? true : false;

        if ($payment_type != 'checkout' && $payment_code == 'novalnet_applepay') {
            $data['order']['billing'] = array('requiredFields' => array('postalAddress', 'phone'));
            if($needs_shipping) {
                $data['order']['shipping'] = array('requiredFields' => array('postalAddress', 'phone', 'email'), 'methodsUpdatedLater' => true);
            } else {
                $data['order']['shipping'] = array('requiredFields' => array('email'));
            }
        } elseif ($payment_type != 'checkout' && $payment_code == 'novalnet_googlepay') {
            $data['order']['billing'] = array('requiredFields' => array('postalAddress', 'phone', 'email'));
            if($needs_shipping) {
                $data['order']['shipping'] = array('requiredFields' => array('postalAddress', 'phone'), 'methodsUpdatedLater' => true);
            }
        }

        $data['button'] = [
            'type'   => defined('MODULE_PAYMENT_'.strtoupper($payment_code).'_BUTTON_TYPE') ? constant('MODULE_PAYMENT_'.strtoupper($payment_code).'_BUTTON_TYPE') : '',
            'style'  => ($payment_code == 'novalnet_applepay' && defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_THEME')) ? constant('MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_THEME') : 'default',
            'locale' => $languageCode,
            'boxSizing' => $payment_code == 'novalnet_applepay' ? 'border-box' : 'fill',
            'dimensions' => ['height' => defined('MODULE_PAYMENT_'.strtoupper($payment_code).'_BUTTON_HEIGHT') ? constant('MODULE_PAYMENT_'.strtoupper($payment_code).'_BUTTON_HEIGHT') : '', 'cornerRadius' => ($payment_code == 'novalnet_applepay' && defined('MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_CORNER_RADIUS')) ? constant('MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_CORNER_RADIUS') : '']
        ];

        $data['custom']['lang'] = $languageCode;

        return $this->helper->serializeData($data);
    }

    /**
     * Check if the product is virtual or physical
     *
     * @param integer $p_products_id
     *
     * @return boolean
     */
    protected function has_product_virtual_tax_class($p_products_id)
    {
        $c_products_id = (int)$p_products_id;
        $t_result = xtc_db_query('SELECT `options_id` FROM `products_attributes` WHERE `products_id` = '. $c_products_id .' GROUP BY `options_id`');

        if(!empty($t_result)) {
            while ($check = xtc_db_fetch_array($t_result)) {
                $option_id = $check['options_id'];
                $virtual_check_query = xtc_db_query("select count(*) as total from " . TABLE_PRODUCTS_ATTRIBUTES . " pa, " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad where pa.products_id = '" . xtc_db_input($c_products_id) . "' and pa.options_values_id = '" . xtc_db_input($option_id) . "' and pa.products_attributes_id = pad.products_attributes_id");
                $virtual_check = xtc_db_fetch_array($virtual_check_query);
                if (isset($virtual_check['total'])) {
                    return true;
                }
            }
            return false;
        }
    }

    /**
     * Get the cart details
     *
     * @param string $payment_code
     * @param array $products
     * @param boolean $needs_shipping
     *
     * @return array
     */
    public function getCartItems($payment_code, $products, $needs_shipping = false)
    {
        global $order;
        $articleDetails = [];
        foreach ($products as $product) {
            $product_name = $product['name'] . ' (' . xtc_format_price_order($product['price'], 1, $_SESSION['currency']) . ' x ' . $product['qty'] . ')';
            $articleDetails[] = array('label' => $product_name, 'amount' => (string)(($product['qty'] * $product['price']) * 100), 'type' => 'SUBTOTAL');
        }

        $tax_amount = $order->info['tax'];

        if (!empty($tax_amount)) {
            if ($_SESSION['customers_status']['customers_status_show_price_tax'] != '0') {
                $articleDetails[] = array(
                    'label'     => defined('MODULE_PAYMENT_'. strtoupper($payment_code) . '_INCL_TAX_LABEL') ? constant('MODULE_PAYMENT_'. strtoupper($payment_code) . '_INCL_TAX_LABEL') : 'Inkl Steuer',
                    'amount'    => (string)((round($tax_amount, 2)) * 100),
                    'type'      => 'SUBTOTAL'
                );
            } else {
                $articleDetails[] = array(
                    'label'     => defined('MODULE_PAYMENT_'. strtoupper($payment_code) . '_EXCL_TAX_LABEL') ? constant('MODULE_PAYMENT_'. strtoupper($payment_code) . '_EXCL_TAX_LABEL') : 'Exkl Steuer',
                    'amount'    => (string)((round($tax_amount, 2)) * 100),
                    'type'      => 'SUBTOTAL'
                );
            }
        }

        if (!empty($order->info['shipping_class']) && $needs_shipping) {
            $articleDetails[] = array(
                'label'     => $order->info['shipping_method'],
                'amount'    => (string)((round($order->info['shipping_cost'], 2)) * 100),
                'type'      => 'SUBTOTAL'
            );
        }

        if (!empty($_SESSION['cc_id']) && !empty($order->info['deduction'])) {
            $articleDetails[] = array('label' => (defined('MODULE_PAYMENT_NOVALNET_GOOGLEPAY_DISCOUNT_AND_GIFT_VOUCHER_LABEL') ? MODULE_PAYMENT_NOVALNET_GOOGLEPAY_DISCOUNT_AND_GIFT_VOUCHER_LABEL : ($_SESSION['language_code'] == 'de' ? 'Rabatt' : 'Discount')),
                                    'amount' => '-' . (string) (round($order->info['deduction'], 2) * 100),
                                    'type' => 'SUBTOTAL');
        }

        return $articleDetails;
    }

    /**
     * Form the iframe paramaters
     *
     * @param string $payment_code
     *
     * @return array
     */
    public function renderIframe()
    {
        global $order;

        $data = [];

        $data['iframe'] = [
            'id' => 'novalnet_iframe',
            'inline' => (int) (defined('MODULE_PAYMENT_NOVALNET_CC_INLINE_FORM') && MODULE_PAYMENT_NOVALNET_CC_INLINE_FORM == 'true') ? 1 : 0,
            'skip_auth' => 1,
            'text' => (isset($_SESSION['language_code'])) ? strtoupper($_SESSION['language_code']) : 'DE',
            'style' => [
                'container' => MODULE_PAYMENT_NOVALNET_CC_CSS_STANDARD_STYLE,
                'input' => MODULE_PAYMENT_NOVALNET_CC_CSS_STANDARD_STYLE_INPUT,
                'label' => MODULE_PAYMENT_NOVALNET_CC_CSS_TEXT
            ]
        ];

        $data['customer'] = [
            'first_name' => $order->billing['firstname'],
            'last_name' => $order->billing['lastname'],
            'email' => $order->customer['email_address']
        ];

        $data['customer']['billing'] = $this->helper->getAddressData($order->billing);

        $data['transaction'] = [
            'amount' => $order->info['total'] * 100,
            'currency' => $order->info['currency'],
            'test_mode' => (int) (defined('MODULE_PAYMENT_NOVALNET_CC_TEST_MODE') && MODULE_PAYMENT_NOVALNET_CC_TEST_MODE == 'true') ? '1' : '0',
            'enforce_3d' => (int) (defined('MODULE_PAYMENT_NOVALNET_CC_3D_SECURE_FORCE') && MODULE_PAYMENT_NOVALNET_CC_3D_SECURE_FORCE == 'true') ? '1' : '0'
        ];

        $data['custom']['lang'] = (isset($_SESSION['language_code'])) ? strtoupper($_SESSION['language_code']) : 'DE';
        $data['clientKey'] = MODULE_PAYMENT_NOVALNET_CLIENT_KEY;
        return $this->helper->serializeData($data);
    }

    /**
     * Fetch the one click details
     *
     * @param string $paymentCode
     *
     * @return array
     */
    public function fetchPaymentDetails(string $paymentCode)
    {
        $savedCardDetails = [];
        if (in_array($paymentCode, ['novalnet_cc', 'novalnet_direct_debit_ach', 'novalnet_sepa', 'novalnet_guarantee_sepa', 'novalnet_instalment_sepa'])) {
            $payment_method = ['DIRECT_DEBIT_SEPA', 'GUARANTEED_DIRECT_DEBIT_SEPA', 'INSTALMENT_DIRECT_DEBIT_SEPA'];
            $customer_id = $_SESSION['customer_id'];
            $status = ['CONFIRMED', 'ON_HOLD'];

            if ($paymentCode == 'novalnet_direct_debit_ach') {
                $payment_method = ['DIRECT_DEBIT_ACH'];
            } elseif ($paymentCode == 'novalnet_cc') {
                $payment_method = ['CREDITCARD'];
            }

            $status_str = "'" . implode("','", $status) . "'";
            $payment_method_str = "'" . implode("','", $payment_method) . "'";

            $payment_details = xtc_db_query(
                "SELECT payment_details, id
                  FROM novalnet_transaction_detail
                  WHERE customer_id = $customer_id
                  AND payment_details IS NOT NULL
                  AND payment_details <> ''
                  AND payment_details != '{}'
                  AND status IN ($status_str)
                  AND payment_type IN ($payment_method_str)
                  ORDER BY id DESC
                  LIMIT 3"
            );

            while ($payment_detail = xtc_db_fetch_array($payment_details)) {
                $decode_details = json_decode($payment_detail['payment_details'], true);

                if (in_array($payment_method, ['DIRECT_DEBIT_SEPA', 'GUARANTEED_DIRECT_DEBIT_SEPA', 'INSTALMENT_DIRECT_DEBIT_SEPA']) && empty($decode_details['iban'])) {
                    continue;
                }

                if ($payment_method == 'DIRECT_DEBIT_ACH' && empty($decode_details['account_number']) && empty($decode_details['routing_number'])) {
                    continue;
                }

                if ($payment_method == 'CREDITCARD') {
                    $check_value = isset($decode_details['card_number']) ? $decode_details['card_brand'] . $decode_details['card_number'] . $decode_details['card_expiry_month'] . $decode_details['card_expiry_year'] : '';
                    if (empty($check_value)) {
                        continue;
                    }
                }
                $decode_details['id'] = $payment_detail['id'];
                array_push($savedCardDetails, $decode_details);
            }
        }
        return $savedCardDetails;
    }
}
