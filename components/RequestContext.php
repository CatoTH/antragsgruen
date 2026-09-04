<?php

namespace app\components;

use app\controllers\Base;
use app\controllers\rest\RestBase;
use app\models\db\User as DbUser;
use yii\web\{Application, Request, Session, User as YiiUser};

final class RequestContext
{
    private static ?DbUser $overrideUser = null;

    /**
     * @return Application<DbUser>
     */
    public static function getWebApplication(): Application
    {
        /** @var Application<DbUser> $app */
        $app = \Yii::$app;

        return $app;
    }

    public static function getAllPostVars(): array
    {
        /** @var array $post */
        $post = self::getWebApplication()->request->post();

        return $post;
    }

    public static function getSession(): Session
    {
        return self::getWebApplication()->session;
    }

    /**
     * @return YiiUser<DbUser>
     */
    public static function getYiiUser(): YiiUser
    {
        /** @var \app\components\yii\User $user */
        $user = self::getWebApplication()->user;

        return $user;
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

    /**
     * Requests to the REST API must not touch the session. The API is polled in the background by
     * widgets embedded into a regular browsing session, authenticating with JWTs; starting a session
     * here would issue a competing session cookie and interfere with the browsing session.
     *
     * @param string|null $controllerClass the controller handling the request, if already known by
     *                                     the caller; defaults to the controller of the current request
     */
    public static function isRestRequest(?string $controllerClass = null): bool
    {
        return is_subclass_of($controllerClass ?? \Yii::$app->controller, RestBase::class);
    }
}
