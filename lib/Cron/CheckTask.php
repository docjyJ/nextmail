<?php

namespace OCA\Nextmail\Cron;

use OCA\Nextmail\Db\Transaction;
use OCA\Nextmail\Models\ServerEntity;
use OCA\Nextmail\Services\StalwartApiService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\Exception;

class CheckTask extends TimedJob {
	public function __construct(
		ITimeFactory                        $time,
		private readonly StalwartApiService $apiService,
		private readonly Transaction        $tr,
	) {
		parent::__construct($time);
		$this->setInterval(3600);
	}

	/** @throws Exception */
	protected function run(mixed $argument): void {
		foreach (array_map(fn (mixed $row) => ServerEntity::parse($row), $this->tr->selectServer()) as $old) {
			$server = $this->apiService->challenge($old);
			if ($server->health !== $old->health) {
				$this->tr->updateServer(
					$server->id,
					$server->endpoint,
					$server->username,
					$server->password,
					$server->health
				);
				$this->tr->commit();
			}
		}
	}
}
