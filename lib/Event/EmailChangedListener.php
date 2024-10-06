<?php

namespace OCA\Stalwart\Event;

use OCA\Stalwart\Db\AccountManager;
use OCA\Stalwart\Db\EmailManager;
use OCA\Stalwart\Models\AccountEntity;
use OCA\Stalwart\Models\ConfigEntity;
use OCP\DB\Exception;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserChangedEvent;

/**
 * @implements IEventListener<UserChangedEvent>
 */
class EmailChangedListener implements IEventListener {
	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private readonly AccountManager $accountManager,
		private readonly EmailManager $emailManager,
	) {
	}

	/**
	 * @param UserChangedEvent $event
	 * @throws Exception
	 */
	public function handle(Event $event): void {
		if ($event->getFeature() === 'email') {
			/** @psalm-suppress MixedAssignment */
			$email = $event->getValue();
			if (is_string($email)) {
				$uid = $event->getUser()->getUID();
				foreach ($this->accountManager->listUser($event->getUser()->getUID()) as $cid) {
					$this->emailManager->setPrimary(new AccountEntity(new ConfigEntity($cid), $uid), $email);
				}
			}
		}
	}
}
