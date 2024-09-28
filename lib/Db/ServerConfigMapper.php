<?php

namespace OCA\Stalwart\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @extends QBMapper<ServerConfig>
 */
class ServerConfigMapper extends QBMapper {
	public const TABLE_NAME = 'server_config';

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, self::TABLE_NAME, ServerConfig::class);
	}

	/**
	 * @return ServerConfig[]
	 * @throws Exception
	 */
	public function listServers(): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from(self::TABLE_NAME);

		return $this->findEntities($qb);
	}

	/**
	 * @throws MultipleObjectsReturnedException
	 * @throws DoesNotExistException
	 * @throws Exception
	 */
	public function getServer(int $index): ServerConfig {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from(self::TABLE_NAME)
			->where($qb->expr()->eq(
				'id', $qb->createNamedParameter($index, IQueryBuilder::PARAM_INT)));
		return $this->findEntity($qb);
	}
}
