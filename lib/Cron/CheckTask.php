<?php

namespace OCA\Nextmail\Cron;

use OCA\Nextmail\Db\ServerManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\Exception;

class CheckTask extends TimedJob {
	public function __construct(
		ITimeFactory                   $time,
		private readonly ServerManager $serverManager,
	) {
		parent::__construct($time);
		$this->setInterval(3600);
	}

	/** @throws Exception */
	protected function run(mixed $argument): void {
		foreach ($this->serverManager->list() as $config) {
			$this->serverManager->save($config);
		}
	}
}
