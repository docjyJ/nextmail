<?php

namespace OCA\Nextmail\Event;

use OCA\Nextmail\Db\UsersManager;
use OCP\DB\Exception;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserCreatedEvent;

/**
 * @implements IEventListener<UserCreatedEvent>
 */
readonly class UserCreateListener implements IEventListener {
	public function __construct(
		private UsersManager $um,
	) {
	}

	/**
	 * @param UserCreatedEvent $event
	 * @throws Exception
	 */
	public function handle(Event $event): void {
		$this->um->syncOne($event->getUser());
		$this->um->commit();
	}
}
