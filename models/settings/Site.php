<?php

namespace app\models\settings;

class Site
{
    use JsonConfigTrait;

    /** @var string */
    public $siteLayout = 'layout-classic';

    /** @var bool */
    public $showAntragsgruenAd  = true;
    public $forceLogin          = false;
    public $managedUserAccounts = false;

    /** @var int */
    public $willingToPay            = 0;
    public $willingToPayLastAskedTs = 0;
    public $billSent                = 0;

    /** @var int[] */
    public $loginMethods = [0, 1, 3];

    /** @var null|string */
    public $emailReplyTo  = null;
    public $emailFromName = null;

    const PAYS_NOT   = 0;
    const PAYS_MAYBE = 1;
    const PAYS_YES   = 2;

    const LOGIN_STD        = 0;
    const LOGIN_WURZELWERK = 1;
    const LOGIN_NAMESPACED = 2;
    const LOGIN_EXTERNAL   = 3;
    const LOGIN_SAML       = 4;

    public static $SITE_MANAGER_LOGIN_METHODS = [0, 1, 3];

    /**
     * @return string[]
     */
    public static function getPaysValues()
    {
        return [
            2 => 'Ja',
            0 => 'Nein',
            1 => 'Will mich später entscheiden'
        ];
    }
}
