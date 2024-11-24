<?php

namespace OCA\Nextmail\Event;

use OCA\Nextmail\Db\UsersManager;
use OCP\DB\Exception;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\PasswordUpdatedEvent;

/**
 * @implements IEventListener<PasswordUpdatedEvent>
 */
readonly class PasswordChangedListener implements IEventListener {
	public function __construct(
		private UsersManager $um,
	) {
	}

	/**
	 * @param PasswordUpdatedEvent $event
	 * @throws Exception
	 */
	public function handle(Event $event): void {
		$this->um->syncOne($event->getUser());
		$this->um->commit();
	}
}
