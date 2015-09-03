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
    public $domainSubdomain       = 'http://<subdomain:[\w_-]+>.antragsgruen-v3.localhost/';
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
}
