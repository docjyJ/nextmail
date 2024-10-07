<?php

namespace OCA\Stalwart\Cron;

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
		foreach ($this->configService->list() as $config) {
			if ($config->hasExpired()) {
				$this->configService->update($config);
			}
		}
	}
}
