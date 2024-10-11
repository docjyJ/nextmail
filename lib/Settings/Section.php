<?php

namespace OCA\Stalwart\Settings;

use OCA\Stalwart\AppInfo\Application;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

readonly class Section implements IIconSection {
	public const SETTING_ID = 'stalwart';

	public function __construct(
		private IL10N         $l,
		private IURLGenerator $urlGenerator,
	) {
	}

	/**
	 * @psalm-return 'stalwart'
	 */
	public function getID(): string {
		return self::SETTING_ID;
	}

	public function getName(): string {
		return $this->l->t('Stalwart');
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
