<?php

namespace OCA\Stalwart\Services;

use OCA\Stalwart\ResponseDefinitions;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUserManager;

/**
 * @psalm-import-type StalwartServerUser from ResponseDefinitions
 */
class UsersService {
	private const TABLE_USERS = 'stalwart_users';

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private readonly IDBConnection $db,
		private readonly IUserManager  $userManager,
	) {
	}

	/**
	 * @param string $uid
	 * @return ?StalwartServerUser
	 */
	private function findUser(string $uid): ?array {
		$user = $this->userManager->get($uid);
		return ($user === null) ? null : [
			'uid' => $uid,
			'displayName' => $user->getDisplayName(),
			'email' => $user->getEMailAddress(),
		];
	}

	/**
	 * @param int $id
	 * @return StalwartServerUser[]
	 * @throws Exception
	 */
	public function findMany(int $id): array {
		$q = $this->db->getQueryBuilder();
		$result = $q->select('uid')
			->from(self::TABLE_USERS)
			->where($q->expr()->eq('config_id', $q->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->executeQuery();
		$entities = [];
		while (true) {
			/** @psalm-suppress MixedAssignment */
			$row = $result->fetch();
			if (is_array($row) && is_string($row['uid'])) {
				$user = $this->findUser($row['uid']);
				if ($user !== null) {
					$entities[] = $user;
				} else {
					$this->dropUser($row['uid']);
				}
			} else {
				$result->closeCursor();
				return $entities;
			}
		}
	}

	/**
	 * @param int $id
	 * @param string $uid
	 * @return ?StalwartServerUser
	 * @throws Exception
	 */
	public function add(int $id, string $uid): ?array {
		$user = $this->findUser($uid);
		if ($user !== null) {
			$q = $this->db->getQueryBuilder();
			$q->insert(self::TABLE_USERS)
				->setValue('config_id', $q->createNamedParameter($id, IQueryBuilder::PARAM_INT))
				->setValue('uid', $q->createNamedParameter($uid))
				->executeStatement();
		}
		return $user;

	}

	/**
	 * @param int $id
	 * @param string $uid
	 * @return ?StalwartServerUser
	 * @throws Exception
	 */
	public function remove(int $id, string $uid): ?array {
		$user = $this->findUser($uid);
		if ($user !== null) {
			$q = $this->db->getQueryBuilder();
			$q->delete(self::TABLE_USERS)
				->where($q->expr()->eq('config_id', $q->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
				->andWhere($q->expr()->eq('uid', $q->createNamedParameter($uid)))
				->executeStatement();
		}
		return $user;
	}

	/**
	 * @throws Exception
	 */
	public function dropUser(string $uid): void {
		$q = $this->db->getQueryBuilder();
		$q->delete(self::TABLE_USERS)
			->where($q->expr()->eq('uid', $q->createNamedParameter($uid)))
			->executeStatement();
	}
}
