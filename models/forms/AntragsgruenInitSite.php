<?php

namespace app\models\forms;

use app\models\db\Site;
use app\models\settings\AntragsgruenApp;

class AntragsgruenInitSite extends SiteCreateForm
{
    use AntragsgruenInitConfigwriteTrait;

    public $siteUrl;
    public $siteEmail;

    /** @var boolean */
    public $prettyUrls = true;

    /**
     * @param string $configFile
     */
    public function __construct($configFile)
    {
        parent::__construct();
        $config = $this->readConfigFromFile($configFile);

        $this->siteUrl    = trim($config->domainPlain, '/') . $config->resourceBase;
        $this->prettyUrls = $config->prettyUrl;
        $this->siteEmail  = $config->mailFromEmail;

        if ($config->siteSubdomain) {
            $this->subdomain = $config->siteSubdomain;
        } else {
            $this->subdomain = 'std';
        }
    }

    /**
     * @return Site|null
     */
    public function getDefaultSite()
    {
        
        $sites = Site::find()->all();
        if (count($sites) > 0) {
            return $sites[0];
        } else {
            return null;
        }
    }

    /**
     * @param AntragsgruenApp $config
     */
    protected function setConfigValues(AntragsgruenApp $config)
    {
        $config->mailFromEmail = $this->siteEmail;
        $config->mailFromName  = $this->title;
        $config->siteSubdomain = $this->subdomain;
        $config->prettyUrl     = $this->prettyUrls;
        //$config->resourceBase = ; @TODO
    }
}