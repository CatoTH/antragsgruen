<?php

namespace app\components\updater;

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
     * @throws \Exception
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
            throw new \Exception("Only patch releases are supported");
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

    /**
     * @return string
     * @throws \Exception
     */
    public function readUpdateJson()
    {
        if (!$this->isDownloaded()) {
            throw new \Exception("File is not yet downloaded");
        }

        $zipfile = new \ZipArchive();
        if ($zipfile->open($this->getAbsolutePath()) !== true) {
            throw new \Exception("Could not open the ZIP file");
        }

        $updateJson      = $zipfile->getFromName('update.json');
        $updateSignature = base64_decode($zipfile->getFromName('update.json.signature'));
        $publicKey       = base64_decode(file_get_contents(__DIR__ . '/../../config/update-public.key'));
        if (!sodium_crypto_sign_verify_detached($updateSignature, $updateJson, $publicKey)) {
            throw new \Exception("The signature of the update file is invalid");
        }

        return new UpdatedFiles($updateJson);
    }
}
