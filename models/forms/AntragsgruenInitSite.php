<?php

namespace app\models\forms;

use app\models\db\Site;
use app\models\settings\AntragsgruenApp;

class AntragsgruenInitSite extends SiteCreateForm
{
    use AntragsgruenInitConfigwriteTrait;

    public string $siteEmail;
    public bool $prettyUrls = true;

    public function __construct(string $configFile)
    {
        parent::__construct();
        $config = $this->readConfigFromFile($configFile);

        $this->prettyUrls = $config->prettyUrl;
        $this->siteEmail  = $config->mailFromEmail;

        if ($config->siteSubdomain) {
            $this->subdomain = $config->siteSubdomain;
        } else {
            if (Site::findOne(['subdomain' => 'std'])) {
                $this->subdomain = 'std' . random_int(0, 99999999);
            } else {
                $this->subdomain = 'std';
            }
        }
    }

    /**
     * @param array $values
     * @param bool $safeOnly
     */
    public function setAttributes($values, $safeOnly = true): void
    {
        parent::setAttributes($values, $safeOnly);
        $this->siteEmail  = $values['siteEmail'];
        $this->prettyUrls = isset($values['prettyUrls']);
    }

    protected function setConfigValues(AntragsgruenApp $config): void
    {
        $config->mailFromEmail = $this->siteEmail;
        $config->mailFromName  = $this->title;
        $config->siteSubdomain = $this->subdomain;
        $config->prettyUrl     = $this->prettyUrls;
    }
}
