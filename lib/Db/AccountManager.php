<?php

namespace OCA\Nextmail\Db;

use OCA\Nextmail\Models\AccountEntity;
use OCA\Nextmail\Models\AccountType;
use OCA\Nextmail\Models\ConfigEntity;
use OCP\DB\Exception;
use OCP\IUser;

readonly class AccountManager {
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
	public function listUser(ConfigEntity $config): array {
		return array_map(fn ($row) => AccountEntity::parse($config, $row), $this->tr->selectAccount($config->cid));
	}

	/** @throws Exception */
	public function findUser(ConfigEntity $config, string $uid): ?AccountEntity {
		$accounts = $this->tr->selectAccount($config->cid, $uid, AccountType::Individual);
		return count($accounts) === 0 ? null : AccountEntity::parse($config, $accounts[0]);
	}

	/** @throws Exception */
	public function createUser(ConfigEntity $config, IUser $user): AccountEntity {
		$account = new AccountEntity(
			$user->getUID(),
			$config,
			$user->getDisplayName(),
			self::getHashFromUser($user),
			AccountType::Individual,
			0);
		$this->tr->insertAccount($account->uid, $account->config->cid, $account->displayName, $account->password, $account->type, $account->quota);
		$this->tr->commit();
		return $account;
	}

	/** @throws Exception */
	public function delete(AccountEntity $account): void {
		$this->tr->deleteAccount($account->uid);
		$this->tr->commit();
	}

}
