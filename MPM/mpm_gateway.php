<?php
/**
 * Copyright (c) 2014, Mollie B.V.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS ``AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE.
 */

class MPM_Gateway extends WC_Payment_Gateway
{
	/**
	 * Does this payment method have any extra fields? Only iDEAL has extra fields.
	 *
	 * @var bool
	 */
	public $has_fields = FALSE;

	protected $_data = null;

	/**
	 * @var Mollie_API_Object_Issuer[]
	 */
	protected $issuers = array();

	public function __construct()
	{
		// Register this method with MPM_Settings
		/** @var MPM_Settings $mpm */
		global $mpm;
		$methods = $mpm->get_methods();
		if ($mpm->count >= count($methods))
		{
			$mpm->count = 0;
		}
		$this->method_index = $mpm->count++;
		$this->_data = $methods[$this->method_index];

		// Assign ids and titles
		$this->id = $this->_data->id;
		$this->method_description = $this->_data->description;
		$this->method_title = $this->_data->description;
		$this->title = __($this->_data->description, 'MPM'); // translate visual title

		// Define issuers (if any)
		$this->issuers = $mpm->get_issuers($this->id);

		if (!empty($this->issuers))
		{
			$this->has_fields = TRUE;
		}

		// Assign image
		if (isset($this->_data->image) && $mpm->get_option('show_images', 'no') !== 'no')
		{
			$this->icon = $this->_data->image->normal;
		}

		// Initialise
		$this->init_form_fields();
		$this->init_settings();
	}

	/**
	 * It seems this option is mandatory for a (visible) gateway
	 * @return void
	 */
	public function init_form_fields()
	{
		$this->form_fields = array(
			'enabled' => array(
				'default' => 'yes',
			),
		);
	}

	/**
	 * Generates a bank list for iDeal payments
	 * @return void
	 */
	public function payment_fields()
	{
		if (!$this->has_fields)
		{
			return;
		}
		echo '<select name="mpm_issuer_' . $this->id . '">';
		echo '<option value="">' . __('Select your bank:', 'MPM') . '</option>';
		foreach ($this->issuers as $issuer)
		{
			echo '<option value="' . htmlspecialchars($issuer->id) . '">' . htmlspecialchars($issuer->name) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Sends a payment request to Mollie, redirects the user to the payscreen.
	 * @param int $order_id
	 * @return array|void
	 */
	public function process_payment($order_id)
	{
		/** @var MPM_Settings $mpm */
		global $mpm, $woocommerce;
		$order = $mpm->order_get($order_id, null, true);
		if ($order === FALSE)
		{
			if (defined('WP_DEBUG') && WP_DEBUG)
			{
				$woocommerce->add_error(__('Could not create payment.', 'MPM') . ' Reason: invalid order ID');
			}
			else
			{
				$woocommerce->add_error(__('Could not create payment.', 'MPM'));
			}
			return array('result' => 'failure');
		}

		$order->update_status('pending', __('Awaiting payment confirmation', 'MPM'));

		$webhook = admin_url('admin-ajax.php') . '?action=mollie_webhook';
                
                $return_url = $order->get_checkout_order_received_url();
                
		$data = array(
			"amount"			=> $order->get_total(),
			"description"		=> str_replace('%', $order->get_order_number(), $mpm->get_option('description', 'Order %')),
			"redirectUrl"		=> $return_url,
			"method"			=> $this->id,
			"issuer"			=> empty($_POST["mpm_issuer_" . $this->id]) ? null : $_POST["mpm_issuer_" . $this->id],
			"metadata"			=> array(
				"order_id"		=> $order_id,
			),
		);

		if (filter_var($webhook, FILTER_VALIDATE_URL) && $mpm->get_option('use_profile_webhook', 'no') === 'no')
		{
			$data['webhookUrl'] = $webhook;
		}


		if (isset($order->billing_city))
		{
			$data['billingCity'] = $order->billing_city;
		}
		if (isset($order->billing_state))
		{
			$data['billingRegion'] = $order->billing_state;
		}
		if (isset($order->billing_postcode))
		{
			$data['billingPostal'] = $order->billing_postcode;
		}
		if (isset($order->billing_country))
		{
			$data['billingCountry'] = $order->billing_country;
		}


		if (isset($order->shipping_city))
		{
			$data['shippingCity'] = $order->shipping_city;
		}
		if (isset($order->shipping_state))
		{
			$data['shippingRegion'] = $order->shipping_state;
		}
		if (isset($order->shipping_postcode))
		{
			$data['shippingPostal'] = $order->shipping_postcode;
		}
		if (isset($order->shipping_country))
		{
			$data['shippingCountry'] = $order->shipping_country;
		}

		try
		{
			$payment = $mpm->get_api()->payments->create($data);
		}
		catch (Mollie_API_Exception $e)
		{
			if (defined('WP_DEBUG') && WP_DEBUG)
			{
				$woocommerce->add_error(__('Could not create payment.', 'MPM') . ' Reason: ' . $e->getMessage());
			}
			else
			{
				$woocommerce->add_error(__('Could not create payment.', 'MPM'));
			}

			return array('result' => 'failure');
		}

		add_post_meta($order_id, '_is_mollie_payment', TRUE, TRUE);
		add_post_meta($order_id, '_mollie_transaction_id', $payment->id, TRUE);

		return array(
			'result' => 'success',
			'redirect' => $payment->getPaymentUrl(),
		);
	}
}
