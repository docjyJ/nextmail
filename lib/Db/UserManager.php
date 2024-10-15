<?php

namespace OCA\Nextmail\Db;

use OCA\Nextmail\Models\AccountRole;
use OCA\Nextmail\Models\EmailType;
use OCA\Nextmail\Models\ServerEntity;
use OCA\Nextmail\Models\UserEntity;
use OCA\Nextmail\SchemaV1\SchAccount;
use OCP\DB\Exception;
use OCP\IUser;
use ValueError;

readonly class UserManager {
	public function __construct(
		private Transaction $tr,
	) {
	}

	/** @throws Exception */
	private function toUserObject(ServerEntity $server, mixed $row): UserEntity {
		if (!is_array($row)) {
			throw new ValueError('row must be an array');
		}
		if (!is_string($row[SchAccount::ID])) {
			throw new ValueError('id must be a string');
		}
		$emails = $this->tr->selectEmail($row[SchAccount::ID], EmailType::Primary);
		if (count($emails) !== 0) {
			if (!is_array($emails[0])) {
				throw new ValueError('email must be an array');
			}
			return UserEntity::parse($server, array_merge($row, $emails[0]));
		} else {
			return UserEntity::parse($server, $row);
		}
	}

	/**
	 * @return UserEntity[]
	 * @throws Exception
	 */
	public function list(ServerEntity $server): array {
		return array_map(fn ($row) => $this->toUserObject($server, $row), $this->tr->selectAccount($server->id, null, [AccountRole::User, AccountRole::Admin]));
	}

	/** @throws Exception */
	public function get(ServerEntity $server, string $id): ?UserEntity {
		$users = $this->tr->selectAccount($server->id, $id, [AccountRole::User, AccountRole::Admin]);
		return count($users) !== 0 ? $this->toUserObject($server, $users[0]) : null;
	}

	/** @throws Exception */
	public function save(UserEntity $account): UserEntity {
		$this->tr->updateAccountRole($account->id, $account->getRoleEnum());
		$this->tr->commit();
		return $account;
	}

	/** @throws Exception */
	public function create(ServerEntity $server, IUser $user, bool $admin, ?int $quota): UserEntity {
		$userObject = UserEntity::fromIUser($server, $user);
		$userObject = $userObject->updateAdminQuota($admin, $quota);
		$this->tr->insertAccount($userObject->id, $userObject->server->id, $userObject->name, $userObject->hash, $userObject->getRoleEnum(), $userObject->quota);

		if ($userObject->primaryEmail !== null) {
			$this->tr->insertEmail($userObject->id, $userObject->primaryEmail, EmailType::Primary);
		}
		$this->tr->commit();
		return $userObject;
	}

	/** @throws Exception */
	public function delete(UserEntity $account): void {
		$this->tr->deleteAccount($account->id);
		$this->tr->commit();
	}

}
