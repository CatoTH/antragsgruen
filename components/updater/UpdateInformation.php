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

    /**
     * @return string
     */
    private function getAbsolutePath()
    {
        $dir = __DIR__ . '/../../runtime/updates/';
        if (!file_exists($dir)) {
            mkdir($dir, 0755);
        }
        $base = explode("/", $this->url);
        return $dir . $base[count($base) - 1];

    }

    /**
     * return bool
     */
    public function isDownloaded()
    {
        if (!file_exists($this->getAbsolutePath())) {
            return false;
        }
        $content = file_get_contents($this->getAbsolutePath());
        return (base64_encode(sodium_crypto_generichash($content)) === $this->signature);
    }

    /**
     * @throws \Exception
     */
    public function download()
    {
        $curlc = curl_init($this->url);
        curl_setopt($curlc, CURLOPT_RETURNTRANSFER, true);
        $resp = curl_exec($curlc);
        $info = curl_getinfo($curlc);
        curl_close($curlc);

        if (!isset($info['http_code'])) {
            throw new \Exception("The update could not be loaded");
        }
        if ($info['http_code'] !== 200) {
            throw new \Exception("The update could not be loaded");
        }

        if (base64_encode(sodium_crypto_generichash($resp)) !== $this->signature) {
            throw new \Exception("The update file has the wrong checksum");
        }

        file_put_contents($this->getAbsolutePath(), $resp);
    }
}
