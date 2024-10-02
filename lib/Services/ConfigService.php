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

	public function __construct(
		private readonly IDBConnection      $db,
		private readonly StalwartAPIService $apiService,
	) {
	}

	/**
	 * @param int $id
	 * @return ?ServerConfig
	 * @throws Exception
	 */
	public function findId(int $id): ?array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from(self::TABLE_CONFIG)
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

		$result = $qb->executeQuery();

		$row = $result->fetch();

		$result->closeCursor();

		if (is_array($row) &&
			is_int($row['id']) &&
			is_string($row['endpoint']) &&
			is_string($row['username']) &&
			is_string($row['password']) &&
			is_int($row['health']) &&
			is_string($row['health_expires'])) {
			try {
				return [
					'id' => $row['id'],
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
			->select('id', 'endpoint', 'username', 'health')
			->from(ConfigService::TABLE_CONFIG)
			->executeQuery();
		$entities = [];
		while (true) {
			$row = $result->fetch();
			if (is_array($row) &&
				is_int($row['id']) &&
				is_string($row['endpoint']) &&
				is_string($row['username']) &&
				is_int($row['health'])) {
				$entities[] = [
					'id' => $row['id'],
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
		$result = $q->select('id')
			->from(ConfigService::TABLE_CONFIG)
			->where($q->expr()->lt('health_expires', $q->createNamedParameter(new DateTime(), IQueryBuilder::PARAM_DATE)))
			->executeQuery();
		while (true) {
			$row = $result->fetch();
			if (is_array($row) && is_int($row['id']) && $entity = $this->findId($row['id'])) {
				$healthResult = $this->apiService->challenge($entity['endpoint'], $entity['username'], $entity['password']);
				$entity['health'] = $healthResult[0];
				$entity['health_expires'] = $healthResult[1];

				$q = $this->db->getQueryBuilder();
				$q->update(ConfigService::TABLE_CONFIG)
					->set('health', $q->createNamedParameter($healthResult[0], IQueryBuilder::PARAM_INT))
					->set('health_expires', $q->createNamedParameter($healthResult[1], IQueryBuilder::PARAM_DATE))
					->where($q->expr()->eq('id', $q->createNamedParameter($entity['id'], IQueryBuilder::PARAM_INT)))
					->executeStatement();

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
	 * @return ServerConfig
	 * @throws Exception
	 */
	public function create(string $endpoint, string $username, string $password): array {
		$healthResult = $this->apiService->challenge($endpoint, $username, $password);

		$q = $this->db->getQueryBuilder();
		$q->insert(ConfigService::TABLE_CONFIG)
			->setValue('endpoint', $q->createNamedParameter(strtolower($endpoint)))
			->setValue('username', $q->createNamedParameter($username))
			->setValue('password', $q->createNamedParameter($password))
			->setValue('health', $q->createNamedParameter($healthResult[0], IQueryBuilder::PARAM_INT))
			->setValue('health_expires', $q->createNamedParameter($healthResult[1], IQueryBuilder::PARAM_DATE))
			->executeStatement();
		return [
			'id' => $q->getLastInsertId(),
			'endpoint' => $endpoint,
			'username' => $username,
			'password' => $password,
			'health' => $healthResult[0],
			'health_expires' => $healthResult[1]
		];
	}

	/**
	 * @param int $id
	 * @param string $endpoint
	 * @param string $username
	 * @param string $password
	 * @return ?ServerConfig
	 * @throws Exception
	 */
	public function updateCredentials(int $id, string $endpoint, string $username, string $password): ?array {
		$entity = $this->findId($id);
		if ($entity !== null) {
			$entity['endpoint'] = $endpoint;
			$entity['username'] = $username;
			if ($password !== '') {
				$entity['password'] = $password;
			}

			$healthResult = $this->apiService->challenge($endpoint, $username, $entity['password']);
			$entity['health'] = $healthResult[0];
			$entity['health_expires'] = $healthResult[1];

			$q = $this->db->getQueryBuilder();
			$q->update(ConfigService::TABLE_CONFIG)
				->set('endpoint', $q->createNamedParameter($entity['endpoint']))
				->set('username', $q->createNamedParameter($entity['username']))
				->set('password', $q->createNamedParameter($entity['password']))
				->set('health', $q->createNamedParameter($healthResult[0], IQueryBuilder::PARAM_INT))
				->set('health_expires', $q->createNamedParameter($healthResult[1], IQueryBuilder::PARAM_DATE))
				->where($q->expr()->eq('id', $q->createNamedParameter($entity['id'], IQueryBuilder::PARAM_INT)))
				->executeStatement();

			return $entity;
		}
		return null;
	}

	/**
	 * @param int $id
	 * @return ?ServerConfig
	 * @throws Exception
	 */
	public function delete(int $id): ?array {
		$entity = $this->findId($id);
		if ($entity !== null) {
			$q = $this->db->getQueryBuilder();
			$q->delete(ConfigService::TABLE_CONFIG)
				->where($q->expr()->eq('id', $q->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
				->executeStatement();
		}
		return $entity;
	}
}
