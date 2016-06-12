<?php

namespace app\models\forms;

use app\models\db\Site;
use app\models\settings\AntragsgruenApp;

class AntragsgruenInitSite extends SiteCreateForm
{
    use AntragsgruenInitConfigwriteTrait;

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

        $this->prettyUrls = $config->prettyUrl;
        $this->siteEmail  = $config->mailFromEmail;

        if ($config->siteSubdomain) {
            $this->subdomain = $config->siteSubdomain;
        } else {
            $this->subdomain = 'std' . rand(0, 99999999);
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
     * @param array $values
     * @param bool $safeOnly
     */
    public function setAttributes($values, $safeOnly = true)
    {
        parent::setAttributes($values, $safeOnly);
        $this->siteEmail  = $values['siteEmail'];
        $this->prettyUrls = isset($values['prettyUrls']);
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
    }
}