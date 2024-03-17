<?php

declare(strict_types=1);

namespace app\components;

use app\models\db\{Consultation, User};
use app\models\exceptions\ConfigurationError;
use app\models\settings\{Privileges, AntragsgruenApp};
use Firebase\JWT\JWT;

class JwtCreator
{
    private const ROLE_SPEECH_ADMIN = 'ROLE_SPEECH_ADMIN';
    private const JWT_VALIDITY = 60; // 1 minute

    private static ?string $currUserId = null;

    public static function createJwt(Consultation $consultation, string $userId, array $roles = []): string
    {
        $params = AntragsgruenApp::getInstance();
        if (!file_exists($params->jwtPrivateKey)) {
            throw new ConfigurationError('JWT Public key file not found');
        }
        $privateKey = (string)file_get_contents($params->jwtPrivateKey);
        if ($params->live) {
            $issuer = $params->live['installationId'];
        } else {
            $issuer = $params->domainPlain;
        }

        $payload = [
            'iss' => $issuer,
            'iat' => time(),
            'exp' => time() + self::JWT_VALIDITY,
            'sub' => $userId,
            'payload' => [
                'consultation' => $consultation->urlPath,
                'site' => $consultation->site->subdomain,
                'roles' => $roles,
            ],
        ];

        return JWT::encode($payload, $privateKey, 'RS256');
    }

    public static function getCurrJwtUserId(): string
    {
        if (!self::$currUserId) {
            if ($user = User::getCurrentUser()) {
                self::$currUserId = 'login-' . $user->id;
            } elseif ($cookieUser = CookieUser::getFromCookieOrCache()) {
                self::$currUserId = 'anonymous-'.$cookieUser->userToken;
            } else {
                self::$currUserId = 'anonymous-'.uniqid();
            }
        }

        return self::$currUserId;
    }

    public static function getJwtConfigForCurrUser(Consultation $consultation): array
    {
        $userId = self::getCurrJwtUserId();

        $roles = [];
        if (User::getCurrentUser()?->hasPrivilege($consultation, Privileges::PRIVILEGE_SPEECH_QUEUES, null)) {
            $roles[] = self::ROLE_SPEECH_ADMIN;
        }

        return [
            'token' => JwtCreator::createJwt($consultation, $userId, $roles),
            'exp' => time() + self::JWT_VALIDITY,
            'reload_uri' => UrlHelper::createUrl("/user/token"),
        ];
    }
}
