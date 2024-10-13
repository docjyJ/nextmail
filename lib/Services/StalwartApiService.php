<?php

namespace OCA\Nextmail\Services;

use Exception;
use OCA\Nextmail\Models\ConfigEntity;
use OCA\Nextmail\Models\ServerStatus;
use OCP\Http\Client\IClientService;
use Psr\Log\LoggerInterface;
use Throwable;

readonly class StalwartApiService {
	public function __construct(
		private IClientService     $clientService,
		private LoggerInterface    $logger,
		private StalwartSqlService $sqlService,
	) {
	}

	private function settings(string $url, string $auth, string $settings): ?int {
		$client = $this->clientService->newClient();
		try {
			return $client->post($url . '/settings', [
				'body' => $settings,
				'headers' => ['Authorization' => $auth]
			])->getStatusCode();
		} catch (Exception $e) {
			$this->logger->warning($e->getMessage(), ['exception' => $e]);
			try {
				return $client->getResponseFromThrowable($e)->getStatusCode();
			} catch (Throwable) {
				return null;
			}
		}
	}

	private function reload(string $url, string $auth): ?int {
		$client = $this->clientService->newClient();
		try {
			return $client->get($url . '/reload', [
				'headers' => ['Authorization' => $auth]
			])->getStatusCode();
		} catch (Exception $e) {
			$this->logger->warning($e->getMessage(), ['exception' => $e]);
			try {
				return $client->getResponseFromThrowable($e)->getStatusCode();
			} catch (Throwable) {
				return null;
			}
		}
	}

	public function challenge(ConfigEntity $config): ConfigEntity {
		$auth = $config->getBasicAuth();
		if ($auth === null) {
			$this->logger->warning('Configurations ' . $config->id . ' has no credentials');
			return $config->updateHealth(ServerStatus::Invalid);
		}

		$url = $config->getUrl();
		if ($url === null) {
			$this->logger->warning('Configurations ' . $config->id . ' has an invalid endpoint');
			return $config->updateHealth(ServerStatus::Invalid);
		}
		try {
			$settings = $this->sqlService->getStalwartConfig($config->id);
		} catch (Exception $e) {
			$this->logger->warning($e->getMessage(), ['exception' => $e]);
			return $config->updateHealth(ServerStatus::Invalid);
		}

		$code = $this->settings($url, $auth, $settings);
		if ($code === 200) {
			$code = $this->reload($url, $auth);
			if ($code === 200) {
				return $config->updateHealth(ServerStatus::Success);
			}
		}
		if ($code === 401) {
			return $config->updateHealth(ServerStatus::Unauthorized);
		}

		if ($code === null) {
			return $config->updateHealth(ServerStatus::BadNetwork);
		}
		return $config->updateHealth(ServerStatus::BadServer);
	}
}
