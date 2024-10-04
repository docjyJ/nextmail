<?php

namespace OCA\Stalwart\Services;

use OCP\IConfig;

interface ISqlService {
	public function __construct(IConfig $config);
	public function getStalwartConfig(int $config_id): array;
}
