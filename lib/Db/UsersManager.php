<?php

namespace OCA\Nextmail\Db;

use OCA\Nextmail\Models\AccountRole;
use OCA\Nextmail\Models\EmailType;
use OCA\Nextmail\Models\UserEntity;
use OCA\Nextmail\SchemaV1\SchAccount;
use OCA\Nextmail\SchemaV1\SchServer;
use OCP\DB\Exception;
use OCP\IUser;
use OCP\IUserManager;

readonly class UsersManager {
	public function __construct(
		private IUserManager $um,
		private Transaction $tr,
	) {
	}

	/** @throws Exception */
	private function loadEntity(IUser $user): ?UserEntity {
		$data = $this->tr->selectAccount($user->getUID(), [AccountRole::User, AccountRole::Admin]);
		$i = array_key_first($data);
		return $i !== null && is_array($data[$i]) ? UserEntity::fromIUser(
			$user,
			is_string($data[$i][SchServer::ID]) ? $data[$i][SchServer::ID] : null,
			$data[$i][SchAccount::ROLE] === AccountRole::Admin->value,
			is_int($data[$i][SchAccount::QUOTA]) ? $data[$i][SchAccount::QUOTA] : null
		) : null;
	}

	/**
	 * @return list<UserEntity>
	 * @throws Exception
	 */
	public function listAll(): array {
		return array_map(fn ($x) => $this->loadEntity($x) ?? UserEntity::fromIUser($x), array_values($this->um->search('')));
	}

	/** @throws Exception */
	private function processQuery(UserEntity $data, bool $create): UserEntity {
		if ($create) {
			$this->tr->insertAccount(
				$data->id,
				$data->name,
				$data->getRoleEnum(),
				$data->server_id,
				$data->hash,
				$data->quota
			);
		} else {
			$this->tr->updateAccount(
				$data->id,
				$data->name,
				$data->getRoleEnum(),
				$data->server_id,
				$data->hash,
				$data->quota
			);
		}
		$this->tr->deleteEmail($data->id, EmailType::Primary);
		if ($data->primaryEmail !== null) {
			$this->tr->insertEmail($data->id, $data->primaryEmail, EmailType::Primary);
		}
		return $data;
	}

	/** @throws Exception */
	public function syncOne(IUser $user): void {
		$data = $this->loadEntity($user);
		if ($data !== null) {
			$this->processQuery($data, false);
		} else {
			$this->processQuery(UserEntity::fromIUser($user), true);
		}
	}

	/** @throws Exception */
	public function delete(IUser $user): void {
		$this->tr->deleteAccount($user->getUID());
	}


	/** @throws Exception */
	public function update(string $uid, ?string $srv, bool $admin, ?int $quota): ?UserEntity {
		$user = $this->um->get($uid);
		if ($user !== null) {
			$data = $this->loadEntity($user);
			return $data === null
				? $this->processQuery(UserEntity::fromIUser($user, $srv, $admin, $quota), true)
				: $this->processQuery($data->update($srv, $admin, $quota), false);
		} else {
			return null;
		}
	}

	/** @throws Exception */
	public function commit(): void {
		$this->tr->commit();
	}

}
