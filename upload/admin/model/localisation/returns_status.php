<?php
/**
 * Class Returns Status
 *
 * @package Admin\Model\Localisation
 */
class ModelLocalisationReturnsStatus extends Model {
	/**
	 * Add Return Status
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return ?int
	 */
	public function addReturnStatus(array $data): ?int {
		$return_status_id = 0;

		foreach ($data['return_status'] as $language_id => $value) {
			if (!$return_status_id) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "return_status` SET `language_id` = '" . (int)$language_id . "', `name` = '" . $this->db->escape($value['name']) . "'");

				$return_status_id = $this->db->getLastId();
			} else {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "return_status` SET `return_status_id` = '" . (int)$return_status_id . "', `language_id` = '" . (int)$language_id . "', `name` = '" . $this->db->escape($value['name']) . "'");
			}
		}

		$this->cache->delete('return_status');

		return $return_status_id;
	}

	/**
	 * Edit Return Status
	 *
	 * @param int                  $return_status_id
	 * @param array<string, mixed> $data
	 *
	 * @return void
	 */
	public function editReturnStatus(int $return_status_id, array $data): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "return_status` WHERE `return_status_id` = '" . (int)$return_status_id . "'");

		foreach ($data['return_status'] as $language_id => $value) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "return_status` SET `return_status_id` = '" . (int)$return_status_id . "', `language_id` = '" . (int)$language_id . "', `name` = '" . $this->db->escape($value['name']) . "'");
		}

		$this->cache->delete('return_status');
	}

	/**
	 * Delete Return Status
	 *
	 * @param int $return_status_id
	 *
	 * @return void
	 */
	public function deleteReturnStatus(int $return_status_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "return_status` WHERE `return_status_id` = '" . (int)$return_status_id . "'");

		$this->cache->delete('return_status');
	}

	/**
	 * Get Return Status
	 *
	 * @param int $return_status_id
	 *
	 * @return array<string, mixed>
	 */
	public function getReturnStatus(int $return_status_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "return_status` WHERE `return_status_id` = '" . (int)$return_status_id . "' AND `language_id` = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row;
	}

	/**
	 * Get Return Statuses
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getReturnStatuses(array $data = []): array {
		if ($data) {
			$sql = "SELECT * FROM `" . DB_PREFIX . "return_status` WHERE `language_id` = '" . (int)$this->config->get('config_language_id') . "'";

			$sql .= " ORDER BY `name`";

			if (isset($data['order']) && ($data['order'] == 'DESC')) {
				$sql .= " DESC";
			} else {
				$sql .= " ASC";
			}

			if (isset($data['start']) || isset($data['limit'])) {
				if ($data['start'] < 0) {
					$data['start'] = 0;
				}

				if ($data['limit'] < 1) {
					$data['limit'] = 20;
				}

				$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
			}

			$query = $this->db->query($sql);

			return $query->rows;
		} else {
			$return_status_data = $this->cache->get('return_status.' . (int)$this->config->get('config_language_id'));

			if (!$return_status_data) {
				$query = $this->db->query("SELECT `return_status_id`, `name` FROM `" . DB_PREFIX . "return_status` WHERE `language_id` = '" . (int)$this->config->get('config_language_id') . "' ORDER BY `name`");

				$return_status_data = $query->rows;

				$this->cache->set('return_status.' . (int)$this->config->get('config_language_id'), $return_status_data);
			}

			return $return_status_data;
		}
	}

	/**
	 * Get Descriptions
	 *
	 * @param int $return_status_id
	 *
	 * @return array<int, array<string, string>>
	 */
	public function getDescriptions(int $return_status_id): array {
		$return_status_data = [];

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "return_status` WHERE `return_status_id` = '" . (int)$return_status_id . "'");

		foreach ($query->rows as $result) {
			$return_status_data[$result['language_id']] = ['name' => $result['name']];
		}

		return $return_status_data;
	}

	/**
	 * Get Total Return Statuses
	 *
	 * @return int
	 */
	public function getTotalReturnStatuses(): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "return_status` WHERE `language_id` = '" . (int)$this->config->get('config_language_id') . "'");

		return (int)$query->row['total'];
	}
}
