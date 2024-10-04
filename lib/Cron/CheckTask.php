<?php

namespace OCA\Stalwart\Cron;

use OCA\Stalwart\Services\ConfigService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\Exception;

/** @psalm-suppress UnusedClass */
class CheckTask extends TimedJob {
	public function __construct(
		ITimeFactory                   $time,
		private readonly ConfigService $configService,
	) {
		parent::__construct($time);
		$this->setInterval(3600);
	}

	/** @throws Exception */
	protected function run(mixed $argument): void {
		$this->configService->updateExpiredHealth();
	}

}
