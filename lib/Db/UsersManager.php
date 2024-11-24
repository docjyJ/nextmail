<?php

namespace OCA\Nextmail\Db;

use OCA\Nextmail\Models\AccountRole;
use OCA\Nextmail\Models\EmailType;
use OCA\Nextmail\Models\UserEntity;
use OCA\Nextmail\SchemaV1\SchAccount;
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
	private function loadEntity(IUser $i_user): ?UserEntity {
		$data = $this->tr->selectAccount($i_user->getUID(), [AccountRole::User, AccountRole::Admin]);
		return is_array($data[0]) ? UserEntity::fromIUser(
			$i_user,
			is_string($data[0][SchAccount::ID]) ? $data[0][SchAccount::ID] : null,
			$data[0][SchAccount::ROLE] === AccountRole::Admin,
			is_int($data[0][SchAccount::QUOTA]) ? $data[0][SchAccount::QUOTA] : null
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
	public function syncAll(): void {
		foreach ($this->um->search('') as $i_user) {
			$this->syncOne($i_user);
		}
	}

	/** @throws Exception */
	public function syncOne(IUser $i_user): void {
		$data = $this->loadEntity($i_user);
		if ($data !== null) {
			$this->processQuery($data, false);
		} else {
			$this->processQuery(UserEntity::fromIUser($i_user), true);
		}
	}


	/** @throws Exception */
	public function update(string $uid, ?string $srv, bool $admin, ?int $quota): ?UserEntity {
		$i_user = $this->um->get($uid);
		if ($i_user !== null) {
			$data = $this->loadEntity($i_user);
			return $data === null
				? $this->processQuery(UserEntity::fromIUser($i_user, $srv, $admin, $quota), true)
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
