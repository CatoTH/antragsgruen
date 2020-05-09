<?php

namespace app\plugins\neos;

use app\controllers\Base;
use app\models\siteSpecificBehavior\DefaultBehavior;

class SiteSpecificBehavior extends DefaultBehavior
{
    public static function hasSiteHomePage(): bool
    {
        return true;
    }

    public static function preferConsultationSpecificHomeLink(): bool
    {
        return true;
    }

    public static function siteHomeIsAlwaysPublic(): bool
    {
        return true;
    }

    public static function getSiteHomePage(): ?string
    {
        /** @var Base $controller */
        $controller = \Yii::$app->controller;
        return $controller->renderContentPage('MV-Seiten');
    }
}
