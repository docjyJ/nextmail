<?php

namespace OCA\Stalwart\Service;

use Exception;
use OCA\Stalwart\Models\StalwartServer;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use Throwable;

class StalwartApiService {
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
		private readonly IClientService $clientService,
	) {
	}

	private function responseToString(IResponse $response): string {
		$body = $response->getBody();
		if (is_string($body)) {
			return $body;
		}
		$data = stream_get_contents($body);
		if (is_string($data)) {
			return $data;
		}
		return '';
	}

	/** @return array{type: string, text: string} */
	public function status(StalwartServer $server): array {
		if (!$server->isValid()) {
			return self::INVALID_CONNECTION;
		}
		$client = $this->clientService->newClient();
		try {//{"type":"Code","client_id":"webadmin","redirect_uri":null}
			$response = $client->post($server->getURL('/oauth'), [
				'body' => '{"type":"Code","client_id":"nextcloud","redirect_uri":null}',
				'headers' => ['Authorization' => $server->getAuthorization()]
			]);
			return str_contains($this->responseToString($response), '"is_admin":true')
				? self::SUCCESS_CONNECTION
				: self::WARNING_CONNECTION;
		} catch (Exception $e) {
			try {
				$response = $client->getResponseFromThrowable($e);
				return $response->getStatusCode() === 401
					? self::UNAUTHORIZED_CONNECTION
					: [
						'type' => 'error',
						'text' => $this->responseToString($response),
					];
			} catch (Throwable) {
				return self::ERROR_CONNECTION;
			}
		}
	}
}
