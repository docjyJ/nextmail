<?php

namespace OCA\Stalwart\Db;

use DateTimeImmutable;
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
	public function update(ConfigEntity $config): ConfigEntity {
		$config = $config->updateHealth($this->apiService->challenge($config));
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
			return $config;
		} catch (Exception $e) {
			$this->db->rollBack();
			throw $e;
		}
	}

	/** @throws Exception */
	public function create(): ConfigEntity {
		$date = new DateTimeImmutable();
		$state = ServerStatus::Invalid;
		$this->db->beginTransaction();
		try {
			$q = $this->db->getQueryBuilder();
			$q->insert(ConfigEntity::TABLE)
				->values([
					'endpoint' => $q->createNamedParameter(''),
					'username' => $q->createNamedParameter(''),
					'password' => $q->createNamedParameter(''),
					'health' => $q->createNamedParameter($state->value),
					'expires' => $q->createNamedParameter($date, IQueryBuilder::PARAM_DATE),
				])
				->executeStatement();

			$config = new ConfigEntity($q->getLastInsertId(), '', '', '', $state, $date);
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
