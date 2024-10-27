<?php

declare(strict_types=1);

namespace OCA\Nextmail\Controller;

use OCA\Nextmail\Db\Transaction;
use OCA\Nextmail\Models\ServerEntity;
use OCA\Nextmail\ResponseDefinitions;
use OCA\Nextmail\Services\StalwartApiService;
use OCA\Nextmail\Settings\Admin;
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
use Psr\Log\LoggerInterface;

/**
 * @psalm-api
 * @psalm-import-type NextmailServer from ResponseDefinitions
 */
class ApiServersController extends OCSController {
	public function __construct(
		string                           $appName,
		IRequest                         $request,
		private readonly Transaction        $tr,
		private readonly StalwartApiService $apiService,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * List all available servers
	 * @return DataResponse<Http::STATUS_OK, NextmailServer[], array{}>
	 * @throws OCSException If an error occurs
	 *
	 * 200: Returns the list of available servers
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'GET', url: '/servers')]
	public function list(): DataResponse {
		try {
			$servers = array_map(fn (mixed $row) => ServerEntity::parse($row)->jsonSerialize(), $this->tr->selectServer());
			return new DataResponse($servers);
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException($e->getMessage(), Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Add a new server
	 * @return DataResponse<Http::STATUS_OK, NextmailServer, array{}>
	 *
	 * 200: Returns the new server configuration
	 * @throws OCSException
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'POST', url: '/servers')]
	public function create(): DataResponse {
		try {
			$server = ServerEntity::newEmpty();
			$this->tr->insertServer(
				$server->id,
				$server->endpoint,
				$server->username,
				$server->password,
				$server->health
			);
			$this->tr->commit();
			return new DataResponse($server->jsonSerialize());
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException($e->getMessage(), Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Set the configuration of a server number `id`
	 * @param string $srv The server number
	 * @param string $endpoint The server endpoint (e.g. `https://mail.example.com:443/api`)
	 * @param string $username The username to authenticate with
	 * @param string $password The password to authenticate with
	 * @return DataResponse<Http::STATUS_OK, NextmailServer, array{}>
	 * @throws OCSNotFoundException If the server number `id` does not exist
	 * @throws OCSException if an error occurs
	 *
	 * 200: Returns the updated server configuration
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'PUT', url: '/servers/{srv}')]
	public function setServer(string $srv, string $endpoint, string $username, string $password): DataResponse {
		try {
			$servers = $this->tr->selectServer($srv);
			if (count($servers) === 1) {
				$server = ServerEntity::parse($servers[0]);
				$server = $this->apiService->challenge($server->updateCredential($endpoint, $username, $password));
				$this->tr->updateServer(
					$server->id,
					$server->endpoint,
					$server->username,
					$server->password,
					$server->health
				);
				$this->tr->commit();
				return new DataResponse($server->jsonSerialize());
			} else {
				throw new OCSNotFoundException();
			}
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException($e->getMessage(), Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Delete the configuration of a server number `id`
	 * @param string $srv The server number
	 * @return DataResponse<Http::STATUS_OK, NextmailServer, array{}>
	 * @throws OCSException if an error occurs
	 * @throws OCSNotFoundException If the server number `id` does not exist
	 *
	 * 200: The server configuration has been deleted
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'DELETE', url: '/servers/{srv}')]
	public function delServer(string $srv): DataResponse {
		try {
			$servers = $this->tr->selectServer($srv);
			if (count($servers) === 1) {
				$server = ServerEntity::parse($servers[0]);
				$this->tr->deleteServer($server->id);
				$this->tr->commit();
				return new DataResponse($server->jsonSerialize());
			} else {
				throw new OCSNotFoundException();
			}
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException($e->getMessage(), Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}
}
