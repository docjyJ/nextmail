<?php

namespace OCA\Nextmail\Event;

use OCA\Nextmail\Db\GroupsManager;
use OCP\DB\Exception;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\GroupDeletedEvent;

/**
 * @implements IEventListener<GroupDeletedEvent>
 */
readonly class GroupDeletedListener implements IEventListener {
	public function __construct(
		private GroupsManager $gm,
	) {
	}

	/**
	 * @param GroupDeletedEvent $event
	 * @throws Exception
	 */
	public function handle(Event $event): void {
		$this->gm->delete($event->getGroup());
		$this->gm->commit();
	}
}
