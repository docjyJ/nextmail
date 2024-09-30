<?php

namespace OCA\Stalwart\Services;

use DateInterval;
use DateTime;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use Throwable;

class StalwartAPIService {
	private const URL_PATTERN = '/^https?:\\/\\/([a-z0-9-]+\\.)*[a-z0-9-]+(:\\d{1,5})?\\/api$/';

	public const SUCCESS_CONNECTION = 0;
	public const NO_ADMIN_CONNECTION = 1;
	public const UNAUTHORIZED_CONNECTION = 2;
	public const ERROR_SERVER = 3;
	public const ERROR_CONNECTION = 4;
	public const INVALID_CONNECTION = 5;


	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private readonly IClientService $clientService,
	) {
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
	 * @return array{0: int, 1: DateTime}
	 */
	public function challenge(string $endpoint, string $username, string $password): array {
		if ($username === '' || $password === '' || preg_match(self::URL_PATTERN, $endpoint) !== 1) {
			return [self::INVALID_CONNECTION, self::getExpiration(false)];
		}

		$client = $this->clientService->newClient();
		try {
			$response = $client->post($endpoint . '/oauth', [
				'body' => '{"type":"Code","client_id":"nextcloud","redirect_uri":null}',
				'headers' => ['Authorization' => self::genCred($username, $password)]
			]);
			return [
				str_contains(self::readBody($response), '"is_admin":true') ? self::SUCCESS_CONNECTION : self::NO_ADMIN_CONNECTION,
				self::getExpiration(true)
			];
		} catch (Throwable $e) {
			try {
				$response = $client->getResponseFromThrowable($e);
				return [
					$response->getStatusCode() === 401 ? self::UNAUTHORIZED_CONNECTION : self::ERROR_SERVER,
					self::getExpiration(false)
				];
			} catch (Throwable) {
				return [self::ERROR_CONNECTION, self::getExpiration(false)];
			}
		}
	}
}
