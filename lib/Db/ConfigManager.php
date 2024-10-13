<?php

namespace OCA\Nextmail\Db;

use OCA\Nextmail\Models\ConfigEntity;
use OCA\Nextmail\Services\StalwartAPIService;
use OCP\DB\Exception;

readonly class ConfigManager {
	public function __construct(
		private Transaction        $tr,
		private StalwartAPIService $apiService,
	) {
	}

	/** @throws Exception */
	public function getById(string $cid): ?ConfigEntity {
		$configs = $this->tr->selectConfig($cid);
		return count($configs) === 0 ? null : ConfigEntity::parse($configs[0]);
	}

	/**
	 * @return ConfigEntity[]
	 * @throws Exception
	 */
	public function list(): array {
		return array_map(fn (mixed $row) => ConfigEntity::parse($row), $this->tr->selectConfig());
	}

	/** @throws Exception */
	public function save(ConfigEntity $config): ConfigEntity {
		$config = $this->apiService->challenge($config);
		$this->tr->updateConfig(
			$config->cid,
			$config->endpoint,
			$config->username,
			$config->password,
			$config->health
		);
		$this->tr->commit();
		return $config;
	}

	/** @throws Exception */
	public function create(): ConfigEntity {
		$config = ConfigEntity::newEmpty();
		$this->tr->insertConfig(
			$config->cid,
			$config->endpoint,
			$config->username,
			$config->password,
			$config->health
		);
		$this->tr->commit();
		return $config;
	}

	/** @throws Exception */
	public function delete(ConfigEntity $config): void {
		$this->tr->deleteConfig($config->cid);
		$this->tr->commit();
	}
}
