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
	public const TABLE_USERS = 'stalwart_users';
	public const TABLE_EMAILS = 'stalwart_alias';
	private const TYPE_USER = 'individual';
	private const EMAIL_PRIMARY = 0;
	//	private const EMAIL_ALIAS = 1;
	//	private const EMAIL_LIST = 2;

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
	 * @return list<StalwartServerUser>
	 * @throws Exception
	 */
	public function findMany(int $id): array {
		$q = $this->db->getQueryBuilder();
		$result = $q->select('uid')
			->from(self::TABLE_USERS)
			->where($q->expr()->eq('cid', $q->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
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
	 * @param int $cid
	 * @param string $uid
	 * @return ?StalwartServerUser
	 * @throws Exception
	 */
	public function addUser(int $cid, string $uid): ?array {
		$user = $this->findUser($uid);
		if ($user !== null) {
			$this->db->beginTransaction();
			try {
				$q = $this->db->getQueryBuilder();
				$q->insert(self::TABLE_USERS)
					->setValue('cid', $q->createNamedParameter($cid, IQueryBuilder::PARAM_INT))
					->setValue('uid', $q->createNamedParameter($uid))
					->setValue('type', $q->createNamedParameter(self::TYPE_USER))
					->setValue('display_name', $q->createNamedParameter($user['displayName']))
					->setValue('password', $q->createNamedParameter(''))
					->setValue('quota', $q->createNamedParameter(null, IQueryBuilder::PARAM_NULL))
					->executeStatement();

				if ($user['email'] !== null) {
					$q = $this->db->getQueryBuilder();
					$q->insert(self::TABLE_EMAILS)
						->setValue('cid', $q->createNamedParameter($cid, IQueryBuilder::PARAM_INT))
						->setValue('uid', $q->createNamedParameter($uid))
						->setValue('email', $q->createNamedParameter($user['email']))
						->setValue('mode', $q->createNamedParameter(self::EMAIL_PRIMARY, IQueryBuilder::PARAM_INT))
						->executeStatement();
				}
				$this->db->commit();
			} catch (Exception $e) {
				$this->db->rollBack();
				throw $e;
			}
		}
		return $user;
	}

	/**
	 * @param int $id
	 * @param string $uid
	 * @return ?StalwartServerUser
	 * @throws Exception
	 */
	public function removeUser(int $id, string $uid): ?array {
		$user = $this->findUser($uid);
		if ($user !== null) {
			$this->db->beginTransaction();
			try {
				$q = $this->db->getQueryBuilder();
				$q->delete(self::TABLE_USERS)
					->where($q->expr()->eq('cid', $q->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
					->andWhere($q->expr()->eq('uid', $q->createNamedParameter($uid)))
					->executeStatement();
				$this->db->commit();
			} catch (Exception $e) {
				$this->db->rollBack();
				throw $e;
			}
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

	/**
	 * @throws Exception
	 */
	public function updatePrimaryEmail(string $uid, ?string $email): void {
		$this->db->beginTransaction();
		try {
			$q = $this->db->getQueryBuilder();
			$q->delete(self::TABLE_EMAILS)
				->where($q->expr()->eq('uid', $q->createNamedParameter($uid)))
				->andWhere($q->expr()->eq('mode', $q->createNamedParameter(self::EMAIL_PRIMARY, IQueryBuilder::PARAM_INT)))
				->executeStatement();
			if ($email !== null) {
				$q = $this->db->getQueryBuilder();
				$q->insert(self::TABLE_EMAILS)
					->setValue('uid', $q->createNamedParameter($uid))
					->setValue('email', $q->createNamedParameter($email))
					->setValue('mode', $q->createNamedParameter(self::EMAIL_PRIMARY, IQueryBuilder::PARAM_INT))
					->executeStatement();
			}
			$this->db->commit();
		} catch (Exception $e) {
			$this->db->rollBack();
			throw $e;
		}
	}

	/**
	 * @throws Exception
	 */
	public function updatePassword(string $uid, string $sha512): void {
		$this->db->beginTransaction();
		try {
			$q = $this->db->getQueryBuilder();
			$q->update(self::TABLE_USERS)
				->set('password', $q->createNamedParameter($sha512))
				->where($q->expr()->eq('uid', $q->createNamedParameter($uid)))
				->executeStatement();
			$this->db->commit();
		} catch (Exception $e) {
			$this->db->rollBack();
			throw $e;
		}
	}

}
