<?php

declare(strict_types=1);

namespace OCA\Stalwart\Controller;

use OCA\Stalwart\Db\AccountManager;
use OCA\Stalwart\Db\ConfigManager;
use OCA\Stalwart\Db\EmailManager;
use OCA\Stalwart\Models\AccountEntity;
use OCA\Stalwart\Models\AccountsType;
use OCA\Stalwart\Models\EmailEntity;
use OCA\Stalwart\Models\EmailType;
use OCA\Stalwart\ResponseDefinitions;
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
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

/**
 * @psalm-api
 * @psalm-import-type StalwartServerConfig from ResponseDefinitions
 * @psalm-import-type StalwartServerUser from ResponseDefinitions
 */
class ApiController extends OCSController {
	public function __construct(
		string                           $appName,
		IRequest                         $request,
		private readonly ConfigManager   $configManager,
		private readonly AccountManager  $accountManager,
		private readonly EmailManager    $emailManager,
		private readonly IUserManager    $userManager,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}


	/**
	 * List all available servers
	 * @return DataResponse<Http::STATUS_OK, StalwartServerConfig[], array{}>
	 * @throws OCSException If an error occurs
	 *
	 * 200: Returns the list of available servers
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'GET', url: '/config')]
	public function list(): DataResponse {
		try {
			$result = $this->configManager->list();
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException($e->getMessage(), Http::STATUS_INTERNAL_SERVER_ERROR);
		}
		return new DataResponse(array_map(static fn ($c) => $c->toData(), $result));
	}

	/**
	 * Add a new server
	 * @return DataResponse<Http::STATUS_OK, int, array{}>
	 *
	 * 200: Returns the new server configuration
	 * @throws OCSException
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'POST', url: '/config')]
	public function post(): DataResponse {
		try {
			$id = $this->configManager->create();
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException($e->getMessage(), Http::STATUS_INTERNAL_SERVER_ERROR);
		}
		return new DataResponse($id);
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
			$result = $this->configManager->find($id);
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException($e->getMessage(), Http::STATUS_INTERNAL_SERVER_ERROR);
		}
		if ($result === null) {
			throw new OCSNotFoundException();
		}
		return new DataResponse($result->toData());
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
			$entity = $this->configManager->find($id);
			if ($entity !== null) {
				$entity->endpoint = $endpoint;
				$entity->username = $username;
				if ($password !== '') {
					$entity->password = $password;
				}
				$this->configManager->update($entity);
			}
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException($e->getMessage(), Http::STATUS_INTERNAL_SERVER_ERROR);
		}
		if ($entity === null) {
			throw new OCSNotFoundException();
		}
		return new DataResponse($entity->toData());
	}

	/**
	 * Delete the configuration of a server number `id`
	 * @param int $id The server number
	 * @return DataResponse<Http::STATUS_OK, null, array{}>
	 * @throws OCSException if an error occurs
	 *
	 * 200: The server configuration has been deleted
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'DELETE', url: '/config/{id}')]
	public function delete(int $id): DataResponse {
		try {
			$this->configManager->delete($id);
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException($e->getMessage(), Http::STATUS_INTERNAL_SERVER_ERROR);
		}
		return new DataResponse(null);

	}

	/**
	 * Get the users of the server number `id`
	 * @param int $id The server number
	 * @return DataResponse<Http::STATUS_OK, StalwartServerUser[], array{}>
	 * @throws OCSException if an error occurs
	 *
	 * 200: Returns the list of users
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'GET', url: '/config/{id}/users')]
	public function getUsers(int $id): DataResponse {
		try {
			$result = $this->accountManager->listConfig($id);
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException($e->getMessage(), Http::STATUS_INTERNAL_SERVER_ERROR);
		}
		return new DataResponse(array_map(static fn ($u) => ['uid' => $u->uid, 'displayName' => $u->displayName, 'email' => null], $result));
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
			$user = $this->userManager->get($uid);
			if ($user !== null) {
				$this->accountManager->create(new AccountEntity($id, $uid, AccountsType::Individual, $user->getDisplayName(), $user->getPasswordHash() ?? '', 0));
				$email = $user->getEMailAddress();
				if ($email !== null) {
					$this->emailManager->create(new EmailEntity($id, $uid, $email, EmailType::Primary));
				}
			}
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException($e->getMessage(), Http::STATUS_INTERNAL_SERVER_ERROR);
		}
		if ($user === null) {
			throw new OCSNotFoundException();
		}
		return new DataResponse(['uid' => $uid,'displayName' => $user->getDisplayName(),'email' => $user->getEMailAddress()]);
	}

	/**
	 * Remove a user from the server number `id`
	 * @param int $id The server number
	 * @param string $uid The user ID
	 * @return DataResponse<Http::STATUS_OK, null, array{}>
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
			$this->accountManager->delete($id, $uid);
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException($e->getMessage(), Http::STATUS_INTERNAL_SERVER_ERROR);
		}
		return new DataResponse(null);
	}
}
