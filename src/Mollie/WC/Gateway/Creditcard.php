<?php

use Mollie\Api\Types\PaymentMethod;

class Mollie_WC_Gateway_Creditcard extends Mollie_WC_Gateway_AbstractSubscription
{
    public function __construct()
    {
        parent::__construct();

        $this->supports = [
            'products',
            'refunds',
        ];

        $this->initSubscriptionSupport();

        $this->hasFieldsIfMollieComponentsIsEnabled();
    }

    /**
     * @inheritDoc
     */
    public function getMollieMethodId()
    {
        return PaymentMethod::CREDITCARD;
    }

    /**
     * @inheritDoc
     */
    public function getDefaultTitle()
    {
        return __('Credit card', 'mollie-payments-for-woocommerce');
    }

    /**
     * @inheritDoc
     */
    public function init_form_fields()
    {
        parent::init_form_fields();

        $this->includeMollieComponentsFields();
        $this->includeCreditCardIconSelector();
    }

    /**
     * @inheritDoc
     */
    public function payment_fields()
    {
        parent::payment_fields();

        $this->mollieComponentsFields();
    }

    /**
     * @inheritDoc
     */
    protected function getSettingsDescription()
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultDescription()
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    protected function getInstructions(
        WC_Order $order,
        Mollie\Api\Resources\Payment $payment,
        $admin_instructions,
        $plain_text
    ) {
        if ($payment->isPaid() && $payment->details) {
            return sprintf(
            /* translators: Placeholder 1: card holder */
                __('Payment completed by <strong>%s</strong>', 'mollie-payments-for-woocommerce'),
                $payment->details->cardHolder
            );
        }

        return parent::getInstructions($order, $payment, $admin_instructions, $plain_text);
    }

    /**
     * Include the credit card icon selector customization in the credit card
     * settings page
     */
    protected function includeCreditCardIconSelector()
    {
        $fields = include Mollie_WC_Plugin::getPluginPath(
            '/inc/settings/mollie_creditcard_icons_selector.php'
        );

        $this->form_fields = array_merge($this->form_fields, $fields);
    }

    /**
     * @return array Array containing the credit cards names enabled in settings
     *               to make customization of checkout icons
     */
    protected function enabledCreditcards()
    {
        $creditCardSettings = get_option('mollie_wc_gateway_creditcard_settings', false) ?: [];
        $enabled = isset($creditCardSettings[Mollie_WC_Helper_PaymentMethodsIconUrl::MOLLIE_CREDITCARD_ICONS_ENABLER])
            ? wc_string_to_bool($creditCardSettings[Mollie_WC_Helper_PaymentMethodsIconUrl::MOLLIE_CREDITCARD_ICONS_ENABLER])
            : false;

        if (!$enabled) {
            return [];
        }
        $optionLexem = Mollie_WC_Helper_PaymentMethodsIconUrl::MOLLIE_CREDITCARD_ICONS;
        $creditcardsAvailable = Mollie_WC_Helper_PaymentMethodsIconUrl::AVAILABLE_CREDITCARD_ICONS;
        $svgFileName = Mollie_WC_Helper_PaymentMethodsIconUrl::SVG_FILE_EXTENSION;
        $enabledCreditcards = [];

        $creditcardSettings = get_option('mollie_wc_gateway_creditcard_settings');
        foreach ($creditcardsAvailable as $card) {
            if ($creditcardSettings[$optionLexem . $card] === 'yes') {
                $enabledCreditcards[] = $card . $svgFileName;
            }
        }

        return $enabledCreditcards;
    }

    /**
     *
     * @return string Newly composed svg string
     */
    public function buildSvgComposed()
    {
        $enabledCreditCards = $this->enabledCreditcards();
        $assetsImagesPath
            = '/app/public/wp-content/plugins/WooCommerce-Mollie/public/images/Creditcard_issuers/';
        $cardWidth = Mollie_WC_Helper_PaymentMethodsIconUrl::CREDIT_CARD_ICON_WIDTH;
        $cardsNumber = count($enabledCreditCards);
        $cardsWidth = $cardWidth * $cardsNumber;
        $cardPositionX = 0;
        $actual
            = "<svg width=\"{$cardsWidth}\" height=\"24\" style=\"float:right\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\">";
        foreach ($enabledCreditCards as $creditCard) {
            $actual .= $this->positionSvgOnX(
                $cardPositionX,
                file_get_contents(
                    $assetsImagesPath . $creditCard
                )
            );
            $cardPositionX += $cardWidth;
        }
        $actual .= "</svg>";

        return $actual;
    }

    /**
     * Method to add the x parameter to the svg string so that the icon can
     * be positioned related to other icons.
     *
     * @param int    $xPosition coordinate to position icon on x axis
     * @param string $svgString svg string to add position to
     *
     * @return string|string[] Modified svg string with the x position added
     */
    protected function positionSvgOnX($xPosition, $svgString)
    {
        $positionString = " x=\"{$xPosition}\"";
        $positionAfterSvgWord = 4;

        return substr_replace($svgString, $positionString, $positionAfterSvgWord, 0);
    }
}
