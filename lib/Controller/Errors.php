<?php

namespace OCA\DavPush\Controller;

use Closure;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;

use OCA\DavPush\Errors\NotFoundException;

trait Errors {
	protected function handleNotFound(Closure $callback): JSONResponse {
		try {
			return new JSONResponse($callback());
		} catch (NotFoundException $e) {
			return $this->notFoundResponse($e);
		}
	}

	private function notFoundResponse(NotFoundException $e) {
		$response = ['error' => get_class($e), 'message' => $e->getMessage()];
		return new JSONResponse($response, Http::STATUS_NOT_FOUND);
	}
}