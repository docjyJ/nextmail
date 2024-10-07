<?php

namespace OCA\Stalwart\Services;

use Exception;
use OCA\Stalwart\Models\ConfigEntity;
use OCA\Stalwart\Models\ServerStatus;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use Throwable;

class StalwartAPIService {
	private readonly ISqlService $sqlService;


	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 * @throws Exception
	 */
	public function __construct(
		private readonly IClientService  $clientService,
		private readonly LoggerInterface $logger,
		IConfig                          $config,
	) {
		/** @psalm-suppress MixedAssignment */
		$type = $config->getSystemValue('dbtype');
		if ($type === 'mysql') {
			$sqlService = new MysqlService($config);
		} else {
			throw new Exception('This app only supports MySQL');
		}
		$this->sqlService = $sqlService;
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

	public function challenge(ConfigEntity $config): ServerStatus {
		$auth = $config->getBasicAuth();
		if ($auth === null) {
			$this->logger->warning('Configurations ' . $config->cid . ' has no credentials');
			return ServerStatus::Invalid;
		}

		$url = $config->getUrl();
		if ($url === null) {
			$this->logger->warning('Configurations ' . $config->cid . ' has an invalid endpoint');
			return ServerStatus::Invalid;
		}
		try {
			$settings = $this->sqlService->getStalwartConfig($config->cid);
		} catch (Exception $e) {
			$this->logger->warning($e->getMessage(), ['exception' => $e]);
			return ServerStatus::Invalid;
		}

		$code = $this->settings($url, $auth, $settings);
		if ($code === 200) {
			$code = $this->reload($url, $auth);
			if ($code === 200) {
				return ServerStatus::Success;
			}
		}
		if ($code === 401) {
			return ServerStatus::Unauthorized;
		}

		if ($code === null) {
			return ServerStatus::BadNetwork;
		}
		return ServerStatus::BadServer;
	}
}
