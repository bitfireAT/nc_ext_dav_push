<?php

namespace OCA\DavPush\Controller;

use OCA\DavPush\AppInfo\Application;
use OCA\DavPush\Service\SubscriptionService;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\IRequest;

class SubscriptionController extends Controller {
	/** @var SubscriptionService */
	private $service;

	/** @var string */
	private $userId;

	use Errors;

	public function __construct(
		IRequest $request,
		SubscriptionService $service,
		$userId
	) {
		parent::__construct(Application::APP_ID, $request);
		$this->service = $service;
		$this->userId = $userId;
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function destroy(int $id): JSONResponse {
		return $this->handleNotFound(function () use ($id) {
			$this->service->delete($this->userId, $id);
			return [
				'success' => True,
			];
		});
	}
}