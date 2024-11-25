<?php

namespace OCA\Nextmail\Event;

use OCA\Nextmail\Db\GroupsManager;
use OCP\DB\Exception;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\UserAddedEvent;

/**
 * @implements IEventListener<UserAddedEvent>
 */
readonly class MemberAddedListener implements IEventListener {
	public function __construct(
		private GroupsManager $gm,
	) {
	}

	/**
	 * @param UserAddedEvent $event
	 * @throws Exception
	 */
	public function handle(Event $event): void {
		$this->gm->addMember($event->getGroup(), $event->getUser());
		$this->gm->commit();
	}
}
