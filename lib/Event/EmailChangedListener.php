<?php

namespace OCA\Stalwart\Event;

use OCA\Stalwart\Db\AccountManager;
use OCA\Stalwart\Db\Transaction;
use OCA\Stalwart\Models\AccountType;
use OCA\Stalwart\Models\EmailType;
use OCP\DB\Exception;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserChangedEvent;

/**
 * @implements IEventListener<UserChangedEvent>
 */
readonly class EmailChangedListener implements IEventListener {
	public function __construct(
		private Transaction $tr,
	) {
	}

	/**
	 * @param UserChangedEvent $event
	 * @throws Exception
	 */
	public function handle(Event $event): void {
		$user = $event->getUser();
		$uid = $user->getUID();
		$this->tr->updateAccount($uid, $user->getDisplayName(),
			AccountManager::getHashFromUser($user), AccountType::Individual, 0);
		$this->tr->deleteEmail($uid, EmailType::Primary);
		$email = $user->getPrimaryEMailAddress();
		if ($email !== null && str_contains($email, '@')) {
			$this->tr->insertEmail($uid, $email, EmailType::Primary);
		}
		$this->tr->commit();
	}
}
