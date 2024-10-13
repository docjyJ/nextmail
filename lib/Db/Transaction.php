<?php

namespace OCA\Nextmail\Db;

use OCA\Nextmail\Models\AccountRole;
use OCA\Nextmail\Models\EmailType;
use OCA\Nextmail\Models\ServerStatus;
use OCA\Nextmail\SchemaV1\Columns;
use OCA\Nextmail\SchemaV1\Tables;
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
		$q = new SelectQuery($this->db, Tables::SERVERS);
		$q->where(Columns::SERVER_ID, $cid);
		return $q->fetchAll();
	}

	/** @throws Exception */
	public function insertConfig(string $cid, string $endpoint, string $username, string $password, ServerStatus $health): void {
		$q = $this->getTransactionBuilder();
		$q->insert(Tables::SERVERS)
			->values([
				Columns::SERVER_ID => $q->createNamedParameter($cid),
				Columns::SERVER_ENDPOINT => $q->createNamedParameter($endpoint),
				Columns::SERVER_USERNAME => $q->createNamedParameter($username),
				Columns::SERVER_PASSWORD => $q->createNamedParameter(self::password($password)),
				Columns::SERVER_HEALTH => $q->createNamedParameter($health->value),
			]);
		$this->execute($q);
	}

	/** @throws Exception */
	public function updateConfig(string $cid, string $endpoint, string $username, string $password, ServerStatus $health): void {
		$q = $this->getTransactionBuilder();
		$q->update(Tables::SERVERS)
			->set(Columns::SERVER_ENDPOINT, $q->createNamedParameter($endpoint))
			->set(Columns::SERVER_USERNAME, $q->createNamedParameter($username))
			->set(Columns::SERVER_PASSWORD, $q->createNamedParameter(self::password($password)))
			->set(Columns::SERVER_HEALTH, $q->createNamedParameter($health->value))
			->where($q->expr()->eq(Columns::SERVER_ID, $q->createNamedParameter($cid)));
		$this->execute($q);
	}

	/** @throws Exception */
	public function deleteConfig(string $cid): void {
		$q = $this->getTransactionBuilder();
		$q->delete(Tables::SERVERS)
			->where($q->expr()->eq(Columns::SERVER_ID, $q->createNamedParameter($cid)));
		$this->execute($q);
	}

	/** @throws Exception */
	public function selectAccount(?string $cid = null, ?string $uid = null, ?AccountRole $type = null): array {
		$q = new SelectQuery($this->db, Tables::ACCOUNTS);
		$q->where(Columns::SERVER_ID, $cid);
		$q->where(Columns::ACCOUNT_ID, $uid);
		$q->where(Columns::ACCOUNT_ROLE, $type?->value);
		return $q->fetchAll();
	}

	/** @throws Exception */
	public function insertAccount(string $uid, string $cid, string $displayName, ?string $password, AccountRole $type, ?int $quota): void {
		$q = $this->getTransactionBuilder();
		$q->insert(Tables::ACCOUNTS)
			->values([
				Columns::ACCOUNT_ID => $q->createNamedParameter($uid),
				Columns::SERVER_ID => $q->createNamedParameter($cid),
				Columns::ACCOUNT_NAME => $q->createNamedParameter($displayName),
				Columns::ACCOUNT_HASH => $password !== null ? $q->createNamedParameter(self::password($password)) : $q->createNamedParameter(null, IQueryBuilder::PARAM_NULL),
				Columns::ACCOUNT_ROLE => $q->createNamedParameter($type->value),
				Columns::ACCOUNT_QUOTA => $quota !== null ? $q->createNamedParameter(null, IQueryBuilder::PARAM_NULL) : $q->createNamedParameter($quota, IQueryBuilder::PARAM_INT),
			]);
		$this->execute($q);
	}

	/** @throws Exception */
	public function updateAccount(string $uid, string $displayName, ?string $password, AccountRole $type, ?int $quota): void {
		$q = $this->getTransactionBuilder();
		$q->update(Tables::ACCOUNTS)
			->set(Columns::ACCOUNT_NAME, $q->createNamedParameter($displayName))
			->set(Columns::ACCOUNT_HASH, $password !== null ? $q->createNamedParameter(self::password($password)) : $q->createNamedParameter(null, IQueryBuilder::PARAM_NULL))
			->set(Columns::ACCOUNT_ROLE, $q->createNamedParameter($type->value))
			->set(Columns::ACCOUNT_QUOTA, $quota !== null ? $q->createNamedParameter(null, IQueryBuilder::PARAM_NULL) : $q->createNamedParameter($quota, IQueryBuilder::PARAM_INT))
			->where($q->expr()->eq(Columns::ACCOUNT_ID, $q->createNamedParameter($uid)));
		$this->execute($q);
	}

	/** @throws Exception */
	public function deleteAccount(string $uid): void {
		$q = $this->getTransactionBuilder();
		$q->delete(Tables::ACCOUNTS)
			->where($q->expr()->eq(Columns::ACCOUNT_ID, $q->createNamedParameter($uid)));
		$this->execute($q);
	}

	/** @throws Exception */
	public function selectEmail(?string $uid = null, ?EmailType $type = null): array {
		$q = new SelectQuery($this->db, Tables::EMAILS);
		$q->where(Columns::ACCOUNT_ID, $uid);
		$q->where(Columns::EMAIL_TYPE, $type?->value);
		return $q->fetchAll();
	}

	/** @throws Exception */
	public function insertEmail(string $uid, string $email, EmailType $type): void {
		$q = $this->getTransactionBuilder();
		$q->insert(Tables::EMAILS)
			->values([
				Columns::ACCOUNT_ID => $q->createNamedParameter($uid),
				Columns::EMAIL_ID => $q->createNamedParameter($email),
				Columns::EMAIL_TYPE => $q->createNamedParameter($type->value),
			]);
		$this->execute($q);
	}


	/** @throws Exception */
	public function deleteEmail(string $uid, ?EmailType $type): void {
		$q = $this->getTransactionBuilder();
		$q->delete(Tables::EMAILS)->where($q->expr()->eq(Columns::ACCOUNT_ID, $q->createNamedParameter($uid)));
		if ($type !== null) {
			$q->andWhere($q->expr()->eq(Columns::EMAIL_TYPE, $q->createNamedParameter($type->value)));
		}
		$this->execute($q);
	}
}
