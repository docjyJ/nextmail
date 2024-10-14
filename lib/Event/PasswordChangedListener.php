<?php

namespace OCA\Nextmail\Event;

use OCA\Nextmail\Db\Transaction;
use OCA\Nextmail\Db\UserManager;
use OCA\Nextmail\Models\AccountRole;
use OCA\Nextmail\Models\EmailType;
use OCP\DB\Exception;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\PasswordUpdatedEvent;

/**
 * @implements IEventListener<PasswordUpdatedEvent>
 */
readonly class PasswordChangedListener implements IEventListener {
	public function __construct(
		private Transaction $tr,
	) {
	}

	/**
	 * @param PasswordUpdatedEvent $event
	 * @throws Exception
	 */
	public function handle(Event $event): void {
		$user = $event->getUser();
		$uid = $user->getUID();
		$this->tr->updateAccount($uid, $user->getDisplayName(),
			UserManager::getHashFromUser($user), AccountRole::User, 0);
		$this->tr->deleteEmail($uid, EmailType::Primary);
		$email = $user->getPrimaryEMailAddress();
		if ($email !== null && str_contains($email, '@')) {
			$this->tr->insertEmail($uid, $email, EmailType::Primary);
		}
		$this->tr->commit();
	}
}
