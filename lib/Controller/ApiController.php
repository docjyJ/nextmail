<?php

declare(strict_types=1);

namespace OCA\Nextmail\Controller;

use OCA\Nextmail\Db\ServerManager;
use OCA\Nextmail\Db\UserManager;
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
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type NextmailServer from ResponseDefinitions
 * @psalm-import-type NextmailUser from ResponseDefinitions
 */
class ApiController extends OCSController {
	public function __construct(
		string                           $appName,
		IRequest                         $request,
		private readonly ServerManager   $serverManager,
		private readonly UserManager     $userManager,
		private readonly IUserManager    $ncUserManager,
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
	public function getServers(): DataResponse {
		try {
			return new DataResponse(array_map(fn ($c) => $c->jsonSerialize(), $this->serverManager->list()));
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
	public function setServers(): DataResponse {
		try {
			return new DataResponse($this->serverManager->create()->jsonSerialize());
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException($e->getMessage(), Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Get the configuration of a server number `id`
	 * @param string $srv The server number
	 * @return DataResponse<Http::STATUS_OK, NextmailServer, array{}>
	 * @throws OCSNotFoundException If the server number `id` does not exist
	 * @throws OCSException if an error occurs
	 *
	 * 200: Returns the server configuration
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'GET', url: '/servers/{srv}')]
	public function getServer(string $srv): DataResponse {
		try {
			if ($server = $this->serverManager->get($srv)) {
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
			if ($server = $this->serverManager->get($srv)) {
				$server = $server->updateCredential($endpoint, $username, $password);
				$server = $this->serverManager->save($server);
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
			if ($server = $this->serverManager->get($srv)) {
				$this->serverManager->delete($server);
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
	 * Get the users of the server number `id`
	 * @param string $srv The server number
	 * @return DataResponse<Http::STATUS_OK, NextmailUser[], array{}>
	 * @throws OCSException if an error occurs
	 *
	 * 200: Returns the list of users
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'GET', url: '/servers/{srv}/users')]
	public function getServerUsers(string $srv): DataResponse {
		try {
			if ($server = $this->serverManager->get($srv)) {
				return new DataResponse(array_map(fn ($u) => $u->jsonSerialize(), $this->userManager->list($server)));
			} else {
				throw new OCSNotFoundException();
			}
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException($e->getMessage(), Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Get the user of the server number `id`
	 * @param string $srv The server number
	 * @param string $usr The user ID
	 * @return DataResponse<Http::STATUS_OK, NextmailUser, array{}>
	 * @throws OCSNotFoundException If the user does not exist
	 * @throws OCSException if an error occurs
	 *
	 * 200: Returns the user
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'GET', url: '/servers/{srv}/users/{usr}')]
	public function getServerUser(string $srv, string $usr): DataResponse {
		try {
			if (($server = $this->serverManager->get($srv)) && $user = $this->userManager->get($server, $usr)) {
				return new DataResponse($user->jsonSerialize());
			} else {
				throw new OCSNotFoundException();
			}
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException($e->getMessage(), Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Add a user to the server number `id`
	 * @param string $srv The server number
	 * @param string $usr The user ID
	 * @param bool $admin Whether the user is an admin
	 * @param int|null $quota The user quota
	 * @return DataResponse<Http::STATUS_OK, NextmailUser, array{}>
	 * @throws OCSNotFoundException If the user does not exist
	 * @throws OCSException if an error occurs
	 *
	 * 200: The user has been added to the server
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'POST', url: '/servers/{srv}/users/{usr}')]
	public function setServerUser(string $srv, string $usr, bool $admin, ?int $quota): DataResponse {
		try {
			if (($server = $this->serverManager->get($srv)) && $user = $this->ncUserManager->get($usr)) {
				return new DataResponse($this->userManager->create($server, $user, $admin, $quota)->jsonSerialize());
			} else {
				throw new OCSNotFoundException();
			}
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException($e->getMessage(), Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Update the user of the server number `id`
	 * @param string $srv The server number
	 * @param string $usr The user ID
	 * @param bool $admin Whether the user is an admin
	 * @param int|null $quota The user quota
	 * @return DataResponse<Http::STATUS_OK, NextmailUser, array{}>
	 * @throws OCSNotFoundException If the user does not exist
	 * @throws OCSException if an error occurs
	 *
	 * 200: The user has been updated
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'PUT', url: '/servers/{srv}/users/{usr}')]
	public function updateServerUser(string $srv, string $usr, bool $admin, ?int $quota): DataResponse {
		try {
			if (($server = $this->serverManager->get($srv)) && $user = $this->userManager->get($server, $usr)) {
				return new DataResponse($this->userManager->save($user->updateAdminQuota($admin, $quota))->jsonSerialize());
			} else {
				throw new OCSNotFoundException();
			}
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException($e->getMessage(), Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Remove a user from the server number `id`
	 * @param string $srv The server number
	 * @param string $usr The user ID
	 * @return DataResponse<Http::STATUS_OK, null, array{}>
	 * @throws OCSNotFoundException If the user does not exist
	 * @throws OCSException if an error occurs
	 *
	 * 200: The user has been removed from the server
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'DELETE', url: '/servers/{srv}/users/{usr}')]
	public function deleteServerUser(string $srv, string $usr): DataResponse {
		try {
			$server = $this->serverManager->get($srv);
			$account = $server ? $this->userManager->get($server, $usr) : null;
			if ($account) {
				$this->userManager->delete($account);
				return new DataResponse(null);
			} else {
				throw new OCSNotFoundException();
			}
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException($e->getMessage(), Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}
}
