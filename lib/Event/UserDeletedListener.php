<?php

namespace OCA\Nextmail\Event;

use OCA\Nextmail\Db\UsersManager;
use OCP\DB\Exception;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserDeletedEvent;

/**
 * @implements IEventListener<UserDeletedEvent>
 */
readonly class UserDeletedListener implements IEventListener {
	public function __construct(
		private UsersManager $um,
	) {
	}

	/**
	 * @param UserDeletedEvent $event
	 * @throws Exception
	 */
	public function handle(Event $event): void {
		$this->um->delete($event->getUser());
		$this->um->commit();
	}
}
