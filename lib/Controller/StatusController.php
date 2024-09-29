<?php

namespace OCA\Stalwart\Controller;

use Exception;
use OCA\Stalwart\ResponseDefinitions;
use OCA\Stalwart\Services\StatusService;
use OCA\Stalwart\Settings\Admin;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

/**
 * @psalm-suppress UnusedClass
 * @psalm-import-type StalwartServerStatus from ResponseDefinitions
 */
class StatusController extends OCSController {
	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		string                         $appName,
		IRequest                       $request,
		private readonly StatusService $statusService,
	) {
		parent::__construct($appName, $request);
	}


	/**
	 * Get the status of the configuration of a server number `id`
	 * @param int $id The server number
	 * @return DataResponse<Http::STATUS_OK, StalwartServerStatus, array{}>
	 * @throw OCSNotFoundException if the server does not exist
	 * @throws OCSException if an error occurs
	 *
	 * 200: Returns the status of the server configuration
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
	#[ApiRoute(verb: 'GET', url: '/status/{id}')]
	public function get(int $id): Response {
		try {
			$result = $this->statusService->challenge($id);
		} catch (Exception $e) {
			throw new OCSException(previous: $e);
		}
		if ($result === null) {
			throw new OCSNotFoundException();
		}
		return new DataResponse($result);
	}
}
