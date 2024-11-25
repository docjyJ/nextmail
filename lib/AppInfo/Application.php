<?php

declare(strict_types=1);

namespace OCA\Nextmail\AppInfo;

use OCA\Nextmail\Event\GroupChangedListener;
use OCA\Nextmail\Event\GroupCreateListener;
use OCA\Nextmail\Event\GroupDeletedListener;
use OCA\Nextmail\Event\MemberAddedListener;
use OCA\Nextmail\Event\MemberRemovedListener;
use OCA\Nextmail\Event\PasswordChangedListener;
use OCA\Nextmail\Event\UserChangedListener;
use OCA\Nextmail\Event\UserCreateListener;
use OCA\Nextmail\Event\UserDeletedListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Group\Events\GroupChangedEvent;
use OCP\Group\Events\GroupCreatedEvent;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\User\Events\PasswordUpdatedEvent;
use OCP\User\Events\UserChangedEvent;
use OCP\User\Events\UserCreatedEvent;
use OCP\User\Events\UserDeletedEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'nextmail';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(UserDeletedEvent::class, UserDeletedListener::class);
		$context->registerEventListener(UserCreatedEvent::class, UserCreateListener::class);
		$context->registerEventListener(UserChangedEvent::class, UserChangedListener::class);
		$context->registerEventListener(PasswordUpdatedEvent::class, PasswordChangedListener::class);
		$context->registerEventListener(GroupDeletedEvent::class, GroupDeletedListener::class);
		$context->registerEventListener(GroupCreatedEvent::class, GroupCreateListener::class);
		$context->registerEventListener(GroupChangedEvent::class, GroupChangedListener::class);
		$context->registerEventListener(UserAddedEvent::class, MemberAddedListener::class);
		$context->registerEventListener(UserRemovedEvent::class, MemberRemovedListener::class);
	}

	public function boot(IBootContext $context): void {
	}
}
