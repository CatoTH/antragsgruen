<?php

namespace app\models\settings;

class Site implements \JsonSerializable
{
    use JsonConfigTrait;

    /** @var string */
    public $siteLayout = 'layout-classic';

    /** @var bool */
    public $showAntragsgruenAd  = true;
    public $forceLogin          = false;
    public $managedUserAccounts = false;

    /** @var int[] */
    public $loginMethods = [0, 1, 3];

    /** @var array */
    public $stylesheetSettings = [];

    /** @var null|string */
    public $emailReplyTo  = null;
    public $emailFromName = null;

    const LOGIN_STD        = 0;
    const LOGIN_WURZELWERK = 1;
    const LOGIN_EXTERNAL   = 3;
    const LOGIN_SAML       = 4;

    public static $SITE_MANAGER_LOGIN_METHODS = [0, 1, 3];

    /**
     * @return Stylesheet
     */
    public function getStylesheet()
    {
        return new Stylesheet($this->stylesheetSettings);
    }

    /**
     * @param Stylesheet $stylesheet
     */
    public function setStylesheet(Stylesheet $stylesheet)
    {
        $this->stylesheetSettings = $stylesheet->jsonSerialize();
    }
}
