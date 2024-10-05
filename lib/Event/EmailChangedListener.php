<?php

namespace OCA\Stalwart\Event;

use OCA\Stalwart\Db\AccountManager;
use OCA\Stalwart\Db\EmailManager;
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
			$value = $event->getValue();
			if (is_string($value)) {
				foreach ($this->accountManager->listUser($event->getUser()->getUID()) as $item) {
					$this->emailManager->updatePrimary($item->cid, $item->uid, $value);
				}
			}
		}
	}
}
