<?php

namespace app\models\settings;

class Site
{
    use JsonConfigTrait;

    /** @var string */
    public $siteLayout = 'layout-classic';

    /** @var bool */
    public $showAntragsgruenAd  = true;
    public $forceLogin          = false; // @TODO Delete this setting after migration is done
    public $managedUserAccounts = false; // @TODO Delete this setting after migration is done

    /** @var int[] */
    public $loginMethods = [0, 1, 3];

    /** @var null|string */
    public $emailReplyTo  = null; // @TODO Delete this setting after migration is done
    public $emailFromName = null; // @TODO Delete this setting after migration is done

    const LOGIN_STD        = 0;
    const LOGIN_WURZELWERK = 1;
    const LOGIN_EXTERNAL   = 3;
    const LOGIN_SAML       = 4;

    public static $SITE_MANAGER_LOGIN_METHODS = [0, 1, 3];
}
