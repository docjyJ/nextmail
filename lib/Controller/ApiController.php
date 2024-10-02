<?php

declare(strict_types=1);

namespace OCA\Stalwart\Controller;

use OCA\Stalwart\ResponseDefinitions;
use OCA\Stalwart\Services\ConfigService;
use OCA\Stalwart\Services\UsersService;
use OCA\Stalwart\Settings\Admin;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\DB\Exception;
use OCP\IRequest;

/**
 * @psalm-api
 * @psalm-import-type StalwartServerConfig from ResponseDefinitions
 * @psalm-import-type StalwartServerUser from ResponseDefinitions
 */
class ApiController extends OCSController {
	public function __construct(
		string                         $appName,
		IRequest                       $request,
		private readonly ConfigService $configService,
		private readonly UsersService  $usersService,
	) {
		parent::__construct($appName, $request);
	}


	/**
	 * List all available servers
	 * @return DataResponse<Http::STATUS_OK, list<StalwartServerConfig>, array{}>
	 * @throws OCSException If an error occurs
	 *
	 * 200: Returns the list of available servers
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'GET', url: '/config')]
	public function list(): DataResponse {
		try {
			$result = $this->configService->findMany();
		} catch (Exception $e) {
			throw new OCSException(previous: $e);
		}
		return new DataResponse($result);
	}

	/**
	 * Add a new server
	 * @param string $endpoint The server endpoint (e.g. `https://mail.example.com:443/api`)
	 * @param string $username The username to authenticate with
	 * @param string $password The password to authenticate with
	 * @return DataResponse<Http::STATUS_OK, StalwartServerConfig, array{}>
	 *
	 * 200: Returns the new server configuration
	 * @throws OCSException
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'POST', url: '/config')]
	public function post(string $endpoint, string $username, string $password): DataResponse {
		try {
			$result = $this->configService->create($endpoint, $username, $password);
		} catch (Exception $e) {
			throw new OCSException(previous: $e);
		}
		unset($result['password']);
		unset($result['health_expires']);
		return new DataResponse($result);
	}

	/**
	 * Get the configuration of a server number `id`
	 * @param int $id The server number
	 * @return DataResponse<Http::STATUS_OK, StalwartServerConfig, array{}>
	 * @throws OCSNotFoundException If the server number `id` does not exist
	 * @throws OCSException if an error occurs
	 *
	 * 200: Returns the server configuration
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'GET', url: '/config/{id}')]
	public function get(int $id): DataResponse {
		try {
			$result = $this->configService->findId($id);
		} catch (Exception $e) {
			throw new OCSException(previous: $e);
		}
		if ($result === null) {
			throw new OCSNotFoundException();
		}
		unset($result['password']);
		unset($result['health_expires']);
		return new DataResponse($result);
	}

	/**
	 * Set the configuration of a server number `id`
	 * @param int $id The server number
	 * @param string $endpoint The server endpoint (e.g. `https://mail.example.com:443/api`)
	 * @param string $username The username to authenticate with
	 * @param string $password The password to authenticate with
	 * @return DataResponse<Http::STATUS_OK, StalwartServerConfig, array{}>
	 * @throws OCSNotFoundException If the server number `id` does not exist
	 * @throws OCSException if an error occurs
	 *
	 * 200: Returns the updated server configuration
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'PUT', url: '/config/{id}')]
	public function put(int $id, string $endpoint, string $username, string $password): DataResponse {
		try {
			$result = $this->configService->updateCredentials($id, $endpoint, $username, $password);
		} catch (Exception $e) {
			throw new OCSException(previous: $e);
		}
		if ($result === null) {
			throw new OCSNotFoundException();
		}
		unset($result['password']);
		unset($result['health_expires']);
		return new DataResponse($result);
	}

	/**
	 * Delete the configuration of a server number `id`
	 * @param int $id The server number
	 * @return DataResponse<Http::STATUS_OK, StalwartServerConfig, array{}>
	 * @throws OCSNotFoundException If the server number `id` does not exist
	 * @throws OCSException if an error occurs
	 *
	 * 200: The server configuration has been deleted
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'DELETE', url: '/config/{id}')]
	public function delete(int $id): DataResponse {
		try {
			$result = $this->configService->delete($id);
		} catch (Exception $e) {
			throw new OCSException(previous: $e);
		}
		if ($result === null) {
			throw new OCSNotFoundException();
		}
		unset($result['password']);
		unset($result['health_expires']);
		return new DataResponse($result);

	}

	/**
	 * Get the users of the server number `id`
	 * @param int $id The server number
	 * @return DataResponse<Http::STATUS_OK, list<StalwartServerUser>, array{}>
	 * @throws OCSException if an error occurs
	 *
	 * 200: Returns the list of users
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'GET', url: '/config/{id}/users')]
	public function getUsers(int $id): DataResponse {
		try {
			$result = $this->usersService->findMany($id);
		} catch (Exception $e) {
			throw new OCSException(previous: $e);
		}
		return new DataResponse($result);
	}

	/**
	 * Add a user to the server number `id`
	 * @param int $id The server number
	 * @param string $uid The user ID
	 * @return DataResponse<Http::STATUS_OK, StalwartServerUser, array{}>
	 * @throws OCSNotFoundException If the user does not exist
	 * @throws OCSException if an error occurs
	 *
	 * 200: The user has been added to the server
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'POST', url: '/config/{id}/users')]
	public function postUsers(int $id, string $uid): DataResponse {
		try {
			$user = $this->usersService->add($id, $uid);
		} catch (Exception $e) {
			throw new OCSException(previous: $e);
		}
		if ($user === null) {
			throw new OCSNotFoundException();
		}
		return new DataResponse($user);
	}

	/**
	 * Remove a user from the server number `id`
	 * @param int $id The server number
	 * @param string $uid The user ID
	 * @return DataResponse<Http::STATUS_OK, StalwartServerUser, array{}>
	 * @throws OCSNotFoundException If the user does not exist
	 * @throws OCSException if an error occurs
	 *
	 * 200: The user has been removed from the server
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'DELETE', url: '/config/{id}/users')]
	public function deleteUsers(int $id, string $uid): DataResponse {
		try {
			$user = $this->usersService->remove($id, $uid);
		} catch (Exception $e) {
			throw new OCSException(previous: $e);
		}
		if ($user === null) {
			throw new OCSNotFoundException();
		}
		return new DataResponse($user);
	}
}
