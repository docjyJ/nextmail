<?php

namespace OCA\Stalwart\Db;

use OCA\Stalwart\Models\AccountEntity;
use OCA\Stalwart\Models\AccountsType;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class AccountManager {
	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private readonly IDBConnection $db,
	) {
	}

	/**
	 * @return AccountEntity[]
	 * @throws Exception
	 */
	public function listConfig(int $cid): array {
		$q = $this->db->getQueryBuilder();
		$q->select('*')
			->from(AccountEntity::TABLE)
			->where($q->expr()->eq('cid', $q->createNamedParameter($cid, IQueryBuilder::PARAM_INT)));
		$result = $q->executeQuery();
		$entities = [];
		while ($row = AccountEntity::fromRow($result->fetch())) {
			$entities[] = $row;
		}
		$result->closeCursor();
		return $entities;
	}

	/**
	 * @return AccountEntity[]
	 * @throws Exception
	 */
	public function listUser(string $uid): array {
		$q = $this->db->getQueryBuilder();
		$q->select('*')
			->from(AccountEntity::TABLE)
			->where($q->expr()->eq('uid', $q->createNamedParameter($uid)));
		$result = $q->executeQuery();
		$entities = [];
		while ($row = AccountEntity::fromRow($result->fetch())) {
			$entities[] = $row;
		}
		$result->closeCursor();
		return $entities;
	}

	/** @throws Exception */
	public function create(AccountEntity $entity): void {
		$this->db->beginTransaction();
		try {
			$q = $this->db->getQueryBuilder();
			$q->insert(AccountEntity::TABLE)
				->values([
					'cid' => $q->createNamedParameter($entity->cid, IQueryBuilder::PARAM_INT),
					'uid' => $q->createNamedParameter($entity->uid),
					'type' => $q->createNamedParameter(AccountsType::Individual->value),
					'display_name' => $q->createNamedParameter($entity->displayName),
					'password' => $q->createNamedParameter($entity->password),
					'quota' => $q->createNamedParameter($entity->quota, IQueryBuilder::PARAM_INT),
				])
				->executeStatement();
			$this->db->commit();
		} catch (Exception $e) {
			$this->db->rollBack();
			throw $e;
		}
	}

	/** @throws Exception */
	public function delete(int $cid, string $uid): void {
		$this->db->beginTransaction();
		try {
			$q = $this->db->getQueryBuilder();
			$q->delete(AccountEntity::TABLE)
				->where($q->expr()->eq('cid', $q->createNamedParameter($cid, IQueryBuilder::PARAM_INT)))
				->andWhere($q->expr()->eq('uid', $q->createNamedParameter($uid)))
				->executeStatement();
			$this->db->commit();
		} catch (Exception $e) {
			$this->db->rollBack();
			throw $e;
		}
	}

	/** @throws Exception */
	public function drop(string $uid): void {
		$this->db->beginTransaction();
		try {
			$q = $this->db->getQueryBuilder();
			$q->delete(AccountEntity::TABLE)
				->where($q->expr()->eq('uid', $q->createNamedParameter($uid)))
				->executeStatement();
			$this->db->commit();
		} catch (Exception $e) {
			$this->db->rollBack();
			throw $e;
		}
	}

	/** @throws Exception */
	public function updatePassword(string $uid, string $password): void {
		$this->db->beginTransaction();
		try {
			$q = $this->db->getQueryBuilder();
			$q->update(AccountEntity::TABLE)
				->set('password', $q->createNamedParameter($password))
				->where($q->expr()->eq('uid', $q->createNamedParameter($uid)))
				->executeStatement();
			$this->db->commit();
		} catch (Exception $e) {
			$this->db->rollBack();
			throw $e;
		}
	}

}
