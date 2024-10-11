<?php

namespace OCA\Stalwart\Db;

use OCA\Stalwart\Models\AccountEntity;
use OCA\Stalwart\Models\AccountType;
use OCA\Stalwart\Models\ConfigEntity;
use OCA\Stalwart\Models\EmailEntity;
use OCA\Stalwart\Models\EmailType;
use OCA\Stalwart\Models\ServerStatus;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

readonly class Transaction {
	public function __construct(
		private IDBConnection $db,
	) {
	}

	private static function password(string $password): string {
		return preg_replace('/^[^$]*/', '', $password) ?? '';
	}

	/** @throws Exception */
	private function getTransactionBuilder(): IQueryBuilder {
		if (!$this->db->inTransaction()) {
			$this->db->beginTransaction();
		}
		return $this->db->getQueryBuilder();
	}

	/** @throws Exception */
	private function execute(IQueryBuilder $q): void {
		try {
			$q->executeStatement();
		} catch (Exception $e) {
			$this->db->rollBack();
			throw $e;
		}
	}

	/** @throws Exception */
	public function commit(): void {
		if ($this->db->inTransaction()) {
			try {
				$this->db->commit();
			} catch (Exception $e) {
				$this->db->rollBack();
				throw $e;
			}
		}
	}

	/** @throws Exception */
	public function selectConfig(?string $cid = null): array {
		$q = new SelectQuery($this->db, ConfigEntity::TABLE);
		$q->where('cid', $cid);
		return $q->fetchAll();
	}

	/** @throws Exception */
	public function insertConfig(string $cid, string $endpoint, string $username, string $password, ServerStatus $health): void {
		$q = $this->getTransactionBuilder();
		$q->insert(ConfigEntity::TABLE)
			->values([
				'cid' => $q->createNamedParameter($cid),
				'endpoint' => $q->createNamedParameter($endpoint),
				'username' => $q->createNamedParameter($username),
				'password' => $q->createNamedParameter(self::password($password)),
				'health' => $q->createNamedParameter($health->value),
			]);
		$this->execute($q);
	}

	/** @throws Exception */
	public function updateConfig(string $cid, string $endpoint, string $username, string $password, ServerStatus $health): void {
		$q = $this->getTransactionBuilder();
		$q->update(ConfigEntity::TABLE)
			->set('endpoint', $q->createNamedParameter($endpoint))
			->set('username', $q->createNamedParameter($username))
			->set('password', $q->createNamedParameter(self::password($password)))
			->set('health', $q->createNamedParameter($health->value))
			->where($q->expr()->eq('cid', $q->createNamedParameter($cid)));
		$this->execute($q);
	}

	/** @throws Exception */
	public function deleteConfig(string $cid): void {
		$q = $this->getTransactionBuilder();
		$q->delete(ConfigEntity::TABLE)
			->where($q->expr()->eq('cid', $q->createNamedParameter($cid)));
		$this->execute($q);
	}

	/** @throws Exception */
	public function selectAccount(?string $cid = null, ?string $uid = null, ?AccountType $type = null): array {
		$q = new SelectQuery($this->db, AccountEntity::TABLE);
		$q->where('cid', $cid);
		$q->where('uid', $uid);
		$q->where('type', $type?->value);
		return $q->fetchAll();
	}

	/** @throws Exception */
	public function insertAccount(string $uid, string $cid, string $displayName, string $password, AccountType $type, int $quota): void {
		$q = $this->getTransactionBuilder();
		$q->insert(AccountEntity::TABLE)
			->values([
				'uid' => $q->createNamedParameter($uid),
				'cid' => $q->createNamedParameter($cid),
				'display_name' => $q->createNamedParameter($displayName),
				'password' => $q->createNamedParameter(self::password($password)),
				'type' => $q->createNamedParameter($type->value),
				'quota' => $q->createNamedParameter($quota, IQueryBuilder::PARAM_INT),
			]);
		$this->execute($q);
	}

	/** @throws Exception */
	public function updateAccount(string $uid, string $displayName, string $password, AccountType $type, int $quota): void {
		$q = $this->getTransactionBuilder();
		$q->update(AccountEntity::TABLE)
			->set('display_name', $q->createNamedParameter($displayName))
			->set('password', $q->createNamedParameter(self::password($password)))
			->set('type', $q->createNamedParameter($type->value))
			->set('quota', $q->createNamedParameter($quota, IQueryBuilder::PARAM_INT))
			->where($q->expr()->eq('uid', $q->createNamedParameter($uid)));
		$this->execute($q);
	}

	/** @throws Exception */
	public function deleteAccount(string $uid): void {
		$q = $this->getTransactionBuilder();
		$q->delete(AccountEntity::TABLE)
			->where($q->expr()->eq('uid', $q->createNamedParameter($uid)));
		$this->execute($q);
	}

	/** @throws Exception */
	public function selectEmail(?string $uid = null, ?EmailType $type = null): array {
		$q = new SelectQuery($this->db, EmailEntity::TABLE);
		$q->where('uid', $uid);
		$q->where('type', $type?->value);
		return $q->fetchAll();
	}

	/** @throws Exception */
	public function insertEmail(string $uid, string $email, EmailType $type): void {
		$q = $this->getTransactionBuilder();
		$q->insert(EmailEntity::TABLE)
			->values([
				'uid' => $q->createNamedParameter($uid),
				'email' => $q->createNamedParameter($email),
				'type' => $q->createNamedParameter($type->value),
			]);
		$this->execute($q);
	}


	/** @throws Exception */
	public function deleteEmail(string $uid, ?EmailType $type): void {
		$q = $this->getTransactionBuilder();
		$q->delete(EmailEntity::TABLE)->where($q->expr()->eq('uid', $q->createNamedParameter($uid)));
		if ($type !== null) {
			$q->andWhere($q->expr()->eq('type', $q->createNamedParameter($type->value)));
		}
		$this->execute($q);
	}
}
