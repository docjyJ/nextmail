<?php

namespace OCA\Stalwart\Event;

use OCA\Stalwart\Services\UsersService;
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
		private readonly UsersService $usersService,
	) {
	}

	/**
	 * @param PasswordUpdatedEvent $event
	 * @throws Exception
	 */
	public function handle(Event $event): void {
		$sha512 = hash('sha512', $event->getPassword());
		$this->usersService->updatePassword($event->getUser()->getUID(), $sha512);
	}
}
