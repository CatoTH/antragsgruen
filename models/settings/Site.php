<?php

namespace app\models\settings;

class Site implements \JsonSerializable
{
    use JsonConfigTrait;

    /** @var string */
    public $siteLayout = 'layout-classic';

    /** @var bool */
    public $showAntragsgruenAd = true;
    /** @var bool */
    public $showBreadcrumbs = true;
    /** @var bool */
    public $apiEnabled = false;

    public array $loginMethods = [
        self::LOGIN_STD,
        self::LOGIN_GRUENES_NETZ,
    ];

    /** @var array */
    public $stylesheetSettings = [];
    /** @var array */
    public $apiCorsOrigins = [];

    public const LOGIN_STD = 0;
    public const LOGIN_GRUENES_NETZ = 1;
    public const LOGIN_EXTERNAL = 3;
    public const LOGIN_OPENSLIDES = 4; // Only available if openslides-plugin is activated

    public const SITE_MANAGER_LOGIN_METHODS = [
        self::LOGIN_STD,
        self::LOGIN_GRUENES_NETZ,
    ];

    public function getStylesheet(): Stylesheet
    {
        return new Stylesheet($this->stylesheetSettings);
    }

    public function setStylesheet(Stylesheet $stylesheet): void
    {
        $this->stylesheetSettings = $stylesheet->jsonSerialize();
    }
}
