<?php

namespace app\async\protocol;

class Configuration
{
    /** @var int */
    public $portInternal;
    /** @var int */
    public $portExternal;
    /** @var string */
    public $yiiHostname;
    /** @var string */
    public $yiiProtocol = 'http';

    /**
     * Configuration constructor.
     * @param array $config
     * @throws \Exception
     */
    public function __construct(array $config)
    {
        if (isset($config['port-internal'])) {
            $this->portInternal = IntVal($config['port-internal']);
        } else {
            throw new \Exception('Missing configuration: port-internal');
        }
        if (isset($config['port-external'])) {
            $this->portExternal = IntVal($config['port-external']);
        } else {
            throw new \Exception('Missing configuration: port-external');
        }
        if (isset($config['yii-hostname'])) {
            $this->yiiHostname = $config['yii-hostname'];
        } else {
            throw new \Exception('Missing configuration: yii-hostname');
        }
    }

    /**
     * @param string $subdomain
     * @return string
     * @throws \Exception
     */
    public function getHostname(string $subdomain): string
    {
        if (preg_match('/[^a-zA-Z0-9_-]/', $subdomain)) {
            throw new \Exception('invalid subdomain');
        }
        return str_replace('%SUBDOMAIN%', $subdomain, $this->yiiHostname);
    }
}
