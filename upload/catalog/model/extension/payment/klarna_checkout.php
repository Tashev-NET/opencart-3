<?php
/**
 * Class Klarna Checkout
 *
 * @package Catalog\Model\Extension\Payment
 */
use Klarna\Rest\Transport\Connector as KCConnector;
use Klarna\Rest\Transport\ConnectorInterface as KCConnectorInterface;
use Klarna\Rest\Checkout\Order as KCOrder;
class ModelExtensionPaymentKlarnaCheckout extends Model {
	/**
	 * orderCreate
	 */
    public function orderCreate(KCConnector $connector, $order_data) {
        try {
            $checkout = new \KCOrder($connector);
            $checkout->create($order_data);

            return $checkout->fetch();
        } catch (\Exception $e) {
            $this->log($e->getMessage(), 1);

            return false;
        }
    }

	/**
	 * orderRetrieve
	 */
    public function orderRetrieve(KCConnector $connector, $order_id) {
        try {
            $checkout = new \KCOrder($connector, $order_id);

            return $checkout->fetch();
        } catch (\Exception $e) {
            $this->log($e->getMessage(), 1);

            return false;
        }
    }

	/**
	 * orderUpdate
	 */
    public function orderUpdate(KCConnector $connector, $order_id, $order_data) {
        try {
            $checkout = new \KCOrder($connector, $order_id);
            $checkout->update($order_data);

            return $checkout->fetch();
        } catch (\Exception $e) {
            $this->log($e->getMessage(), 1);

            return false;
        }
    }

	/**
	 * omOrderRetrieve
	 */
    public function omOrderRetrieve(KCConnector $connector, $order_id) {
        try {
            $order = new \Klarna\Rest\OrderManagement\Order($connector, $order_id);

            return $order->fetch();
        } catch (\Exception $e) {
            $this->log($e->getMessage(), 1);

            return false;
        }
    }

	/**
	 * getMethod
	 *
	 * @param array $address
	 *
	 * @return array
	 */
    public function getMethod(array $address): array {
        // Not shown in the payment method list
        return [];
    }

	/**
	 * getConnector
	 */
    public function getConnector($accounts, $currency) {
        $klarna_account = false;
        $connector = false;

        if ($accounts && $currency) {
            foreach ($accounts as $account) {
                if ($account['currency'] == $currency) {
                    if ($account['environment'] == 'test') {
                        if ($account['api'] == 'NA') {
                            $base_url = KCConnectorInterface::NA_TEST_BASE_URL;
                        } elseif ($account['api'] == 'EU') {
                            $base_url = KCConnectorInterface::EU_TEST_BASE_URL;
                        }
                    } elseif ($account['environment'] == 'live') {
                        if ($account['api'] == 'NA') {
                            $base_url = KCConnectorInterface::NA_BASE_URL;
                        } elseif ($account['api'] == 'EU') {
                            $base_url = KCConnectorInterface::EU_BASE_URL;
                        }
                    }

                    $klarna_account = $account;
                    $connector = $this->connector($account['merchant_id'], $account['secret'], $base_url);
                    break;
                }
            }
        }

        return [
            $klarna_account,
            $connector
        ];
    }

	/**
	 * getOrder
	 *
	 * @param string $order_ref
	 *
	 * @return array
	 */
    public function getOrder(string $order_ref): array {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "klarna_checkout_order` WHERE `order_ref` = '" . $this->db->escape($order_ref) . "' LIMIT 1");

		return $query->row;
    }

	/**
	 * getOrderByOrderId
	 *
	 * @param int $order_id
	 *
	 * @return array
	 */
    public function getOrderByOrderId(int $order_id): array {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "klarna_checkout_order` WHERE `order_id` = '" . (int)$order_id . "' LIMIT 1");

		return $query->row;
    }

	/**
	 * addOrder
	 *
	 * @param int    $order_id
	 * @param string $order_ref
	 * @param string $data
	 *
	 * @return void
	 */
    public function addOrder(int $order_id, string $order_ref, string $data): void {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "klarna_checkout_order` SET `order_id` = '" . (int)$order_id . "', `order_ref` = '" . $this->db->escape($order_ref) . "', `data` = '" . $this->db->escape($data) . "'");
    }

	/**
	 * updateOrder
	 *
	 * @param int    $order_id
	 * @param string $order_ref
	 * @param string $data
	 *
	 * @return void
	 */
    public function updateOrder(int $order_id, string $order_ref, string $data): void {
        $this->db->query("UPDATE `" . DB_PREFIX . "klarna_checkout_order` SET `order_id` = '" . (int)$order_id . "', `data` = '" . $this->db->escape($data) . "' WHERE `order_ref` = '" . $this->db->escape($order_ref) . "'");
    }

	/**
	 * updateOcOrder
	 *
	 * @param int    $order_id
	 * @param array  $data
	 *
	 * @return void
	 */
    public function updateOcOrder(int $order_id, array $data): void {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET `firstname` = '" . $this->db->escape($data['firstname']) . "', `lastname` = '" . $this->db->escape($data['lastname']) . "', `telephone` = '" . $this->db->escape($data['telephone']) . "', `payment_firstname` = '" . $this->db->escape($data['payment_firstname']) . "', `payment_lastname` = '" . $this->db->escape($data['payment_lastname']) . "', `payment_address_1` = '" . $this->db->escape($data['payment_address_1']) . "', `payment_address_2` = '" . $this->db->escape($data['payment_address_2']) . "', `payment_city` = '" . $this->db->escape($data['payment_city']) . "', `payment_postcode` = '" . $this->db->escape($data['payment_postcode']) . "', `payment_zone` = '" . $this->db->escape($data['payment_zone']) . "', `payment_zone_id` = '" . (int)$data['payment_zone_id'] . "', `payment_country` = '" . $this->db->escape($data['payment_country']) . "', `payment_country_id` = '" . (int)$data['payment_country_id'] . "', `payment_address_format` = '" . $this->db->escape($data['payment_address_format']) . "', `shipping_firstname` = '" . $this->db->escape($data['shipping_firstname']) . "', `shipping_lastname` = '" . $this->db->escape($data['shipping_lastname']) . "', `shipping_address_1` = '" . $this->db->escape($data['shipping_address_1']) . "', `shipping_address_2` = '" . $this->db->escape($data['shipping_address_2']) . "', `shipping_city` = '" . $this->db->escape($data['shipping_city']) . "', `shipping_postcode` = '" . $this->db->escape($data['shipping_postcode']) . "', `shipping_zone` = '" . $this->db->escape($data['shipping_zone']) . "', `shipping_zone_id` = '" . (int)$data['shipping_zone_id'] . "', `shipping_country` = '" . $this->db->escape($data['shipping_country']) . "', `shipping_country_id` = '" . (int)$data['shipping_country_id'] . "', `shipping_address_format` = '" . $this->db->escape($data['shipping_address_format']) . "' WHERE `order_id` = '" . (int)$order_id . "'");
    }

	/**
	 * updateOcOrderEmail
	 *
	 * @param int    $order_id
	 * @param string $email
	 *
	 * @return void
	 */
    public function updateOcOrderEmail(int $order_id, string $email): void {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET `email` = '" . $this->db->escape($email) . "' WHERE `order_id` = '" . (int)$order_id . "'");
    }

	/**
	 * getZoneByCode
	 *
	 * @param string $code
	 * @param int    $country_id
	 *
	 * @return array
	 */
    public function getZoneByCode(string $code, int $country_id): array {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE (`code` = '" . $this->db->escape($code) . "' OR `name` = '" . $this->db->escape($code) . "') AND `country_id` = '" . (int)$country_id . "' AND `status` = '1'");

        return $query->row;
    }

	/**
	 * getCountriesByGeoZone
	 *
	 * @param int $geo_zone_id
	 *
	 * @return array
	 */
    public function getCountriesByGeoZone(int $geo_zone_id): array {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '" . (int)$geo_zone_id . "' GROUP BY `country_id` ORDER BY `country_id` ASC");

        return $query->rows;
    }

	/**
	 * checkForPaymentTaxes
	 *
	 * @param array $products
	 *
	 * @return bool
	 */
    public function checkForPaymentTaxes(array $products = []): bool {
        foreach ($products as $product) {
            $query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "tax_rule` WHERE `based` = 'payment' AND `tax_class_id` = '" . (int)$product['tax_class_id'] . "'");

            if ($query->row['total']) {
                return true;
            }
        }

        return false;
    }

	/**
	 * getDefaultShippingMethod
	 *
	 * @param array $shipping_methods
	 *
	 * @return array
	 */
    public function getDefaultShippingMethod(array $shipping_methods): array {
        $first_shipping_method = reset($shipping_methods);

        if ($first_shipping_method && isset($first_shipping_method['quote'])) {
            $first_shipping_method_quote = reset($first_shipping_method['quote']);

            if ($first_shipping_method_quote) {
                $shipping = explode('.', $first_shipping_method_quote['code']);

                return $shipping_methods[$shipping[0]]['quote'][$shipping[1]];
            }
        }

        return [];
    }

	/**
	 * Log
	 *
	 * @param string $data
	 * @param int    $step
	 *
	 * @return void
	 */
    public function log(string $data, int $step = 6): void {
        if ($this->config->get('payment_klarna_checkout_debug')) {
            // Log
            $log = new \Log('klarna_checkout.log');
            $backtrace = debug_backtrace();
            $log->write('(' . $backtrace[$step]['class'] . '::' . $backtrace[$step]['function'] . ') - ' . print_r($data, true));
        }
    }

	/**
	 * subscribeNewsletter
	 *
	 * @param int $customer_id
	 *
	 * @return void
	 */
    public function subscribeNewsletter(int $customer_id): void {
        $this->db->query("UPDATE `" . DB_PREFIX . "customer` SET `newsletter` = '1' WHERE `customer_id` = '" . (int)$customer_id . "'");
    }

	/**
	 * getTotals
	 *
	 * @return array
	 */
    public function getTotals(): array {
        $taxes = $this->cart->getTaxes();
        $total = 0;
        $totals = [];

        // Because __call can not keep var references, so we put them into an array.
        $total_data = [
            'totals' => &$totals,
            'taxes'  => &$taxes,
            'total'  => &$total
        ];

        // Extensions
        $this->load->model('setting/extension');

        $sort_order = [];

        $results = $this->model_setting_extension->getExtensionsByType('total');

        foreach ($results as $key => $value) {
            $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
        }

        array_multisort($sort_order, SORT_ASC, $results);

        foreach ($results as $result) {
            if ($this->config->get('total_' . $result['code'] . '_status')) {
                $this->load->model('extension/total/' . $result['code']);

                // We have to put the totals in an array so that they pass by reference.
                $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
            }
        }

        $sort_order = [];

        foreach ($totals as $key => $value) {
            $sort_order[$key] = $value['sort_order'];
        }

        array_multisort($sort_order, SORT_ASC, $totals);

        return [
            $totals,
            $taxes,
            $total
        ];
    }

    private function connector($merchant_id, $secret, $url) {
        try {
            $connector = KCConnector::create($merchant_id, $secret, $url);

            return $connector;
        } catch (\Exception $e) {
            $this->log($e->getMessage(), 1);

            return false;
        }
    }
}
