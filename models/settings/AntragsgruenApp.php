<?php

namespace app\models\settings;

class AntragsgruenApp
{
    use JsonConfigTrait;

    public $dbConnection          = null;
    public $siteSubdomain         = null;
    public $prettyUrl             = true;
    public $resourceBase          = '/';
    public $baseLanguage          = 'de';
    public $randomSeed            = '';
    public $multisiteMode         = false;
    public $domainPlain           = 'http://antragsgruen-v3.localhost/';
    public $domainSubdomain       = '';
    public $hasWurzelwerk         = true;
    public $createNeedsWurzelwerk = false;
    public $prependWWWToSubdomain = true;
    public $pdfLogo               = 'LOGO_PFAD';
    public $confirmEmailAddresses = true;
    public $mailFromName          = 'Antragsgrün';
    public $mailFromEmail         = 'EMAILADRESSE';
    public $adminUserIds          = [];
    public $siteBehaviorClasses   = [];
    public $authClientCollection  = [];
    public $autoLoginDuration     = 31536000; // 1 Year
    public $tmpDir                = '/tmp/';
    public $xelatexPath           = null;
    public $xdvipdfmx             = null;

    /** @var null|array */
    public $mailService = null;

    /**
     * @return bool
     */
    private function isHttps()
    {
        // Needs to be equal to Yii2's web/Request.php
        return isset($_SERVER['HTTPS']) && (strcasecmp($_SERVER['HTTPS'], 'on') === 0 || $_SERVER['HTTPS'] == 1)
        || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0;
    }

    /**
     * @param string|null $data
     * @throws \Exception
     */
    public function __construct($data)
    {
        $this->setPropertiesFromJSON($data);

        if ($data == '') {
            $this->resourceBase = $_SERVER['SCRIPT_NAME'];
            $this->resourceBase = str_replace('index.php', '', $this->resourceBase);
            $this->domainPlain  = ($this->isHttps() ? 'https' : 'http');
            $this->domainPlain .= '://' . $_SERVER['HTTP_HOST'] . '/';
        }
    }
}
