<?php

namespace OCA\Stalwart\Services;

use OCA\Stalwart\ResponseDefinitions;
use OCP\DB\Exception;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use Throwable;

/**
 * @psalm-import-type StalwartServerStatus from ResponseDefinitions
 */
class StatusService {
	private const URL_PATTERN = '/^https?:\\/\\/([a-zA-Z0-9-]+\\.)*[a-zA-Z0-9-]+(:\\d{1,5})?\\/api$/';

	private const SUCCESS_CONNECTION = [
		'type' => 'success',
		'text' => 'Connection successful',
	];
	private const WARNING_CONNECTION = [
		'type' => 'warning',
		'text' => 'The user is not an administrator',
	];
	private const INVALID_CONNECTION = [
		'type' => 'error',
		'text' => 'User or password is not set or Stalwart endpoint is invalid',
	];
	private const UNAUTHORIZED_CONNECTION = [
		'type' => 'error',
		'text' => 'Credentials are invalid or the user is not authorized to access the server',
	];
	private const ERROR_CONNECTION = [
		'type' => 'error',
		'text' => 'Can\'t connect to the server',
	];

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private readonly ConfigService  $configService,
		private readonly IClientService $clientService,
	) {
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
	 * @param int $id
	 * @return ?StalwartServerStatus
	 * @throws Exception
	 */
	public function challenge(int $id): ?array {
		$config = $this->configService->find($id);

		if ($config === null) {
			return null;
		}

		if ($config['username'] === '' || $config['password'] === '' || preg_match(self::URL_PATTERN, $config['endpoint']) !== 1) {
			return self::INVALID_CONNECTION;
		}

		$client = $this->clientService->newClient();
		try {
			$response = $client->post($config['endpoint'] . '/oauth', [
				'body' => '{"type":"Code","client_id":"nextcloud","redirect_uri":null}',
				'headers' => ['Authorization' => 'Basic ' . base64_encode($config['username'] . ':' . $config['password'])],
			]);
			return str_contains(self::readBody($response), '"is_admin":true')
				? self::SUCCESS_CONNECTION
				: self::WARNING_CONNECTION;
		} catch (Throwable $e) {
			try {
				$response = $client->getResponseFromThrowable($e);
				return $response->getStatusCode() === 401
					? self::UNAUTHORIZED_CONNECTION
					: [
						'type' => 'error',
						'text' => self::readBody($response)
					];
			} catch (Throwable) {
				return self::ERROR_CONNECTION;
			}
		}
	}
}
