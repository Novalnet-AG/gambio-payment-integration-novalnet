<?php

$t_language_text_section_content_array = array
(
    /*
     * ==============================================================
     * LEGACY PAYMENT SETTINGS
     * ==============================================================
     */

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_TEXT_TITLE' =>
        'Novalnet Applepay',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_TEXT_DESC' =>
        'The payment will be processed in the test mode therefore amount for this transaction will not be charged',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_STATUS_TITLE' =>
        'Enable payment method',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BLOCK_TITLE' =>
        'Applepay configuration',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_STATUS_DESC' =>
        'Do you want to accept Applepay payment?',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_TEST_MODE_TITLE' =>
        'Enable test mode',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_TEST_MODE_DESC' =>
        'The payment will be processed in the test mode therefore amount for this transaction will not be charged',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_SORT_ORDER_TITLE' =>
        'Display Sort Order',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_SORT_ORDER_DESC' =>
        'Display sort order; the lowest value is displayed first.',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_ORDER_STATUS_TITLE' =>
        'Completed order Status',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_ORDER_STATUS_DESC' =>
        'Status to be used for successful orders',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_AUTHENTICATE_TITLE' =>
        'Payment action',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_AUTHENTICATE_DESC' =>
        'Choose whether or not the payment should be charged immediately.',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_MANUAL_CHECK_LIMIT_TITLE' =>
        'Minimum transaction amount for authorization',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_MANUAL_CHECK_LIMIT_DESC' =>
        'In case the order amount exceeds the mentioned limit, the transaction will be set on-hold till your confirmation of the transaction. You can leave the field empty if you wish to process all the transactions as on-hold (in minimum unit of currency. E.g. enter 100 which is equal to 1.00).',

    /*
     * Keep HTML here because this is used by the
     * existing Payment Settings page.
     */
    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_TYPE_TITLE' =>
        'Button Design',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_TYPE_DESC' =>
        'Style for Apple Pay button',
   
    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_THEME_TITLE' =>
        'Button Theme',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_THEME_DESC' =>
        '',
    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_HEIGHT_TITLE' =>
        'Button Height',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_HEIGHT_DESC' =>
        'Range from 30 to 64 pixels',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_CORNER_RADIUS_TITLE' =>
        'Button Corner Radius',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_CORNER_RADIUS_DESC' =>
        'Range from 1 to 10 pixels',

    /*
     * Keep existing <br> because Payment Settings uses it.
     */
    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_DISPLAY_TITLE' =>
        'Display the Apple Pay Button on',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_DISPLAY_DESC' =>
        '<br> The selected pages will display the Apple pay button to pay instantly as an express checkout button <br><br>',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_ALLOWED_TITLE' =>
        'Allowed zone(-s)',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_ALLOWED_DESC' =>
        'This payment method will be allowed for the mentioned zone(-s). Enter the zone(-s) in the following format E.g: DE, AT, CH. In case if the field is empty, all the zones will be allowed.',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_PAYMENT_ZONE_TITLE' =>
        'Payment zone',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_PAYMENT_ZONE_DESC' =>
        'If a zone is selected, only enable this payment method for that zone.',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_ENDCUSTOMER_INFO_TITLE' =>
        'Notification for the buyer',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_ENDCUSTOMER_INFO_DESC' =>
        'The entered text will be displayed on the checkout page',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_TEXT_INFO' =>
        'Amount will be booked from your card after successful authentication',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_PUBLIC_TITLE' =>
        xtc_image(
            DIR_WS_ICONS . 'novalnet/novalnet_applepay.png',
            'Applepay'
        ),

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_VISIBILITY_BY_AMOUNT_TITLE' =>
        'Minimum order amount',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_VISIBILITY_BY_AMOUNT_DESC' =>
        'Minimum order amount to display the selected payment method (s) at during checkout (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BUSINESS_NAME_TITLE' =>
        'Business name',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BUSINESS_NAME_DESC' =>
        'The business name is rendered in the Apple Pay payment sheet, and this text will appear as PAY "BUSINESS NAME" so that the customer knows where he is paying to',

    /*
     * Existing Apple Pay option values
     */
    'MODULE_PAYMENT_NOVALNET_APPLEPAY_DISPLAY_CARTPAGE' =>
        'Shopping cart page',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_DISPLAY_PRODUCTPAGE' =>
        'Product page',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_DISPLAY_CHECKOUTPAGE' =>
        'Checkout page',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_PLAIN' =>
        'Plain',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BUY' =>
        'Buy',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_DONATE' =>
        'Donate',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BOOK' =>
        'Book',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_CHECKOUT' =>
        'Checkout',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_ORDER' =>
        'Order',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_SUBSCRIBE' =>
        'Subscribe',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_PAY' =>
        'Pay',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_CONTRIBUTE' =>
        'Contribute',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_TIP' =>
        'Tip',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_RENT' =>
        'Rent',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_RELOAD' =>
        'Reload',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_SUPPORT' =>
        'Support',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_DARK' =>
        'Dark',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_LIGHT' =>
        'Light',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_LIGHT_OUTLINE' =>
        'Light-Outline',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_ESTIMATEDTOTAL_LABEL' =>
        'Estimated total (No offers applied)',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_TOTAL_LABEL' =>
        'Total',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_INCL_TAX_LABEL' =>
        'Incl Tax',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_EXCL_TAX_LABEL' =>
        'Excl Tax',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_DISCOUNT_AND_GIFT_VOUCHER_LABEL' =>
        'Discount',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_SHIPPING_LABEL' =>
        'Shipping',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_PAGES_TEXT' =>
        'Button display pages',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_CONDITION_ERROR' => 
        '* We cannot accept your order if you do not accept our General Business Conditions!',
    'MODULE_PAYMENT_NOVALNET_APPLEPAY_WITHDRAWAL_ERROR' =>
        '* We cannot accept your order if you do not accept our Right of Withdrawal Conditions!', 
        
    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BOTH_ERROR' =>
        '* We cannot accept your order if you do not accept our General Business and Right of Withdrawal Conditions!',
        
    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BILLING_ERROR' => 
        'The selected billing address is incomplete. Please update the address.',  
        
    'MODULE_PAYMENT_NOVALNET_APPLEPAY_SHIPPING_ERROR' => 
        'The selected shipping address is incomplete. Please update the address.',      
        
    /*
     * ==============================================================
     * GXMODULE / MODULE CENTER TRANSLATIONS
     * ==============================================================
     *
     * These keys must NOT contain HTML.
     *
     * GXModule.json should reference them using:
     *
     * novalnet_applepay.KEY
     *
     */


    /*
     * Button Design
     */
    'BUTTON_DESIGN' =>
        'Button Design',

    'BUTTON_DESIGN_DESCRIPTION' =>
        'Style for Apple Pay button',


    /*
     * Button Type
     */
    'BUTTON_TYPE' =>
        'Button Type',

    'BUTTON_TYPE_PLAIN' =>
        'Plain',

    'BUTTON_TYPE_BUY' =>
        'Buy',

    'BUTTON_TYPE_DONATE' =>
        'Donate',

    'BUTTON_TYPE_BOOK' =>
        'Book',

    'BUTTON_TYPE_CHECKOUT' =>
        'Checkout',

    'BUTTON_TYPE_ORDER' =>
        'Order',

    'BUTTON_TYPE_SUBSCRIBE' =>
        'Subscribe',

    'BUTTON_TYPE_PAY' =>
        'Pay',

    'BUTTON_TYPE_CONTRIBUTE' =>
        'Contribute',

    'BUTTON_TYPE_TIP' =>
        'Tip',

    'BUTTON_TYPE_RENT' =>
        'Rent',

    'BUTTON_TYPE_RELOAD' =>
        'Reload',

    'BUTTON_TYPE_SUPPORT' =>
        'Support',


    /*
     * Button Theme
     */
    'BUTTON_THEME' =>
        'Button Theme',

    'BUTTON_THEME_DARK' =>
        'Dark',

    'BUTTON_THEME_LIGHT' =>
        'Light',

    'BUTTON_THEME_LIGHT_OUTLINE' =>
        'Light-Outline',


    /*
     * Button Height
     */
    'BUTTON_HEIGHT' =>
        'Button Height',

    'BUTTON_HEIGHT_DESCRIPTION' =>
        'Range from 30 to 64 pixels',


    /*
     * Button Corner Radius
     */
    'BUTTON_CORNER_RADIUS' =>
        'Button Corner Radius',

    'BUTTON_CORNER_RADIUS_DESCRIPTION' =>
        'Range from 1 to 10 pixels',


    /*
     * Display Button
     */
    'DISPLAY_BUTTON_ON' =>
        'Display the Apple Pay Button on',

    'DISPLAY_BUTTON_ON_DESCRIPTION' =>
        'The selected pages will display the Apple Pay button to pay instantly as an express checkout button',

    'DISPLAY_CART_PAGE' =>
        'Shopping cart page',

    'DISPLAY_PRODUCT_PAGE' =>
        'Product page',

    'DISPLAY_CHECKOUT_PAGE' =>
        'Checkout page',


    /*
     * Other Apple Pay fields
     */
    'ALLOWED_ZONE' =>
        'Allowed zone(-s)',

    'PAYMENT_ZONE' =>
        'Payment zone',

    'ENDCUSTOMER_INFO' =>
        'Notification for the buyer',

    'VISIBILITY_BY_AMOUNT' =>
        'Minimum order amount',

    'BUSINESS_NAME' =>
        'Business name',

    'BUSINESS_NAME_DESCRIPTION' =>
        'The business name is rendered in the Apple Pay payment sheet, and this text will appear as PAY "BUSINESS NAME" so that the customer knows where he is paying to',
);
