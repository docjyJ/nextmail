<?php

declare(strict_types=1);

namespace OCA\Stalwart\Controller;

use OCA\Stalwart\Db\ServerConfig;
use OCA\Stalwart\Db\ServerConfigMapper;
use OCA\Stalwart\Service\StalwartApiService;
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
use OCP\DB\Exception;
use OCP\IRequest;

/**
 * @psalm-suppress UnusedClass
 * @type
 */
class ApiController extends OCSController {
	public function __construct(
		string                              $appName,
		IRequest                            $request,
		private readonly ServerConfigMapper $serverService,
		private readonly StalwartApiService $stalwartApiService,
	) {
		parent::__construct($appName, $request);
	}


	/**
	 * List all available servers
	 * @return DataResponse<Http::STATUS_OK, list<array{id: int, endpoint: string, username: string, password: string}>, array{}>
	 * @throws OCSException If an error occurs
	 *
	 * 200: Returns the list of available servers
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'GET', url: '/config')]
	public function listServers(): DataResponse {
		try {
			return new DataResponse(
				array_values(array_map(fn ($i) => $i->jsonSerialize(), $this->serverService->listServers())),
				Http::STATUS_OK, []);
		} catch (Exception $e) {
			throw new OCSException(previous: $e);
		}
	}

	/**
	 * Add a new server
	 * @return DataResponse<Http::STATUS_OK, array{id: int, endpoint: string, username: string, password: string}, array{}>
	 *
	 * 200: Returns the number of the new server
	 * @throws OCSException
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'POST', url: '/servers')]
	public function addServer(): DataResponse {
		try {
			return new DataResponse($this->serverService->insert(new ServerConfig())->jsonSerialize(), Http::STATUS_OK, []);
		} catch (Exception $e) {
			throw new OCSException(previous: $e);
		}
	}

	/**
	 * Get the configuration of a server number `id`
	 * @param int $id The server number
	 * @return DataResponse<Http::STATUS_OK, array{id: int, endpoint: string, username: string, password: string}, array{}>
	 * @throws OCSNotFoundException If the server number `id` does not exist
	 * @throws OCSException if an error occurs
	 *
	 * 200: Returns the server configuration
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'GET', url: '/config/{id}')]
	public function getServerConfig(int $id): DataResponse {
		try {
			$json = $this->serverService->getServer($id)->jsonSerialize();
			$json['password'] = '';
			return new DataResponse($json, Http::STATUS_OK, []);
		} catch (DoesNotExistException $e) {
			throw new OCSNotFoundException(previous: $e);

		} catch (MultipleObjectsReturnedException|Exception $e) {
			throw new OCSException(previous: $e);
		}
	}

	/**
	 * Set the configuration of a server number `id`
	 * @param int $id The server number
	 * @param string $endpoint The server endpoint (e.g. `https://mail.example.com:443/api`)
	 * @param string $username The username to authenticate with
	 * @param string $password The password to authenticate with
	 * @return DataResponse<Http::STATUS_OK, array{id: int, endpoint: string, username: string, password: string}, array{}>
	 * @throws OCSNotFoundException If the server number `id` does not exist
	 * @throws OCSException if an error occurs
	 *
	 * 200: The server configuration has been set
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'POST', url: '/config/{id}')]
	public function setServerConfig(int $id, string $endpoint, string $username, string $password): Response {
		try {
			$entity = $this->serverService->getServer($id);
			$entity->setEndpoint($endpoint);
			$entity->setUsername($username);
			$entity->setPassword($password);
			return new DataResponse($this->serverService->update($entity)->jsonSerialize(), Http::STATUS_OK, []);
		} catch (DoesNotExistException $e) {
			throw new OCSNotFoundException(previous: $e);
		} catch (MultipleObjectsReturnedException|Exception $e) {
			throw new OCSException(previous: $e);
		}
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
	#[ApiRoute(verb: 'GET', url: '/{id}/status')]
	public function getServerStatus(int $id): Response {
		try {
			$server = $this->serverService->getServer($id);
		} catch (DoesNotExistException $e) {
			throw new OCSNotFoundException(previous: $e);
		} catch (MultipleObjectsReturnedException|Exception $e) {
			throw new OCSException(previous: $e);
		}
		return new DataResponse($this->stalwartApiService->status($server), Http::STATUS_OK, []);
	}

}
