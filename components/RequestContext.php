<?php

namespace app\components;

use yii\web\{Application, Controller, Session, User};

final class RequestContext
{
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

    public static function getUser(): User
    {
        return self::getWebApplication()->user;
    }

    public static function getController(): Controller
    {
        return self::getWebApplication()->controller;
    }
}
