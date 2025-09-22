<?php

declare(strict_types=1);

/**
 * This file is an adaptation of a class from the Nextcloud core.
 * Forked from https://github.com/nextcloud/server/blob/52acc5ef1515c37bbcfd3da349df5ba09970cbc3/lib/private/Http/Client/NegativeDnsCache.php
 * 
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DavPush\Http\Client;

use OCP\ICache;
use OCP\ICacheFactory;

class NegativeDnsCache {
	/** @var ICache */
	private $cache;

	public function __construct(ICacheFactory $memcache) {
		$this->cache = $memcache->createLocal('NegativeDnsCache');
	}

	private function createCacheKey(string $domain, int $type) : string {
		return $domain . '-' . (string)$type;
	}

	public function setNegativeCacheForDnsType(string $domain, int $type, int $ttl) : void {
		$this->cache->set($this->createCacheKey($domain, $type), 'true', $ttl);
	}

	public function isNegativeCached(string $domain, int $type) : bool {
		return (bool)$this->cache->hasKey($this->createCacheKey($domain, $type));
	}
}