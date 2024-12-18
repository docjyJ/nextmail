<?php

declare(strict_types=1);

namespace OCA\Nextmail\Controller;

use OCA\Nextmail\Db\UsersManager;
use OCA\Nextmail\Models\UserEntity;
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
 * @psalm-import-type NextmailUser from ResponseDefinitions
 */
class ApiUsersController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly UsersManager $um,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * List all available users
	 * @return DataResponse<Http::STATUS_OK, list<NextmailUser>, array{}>
	 * @throws OCSException if an error occurs
	 *
	 * 200: Returns the list of users
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'GET', url: '/users')]
	public function getUsers(): DataResponse {
		try {
			return new DataResponse(array_map(fn (UserEntity $x) => $x->jsonSerialize(), $this->um->listAll()));
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException($e->getMessage(), Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Update the user of the server number `id`
	 * @param ?string $server_id The server number
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
	#[ApiRoute(verb: 'PUT', url: '/users/{usr}')]
	public function updateUser(string $usr, ?string $server_id, bool $admin, ?int $quota): DataResponse {
		try {
			$data = $this->um->update($usr, $server_id, $admin, $quota);
			$this->um->commit();
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
