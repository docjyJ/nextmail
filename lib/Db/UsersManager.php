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
		private IUserManager $userManager,
		private Transaction  $tr,
	) {
	}

	/** @throws Exception */
	private function buildQuery(IUser $i_user, bool $isNew, ?string $server_id = null, bool $admin = false, ?int $quota = null): UserEntity {
		$user = UserEntity::fromIUser($i_user, $server_id, $admin, $quota);
		if ($isNew) {
			$this->tr->insertAccount(
				$user->id,
				$user->name,
				$user->getRoleEnum(),
				$user->server_id,
				$user->hash,
				$user->quota
			);
		} else {
			$this->tr->updateAccount(
				$user->id,
				$user->name,
				$user->getRoleEnum(),
				$user->server_id,
				$user->hash,
				$user->quota
			);
		}
		$this->tr->deleteEmail($user->id, EmailType::Primary);
		if ($user->primaryEmail !== null) {
			$this->tr->insertEmail($user->id, $user->primaryEmail, EmailType::Primary);
		}
		return $user;
	}

	/**
	 * @return array{new: bool, srv: ?string, admin: bool, quota: ?int}
	 * @throws Exception
	 */
	private function loadData(string $uid): array {
		$users = $this->tr->selectAccount($uid, [AccountRole::User, AccountRole::Admin]);
		return is_array($users[0]) ? [
			'new' => false,
			'srv' => is_string($users[0][SchAccount::ID]) ? $users[0][SchAccount::ID] : null,
			'admin' => $users[0][SchAccount::ROLE] === AccountRole::Admin,
			'quota' => is_int($users[0][SchAccount::QUOTA]) ? $users[0][SchAccount::QUOTA] : null
		] : [
			'new' => true,
			'srv' => null,
			'admin' => false,
			'quota' => null
		];
	}

	/** @throws Exception */
	public function updateAllIUser(): void {
		foreach ($this->userManager->search('') as $i_user) {
			$this->updateIUser($i_user);
		}
	}

	/** @throws Exception */
	public function updateIUser(IUser $i_user): void {
		$userData = $this->loadData($i_user->getUID());
		$this->buildQuery($i_user, $userData['new'], $userData['srv'], $userData['admin'], $userData['quota']);
	}

	/** @throws Exception */
	public function updateFromStarch(string $uid, ?string $srv, bool $admin, ?int $quota): ?UserEntity {
		$i_user = $this->userManager->get($uid);
		return $i_user !== null ? $this->buildQuery(
			$i_user,
			$this->loadData($uid)['new'],
			$srv,
			$admin,
			$quota
		) : null;
	}

	/** @throws Exception */
	public function commit(): void {
		$this->tr->commit();
	}

}
