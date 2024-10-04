<?php

declare(strict_types=1);

namespace OCA\Stalwart\AppInfo;

use OCA\Stalwart\Event\EmailChangedListener;
use OCA\Stalwart\Event\PasswordChangedListener;
use OCA\Stalwart\Event\UserDeletedListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\User\Events\PasswordUpdatedEvent;
use OCP\User\Events\UserChangedEvent;
use OCP\User\Events\UserDeletedEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'stalwart';

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(UserDeletedEvent::class, UserDeletedListener::class);
		$context->registerEventListener(UserChangedEvent::class, EmailChangedListener::class);
		$context->registerEventListener(PasswordUpdatedEvent::class, PasswordChangedListener::class);
	}

	public function boot(IBootContext $context): void {
	}
}
