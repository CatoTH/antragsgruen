<?php

namespace app\plugins\neos;

use app\controllers\Base;
use app\models\siteSpecificBehavior\DefaultBehavior;

class SiteSpecificBehavior extends DefaultBehavior
{
    /**
     * @return string
     */
    public static function hasSiteHomePage()
    {
        return true;
    }

    /**
     * @return bool
     */
    public static function preferConsultationSpecificHomeLink()
    {
        return true;
    }

    /**
     * @return bool
     */
    public static function siteHomeIsAlwaysPublic()
    {
        return true;
    }

    /**
     * @return string
     */
    public static function getSiteHomePage()
    {
        /** @var Base $controller */
        $controller = \Yii::$app->controller;
        return $controller->renderContentPage('MV-Seiten');
    }
}
