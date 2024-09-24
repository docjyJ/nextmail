<?php

namespace OCA\Stalwart\Service;

use OCA\Stalwart\Models\StalwartServer;
use OCP\AppFramework\Services\IAppConfig;

class ServerService {
	private const SETTING_KEY = 'servers';

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private readonly IAppConfig $config,
	) {
	}

	public function getServer(int $index): ?StalwartServer {
		$array = $this->config->getAppValueArray(self::SETTING_KEY);
		return key_exists($index, $array) && is_array($array[$index]) ? StalwartServer::jsonDeserialize($array[$index]) : null;
	}

	public function setServer(int $index, string $endpoint, string $username, string $password): void {
		$array = $this->config->getAppValueArray(self::SETTING_KEY);
		$tmp = key_exists($index, $array) && is_array($array[$index]) ? StalwartServer::jsonDeserialize($array[$index]) : null;
		$tmp = $tmp ?? StalwartServer::default();
		$tmp->setEndpoint($endpoint);
		$tmp->setCredentials($username, $password);
		$array[$index] = $tmp->jsonSerialize();
		$this->config->setAppValueArray(self::SETTING_KEY, $array);
	}
}
