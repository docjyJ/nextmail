<?php

namespace OCA\Stalwart\Event;

use OCA\Stalwart\Services\UsersService;
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
		private readonly UsersService $usersService,
	) {
	}

	/**
	 * @param UserChangedEvent $event
	 * @throws Exception
	 */
	public function handle(Event $event): void {
		if ($event->getFeature() === 'email') {
			/**  @psalm-suppress MixedAssignment */
			$value = $event->getValue();
			$this->usersService->updatePrimaryEmail($event->getUser()->getUID(), is_string($value) ? $value : null);
		}
	}
}
