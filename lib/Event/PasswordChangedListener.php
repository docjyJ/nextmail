<?php

namespace OCA\Stalwart\Event;

use OCA\Stalwart\Db\AccountManager;
use OCP\DB\Exception;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\PasswordUpdatedEvent;

/**
 * @implements IEventListener<PasswordUpdatedEvent>
 */
class PasswordChangedListener implements IEventListener {
	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private readonly AccountManager $accountManager,
	) {
	}

	/**
	 * @param PasswordUpdatedEvent $event
	 * @throws Exception
	 */
	public function handle(Event $event): void {
		$password = $event->getUser()->getPasswordHash();
		if ($password !== null) {
			$this->accountManager->forceUpdatePassword($event->getUser()->getUID(), $password);
		}
	}
}
