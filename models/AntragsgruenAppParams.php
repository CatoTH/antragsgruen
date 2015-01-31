<?php

namespace app\models;

class AntragsgruenAppParams
{
    public $multisiteMode = true;
    public $domainPlain = "http://antragsgruen-v3.localhost/";
    public $domainSubdomain = "http://<siteId:[\w_-]+>.antragsgruen-v3.localhost/";
    public $standardSite = "default";
    public $pdfLogo = 'LOGO_PFAD';
    public $contactEmail = 'EMAILADRESSE';
    public $mailFromName = 'Antragsgrün';
    public $mailFromEmail = 'EMAILADRESSE';
    public $adminUserId = null;
    public $odtDefaultTemplate = null;
}
