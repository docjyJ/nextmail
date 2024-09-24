<?php

namespace OCA\Stalwart\Settings;

use OCA\Stalwart\AppInfo\Application;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class Section implements IIconSection {
	public const SETTING_ID = 'stalwart';

	/**
	 * @psalm-suppress PossiblyUnusedParam
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function __construct(
		string                         $appName,
		private readonly IL10N         $l,
		private readonly IURLGenerator $urlGenerator,
	) {
	}

	public function getID(): string {
		return self::SETTING_ID;
	}

	public function getName(): string {
		return $this->l->t('Stalwart');
	}

	public function getPriority(): int {
		return 80;
	}

	public function getIcon(): string {
		return $this->urlGenerator->imagePath(Application::APP_ID, 'app.svg');
	}

}
