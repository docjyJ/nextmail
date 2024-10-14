<?php

namespace OCA\Nextmail\Db;

use OCA\Nextmail\Models\ServerEntity;
use OCA\Nextmail\Services\StalwartApiService;
use OCP\DB\Exception;

readonly class ServerManager {
	public function __construct(
		private Transaction        $tr,
		private StalwartApiService $apiService,
	) {
	}

	/** @throws Exception */
	public function get(string $id): ?ServerEntity {
		$servers = $this->tr->selectServer($id);
		return count($servers) === 0 ? null : ServerEntity::parse($servers[0]);
	}

	/**
	 * @return ServerEntity[]
	 * @throws Exception
	 */
	public function list(): array {
		return array_map(fn (mixed $row) => ServerEntity::parse($row), $this->tr->selectServer());
	}

	/** @throws Exception */
	public function save(ServerEntity $server): ServerEntity {
		$server = $this->apiService->challenge($server);
		$this->tr->updateServer(
			$server->id,
			$server->endpoint,
			$server->username,
			$server->password,
			$server->health
		);
		$this->tr->commit();
		return $server;
	}

	/** @throws Exception */
	public function create(): ServerEntity {
		$server = ServerEntity::newEmpty();
		$this->tr->insertServer(
			$server->id,
			$server->endpoint,
			$server->username,
			$server->password,
			$server->health
		);
		$this->tr->commit();
		return $server;
	}

	/** @throws Exception */
	public function delete(ServerEntity $server): void {
		$this->tr->deleteServer($server->id);
		$this->tr->commit();
	}
}
