<?php

namespace OCA\Nextmail\Db;

use OCA\Nextmail\Models\AccountEntity;
use OCA\Nextmail\Models\AccountRole;
use OCA\Nextmail\Models\ServerEntity;
use OCP\DB\Exception;
use OCP\IUser;

readonly class UserManager {
	public function __construct(
		private Transaction $tr,
	) {
	}

	public static function getHashFromUser(IUser $user): string {
		return preg_replace('/^[^$]*/', '', $user->getPasswordHash() ?? '') ?? '';
	}

	/**
	 * @return AccountEntity[]
	 * @throws Exception
	 */
	public function listUser(ServerEntity $server): array {
		return array_map(fn ($row) => AccountEntity::parse($server, $row), $this->tr->selectAccount($server->id));
	}

	/** @throws Exception */
	public function findUser(ServerEntity $server, string $id): ?AccountEntity {
		$users = $this->tr->selectAccount($server->id, $id, AccountRole::User);
		return count($users) === 0 ? null : AccountEntity::parse($server, $users[0]);
	}

	/** @throws Exception */
	public function createUser(ServerEntity $server, IUser $user): AccountEntity {
		$account = new AccountEntity(
			$user->getUID(),
			$server,
			$user->getDisplayName(),
			self::getHashFromUser($user),
			AccountRole::User,
			0);
		$this->tr->insertAccount($account->id, $account->server->id, $account->name, $account->hash, $account->role, $account->quota);
		$this->tr->commit();
		return $account;
	}

	/** @throws Exception */
	public function delete(AccountEntity $account): void {
		$this->tr->deleteAccount($account->id);
		$this->tr->commit();
	}

}
