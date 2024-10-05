<?php

namespace OCA\Stalwart\Services;

use DateInterval;
use DateTime;
use Exception;
use OCA\Stalwart\Models\ServerStatus;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use Throwable;

class StalwartAPIService {
	private const URL_PATTERN = '/^https?:\\/\\/([a-z0-9-]+\\.)*[a-z0-9-]+(:\\d{1,5})?\\/api$/';
	private readonly ISqlService $sqlService;


	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 * @throws Exception
	 */
	public function __construct(
		private readonly IClientService $clientService,
		private readonly LoggerInterface $logger,
		IConfig $config,
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

	private static function genCred(string $username, string $password): string {
		return 'Basic ' . base64_encode($username . ':' . $password);
	}

	/**
	 * @param IResponse $response
	 * @return string
	 */
	private static function readBody(IResponse $response): string {
		$body = $response->getBody();
		if (is_string($body)) {
			return $body;
		}
		$content = stream_get_contents($body);
		if ($content !== false) {
			return $content;
		}
		return 'Can\'t read the response body';
	}

	/**
	 * @param bool $success
	 * @return DateTime
	 */
	private static function getExpiration(bool $success): DateTime {
		$expiration = new DateTime();
		return $expiration->add($success
			? new DateInterval('P1D')
			: new DateInterval('PT1H'));
	}

	/**
	 * @param string $endpoint
	 * @param string $username
	 * @param string $password
	 *
	 * @return array{0: ServerStatus, 1: DateTime}
	 * @psalm-return list{ServerStatus, DateTime}
	 */
	public function challenge(string $endpoint, string $username, string $password): array {
		if ($username === '' || $password === '' || preg_match(self::URL_PATTERN, $endpoint) !== 1) {
			$this->logger->warning('A Stalwart server configuration is invalid');
			return [ServerStatus::Invalid, self::getExpiration(false)];
		}

		$client = $this->clientService->newClient();
		try {
			$response = $client->post($endpoint . '/oauth', [
				'body' => json_encode([
					'type' => 'Code',
					'client_id' => 'nextcloud',
					'redirect_uri' => null
				], JSON_THROW_ON_ERROR),
				'headers' => ['Authorization' => self::genCred($username, $password)]
			]);
			if (str_contains(self::readBody($response), '"is_admin":true')) {
				return [ ServerStatus::Success, self::getExpiration(true) ];
			} else {
				$this->logger->warning('The user of a Stalwart server is not an admin');
				return [ ServerStatus::NoAdmin, self::getExpiration(true) ];
			}
		} catch (Throwable $e) {
			try {
				$response = $client->getResponseFromThrowable($e);
				$this->logger->warning($e->getMessage(), ['exception' => $e]);
				return [
					$response->getStatusCode() === 401 ? ServerStatus::Unauthorized : ServerStatus::BadServer,
					self::getExpiration(false)
				];
			} catch (Throwable $e) {
				$this->logger->warning($e->getMessage(), ['exception' => $e]);
				return [ServerStatus::BadNetwork, self::getExpiration(false)];
			}
		}
	}

	/**
	 * @throws Exception
	 */
	public function pushDataBase(int $config_id, string $endpoint, string $username, string $password): void {
		$this->clientService->newClient()->post($endpoint . '/settings', [
			'body' => $this->sqlService->getStalwartConfig($config_id),
			'headers' => ['Authorization' => self::genCred($username, $password)]
		]);
	}
}
