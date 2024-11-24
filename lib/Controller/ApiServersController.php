<?php

declare(strict_types=1);

namespace OCA\Nextmail\Controller;

use OCA\Nextmail\Db\ServerManager;
use OCA\Nextmail\Models\ServerEntity;
use OCA\Nextmail\ResponseDefinitions;
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
		string $appName,
		IRequest $request,
		private readonly ServerManager $sm,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * List all available servers
	 * @return DataResponse<Http::STATUS_OK, list<NextmailServer>, array{}>
	 * @throws OCSException If an error occurs
	 *
	 * 200: Returns the list of available servers
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'GET', url: '/servers')]
	public function list(): DataResponse {
		try {
			return new DataResponse(array_map(fn (ServerEntity $x) => $x->jsonSerialize(), $this->sm->listAll()));
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
			$data = $this->sm->create();
			$this->sm->commit();
			return new DataResponse($data->jsonSerialize());
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
			$data = $this->sm->update($srv, $endpoint, $username, $password);
			$this->sm->commit();
			if ($data !== null) {
				return new DataResponse($data->jsonSerialize());
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
			$data = $this->sm->delete($srv);
			$this->sm->commit();
			if ($data !== null) {
				return new DataResponse($data->jsonSerialize());
			} else {
				throw new OCSNotFoundException();
			}
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException($e->getMessage(), Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}
}
