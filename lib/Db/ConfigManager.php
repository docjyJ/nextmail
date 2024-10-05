<?php

namespace OCA\Stalwart\Db;

use DateTime;
use OCA\Stalwart\Models\ConfigEntity;
use OCA\Stalwart\Models\ServerStatus;
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
		$row = ConfigEntity::fromRow($result->fetch());
		$result->closeCursor();
		return $row;
	}

	/**
	 * @return ConfigEntity[]
	 * @throws Exception
	 */
	public function list(): array {
		$q = $this->db->getQueryBuilder();
		$q->select('')
			->from(ConfigEntity::TABLE);
		$result = $q->executeQuery();
		$entities = [];
		while ($row = ConfigEntity::fromRow($result->fetch())) {
			$entities[] = $row;
		}
		$result->closeCursor();
		return $entities;
	}

	/** @throws Exception */
	public function update(ConfigEntity $entity): void {
		$heathResult = $this->apiService->challenge($entity->endpoint, $entity->username, $entity->password);
		$entity->health = $heathResult[0];
		$entity->expires = $heathResult[1];
		if ($heathResult[0] === ServerStatus::Success || $heathResult[0] === ServerStatus::NoAdmin) {
			try {
				$this->apiService->pushDataBase($entity->cid, $entity->endpoint, $entity->username, $entity->password);
			} catch (\Exception $e) {
				throw new Exception('Failed to push data to server', previous: $e);
			}
		}
		$this->db->beginTransaction();
		try {
			$q = $this->db->getQueryBuilder();
			$q->update(ConfigEntity::TABLE)
				->set('endpoint', $q->createNamedParameter(strtolower($entity->endpoint)))
				->set('username', $q->createNamedParameter($entity->username))
				->set('password', $q->createNamedParameter($entity->password))
				->set('health', $q->createNamedParameter($entity->health->value))
				->set('expires', $q->createNamedParameter($entity->expires, IQueryBuilder::PARAM_DATE))
				->where($q->expr()->eq('cid', $q->createNamedParameter($entity->cid, IQueryBuilder::PARAM_INT)))
				->executeStatement();
			$this->db->commit();
		} catch (Exception $e) {
			$this->db->rollBack();
			throw $e;
		}
	}

	/** @throws Exception */
	public function create(): int {
		$this->db->beginTransaction();
		try {
			$q = $this->db->getQueryBuilder();
			$q->insert(ConfigEntity::TABLE)
				->values([
					'endpoint' => $q->createNamedParameter(''),
					'username' => $q->createNamedParameter(''),
					'password' => $q->createNamedParameter(''),
					'health' => $q->createNamedParameter(ServerStatus::Invalid->value),
					'expires' => $q->createNamedParameter(new DateTime(), IQueryBuilder::PARAM_DATE),
				])
				->executeStatement();

			$cid = $q->getLastInsertId();
			$this->db->commit();
		} catch (Exception $e) {
			$this->db->rollBack();
			throw $e;
		}
		return $cid;
	}

	/** @throws Exception */
	public function delete(int $cid): void {
		$this->db->beginTransaction();
		try {
			$q = $this->db->getQueryBuilder();
			$q->delete(ConfigEntity::TABLE)
				->where($q->expr()->eq('cid', $q->createNamedParameter($cid, IQueryBuilder::PARAM_INT)))
				->executeStatement();
			$this->db->commit();
		} catch (Exception $e) {
			$this->db->rollBack();
			throw $e;
		}
	}
}
