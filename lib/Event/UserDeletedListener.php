<?php

namespace OCA\Stalwart\Event;

use OCA\Stalwart\Services\UsersService;
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
		private readonly UsersService $usersService,
	) {
	}

	/**
	 * @param UserDeletedEvent $event
	 * @throws Exception
	 */
	public function handle(Event $event): void {
		$this->usersService->dropUser($event->getUser()->getUID());
	}
}
