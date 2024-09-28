<?php

namespace OCA\Stalwart\Controller;

use Exception;
use OCA\Stalwart\Db\ServerConfig;
use OCA\Stalwart\Db\ServerConfigMapper;
use OCA\Stalwart\Settings\Admin;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IRequest;
use Throwable;

/** @psalm-suppress UnusedClass */
class StatusController extends OCSController {
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
		string                              $appName,
		IRequest                            $request,
		private readonly ServerConfigMapper $serverService,
		private readonly IClientService     $clientService,
	) {
		parent::__construct($appName, $request);
	}


	/**
	 * Get the status of the configuration of a server number `id`
	 * @param int $id The server number
	 * @return DataResponse<Http::STATUS_OK, array{type: string, text: string}, array{}>
	 * @throws OCSNotFoundException If the server number `id` does not exist
	 * @throws OCSException if an error occurs
	 *
	 * 200: Returns the status of the server configuration
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'GET', url: '/status/{id}')]
	public function getServerStatus(int $id): Response {
		try {
			$server = $this->serverService->getServer($id);
		} catch (DoesNotExistException $e) {
			throw new OCSNotFoundException(previous: $e);
		} catch (MultipleObjectsReturnedException|\OCP\DB\Exception $e) {
			throw new OCSException(previous: $e);
		}
		return new DataResponse($this->status($server), Http::STATUS_OK, []);
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
	private function status(ServerConfig $server): array {
		if (!$server->isValid()) {
			return self::INVALID_CONNECTION;
		}
		$client = $this->clientService->newClient();
		try {
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
