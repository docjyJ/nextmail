<?php

namespace OCA\Nextmail\Services;

use Exception;
use OCA\Nextmail\Models\ServerEntity;
use OCA\Nextmail\Models\ServerHealth;
use OCP\Http\Client\IClientService;
use Psr\Log\LoggerInterface;
use Throwable;

readonly class StalwartApiService {
	public function __construct(
		private IClientService $clientService,
		private LoggerInterface $logger,
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

	public function challenge(ServerEntity $srv): ServerEntity {
		$auth = $srv->getBasicAuth();
		if ($auth === null) {
			$this->logger->warning('Configurations ' . $srv->id . ' has no credentials');
			return $srv->updateHealth(ServerHealth::Invalid);
		}

		$url = $srv->getUrl();
		if ($url === null) {
			$this->logger->warning('Configurations ' . $srv->id . ' has an invalid endpoint');
			return $srv->updateHealth(ServerHealth::Invalid);
		}
		try {
			$settings = $this->sqlService->getStalwartConfig($srv->id);
		} catch (Exception $e) {
			$this->logger->warning($e->getMessage(), ['exception' => $e]);
			return $srv->updateHealth(ServerHealth::Invalid);
		}

		$code = $this->settings($url, $auth, $settings);
		if ($code === 200) {
			$code = $this->reload($url, $auth);
			if ($code === 200) {
				return $srv->updateHealth(ServerHealth::Success);
			}
		}
		if ($code === 401) {
			return $srv->updateHealth(ServerHealth::Unauthorized);
		}

		if ($code === null) {
			return $srv->updateHealth(ServerHealth::BadNetwork);
		}
		return $srv->updateHealth(ServerHealth::BadServer);
	}
}
