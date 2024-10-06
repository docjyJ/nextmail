<?php

namespace OCA\Stalwart\Db;

use DateTime;
use OCA\Stalwart\Models\ConfigEntity;
use OCA\Stalwart\Models\ServerStatus;
use OCA\Stalwart\ParseMixed;
use OCA\Stalwart\Services\StalwartAPIService;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class ConfigManager {
	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private readonly IDBConnection      $db,
		private readonly StalwartAPIService $apiService,
	) {
	}

	/** @throws Exception */
	public function find(int $cid): ?ConfigEntity {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from(ConfigEntity::TABLE)
			->where($qb->expr()->eq('cid', $qb->createNamedParameter($cid, IQueryBuilder::PARAM_INT)));
		$result = $qb->executeQuery();
		$config = ParseMixed::configEntity($result->fetch());
		$result->closeCursor();
		return $config;
	}

	/**
	 * @return ConfigEntity[]
	 * @throws Exception
	 */
	public function list(): array {
		$q = $this->db->getQueryBuilder();
		$q->select('*')
			->from(ConfigEntity::TABLE);
		$result = $q->executeQuery();
		$entities = [];
		while ($id = ParseMixed::configEntity($result->fetch())) {
			$entities[] = $id;
		}
		$result->closeCursor();
		return $entities;
	}

	/** @throws Exception */
	public function update(ConfigEntity $config): void {
		$heathResult = $this->apiService->challenge($config->endpoint, $config->username, $config->password);
		$config->health = $heathResult[0];
		$config->expires = $heathResult[1];
		if ($heathResult[0] === ServerStatus::Success || $heathResult[0] === ServerStatus::NoAdmin) {
			try {
				$this->apiService->pushDataBase($config->cid, $config->endpoint, $config->username, $config->password);
			} catch (\Exception $e) {
				throw new Exception('Failed to push data to server', previous: $e);
			}
		}
		$this->db->beginTransaction();
		try {
			$q = $this->db->getQueryBuilder();
			$q->update(ConfigEntity::TABLE)
				->set('endpoint', $q->createNamedParameter(strtolower($config->endpoint)))
				->set('username', $q->createNamedParameter($config->username))
				->set('password', $q->createNamedParameter($config->password))
				->set('health', $q->createNamedParameter($config->health->value))
				->set('expires', $q->createNamedParameter($config->expires, IQueryBuilder::PARAM_DATE))
				->where($q->expr()->eq('cid', $q->createNamedParameter($config->cid, IQueryBuilder::PARAM_INT)))
				->executeStatement();
			$this->db->commit();
		} catch (Exception $e) {
			$this->db->rollBack();
			throw $e;
		}
	}

	/** @throws Exception */
	public function create(): ConfigEntity {
		$config = new ConfigEntity(
			0,
			'',
			'',
			'',
			ServerStatus::Invalid,
			new DateTime(),
		);
		$this->db->beginTransaction();
		try {
			$q = $this->db->getQueryBuilder();
			$q->insert(ConfigEntity::TABLE)
				->values([
					'endpoint' => $q->createNamedParameter($config->endpoint),
					'username' => $q->createNamedParameter($config->username),
					'password' => $q->createNamedParameter($config->password),
					'health' => $q->createNamedParameter($config->health->value),
					'expires' => $q->createNamedParameter($config->expires, IQueryBuilder::PARAM_DATE),
				])
				->executeStatement();

			$config->cid = $q->getLastInsertId();
			$this->db->commit();
		} catch (Exception $e) {
			$this->db->rollBack();
			throw $e;
		}
		return $config;
	}

	/** @throws Exception */
	public function delete(ConfigEntity $config): void {
		$this->db->beginTransaction();
		try {
			$q = $this->db->getQueryBuilder();
			$q->delete(ConfigEntity::TABLE)
				->where($q->expr()->eq('cid', $q->createNamedParameter($config->cid, IQueryBuilder::PARAM_INT)))
				->executeStatement();
			$this->db->commit();
		} catch (Exception $e) {
			$this->db->rollBack();
			throw $e;
		}
	}
}
