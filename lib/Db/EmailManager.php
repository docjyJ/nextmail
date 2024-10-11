<?php

namespace OCA\Stalwart\Db;

use OCA\Stalwart\Models\AccountEntity;
use OCA\Stalwart\Models\EmailEntity;
use OCA\Stalwart\Models\EmailType;
use OCP\DB\Exception;

readonly class EmailManager {
	public function __construct(
		private Transaction $tr,
	) {
	}

	/** @throws Exception */
	public function findPrimary(AccountEntity $account): ?EmailEntity {
		$emails = $this->tr->selectEmail($account->uid, EmailType::Primary);
		return count($emails) === 0 ? null : EmailEntity::parse($account, $emails[0]);
	}

	/** @throws Exception */
	public function setPrimary(AccountEntity $account, string $email): EmailEntity {
		$e = new EmailEntity($account, strtolower($email), EmailType::Primary);
		$this->tr->deleteEmail($e->account->uid, $e->type);
		$this->tr->insertEmail($e->account->uid, $e->email, $e->type);
		$this->tr->commit();
		return $e;
	}
}
