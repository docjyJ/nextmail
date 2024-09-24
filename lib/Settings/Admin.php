<?php

namespace OCA\Stalwart\Settings;

use OCA\Stalwart\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\Settings\IDelegatedSettings;

class Admin implements IDelegatedSettings {
	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function __construct(
		private readonly IL10N $l,
	) {
	}

	public function getForm(): TemplateResponse {
		return new TemplateResponse(Application::APP_ID, 'main');
	}

	public function getSection(): string {
		return Section::SETTING_ID;
	}

	public function getPriority(): int {
		return 50;
	}

	public function getName(): ?string {
		return $this->l->t('Stalwart');
	}

	public function getAuthorizedAppConfig(): array {
		return ['/*/'];
	}
}
