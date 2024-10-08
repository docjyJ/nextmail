<?php

namespace OCA\Stalwart\Db;

use OCA\Stalwart\FromMixed;
use OCA\Stalwart\Models\AccountEntity;
use OCA\Stalwart\Models\ConfigEntity;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUser;

class AccountManager {
	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private readonly IDBConnection $db,
	) {
	}

	private static function getHashFromUser(IUser $user): string {
		return preg_replace('/^[^$]*/', '', $user->getPasswordHash() ?? '') ?? '';
	}

	/**
	 * @return AccountEntity[]
	 * @throws Exception
	 */
	public function list(ConfigEntity $config): array {
		$q = $this->db->getQueryBuilder();
		$q->select('*')
			->from(AccountEntity::TABLE)
			->where($q->expr()->eq('cid', $q->createNamedParameter($config->cid, IQueryBuilder::PARAM_INT)));
		$result = $q->executeQuery();
		$entities = [];
		while ($account = AccountEntity::fromMixed($config, $result->fetch())) {
			$entities[] = $account;
		}
		$result->closeCursor();
		return $entities;
	}

	/** @throws Exception */
	public function find(ConfigEntity $config, string $uid): ?AccountEntity {
		$q = $this->db->getQueryBuilder();
		$q->select('*')
			->from(AccountEntity::TABLE)
			->where($q->expr()->eq('cid', $q->createNamedParameter($config->cid, IQueryBuilder::PARAM_INT)))
			->andWhere($q->expr()->eq('uid', $q->createNamedParameter($uid)));
		$result = $q->executeQuery();
		$account = AccountEntity::fromMixed($config, $result->fetch());
		$result->closeCursor();
		return $account;
	}

	/** @throws Exception */
	public function createIndividual(ConfigEntity $config, IUser $user): AccountEntity {
		$account = new AccountEntity($config, $user->getUID(), $user->getDisplayName(), self::getHashFromUser($user));
		$this->db->beginTransaction();
		try {
			$q = $this->db->getQueryBuilder();
			$q->insert(AccountEntity::TABLE)
				->values([
					'cid' => $q->createNamedParameter($account->config->cid, IQueryBuilder::PARAM_INT),
					'uid' => $q->createNamedParameter($account->uid),
					'type' => $q->createNamedParameter($account->type->value),
					'display_name' => $q->createNamedParameter($account->displayName),
					'password' => $q->createNamedParameter($account->password),
					'quota' => $q->createNamedParameter($account->quota, IQueryBuilder::PARAM_INT),
				])
				->executeStatement();
			$this->db->commit();
			return $account;
		} catch (Exception $e) {
			$this->db->rollBack();
			throw $e;
		}
	}

	/** @throws Exception */
	public function delete(AccountEntity $account): void {
		$this->db->beginTransaction();
		try {
			$q = $this->db->getQueryBuilder();
			$q->delete(AccountEntity::TABLE)
				->where($q->expr()->eq('cid', $q->createNamedParameter($account->config->cid, IQueryBuilder::PARAM_INT)))
				->andWhere($q->expr()->eq('uid', $q->createNamedParameter($account->uid)))
				->executeStatement();
			$this->db->commit();
		} catch (Exception $e) {
			$this->db->rollBack();
			throw $e;
		}
	}

	/**
	 * @return int[]
	 * @throws Exception
	 */
	public function listUser(string $uid): array {
		$q = $this->db->getQueryBuilder();
		$q->select('cid')
			->from(AccountEntity::TABLE)
			->where($q->expr()->eq('uid', $q->createNamedParameter($uid)));
		$result = $q->executeQuery();
		$entities = [];
		while ($cid = FromMixed::int($result->fetch())) {
			$entities[] = $cid;
		}
		$result->closeCursor();
		return $entities;
	}

	/** @throws Exception */
	public function deleteUser(IUser $user): void {
		$this->db->beginTransaction();
		try {
			$q = $this->db->getQueryBuilder();
			$q->delete(AccountEntity::TABLE)
				->where($q->expr()->eq('uid', $q->createNamedParameter($user->getUID())))
				->executeStatement();
			$this->db->commit();
		} catch (Exception $e) {
			$this->db->rollBack();
			throw $e;
		}
	}

	/** @throws Exception */
	public function updateUser(IUser $user): void {
		$this->db->beginTransaction();
		try {
			$q = $this->db->getQueryBuilder();
			$q->update(AccountEntity::TABLE)
				->set('password', $q->createNamedParameter(self::getHashFromUser($user)))
				->set('display_name', $q->createNamedParameter($user->getDisplayName()))
				->where($q->expr()->eq('uid', $q->createNamedParameter($user->getUID())))
				->executeStatement();
			$this->db->commit();
		} catch (Exception $e) {
			$this->db->rollBack();
			throw $e;
		}
	}

}
