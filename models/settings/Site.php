<?php

namespace app\models\settings;

class Site implements \JsonSerializable
{
    use JsonConfigTrait;

    public string $siteLayout = 'layout-classic';

    public bool $showAntragsgruenAd = true;
    public bool $showBreadcrumbs = true;
    public bool $apiEnabled = false;

    public array $loginMethods = [
        self::LOGIN_STD,
        self::LOGIN_GRUENES_NETZ,
    ];

    public array $stylesheetSettings = [];
    public array $apiCorsOrigins = [];

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
