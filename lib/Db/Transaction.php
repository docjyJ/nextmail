<?php

namespace OCA\Nextmail\Db;

use OCA\Nextmail\Models\AccountRole;
use OCA\Nextmail\Models\EmailType;
use OCA\Nextmail\Models\ServerHealth;
use OCA\Nextmail\SchemaV1\SchAccount;
use OCA\Nextmail\SchemaV1\SchEmail;
use OCA\Nextmail\SchemaV1\SchServer;
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
	public function selectServer(?string $id = null): array {
		$q = new SelectQuery($this->db, SchServer::TABLE);
		$q->where(SchServer::ID, $id);
		return $q->fetchAll();
	}

	/** @throws Exception */
	public function insertServer(string $id, string $endpoint, string $username, string $password, ServerHealth $health): void {
		$q = $this->getTransactionBuilder();
		$q->insert(SchServer::TABLE)
			->values([
				SchServer::ID => $q->createNamedParameter($id),
				SchServer::ENDPOINT => $q->createNamedParameter($endpoint),
				SchServer::USERNAME => $q->createNamedParameter($username),
				SchServer::PASSWORD => $q->createNamedParameter(self::password($password)),
				SchServer::HEALTH => $q->createNamedParameter($health->value),
			]);
		$this->execute($q);
	}

	/** @throws Exception */
	public function updateServer(string $id, string $endpoint, string $username, string $password, ServerHealth $health): void {
		$q = $this->getTransactionBuilder();
		$q->update(SchServer::TABLE)
			->set(SchServer::ENDPOINT, $q->createNamedParameter($endpoint))
			->set(SchServer::USERNAME, $q->createNamedParameter($username))
			->set(SchServer::PASSWORD, $q->createNamedParameter(self::password($password)))
			->set(SchServer::HEALTH, $q->createNamedParameter($health->value))
			->where($q->expr()->eq(SchServer::ID, $q->createNamedParameter($id)));
		$this->execute($q);
	}

	/** @throws Exception */
	public function deleteServer(string $id): void {
		$q = $this->getTransactionBuilder();
		$q->delete(SchServer::TABLE)
			->where($q->expr()->eq(SchServer::ID, $q->createNamedParameter($id)));
		$this->execute($q);
	}

	/** @throws Exception */
	public function selectAccount(?string $server_id = null, ?string $id = null, ?AccountRole $role = null): array {
		$q = new SelectQuery($this->db, SchAccount::TABLE);
		$q->where(SchServer::ID, $server_id);
		$q->where(SchAccount::ID, $id);
		$q->where(SchAccount::ROLE, $role?->value);
		return $q->fetchAll();
	}

	/** @throws Exception */
	public function insertAccount(string $id, string $server_id, string $name, ?string $hash, AccountRole $role, ?int $quota): void {
		$q = $this->getTransactionBuilder();
		$q->insert(SchAccount::TABLE)
			->values([
				SchAccount::ID => $q->createNamedParameter($id),
				SchServer::ID => $q->createNamedParameter($server_id),
				SchAccount::NAME => $q->createNamedParameter($name),
				SchAccount::HASH => $hash !== null ? $q->createNamedParameter(self::password($hash)) : $q->createNamedParameter(null, IQueryBuilder::PARAM_NULL),
				SchAccount::ROLE => $q->createNamedParameter($role->value),
				SchAccount::QUOTA => $quota !== null ? $q->createNamedParameter(null, IQueryBuilder::PARAM_NULL) : $q->createNamedParameter($quota, IQueryBuilder::PARAM_INT),
			]);
		$this->execute($q);
	}

	/** @throws Exception */
	public function updateAccount(string $id, string $name, ?string $hash, AccountRole $role, ?int $quota): void {
		$q = $this->getTransactionBuilder();
		$q->update(SchAccount::TABLE)
			->set(SchAccount::NAME, $q->createNamedParameter($name))
			->set(SchAccount::HASH, $hash !== null ? $q->createNamedParameter(self::password($hash)) : $q->createNamedParameter(null, IQueryBuilder::PARAM_NULL))
			->set(SchAccount::ROLE, $q->createNamedParameter($role->value))
			->set(SchAccount::QUOTA, $quota !== null ? $q->createNamedParameter(null, IQueryBuilder::PARAM_NULL) : $q->createNamedParameter($quota, IQueryBuilder::PARAM_INT))
			->where($q->expr()->eq(SchAccount::ID, $q->createNamedParameter($id)));
		$this->execute($q);
	}

	/** @throws Exception */
	public function deleteAccount(string $id): void {
		$q = $this->getTransactionBuilder();
		$q->delete(SchAccount::TABLE)
			->where($q->expr()->eq(SchAccount::ID, $q->createNamedParameter($id)));
		$this->execute($q);
	}

	/** @throws Exception */
	public function selectEmail(?string $id = null, ?EmailType $type = null): array {
		$q = new SelectQuery($this->db, SchEmail::TABLE);
		$q->where(SchAccount::ID, $id);
		$q->where(SchEmail::TYPE, $type?->value);
		return $q->fetchAll();
	}

	/** @throws Exception */
	public function insertEmail(string $id, string $email, EmailType $type): void {
		$q = $this->getTransactionBuilder();
		$q->insert(SchEmail::TABLE)
			->values([
				SchAccount::ID => $q->createNamedParameter($id),
				SchEmail::EMAIL => $q->createNamedParameter($email),
				SchEmail::TYPE => $q->createNamedParameter($type->value),
			]);
		$this->execute($q);
	}


	/** @throws Exception */
	public function deleteEmail(string $id, ?EmailType $type): void {
		$q = $this->getTransactionBuilder();
		$q->delete(SchEmail::TABLE)->where($q->expr()->eq(SchAccount::ID, $q->createNamedParameter($id)));
		if ($type !== null) {
			$q->andWhere($q->expr()->eq(SchEmail::TYPE, $q->createNamedParameter($type->value)));
		}
		$this->execute($q);
	}
}
