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
        'Die Zahlung wird im Testmodus verarbeitet, daher wird der Betrag für diese Transaktion nicht berechnet.',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_STATUS_TITLE' =>
        'Aktivieren der Zahlungsmethode',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BLOCK_TITLE' =>
        'Applepay-Konfiguration',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_STATUS_DESC' =>
        'Möchten Sie Applepay-Zahlungen akzeptieren?',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_TEST_MODE_TITLE' =>
        'Testmodus aktivieren',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_TEST_MODE_DESC' =>
        'Die Zahlung wird im Testmodus verarbeitet, daher wird der Betrag für diese Transaktion nicht berechnet.',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_ORDER_STATUS_TITLE' =>
        'Status für erfolgreichen Auftragsabschluss',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_ORDER_STATUS_DESC' =>
        'Status, der für erfolgreiche Bestellungen zu verwenden ist',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_SORT_ORDER_TITLE' =>
        'Sortierreihenfolge anzeigen',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_SORT_ORDER_DESC' =>
        'Sortierreihenfolge anzeigen; der niedrigste Wert wird zuerst angezeigt.',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_AUTHENTICATE_TITLE' =>
        'Aktion für vom Besteller autorisierte Zahlungen',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_AUTHENTICATE_DESC' =>
        'Wählen Sie, ob die Zahlung sofort belastet werden soll oder nicht.',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_MANUAL_CHECK_LIMIT_TITLE' =>
        'Mindesttransaktionsbetrag für die Autorisierung',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_MANUAL_CHECK_LIMIT_DESC' =>
        'Übersteigt der Bestellbetrag das genannte Limit, wird die Transaktion, bis zu ihrer Bestätigung durch Sie, auf on hold gesetzt. Sie können das Feld leer lassen, wenn Sie möchten, dass alle Transaktionen als on hold behandelt werden. (in der kleinsten Währungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR).',


    /*
     * Payment Settings HTML - keep unchanged
     */
    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_TYPE_TITLE' =>
        'Button-Design',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_TYPE_DESC' =>
        'Stil für Apple Pay Schaltfläche',    

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_THEME_TITLE' =>
        'Button-Farbe',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_THEME_DESC' =>
    '',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_HEIGHT_TITLE' =>
        'Button-Höhe',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_HEIGHT_DESC' =>
        'zwischen 30 und 64 Pixel',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_CORNER_RADIUS_TITLE' =>
        'Abrundungsgrad der Ecken des Buttons',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_CORNER_RADIUS_DESC' =>
        'zwischen 1 und 10 Pixel',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_DISPLAY_TITLE' =>
        'Apple Pay-Button anzeigen auf',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_DISPLAY_DESC' =>
        'Auf den ausgewählten Seiten wird die Apple-Pay-Schaltfläche zur sofortigen Bezahlung als Express-Checkout-Schaltfläche angezeigt <br><br>',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_ALLOWED_TITLE' =>
        'Erlaubte Zone(n)',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_ALLOWED_DESC' =>
        'Diese Zahlungsmethode ist nur für die angegebene(n) Zone(n) zulässig. Geben Sie die Zone(n) in folgendem Format ein, z.B: DE, AT, CH. Wenn das Feld leer ist, werden alle Zonen zugelassen.',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_PAYMENT_ZONE_TITLE' =>
        'Zahlungsbereich',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_PAYMENT_ZONE_DESC' =>
        'Wenn eine Zone ausgewählt ist, aktivieren Sie diese Zahlungsmethode nur für diese Zone.',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_ENDCUSTOMER_INFO_TITLE' =>
        'Benachrichtigung des Käufers',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_ENDCUSTOMER_INFO_DESC' =>
        'Der eingegebene Text wird auf der Kassenseite angezeigt',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_TEXT_INFO' =>
        'Der Betrag wird nach erfolgreicher Authentifizierung von Ihrer Karte abgebucht',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_PUBLIC_TITLE' =>
        xtc_image(
            DIR_WS_ICONS . 'novalnet/novalnet_applepay.png',
            'Applepay'
        ),

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_VISIBILITY_BY_AMOUNT_TITLE' =>
        'Mindestbestellbetrag',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_VISIBILITY_BY_AMOUNT_DESC' =>
        'Mindestbestellsumme zur Anzeige der ausgewählten Zahlungsart(en) im Checkout (in der kleinsten Währungseinheit, z.B. 100 Cent = entsprechen 1.00 EUR)',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BUSINESS_NAME_TITLE' =>
        'Name des Geschäfts',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BUSINESS_NAME_DESC' =>
        'Der Name des Geschäfts wird in den Zahlungsbeleg von Apple Pay eingefügt und der Text wird als PAY Name des Geschäfts angezeigt, so dass der Endkunde weiß, an wen er zahlt.',


    /*
     * Existing Apple Pay option translations
     */
    'MODULE_PAYMENT_NOVALNET_APPLEPAY_DISPLAY_CARTPAGE' =>
        'Warenkorb',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_DISPLAY_PRODUCTPAGE' =>
        'Produktseite',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_DISPLAY_CHECKOUTPAGE' =>
        'Checkout-Seite',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_PLAIN' =>
        'Einfach',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BUY' =>
        'Kaufen',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_DONATE' =>
        'Spenden',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BOOK' =>
        'Buchen',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_CHECKOUT' =>
        'Bezahlen',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_ORDER' =>
        'Bestellen',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_SUBSCRIBE' =>
        'Abonnieren',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_PAY' =>
        'Bezahlen',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_CONTRIBUTE' =>
        'Beitragen',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_TIP' =>
        'Trinkgeld',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_RENT' =>
        'Mieten',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_RELOAD' =>
        'Aufladen',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_SUPPORT' =>
        'Unterstützen',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_DARK' =>
        'Dunkel',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_LIGHT' =>
        'Hell',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_LIGHT_OUTLINE' =>
        'An Hintergrund anpassen',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_ESTIMATEDTOTAL_LABEL' =>
        'Geschätzter Gesamtbetrag (Keine Angebote angewendet)',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_TOTAL_LABEL' =>
        'Insgesamt',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_INCL_TAX_LABEL' =>
        'Inkl Steuer',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_EXCL_TAX_LABEL' =>
        'Exkl Steuer',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_DISCOUNT_AND_GIFT_VOUCHER_LABEL' =>
        'Rabatt',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_SHIPPING_LABEL' =>
        'Versand',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BUTTON_PAGES_TEXT' =>
        'Button-Anzeigeseiten',
        
    'MODULE_PAYMENT_NOVALNET_APPLEPAY_CONDITION_ERROR' => 
        '* Sofern Sie unsere Allgemeinen Geschäftsbedingungen nicht akzeptieren, können wir Ihre Bestellung leider nicht entgegennehmen!',
    'MODULE_PAYMENT_NOVALNET_APPLEPAY_WITHDRAWAL_ERROR' =>
        '* Sofern Sie unser Widerrufsrecht nicht akzeptieren, können wir Ihre Bestellung leider nicht entgegennehmen!', 
        
    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BOTH_ERROR' =>
        '* Sofern Sie unsere Allgemeinen Geschäftsbedingungen und unser Widerrufsrecht nicht akzeptieren, können wir Ihre Bestellung leider nicht entgegennehmen!',

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_BILLING_ERROR' => 
        'Die ausgewählte Rechnungsadresse ist unvollständig. Bitte aktualisieren Sie die Adresse.',   

    'MODULE_PAYMENT_NOVALNET_APPLEPAY_SHIPPING_ERROR' =>
        'Die ausgewählte Lieferadresse ist unvollständig. Bitte aktualisieren Sie die Adresse.',    

    /*    
    /*
     * ==============================================================
     * GXMODULE / MODULE CENTER TRANSLATIONS
     * ==============================================================
     */

    'BUTTON_DESIGN' =>
        'Button-Design',

    'BUTTON_DESIGN_DESCRIPTION' =>
        'Stil für Apple Pay Schaltfläche',


    /*
     * Button Type
     */
    'BUTTON_TYPE' =>
        'Button-Typ',

    'BUTTON_TYPE_PLAIN' =>
        'Einfach',

    'BUTTON_TYPE_BUY' =>
        'Kaufen',

    'BUTTON_TYPE_DONATE' =>
        'Spenden',

    'BUTTON_TYPE_BOOK' =>
        'Buchen',

    'BUTTON_TYPE_CHECKOUT' =>
        'Bezahlen',

    'BUTTON_TYPE_ORDER' =>
        'Bestellen',

    'BUTTON_TYPE_SUBSCRIBE' =>
        'Abonnieren',

    'BUTTON_TYPE_PAY' =>
        'Bezahlen',

    'BUTTON_TYPE_CONTRIBUTE' =>
        'Beitragen',

    'BUTTON_TYPE_TIP' =>
        'Trinkgeld',

    'BUTTON_TYPE_RENT' =>
        'Mieten',

    'BUTTON_TYPE_RELOAD' =>
        'Aufladen',

    'BUTTON_TYPE_SUPPORT' =>
        'Unterstützen',


    /*
     * Button Theme
     */
    'BUTTON_THEME' =>
        'Button-Farbe',

    'BUTTON_THEME_DARK' =>
        'Dunkel',

    'BUTTON_THEME_LIGHT' =>
        'Hell',

    'BUTTON_THEME_LIGHT_OUTLINE' =>
        'An Hintergrund anpassen',


    /*
     * Button Height
     */
    'BUTTON_HEIGHT' =>
        'Button-Höhe',

    'BUTTON_HEIGHT_DESCRIPTION' =>
        'zwischen 30 und 64 Pixel',


    /*
     * Button Corner Radius
     */
    'BUTTON_CORNER_RADIUS' =>
        'Abrundungsgrad der Ecken des Buttons',

    'BUTTON_CORNER_RADIUS_DESCRIPTION' =>
        'zwischen 1 und 10 Pixel',


    /*
     * Display Button
     */
    'DISPLAY_BUTTON_ON' =>
        'Apple Pay-Button anzeigen auf',

    'DISPLAY_BUTTON_ON_DESCRIPTION' =>
        'Auf den ausgewählten Seiten wird die Apple-Pay-Schaltfläche zur sofortigen Bezahlung als Express-Checkout-Schaltfläche angezeigt',

    'DISPLAY_CART_PAGE' =>
        'Warenkorb',

    'DISPLAY_PRODUCT_PAGE' =>
        'Produktseite',

    'DISPLAY_CHECKOUT_PAGE' =>
        'Checkout-Seite',


    /*
     * Other Apple Pay fields
     */
    'ALLOWED_ZONE' =>
        'Erlaubte Zone(n)',

    'PAYMENT_ZONE' =>
        'Zahlungsbereich',

    'ENDCUSTOMER_INFO' =>
        'Benachrichtigung des Käufers',

    'VISIBILITY_BY_AMOUNT' =>
        'Mindestbestellbetrag',

    'BUSINESS_NAME' =>
        'Name des Geschäfts',

    'BUSINESS_NAME_DESCRIPTION' =>
        'Der Name des Geschäfts wird in den Zahlungsbeleg von Apple Pay eingefügt und der Text wird als PAY Name des Geschäfts angezeigt, so dass der Endkunde weiß, an wen er zahlt.',
);
