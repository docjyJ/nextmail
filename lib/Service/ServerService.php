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

	/** @return list<mixed> */
	private function getArray(): array {
		return array_values($this->config->getAppValueArray(self::SETTING_KEY));
	}


	/** @return list<int> */
	public function listServers(): array {
		return array_keys($this->getArray());
	}

	public function pushServer(): int {
		$array = $this->getArray();
		$array[] = StalwartServer::default()->jsonSerialize();
		$this->config->setAppValueArray(self::SETTING_KEY, $array);
		return count($array) - 1;

	}

	public function getServer(int $index): ?StalwartServer {
		$array = $this->getArray();
		return is_array($array[$index]) ? StalwartServer::jsonDeserialize($array[$index]) : null;
	}

	public function setServer(int $index, string $endpoint, string $username, string $password): bool {
		$array = $this->getArray();
		$tmp = key_exists($index, $array) && is_array($array[$index]) ? StalwartServer::jsonDeserialize($array[$index]) : null;
		if ($tmp === null) {
			return false;
		}


		$tmp->setEndpoint($endpoint);
		$tmp->setCredentials($username, $password);
		$array[$index] = $tmp->jsonSerialize();
		$this->config->setAppValueArray(self::SETTING_KEY, $array);
		return true;
	}
}
