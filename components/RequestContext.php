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
     * True if the request currently being handled is served by the REST API.
     *
     * The API is stateless: it authenticates by JWT alone (see RestBase::beforeAction()) and must
     * neither read nor write the session - a poll running every few seconds would take PHP's session
     * lock each time, and cross-origin clients send no cookie at all. Everything that would otherwise
     * fall back to session state needs to ask this first (LanguageTools, the session guard, ...).
     */
    public static function isRestApiRequest(): bool
    {
        return is_subclass_of(\Yii::$app->controller, RestBase::class);
    }
}
