<?php

namespace OCA\Nextmail\Event;

use OCA\Nextmail\Db\UsersManager;
use OCP\DB\Exception;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserChangedEvent;

/**
 * @implements IEventListener<UserChangedEvent>
 */
readonly class UserChangedListener implements IEventListener {
	public function __construct(
		private UsersManager $um,
	) {
	}

	/**
	 * @param UserChangedEvent $event
	 * @throws Exception
	 */
	public function handle(Event $event): void {
		$this->um->updateIUser($event->getUser());
		$this->um->commit();
	}
}
