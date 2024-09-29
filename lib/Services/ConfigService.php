<?php

namespace OCA\Stalwart\Services;

use OCA\Stalwart\ResponseDefinitions;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @psalm-import-type StalwartServerConfig from ResponseDefinitions
 */
class ConfigService {
	private const TABLE_NAME = 'stalwart_config';

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private readonly IDBConnection $db,
	) {
	}

	/**
	 * @param mixed $row
	 * @return ?StalwartServerConfig
	 */
	private static function entityValidate(mixed $row): ?array {
		if (is_array($row) && is_numeric($row['id']) && is_string($row['endpoint']) && is_string($row['username']) && is_string($row['password'])) {
			return [
				'id' => intval($row['id']),
				'endpoint' => $row['endpoint'],
				'username' => $row['username'],
				'password' => $row['password']
			];
		}
		return null;
	}

	/**
	 * @param int $id
	 * @return ?StalwartServerConfig
	 * @throws Exception
	 */
	public function find(int $id): ?array {
		$q = $this->db->getQueryBuilder();
		$result = $q->select('*')
			->from(ConfigService::TABLE_NAME)
			->where($q->expr()->eq('id', $q->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->executeQuery();

		$row = self::entityValidate($result->fetch());
		$result->closeCursor();

		return $row;
	}

	/**
	 * @return StalwartServerConfig[]
	 * @throws Exception
	 */
	public function list(): array {
		$result = $this->db->getQueryBuilder()
			->select('*')
			->from(ConfigService::TABLE_NAME)
			->executeQuery();
		$entities = [];
		while ($row = self::entityValidate($result->fetch())) {
			$entities[] = $row;
		}
		$result->closeCursor();
		return $entities;
	}

	/**
	 * @param string $endpoint
	 * @param string $username
	 * @param string $password
	 * @return StalwartServerConfig
	 * @throws Exception
	 */
	public function create(string $endpoint, string $username, string $password): array {
		$q = $this->db->getQueryBuilder();
		$q->insert(ConfigService::TABLE_NAME)
			->setValue('endpoint', $q->createNamedParameter($endpoint))
			->setValue('username', $q->createNamedParameter($username))
			->setValue('password', $q->createNamedParameter($password))
			->executeStatement();
		return [
			'id' => $q->getLastInsertId(),
			'endpoint' => $endpoint,
			'username' => $username,
			'password' => $password
		];
	}

	/**
	 * @param int $id
	 * @param string $endpoint
	 * @param string $username
	 * @param string $password
	 * @return ?StalwartServerConfig
	 * @throws Exception
	 */
	public function update(int $id, string $endpoint, string $username, string $password): ?array {
		$entity = $this->find($id);
		if ($entity !== null) {
			$q = $this->db->getQueryBuilder();
			$q->update(ConfigService::TABLE_NAME)
				->set('endpoint', $q->createNamedParameter($endpoint))
				->set('username', $q->createNamedParameter($username))
				->set('password', $q->createNamedParameter($password))
				->where($q->expr()->eq('id', $q->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
				->executeStatement();
			return [
				'id' => $id,
				'endpoint' => $endpoint,
				'username' => $username,
				'password' => $password
			];
		} else {
			return null;
		}
	}

	/**
	 * @param int $id
	 * @return ?StalwartServerConfig
	 * @throws Exception
	 */
	public function delete(int $id): ?array {
		$entity = $this->find($id);
		if ($entity !== null) {
			$q = $this->db->getQueryBuilder();
			$q->delete(ConfigService::TABLE_NAME)
				->where($q->expr()->eq('id', $q->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
				->executeStatement();
		}
		return $entity;
	}
}
