<?php

namespace app\models\settings;

class AntragsgruenApp
{
    public $dbConnection          = null;
    public $randomSeed            = "";
    public $multisiteMode         = true;
    public $domainPlain           = "http://antragsgruen-v3.localhost/";
    public $domainSubdomain       = "http://<siteId:[\w_-]+>.antragsgruen-v3.localhost/";
    public $createNeedsWurzelwerk = false;
    public $prependWWWToSubdomain = true;
    public $standardSite          = "default";
    public $pdfLogo               = 'LOGO_PFAD';
    public $confirmEmailAddresses = true;
    public $contactEmail          = 'EMAILADRESSE';
    public $mailFromName          = 'Antragsgrün';
    public $mailFromEmail         = 'EMAILADRESSE';
    public $adminUserId           = null;
    public $odtDefaultTemplate    = null;
    public $mandrillApiKey        = null;
    public $siteBehaviorClasses   = [];
}
