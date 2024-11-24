<?php

namespace OCA\Nextmail\Db;

use OCA\Nextmail\Models\ServerEntity;
use OCA\Nextmail\Services\StalwartApiService;
use OCP\DB\Exception;

readonly class ServerManager {

	public function __construct(
		private Transaction $tr,
		private StalwartApiService $api,
	) {
	}

	/** @throws Exception	 */
	private function loadEntity(string $srv): ?ServerEntity {
		$data = $this->tr->selectServer($srv);
		return isset($data[0]) ? ServerEntity::parse($data[0]) : null;
	}

	/**
	 * @return list<ServerEntity>
	 * @throws Exception
	 */
	public function listAll(): array {
		return array_map(fn ($x) => ServerEntity::parse($x), $this->tr->selectServer());
	}


	/** @throws Exception */
	private function processQuery(ServerEntity $data, bool $create): ServerEntity {
		$data = $this->api->challenge($data);
		if ($create) {
			$this->tr->insertServer(
				$data->id,
				$data->endpoint,
				$data->username,
				$data->password,
				$data->health
			);
		} else {
			$this->tr->updateServer(
				$data->id,
				$data->endpoint,
				$data->username,
				$data->password,
				$data->health
			);
		}
		return $data;
	}

	/** @throws Exception */
	public function syncAll(): void {
		foreach ($this->listAll() as $data) {
			$this->syncOne($data);
		}
	}

	/** @throws Exception */
	public function syncOne(ServerEntity $data): void {
		$this->processQuery($data, false);
	}

	/** @throws Exception */
	public function update(string $srv, string $endpoint, string $username, string $password): ?ServerEntity {
		$data = $this->loadEntity($srv);
		return $data !== null
			? $this->processQuery($data->update($endpoint, $username, $password), false)
			: null;

	}

	/** @throws Exception */
	public function create(): ServerEntity {
		return $this->processQuery(ServerEntity::newEmpty(), true);
	}

	/** @throws Exception */
	public function delete(string $srv): ?ServerEntity {
		$data = $this->loadEntity($srv);
		$this->tr->deleteServer($srv);
		$this->tr->commit();
		return $data;
	}

	/** @throws Exception */
	public function commit(): void {
		$this->tr->commit();
	}


}
