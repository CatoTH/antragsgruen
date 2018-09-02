<?php

namespace app\models\settings;

class Site implements \JsonSerializable
{
    use JsonConfigTrait;

    /** @var string */
    public $siteLayout = 'layout-classic';

    /** @var bool */
    public $showAntragsgruenAd  = true;

    /** @var int[] */
    public $loginMethods = [0, 1, 3];

    const LOGIN_STD        = 0;
    const LOGIN_WURZELWERK = 1;
    const LOGIN_EXTERNAL   = 3;
    const LOGIN_SAML       = 4;

    public static $SITE_MANAGER_LOGIN_METHODS = [0, 1, 3];
}
