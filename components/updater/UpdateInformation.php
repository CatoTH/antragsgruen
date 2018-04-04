<?php

namespace app\components\updater;

use app\models\exceptions\Internal;

class UpdateInformation
{
    const TYPE_PATCH = 'patch';

    public $type;
    public $version;
    public $changelog;
    public $url;
    public $filesize;
    public $signature;

    /**
     * UpdateInformation constructor.
     *
     * @param array $json
     * @throws Internal
     */
    public function __construct($json)
    {
        $this->type      = $json['type'];
        $this->version   = $json['version'];
        $this->changelog = $json['changelog'];
        $this->url       = $json['url'];
        $this->filesize  = $json['filesize'];
        $this->signature = $json['signature'];

        if ($this->type !== static::TYPE_PATCH) {
            throw new Internal("Only patch releases are supported");
        }
    }
}
