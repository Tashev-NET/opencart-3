<?php
class ControllerExtensionPaymentPPStandard extends Controller {
    public function index(): string {
        if (!isset($this->session->data['order_id'])) {
            return false;
        }

        $this->load->language('extension/payment/pp_standard');

        $data['testmode'] = $this->config->get('payment_pp_standard_test');

        if (!$this->config->get('payment_pp_standard_test')) {
            $data['action'] = 'https://www.paypal.com/cgi-bin/webscr&pal=V4T754QB63XXL';
        } else {
            $data['action'] = 'https://www.sandbox.paypal.com/cgi-bin/webscr&pal=V4T754QB63XXL';
        }

        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        if ($order_info) {
            $data['business'] = $this->config->get('payment_pp_standard_email');
            $data['item_name'] = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');

            $data['products'] = [];

            foreach ($this->cart->getProducts() as $product) {
                $option_data = [];

                foreach ($product['option'] as $option) {
                    if ($option['type'] != 'file') {
                        $value = $option['value'];
                    } else {
                        $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

                        if ($upload_info) {
                            $value = $upload_info['name'];
                        } else {
                            $value = '';
                        }
                    }

                    $option_data[] = [
                        'name'  => (oc_strlen($option['name']) > 64 ? oc_substr($option['name'], 0, 62) . '..' : $option['name']),
                        'value' => (oc_strlen($value) > 20 ? oc_substr($value, 0, 20) . '..' : $value)
                    ];
                }

                $data['products'][] = [
                    'name'     => htmlspecialchars($product['name']),
                    'model'    => htmlspecialchars($product['model']),
                    'price'    => $this->currency->format($product['price'], $order_info['currency_code'], false, false),
                    'quantity' => $product['quantity'],
                    'option'   => $option_data,
                    'weight'   => $product['weight']
                ];
            }

            $data['discount_amount_cart'] = 0;

            $total = $this->currency->format($order_info['total'] - $this->cart->getSubTotal(), $order_info['currency_code'], false, false);

            if ($total > 0) {
                $data['products'][] = [
                    'name'     => $this->language->get('text_total'),
                    'model'    => '',
                    'price'    => $total,
                    'quantity' => 1,
                    'option'   => [],
                    'weight'   => 0
                ];
            } else {
                $data['discount_amount_cart'] -= $total;
            }

            $ship_to_state_codes = [
                'BR',
                // Brazil
                'CA',
                // Canada
                'IT',
                // Italy
                'MX',
                // Mexico
                'US'
                // USA
            ];

            if ($this->cart->hasShipping()) {
                if (in_array($order_info['shipping_iso_code_2'], $ship_to_state_codes)) {
                    $data['state'] = $order_info['shipping_zone_code'];
                } else {
                    $data['state'] = $order_info['shipping_zone'];
                }

                $data['no_shipping'] = 2;
                $data['address_override'] = 1;
                $data['first_name'] = $order_info['shipping_firstname'];
                $data['last_name'] = $order_info['shipping_lastname'];
                $data['address1'] = $order_info['shipping_address_1'];
                $data['address2'] = $order_info['shipping_address_2'];
                $data['city'] = $order_info['shipping_city'];
                $data['zip'] = $order_info['shipping_postcode'];
                $data['country'] = $order_info['shipping_iso_code_2'];
            } else {
                $data['no_shipping'] = 1;
                $data['address_override'] = 0;
                $data['first_name'] = $order_info['payment_firstname'];
                $data['last_name'] = $order_info['payment_lastname'];
                $data['address1'] = $order_info['payment_address_1'];
                $data['address2'] = $order_info['payment_address_2'];
                $data['city'] = $order_info['payment_city'];

                if (in_array($order_info['payment_iso_code_2'], $ship_to_state_codes)) {
                    $data['state'] = $order_info['payment_zone_code'];
                } else {
                    $data['state'] = $order_info['payment_zone'];
                }

                $data['zip'] = $order_info['payment_postcode'];
                $data['country'] = $order_info['payment_iso_code_2'];
            }

            $data['currency_code'] = $order_info['currency_code'];
            $data['email'] = $order_info['email'];
            $data['invoice'] = $this->session->data['order_id'] . ' - ' . $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'];
            $data['lc'] = $this->config->get('config_language');
            $data['return'] = $this->url->link('checkout/success');
            $data['notify_url'] = $this->url->link('extension/payment/pp_standard/callback', '', true);
            $data['cancel_return'] = $this->url->link('checkout/checkout', '', true);

            if (!$this->config->get('payment_pp_standard_transaction')) {
                $data['paymentaction'] = 'authorization';
            } else {
                $data['paymentaction'] = 'sale';
            }

            $data['custom'] = (int)$this->session->data['order_id'];

            return $this->load->view('extension/payment/pp_standard', $data);
        } else {
            return '';
        }
    }

    public function callback(): void {
        if (isset($this->request->post['custom'])) {
            $order_id = (int)$this->request->post['custom'];
        } else {
            $order_id = 0;
        }

        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($order_id);

        if ($order_info) {
            $request = 'cmd=_notify-validate';

            foreach ($this->request->post as $key => $value) {
                $request .= '&' . $key . '=' . urlencode(html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
            }

            if (!$this->config->get('payment_pp_standard_test')) {
                $curl = curl_init('https://www.paypal.com/cgi-bin/webscr');
            } else {
                $curl = curl_init('https://www.sandbox.paypal.com/cgi-bin/webscr');
            }

            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($curl);

            if (!$response) {
                $this->log->write('PP_STANDARD :: CURL failed ' . curl_error($curl) . '(' . curl_errno($curl) . ')');
            }

            if ($this->config->get('payment_pp_standard_debug')) {
                $this->log->write('PP_STANDARD :: IPN REQUEST: ' . $request);
                $this->log->write('PP_STANDARD :: IPN RESPONSE: ' . $response);
            }

            if ((strcmp($response, 'VERIFIED') == 0 || strcmp($response, 'UNVERIFIED') == 0) && isset($this->request->post['payment_status'])) {
                $order_status_id = $this->config->get('config_order_status_id');

                switch ($this->request->post['payment_status']) {
                    case 'Canceled_Reversal':
                        $order_status_id = $this->config->get('payment_pp_standard_canceled_reversal_status_id');
                        break;
                    case 'Completed':
                        $receiver_match = (strtolower($this->request->post['receiver_email']) == strtolower($this->config->get('payment_pp_standard_email')));
                        $total_paid_match = ((float)$this->request->post['mc_gross'] == $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false));

                        if ($receiver_match && $total_paid_match) {
                            $order_status_id = $this->config->get('payment_pp_standard_completed_status_id');
                        }

                        if (!$receiver_match) {
                            $this->log->write('PP_STANDARD :: RECEIVER EMAIL MISMATCH! ' . strtolower($this->request->post['receiver_email']));
                        }

                        if (!$total_paid_match) {
                            $this->log->write('PP_STANDARD :: TOTAL PAID MISMATCH! ' . $this->request->post['mc_gross']);
                        }
                        break;
                    case 'Denied':
                        $order_status_id = $this->config->get('payment_pp_standard_denied_status_id');
                        break;
                    case 'Expired':
                        $order_status_id = $this->config->get('payment_pp_standard_expired_status_id');
                        break;
                    case 'Failed':
                        $order_status_id = $this->config->get('payment_pp_standard_failed_status_id');
                        break;
                    case 'Pending':
                        $order_status_id = $this->config->get('payment_pp_standard_pending_status_id');
                        break;
                    case 'Processed':
                        $order_status_id = $this->config->get('payment_pp_standard_processed_status_id');
                        break;
                    case 'Refunded':
                        $order_status_id = $this->config->get('payment_pp_standard_refunded_status_id');
                        break;
                    case 'Reversed':
                        $order_status_id = $this->config->get('payment_pp_standard_reversed_status_id');
                        break;
                    case 'Voided':
                        $order_status_id = $this->config->get('payment_pp_standard_voided_status_id');
                        break;
                }

                $this->model_checkout_order->addOrderHistory($order_id, $order_status_id);
            } else {
                $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('config_order_status_id'));
            }

            curl_close($curl);
        }
    }
}