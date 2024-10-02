<?php

namespace OCA\Stalwart\Settings;

use OCA\Stalwart\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\Settings\IDelegatedSettings;

class Admin implements IDelegatedSettings {
	public function __construct(
		private readonly IL10N $l,
	) {
	}

	/**
	 * @return TemplateResponse<200, array<never, never>>
	 */
	public function getForm(): TemplateResponse {
		return new TemplateResponse(Application::APP_ID, 'main');
	}

	/**
	 * @return Section::SETTING_ID
	 */
	public function getSection(): string {
		return Section::SETTING_ID;
	}

	/**
	 * @return 50
	 */
	public function getPriority(): int {
		return 50;
	}

	public function getName(): string {
		return $this->l->t('Stalwart');
	}

	/**
	 * @return array{}
	 */
	public function getAuthorizedAppConfig(): array {
		return [];
	}
}
