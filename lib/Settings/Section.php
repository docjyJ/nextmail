<?php

namespace OCA\Nextmail\Settings;

use OCA\Nextmail\AppInfo\Application;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

readonly class Section implements IIconSection {
	public const SETTING_ID = 'nextmail';

	public function __construct(
		private IL10N         $l,
		private IURLGenerator $urlGenerator,
	) {
	}

	/**
	 * @psalm-return 'nextmail'
	 */
	public function getID(): string {
		return self::SETTING_ID;
	}

	public function getName(): string {
		return $this->l->t('Nextmail');
	}

	/**
	 * @psalm-return 80
	 */
	public function getPriority(): int {
		return 80;
	}

	public function getIcon(): string {
		return $this->urlGenerator->imagePath(Application::APP_ID, 'app-dark.svg');
	}

}
