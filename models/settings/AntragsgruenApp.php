<?php

namespace app\models\settings;

class AntragsgruenApp
{
    public $dbConnection          = null;
    public $randomSeed            = '';
    public $cookieValidationKey   = '';
    public $multisiteMode         = true;
    public $domainPlain           = 'http://antragsgruen-v3.localhost/';
    public $domainSubdomain       = 'http://<subdomain:[\w_-]+>.antragsgruen-v3.localhost/';
    public $hasWurzelwerk         = true;
    public $createNeedsWurzelwerk = false;
    public $prependWWWToSubdomain = true;
    public $standardSite          = 'default';
    public $pdfLogo               = 'LOGO_PFAD';
    public $confirmEmailAddresses = true;
    public $contactEmail          = 'EMAILADRESSE';
    public $mailFromName          = 'Antragsgrün';
    public $mailFromEmail         = 'EMAILADRESSE';
    public $adminUserIds          = [];
    public $odtDefaultTemplate    = null;
    public $mandrillApiKey        = null;
    public $siteBehaviorClasses   = [];
    public $authClientCollection  = [];
    public $autoLoginDuration     = 0;
    public $tmpDir                = '/tmp/';
    public $xelatexPath           = null;
    public $xdvipdfmx              = null;
}
