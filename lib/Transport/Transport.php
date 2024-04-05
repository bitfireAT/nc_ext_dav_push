<?php

declare(strict_types=1);

namespace OCA\DavPush\Transport;

abstract class Transport {
    protected $id;

    public function getId() {
        return $this->id;
    }

    public function getAdditionalInformation() {
        return [];
    }
}