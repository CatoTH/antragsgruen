<?php

declare(strict_types=1);

namespace app\components;

use app\models\exceptions\{ApiResponseException, NotFound, ConfigurationError};
use app\models\db\{Consultation, User};
use Firebase\JWT\{ExpiredException, Key, SignatureInvalidException, JWT};
use app\models\settings\{Privileges, AntragsgruenApp};

/**
 * JWTs have two consumers:
 * - The REST API (always)
 * - The Live Proxy (optional)
 *
 * The Live Proxy relies on RS256 algorithm for JWT, as it only has the Public Key.
 * RS256 is the recommended way, and is enabled once a private and public key is registered in the configuration.
 * To also enable the REST API in simple setups, there is a fallback to HS256,
 * with a combination of the global app secret and the per-user secret being the signing key.
 */
class JwtCreator
{
    private const ROLE_SPEECH_ADMIN = 'ROLE_SPEECH_ADMIN';
    private const JWT_VALIDITY = 60 * 10; // 10 minutes

    private const USER_PREFIX_REGULAR = 'login-';
    private const USER_PREFIX_ANONYMOUS = 'anonymous-';

    private static ?string $currUserId = null;

    public static function createJwt(Consultation $consultation, string $userId, array $roles = []): string
    {
        $params = AntragsgruenApp::getInstance();
        if ($params->jwtPrivateKey !== null && $params->jwtPublicKey !== null) {
            $privateKey = self::retrieveKey($params->jwtPrivateKey);
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

    private static function retrieveKey(string $fileOrContent): string
    {
        $fileOrContent = trim($fileOrContent);
        if (str_starts_with($fileOrContent, '-----BEGIN')) {
            return $fileOrContent;
        }

        if (str_starts_with($fileOrContent, 'file://')) {
            $parts = parse_url($fileOrContent);
            if (!isset($parts['path'])) {
                throw new ConfigurationError('Incomplete file provided');
            }
            $file = $parts['path'];
        } else {
            $file = $fileOrContent;
        }

        if (!file_exists($file)) {
            throw new ConfigurationError('JWT key file not found');
        }

        return (string)file_get_contents($file);
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
        if ($params->jwtPrivateKey !== null && $params->jwtPublicKey !== null) {
            $publicKey = self::retrieveKey($params->jwtPublicKey);
            $key = new Key($publicKey, 'RS256');
        } else {
            $key = new Key($user->getJwtSigningKey(), 'HS256');
        }

        try {
            $decoded = JWT::decode($token, $key);
        } catch (ExpiredException $e) {
            throw new ApiResponseException('Token expired', 403, $e);
        } catch (SignatureInvalidException $e) {
            throw new ApiResponseException('Signature Invalid', 403, $e);
        } catch (\Exception $e) {
            throw new ApiResponseException('Invalid Token: ' . $e->getMessage(), 403, $e);
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
