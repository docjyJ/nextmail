<?php

namespace OCA\Stalwart\Cron;

use DateTime;
use OCA\Stalwart\Db\ConfigManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\Exception;

/** @psalm-suppress UnusedClass */
class CheckTask extends TimedJob {
	public function __construct(
		ITimeFactory                   $time,
		private readonly ConfigManager $configService,
	) {
		parent::__construct($time);
		$this->setInterval(3600);
	}

	/** @throws Exception */
	protected function run(mixed $argument): void {
		$now = new DateTime();
		foreach ($this->configService->list() as $config) {
			if ($config->expires <= $now) {
				$this->configService->update($config);
			}
		}
	}
}
