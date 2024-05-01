<?php

namespace app\components;

use app\controllers\Base;
use app\models\db\User as DbUser;
use yii\web\{Application, Request, Session, User as YiiUser};

final class RequestContext
{
    private static ?DbUser $overrideUser = null;

    public static function getWebApplication(): Application
    {
        /** @var Application $app */
        $app = \Yii::$app;

        return $app;
    }

    public static function getSession(): Session
    {
        return self::getWebApplication()->session;
    }

    public static function getYiiUser(): YiiUser
    {
        return self::getWebApplication()->user;
    }

    public static function getDbUser(): ?DbUser
    {
        if (self::$overrideUser) {
            return self::$overrideUser;
        }
        try {
            if (RequestContext::getYiiUser()->getIsGuest()) {
                return null;
            } else {
                /** @var DbUser $user */
                $user = RequestContext::getYiiUser()->identity;
                return $user;
            }
        } catch (\Throwable) {
            // Can happen with console commands
            return null;
        }
    }

    public static function setOverrideUser(?DbUser $user): void
    {
        self::$overrideUser = $user;
    }

    public static function getController(): Base
    {
        /** @var Base $controller */
        $controller = self::getWebApplication()->controller;
        return $controller;
    }

    public static function getWebRequest(): Request
    {
        return self::getWebApplication()->request;
    }
}
