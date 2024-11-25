<?php

declare(strict_types=1);

namespace OCA\Nextmail\Controller;

use OCA\Nextmail\Db\GroupsManager;
use OCA\Nextmail\Models\GroupEntity;
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
 * @psalm-import-type NextmailGroup from ResponseDefinitions
 */
class ApiGroupsController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly GroupsManager $gm,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * List all available groups
	 * @return DataResponse<Http::STATUS_OK, list<NextmailGroup>, array{}>
	 * @throws OCSException if an error occurs
	 *
	 * 200: Returns the list of groups
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'GET', url: '/groups')]
	public function getGroups(): DataResponse {
		try {
			return new DataResponse(array_map(fn (GroupEntity $x) => $x->jsonSerialize(), $this->gm->listAll()));
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException($e->getMessage(), Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Update the group of the server number `id`
	 * @param ?string $server_id The server number
	 * @param string $usr The group ID
	 * @param ?string $email The primary email
	 * @return DataResponse<Http::STATUS_OK, NextmailGroup, array{}>
	 * @throws OCSNotFoundException If the group does not exist
	 * @throws OCSException if an error occurs
	 *
	 * 200: The group has been updated
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'PUT', url: '/groups/{usr}')]
	public function updateGroup(string $usr, ?string $server_id, ?string $email): DataResponse {
		try {
			$data = $this->gm->update($usr, $server_id, $email);
			$this->gm->commit();
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
