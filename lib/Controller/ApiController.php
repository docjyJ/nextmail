<?php

declare(strict_types=1);

namespace OCA\Stalwart\Controller;

use OCA\Stalwart\Service\ServerService;
use OCA\Stalwart\Service\StalwartApiService;
use OCA\Stalwart\Settings\Admin;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

/**
 * @psalm-suppress UnusedClass
 */
class ApiController extends OCSController {
	public function __construct(
		string                              $appName,
		IRequest                            $request,
		private readonly ServerService      $serverService,
		private readonly StalwartApiService $stalwartApiService,
	) {
		parent::__construct($appName, $request);
	}


	/**
	 * List all available servers
	 * @return DataResponse<Http::STATUS_OK, list<int>, array{}>
	 *
	 * 200: Returns the list of available servers
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'GET', url: '/servers')]
	public function listServers(): DataResponse {
		$server = $this->serverService->listServers();
		return new DataResponse($server, Http::STATUS_OK, []);
	}

	/**
	 * Add a new server
	 * @return DataResponse<Http::STATUS_OK, array{id: int}, array{}>
	 *
	 * 200: Returns the number of the new server
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'POST', url: '/servers')]
	public function addServer(): DataResponse {
		$server = $this->serverService->pushServer();
		return new DataResponse(['id' => $server], Http::STATUS_OK, []);
	}

	/**
	 * Get the configuration of a server number `id`
	 * @param int $id The server number
	 * @return DataResponse<Http::STATUS_OK, array{endpoint: string, username: string, password: string}, array{}>
	 * @throws OCSNotFoundException If the server number `id` does not exist
	 *
	 * 200: Returns the server configuration
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'GET', url: '/servers/{id}/config')]
	public function getServerConfig(int $id): DataResponse {
		$server = $this->serverService->getServer($id);
		if ($server === null) {
			throw new OCSNotFoundException();
		}
		$json = $server->jsonSerialize();
		$json['password'] = '';
		return new DataResponse($json, Http::STATUS_OK, []);
	}

	/**
	 * Set the configuration of a server number `id`
	 * @param int $id The server number
	 * @param string $endpoint The server endpoint (e.g. `https://mail.example.com:443/api`)
	 * @param string $username The username to authenticate with
	 * @param string $password The password to authenticate with
	 * @return Response<Http::STATUS_CREATED, array{}>
	 * @throws OCSNotFoundException If the server number `id` does not exist
	 *
	 * 201: The server configuration has been set
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'POST', url: '/servers/{id}/config')]
	public function setServerConfig(int $id, string $endpoint, string $username, string $password): Response {
		if ($this->serverService->setServer($id, $endpoint, $username, $password)) {
			return new Response(Http::STATUS_CREATED, []);
		} else {
			throw new OCSNotFoundException();
		}
	}

	/**
	 * Get the status of the configuration of a server number `id`
	 * @param int $id The server number
	 * @return DataResponse<Http::STATUS_OK, array{type: string, text: string}, array{}>
	 * @throws OCSNotFoundException If the server number `id` does not exist
	 *
	 * 200: Returns the status of the server configuration
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'GET', url: '/servers/{id}/status')]
	public function getServerStatus(int $id): Response {
		$server = $this->serverService->getServer($id);
		if ($server === null) {
			throw new OCSNotFoundException();
		}
		return new DataResponse($this->stalwartApiService->status($server), Http::STATUS_OK, []);
	}

}
