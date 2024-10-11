<?php

declare(strict_types=1);

namespace OCA\Stalwart\Controller;

use OCA\Stalwart\Db\AccountManager;
use OCA\Stalwart\Db\ConfigManager;
use OCA\Stalwart\Db\EmailManager;
use OCA\Stalwart\Models\AccountEntity;
use OCA\Stalwart\Models\EmailEntity;
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

	/** @return StalwartServerUser */
	private static function getUserDataWithoutMail(AccountEntity $account): array {
		return [
			'id' => $account->uid,
			'displayName' => $account->displayName,
			'email' => null
		];
	}

	/** @return StalwartServerUser */
	private static function getUserData(EmailEntity $email): array {
		return [
			'id' => $email->account->uid,
			'displayName' => $email->account->displayName,
			'email' => $email->email
		];
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
	public function getConfig(): DataResponse {
		try {
			return new DataResponse(array_map(fn ($c) => $c->jsonSerialize(), $this->configManager->list()));
		} catch (Exception $config) {
			$this->logger->error($config->getMessage(), ['exception' => $config]);
			throw new OCSException($config->getMessage(), Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Add a new server
	 * @return DataResponse<Http::STATUS_OK, StalwartServerConfig, array{}>
	 *
	 * 200: Returns the new server configuration
	 * @throws OCSException
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'POST', url: '/config')]
	public function setConfig(): DataResponse {
		try {
			return new DataResponse($this->configManager->create()->jsonSerialize());
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException($e->getMessage(), Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Get the configuration of a server number `id`
	 * @param string $cid The server number
	 * @return DataResponse<Http::STATUS_OK, StalwartServerConfig, array{}>
	 * @throws OCSNotFoundException If the server number `id` does not exist
	 * @throws OCSException if an error occurs
	 *
	 * 200: Returns the server configuration
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'GET', url: '/config/{cid}')]
	public function getConfigId(string $cid): DataResponse {
		try {
			if ($config = $this->configManager->getById($cid)) {
				return new DataResponse($config->jsonSerialize());
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
	 * @param string $cid The server number
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
	#[ApiRoute(verb: 'PUT', url: '/config/{cid}')]
	public function seConfigId(string $cid, string $endpoint, string $username, string $password): DataResponse {
		try {
			if ($config = $this->configManager->getById($cid)) {
				$config = $config->updateCredential($endpoint, $username, $password);
				$config = $this->configManager->save($config);
				return new DataResponse($config->jsonSerialize());
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
	 * @param string $cid The server number
	 * @return DataResponse<Http::STATUS_OK, StalwartServerConfig, array{}>
	 * @throws OCSException if an error occurs
	 * @throws OCSNotFoundException If the server number `id` does not exist
	 *
	 * 200: The server configuration has been deleted
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'DELETE', url: '/config/{cid}')]
	public function deleteConfigId(string $cid): DataResponse {
		try {
			if ($config = $this->configManager->getById($cid)) {
				$this->configManager->delete($config);
				return new DataResponse($config->jsonSerialize());
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
	 * @param string $cid The server number
	 * @return DataResponse<Http::STATUS_OK, StalwartServerUser[], array{}>
	 * @throws OCSException if an error occurs
	 *
	 * 200: Returns the list of users
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'GET', url: '/config/{cid}/users')]
	public function getConfigIdUsers(string $cid): DataResponse {
		try {
			if ($config = $this->configManager->getById($cid)) {
				return new DataResponse(array_map(fn ($a) => ($e = $this->emailManager->findPrimary($a))
					? self::getUserData($e)
					: self::getUserDataWithoutMail($a), $this->accountManager->listUser($config)));
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
	 * @param string $cid The server number
	 * @param string $uid The user ID
	 * @return DataResponse<Http::STATUS_OK, StalwartServerUser, array{}>
	 * @throws OCSNotFoundException If the user does not exist
	 * @throws OCSException if an error occurs
	 *
	 * 200: Returns the user
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'GET', url: '/config/{cid}/users/{uid}')]
	public function getConfigIdUsersId(string $cid, string $uid): DataResponse {
		try {
			if (($config = $this->configManager->getById($cid)) && $account = $this->accountManager->findUser($config, $uid)) {
				return new DataResponse(($userEmail = $this->emailManager->findPrimary($account))
					? self::getUserData($userEmail)
					: self::getUserDataWithoutMail($account));
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
	 * @param string $cid The server number
	 * @param string $uid The user ID
	 * @return DataResponse<Http::STATUS_OK, StalwartServerUser, array{}>
	 * @throws OCSNotFoundException If the user does not exist
	 * @throws OCSException if an error occurs
	 *
	 * 200: The user has been added to the server
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'POST', url: '/config/{cid}/users/{uid}')]
	public function setConfigIdUsersId(string $cid, string $uid): DataResponse {
		try {
			if (($config = $this->configManager->getById($cid)) && $user = $this->userManager->get($uid)) {
				$account = $this->accountManager->createUser($config, $user);
				if (null !== $userEmail = $user->getEMailAddress()) {
					return new DataResponse(self::getUserData($this->emailManager->setPrimary($account, $userEmail)));
				} else {
					return new DataResponse(self::getUserDataWithoutMail($account));
				}
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
	 * @param string $cid The server number
	 * @param string $uid The user ID
	 * @return DataResponse<Http::STATUS_OK, null, array{}>
	 * @throws OCSNotFoundException If the user does not exist
	 * @throws OCSException if an error occurs
	 *
	 * 200: The user has been removed from the server
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'DELETE', url: '/config/{cid}/users/{uid}')]
	public function deleteConfigIdUsersId(string $cid, string $uid): DataResponse {
		try {
			$config = $this->configManager->getById($cid);
			$account = $config ? $this->accountManager->findUser($config, $uid) : null;
			if ($account) {
				$this->accountManager->delete($account);
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
