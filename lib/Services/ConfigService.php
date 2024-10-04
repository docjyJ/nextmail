<?php

namespace OCA\Stalwart\Services;

use DateTime;
use OCA\Stalwart\ResponseDefinitions;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Throwable;

/**
 * @psalm-type ServerConfig = array{
 *      id: int,
 *      endpoint: string,
 *      username: string,
 *      password: string,
 *      health: int,
 *      health_expires: DateTime
 *  }
 * @psalm-import-type StalwartServerConfig from ResponseDefinitions
 */
class ConfigService {
	private const TABLE_CONFIG = 'stalwart_config';

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private readonly IDBConnection      $db,
		private readonly StalwartAPIService $apiService,
	) {
	}

	/**
	 * @param int $cid
	 * @return ?ServerConfig
	 * @throws Exception
	 */
	public function findId(int $cid): ?array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from(self::TABLE_CONFIG)
			->where($qb->expr()->eq('cid', $qb->createNamedParameter($cid, IQueryBuilder::PARAM_INT)));

		$result = $qb->executeQuery();

		/** @psalm-suppress MixedAssignment */
		$row = $result->fetch();

		$result->closeCursor();

		if (is_array($row) &&
			is_int($row['cid']) &&
			is_string($row['endpoint']) &&
			is_string($row['username']) &&
			is_string($row['password']) &&
			is_int($row['health']) &&
			is_string($row['health_expires'])) {
			try {
				return [
					'id' => $row['cid'],
					'endpoint' => $row['endpoint'],
					'username' => $row['username'],
					'password' => $row['password'],
					'health' => $row['health'],
					'health_expires' => new DateTime($row['health_expires'])
				];
			} catch (Throwable $e) {
				throw new Exception(previous: $e);
			}
		}
		return null;
	}

	/**
	 * @return list<StalwartServerConfig>
	 * @throws Exception
	 */
	public function findMany(): array {
		$result = $this->db->getQueryBuilder()
			->select('cid', 'endpoint', 'username', 'health')
			->from(ConfigService::TABLE_CONFIG)
			->executeQuery();
		$entities = [];
		while (true) {
			/** @psalm-suppress MixedAssignment */
			$row = $result->fetch();
			if (is_array($row) &&
				is_int($row['cid']) &&
				is_string($row['endpoint']) &&
				is_string($row['username']) &&
				is_int($row['health'])) {
				$entities[] = [
					'id' => $row['cid'],
					'endpoint' => $row['endpoint'],
					'username' => $row['username'],
					'health' => $row['health']
				];
			} else {
				$result->closeCursor();
				return $entities;
			}
		}
	}

	/**
	 * @throws Exception
	 */
	public function updateExpiredHealth(): void {
		$q = $this->db->getQueryBuilder();
		$result = $q->select('cid')
			->from(ConfigService::TABLE_CONFIG)
			->where($q->expr()->lt('health_expires', $q->createNamedParameter(new DateTime(), IQueryBuilder::PARAM_DATE)))
			->executeQuery();
		while (true) {
			/** @psalm-suppress MixedAssignment */
			$row = $result->fetch();
			if (is_array($row) && is_int($row['cid']) && $entity = $this->findId($row['cid'])) {
				$healthResult = $this->apiService->challenge($entity['endpoint'], $entity['username'], $entity['password']);
				if ($healthResult[0] === ServerStatus::Success || $healthResult[0] === ServerStatus::NoAdmin) {
					try {
						$this->apiService->pushDataBase($row['cid'], $entity['endpoint'], $entity['username'], $entity['password']);
					} catch (\Exception $e) {
						throw new Exception('Failed to push data to server', previous: $e);
					}
				}
				$entity['health'] = $healthResult[0];
				$entity['health_expires'] = $healthResult[1];
				$this->db->beginTransaction();
				try {
					$q = $this->db->getQueryBuilder();
					$q->update(ConfigService::TABLE_CONFIG)
						->set('health', $q->createNamedParameter($healthResult[0], IQueryBuilder::PARAM_INT))
						->set('health_expires', $q->createNamedParameter($healthResult[1], IQueryBuilder::PARAM_DATE))
						->where($q->expr()->eq('cid', $q->createNamedParameter($entity['id'], IQueryBuilder::PARAM_INT)))
						->executeStatement();
					$this->db->commit();
				} catch (Exception $e) {
					$this->db->rollBack();
					throw $e;
				}

			} else {
				$result->closeCursor();
				return;
			}
		}
	}

	/**
	 * @param string $endpoint
	 * @param string $username
	 * @param string $password
	 *
	 * @return ServerConfig
	 *
	 * @throws Exception
	 */
	public function create(string $endpoint, string $username, string $password): array {
		$healthResult = $this->apiService->challenge($endpoint, $username, $password);

		$this->db->beginTransaction();
		try {
			$q = $this->db->getQueryBuilder();
			$q->insert(ConfigService::TABLE_CONFIG)
				->setValue('endpoint', $q->createNamedParameter(strtolower($endpoint)))
				->setValue('username', $q->createNamedParameter($username))
				->setValue('password', $q->createNamedParameter($password))
				->setValue('health', $q->createNamedParameter($healthResult[0], IQueryBuilder::PARAM_INT))
				->setValue('health_expires', $q->createNamedParameter($healthResult[1], IQueryBuilder::PARAM_DATE))
				->executeStatement();

			$cid = $q->getLastInsertId();
			$this->db->commit();
		} catch (Exception $e) {
			$this->db->rollBack();
			throw $e;
		}

		if ($healthResult[0] === ServerStatus::Success || $healthResult[0] === ServerStatus::NoAdmin) {
			try {
				$this->apiService->pushDataBase($cid, $endpoint, $username, $password);
			} catch (\Exception $e) {
				throw new Exception('Failed to push data to server', previous: $e);
			}
		}
		return [
			'id' => $cid,
			'endpoint' => $endpoint,
			'username' => $username,
			'password' => $password,
			'health' => $healthResult[0]->value,
			'health_expires' => $healthResult[1]
		];
	}

	/**
	 * @param int $cid
	 * @param string $endpoint
	 * @param string $username
	 * @param string $password
	 * @return ?ServerConfig
	 * @throws Exception
	 */
	public function updateCredentials(int $cid, string $endpoint, string $username, string $password): ?array {
		$entity = $this->findId($cid);
		if ($entity !== null) {
			$entity['endpoint'] = $endpoint;
			$entity['username'] = $username;
			if ($password !== '') {
				$entity['password'] = $password;
			}

			$healthResult = $this->apiService->challenge($endpoint, $username, $entity['password']);
			if ($healthResult[0] === ServerStatus::Success || $healthResult[0] === ServerStatus::NoAdmin) {
				try {
					$this->apiService->pushDataBase($cid, $endpoint, $username, $entity['password']);
				} catch (\Exception $e) {
					throw new Exception('Failed to push data to server', previous: $e);
				}
			}
			$entity['health'] = $healthResult[0]->value;
			$entity['health_expires'] = $healthResult[1];
			$this->db->beginTransaction();
			try {
				$q = $this->db->getQueryBuilder();
				$q->update(ConfigService::TABLE_CONFIG)
					->set('endpoint', $q->createNamedParameter($entity['endpoint']))
					->set('username', $q->createNamedParameter($entity['username']))
					->set('password', $q->createNamedParameter($entity['password']))
					->set('health', $q->createNamedParameter($healthResult[0], IQueryBuilder::PARAM_INT))
					->set('health_expires', $q->createNamedParameter($healthResult[1], IQueryBuilder::PARAM_DATE))
					->where($q->expr()->eq('cid', $q->createNamedParameter($entity['id'], IQueryBuilder::PARAM_INT)))
					->executeStatement();
				$this->db->commit();
			} catch (Exception $e) {
				$this->db->rollBack();
				throw $e;
			}
			return $entity;
		}
		return null;
	}

	/**
	 * @param int $cid
	 * @return ?ServerConfig
	 * @throws Exception
	 */
	public function delete(int $cid): ?array {
		$entity = $this->findId($cid);
		if ($entity !== null) {
			$this->db->beginTransaction();
			try {
				$q = $this->db->getQueryBuilder();
				$q->delete(ConfigService::TABLE_CONFIG)
					->where($q->expr()->eq('cid', $q->createNamedParameter($cid, IQueryBuilder::PARAM_INT)))
					->executeStatement();
				$this->db->commit();
			} catch (Exception $e) {
				$this->db->rollBack();
				throw $e;
			}
		}
		return $entity;
	}
}
