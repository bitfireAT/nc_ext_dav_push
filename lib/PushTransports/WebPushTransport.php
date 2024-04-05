<?php

declare(strict_types=1);

namespace OCA\DavPush\PushTransports;

use OCA\DavPush\Transport\Transport;

class WebPushTransport extends Transport {
    protected $id = "web-push";
}