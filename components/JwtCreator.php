<?php

declare(strict_types=1);

namespace app\components;

use app\models\exceptions\{ApiResponseException, NotFound, ConfigurationError};
use app\models\db\{Consultation, User};
use Firebase\JWT\{ExpiredException, Key, SignatureInvalidException, JWT};
use app\models\settings\{Privileges, AntragsgruenApp};

class JwtCreator
{
    private const ROLE_SPEECH_ADMIN = 'ROLE_SPEECH_ADMIN';
    private const JWT_VALIDITY = 60; // 1 minute

    private const USER_PREFIX_REGULAR = 'login-';
    private const USER_PREFIX_ANONYMOUS = 'anonymous-';

    private static ?string $currUserId = null;

    public static function createJwt(Consultation $consultation, string $userId, array $roles = []): string
    {
        $params = AntragsgruenApp::getInstance();
        if ($params->jwtPrivateKey !== null) {
            if (!file_exists($params->jwtPrivateKey)) {
                throw new ConfigurationError('JWT Public key file not found');
            }
            $privateKey = (string)file_get_contents($params->jwtPrivateKey);
            $algorithm = 'RS256';
        } else {
            if (User::getCurrentUser()) {
                $privateKey = User::getCurrentUser()->getJwtSigningKey();
                $algorithm = 'HS256';
            } else {
                throw new ConfigurationError('Cannot sign JWT for unauthenticated user');
            }
        }

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

        return JWT::encode($payload, $privateKey, $algorithm);
    }

    public static function getAuthenticatedUserByToken(string $token): ?User
    {
        $parts = explode('.', $token);
        $payload = json_decode(JWT::urlsafeB64Decode($parts[1]), true);

        $subject = $payload['sub'] ?? null;
        if ($subject === null || !str_starts_with($subject, self::USER_PREFIX_REGULAR)) {
            return null;
        }
        $userId = intval(explode('-', $subject)[1]);
        /** @var User|null $user */
        $user = User::findOne(['id' => $userId]);
        if ($user === null) {
            throw new NotFound('User not found');
        }

        $params = AntragsgruenApp::getInstance();
        if ($params->jwtPrivateKey !== null) {
            if (!file_exists($params->jwtPrivateKey)) {
                throw new ConfigurationError('JWT Public key file not found');
            }
            $privateKey = (string)file_get_contents($params->jwtPrivateKey);
            $key = new Key($privateKey, 'RS256');
        } else {
            $key = new Key($user->getJwtSigningKey(), 'HS256');
        }

        try {
            $decoded = JWT::decode($token, $key);
        } catch (ExpiredException $e) {
            throw new ApiResponseException('Token expired', 410, $e);
        } catch (SignatureInvalidException $e) {
            throw new ApiResponseException('Signature Invalid', 403, $e);
        } catch (\Exception $e) {
            throw new ApiResponseException('Invalid Token', 403, $e);
        }

        if ($decoded->sub !== self::USER_PREFIX_REGULAR . $user->id) {
            throw new ApiResponseException('Invalid Subject', 403);
        }

        return $user;
    }

    public static function getCurrJwtUserId(): string
    {
        if (!self::$currUserId) {
            if ($user = User::getCurrentUser()) {
                self::$currUserId = self::USER_PREFIX_REGULAR . $user->id;
            } elseif ($cookieUser = CookieUser::getFromCookieOrCache()) {
                self::$currUserId = self::USER_PREFIX_ANONYMOUS . $cookieUser->userToken;
            } else {
                self::$currUserId = self::USER_PREFIX_ANONYMOUS . uniqid();
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
