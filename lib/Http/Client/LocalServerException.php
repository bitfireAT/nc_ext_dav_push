<?php

declare(strict_types=1);

/**
 * This file is an adaptation of a class from the Nextcloud core.
 * Forked from https://github.com/nextcloud/server/blob/52acc5ef1515c37bbcfd3da349df5ba09970cbc3/lib/public/Http/Client/LocalServerException.php
 * 
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DavPush\Http\Client;

use OCA\DavPush\Vendor\GuzzleHttp\Exception\RequestException;
use OCA\DavPush\Vendor\Psr\Http\Message\RequestInterface;

class LocalServerException extends RequestException {
	public function __construct(
		string $message,
		RequestInterface $request,
	) {
		parent::__construct($message, $request);
	}
}