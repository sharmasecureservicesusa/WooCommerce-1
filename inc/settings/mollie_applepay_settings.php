<?php
$pluginName = Mollie_WC_Plugin::PLUGIN_ID;
$gateway = new Mollie_WC_Gateway_Applepay();
$title = $gateway->method_title;
$description = $gateway->method_description;
$pluginId = Mollie_WC_Plugin::PLUGIN_ID;
$applePayOption = get_option('mollie_wc_gateway_applepay_settings');

return [
    [
        'id'    => $title . '_' .'title',
        'title' => __($title, 'mollie-payments-for-woocommerce'),
        'type'  => 'title',
        'desc'  => '<p>' . __('The following options are required to use the Apple Pay gateway', 'mollie-payments-for-woocommerce') . '</p>',
    ],

    [
        'id'                =>'enabled',
        'title'             => __('Enable/Disable', 'mollie-payments-for-woocommerce'),
        /* translators: Placeholder 1: enabled or disabled */
        'desc'              => sprintf(__('Enable %s', 'mollie-payments-for-woocommerce'), $title),
        'type'              => 'checkbox',
        'default'           =>  'yes',
        'value'             => $applePayOption['enabled']

    ],
    [
        'id'                => 'title',
        'title'             => __('Title', 'mollie-payments-for-woocommerce'),
        /* translators: Placeholder 1: enabled or disabled */
        'desc'              => sprintf(
            __(
                'This controls the title which the user sees during checkout. Default <code>%s</code>',
                'mollie-payments-for-woocommerce'
            ),
            $title),
        'desc_tip' => true,
        'type'              => 'text',
        'default'           =>  $title,
        'value'             => $applePayOption['title']

    ],
    [
        'id'                => 'display_logo',
        'title'             => __('Display logo', 'mollie-payments-for-woocommerce'),
        /* translators: Placeholder 1: enabled or disabled */
        'desc'              => sprintf(
            __(
                'Display logo',
                'mollie-payments-for-woocommerce'
            )),
        'desc_tip'          => true,
        'type'              => 'checkbox',
        'default'           => 'yes',
        'value'             => $applePayOption['display_logo']

    ],
    [
        'id'                => 'description',
        'title'             => __('Description', 'mollie-payments-for-woocommerce'),
        /* translators: Placeholder 1: enabled or disabled */
        'desc'              => sprintf(
            __(
                'Payment method description that the customer will see on your checkout. Default <code>%s</code>',
                'mollie-payments-for-woocommerce'
            ),
            $title),
        'desc_tip'          => true,
        'type'              => 'text',
        'default'           => $description,
        'value'             => $applePayOption['description']
    ],
    [
        'id' => $pluginId . '_' . 'sectionend',
        'type' => 'sectionend',
    ],
    [
        'id'    => $title . '_' .'title_button',
        'title' =>  __(
            'Apple Pay button settings',
            'mollie-payments-for-woocommerce'
        ),
        'type'  => 'title',
        'desc'  => '<p>' . __('The following options are required to use the Apple Pay Direct Button', 'mollie-payments-for-woocommerce') . '</p>',
    ],
    [
        'id'                => 'mollie_apple_pay_button_enabled',
        'title'             => __('Enable Apple Pay Button', 'mollie-payments-for-woocommerce'),
        /* translators: Placeholder 1: enabled or disabled */
        'desc'              => sprintf(
            __(
                'Enable the Apple Pay direct buy button',
                'mollie-payments-for-woocommerce'
            ),
            $description),
        'type'              => 'checkbox',
        'default'           => 'no',
        'value'             => $applePayOption['mollie_apple_pay_button_enabled']

    ],
    [
        'id'   => $pluginName . '_' .'sectionend',
        'type' => 'sectionend',
    ]
];

