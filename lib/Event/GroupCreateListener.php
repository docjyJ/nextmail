<?php

namespace OCA\Nextmail\Event;

use OCA\Nextmail\Db\GroupsManager;
use OCP\DB\Exception;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\GroupCreatedEvent;

/**
 * @implements IEventListener<GroupCreatedEvent>
 */
readonly class GroupCreateListener implements IEventListener {
	public function __construct(
		private GroupsManager $gm,
	) {
	}

	/**
	 * @param GroupCreatedEvent $event
	 * @throws Exception
	 */
	public function handle(Event $event): void {
		$this->gm->syncOne($event->getGroup());
		$this->gm->commit();
	}
}
