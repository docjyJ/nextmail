<?php

namespace OCA\Stalwart\Event;

use OCA\Stalwart\Db\AccountManager;
use OCP\DB\Exception;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserDeletedEvent;

/**
 * @implements IEventListener<UserDeletedEvent>
 */
class UserDeletedListener implements IEventListener {
	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private readonly AccountManager $accountManager,
	) {
	}

	/**
	 * @param UserDeletedEvent $event
	 * @throws Exception
	 */
	public function handle(Event $event): void {
		$this->accountManager->deleteUser($event->getUser());
	}
}
