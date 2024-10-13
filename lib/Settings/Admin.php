<?php

namespace OCA\Nextmail\Settings;

use OCA\Nextmail\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\Settings\IDelegatedSettings;

readonly class Admin implements IDelegatedSettings {
	public function __construct(
		private IL10N $l,
	) {
	}

	/**
	 * @return TemplateResponse<200, array<never, never>>
	 */
	public function getForm(): TemplateResponse {
		return new TemplateResponse(Application::APP_ID, 'main');
	}

	/**
	 * @psalm-return 'nextmail'
	 */
	public function getSection(): string {
		return Section::SETTING_ID;
	}

	/**
	 * @psalm-return 50
	 */
	public function getPriority(): int {
		return 50;
	}

	public function getName(): string {
		return $this->l->t('Nextmail');
	}

	/**
	 * @return array{}
	 */
	public function getAuthorizedAppConfig(): array {
		return [];
	}
}
