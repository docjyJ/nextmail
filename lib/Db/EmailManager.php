<?php

namespace OCA\Nextmail\Db;

use OCA\Nextmail\Models\AccountEntity;
use OCA\Nextmail\Models\EmailEntity;
use OCA\Nextmail\Models\EmailType;
use OCP\DB\Exception;

readonly class EmailManager {
	public function __construct(
		private Transaction $tr,
	) {
	}

	/** @throws Exception */
	public function findPrimary(AccountEntity $account): ?EmailEntity {
		$emails = $this->tr->selectEmail($account->id, EmailType::Primary);
		return count($emails) === 0 ? null : EmailEntity::parse($account, $emails[0]);
	}

	/** @throws Exception */
	public function setPrimary(AccountEntity $account, string $email): EmailEntity {
		$e = new EmailEntity($account, strtolower($email), EmailType::Primary);
		$this->tr->deleteEmail($e->account->id, $e->type);
		$this->tr->insertEmail($e->account->id, $e->email, $e->type);
		$this->tr->commit();
		return $e;
	}
}
