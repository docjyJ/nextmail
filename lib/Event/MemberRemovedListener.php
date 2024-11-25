<?php

namespace OCA\Nextmail\Event;

use OCA\Nextmail\Db\GroupsManager;
use OCP\DB\Exception;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\UserRemovedEvent;

/**
 * @implements IEventListener<UserRemovedEvent>
 */
readonly class MemberRemovedListener implements IEventListener {
	public function __construct(
		private GroupsManager $gm,
	) {
	}

	/**
	 * @param UserRemovedEvent $event
	 * @throws Exception
	 */
	public function handle(Event $event): void {
		$this->gm->removeMember($event->getGroup(), $event->getUser());
		$this->gm->commit();
	}
}
