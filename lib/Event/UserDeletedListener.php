<?php

namespace OCA\Nextmail\Event;

use OCA\Nextmail\Db\Transaction;
use OCP\DB\Exception;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserDeletedEvent;

/**
 * @implements IEventListener<UserDeletedEvent>
 */
readonly class UserDeletedListener implements IEventListener {
	public function __construct(
		private Transaction $tr,
	) {
	}

	/**
	 * @param UserDeletedEvent $event
	 * @throws Exception
	 */
	public function handle(Event $event): void {
		$this->tr->deleteAccount($event->getUser()->getUID());
		$this->tr->commit();
	}
}
