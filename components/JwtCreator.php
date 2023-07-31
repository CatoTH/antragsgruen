<?php

declare(strict_types=1);

namespace app\components;

use app\models\db\{Consultation, User};
use app\models\exceptions\ConfigurationError;
use app\models\settings\AntragsgruenApp;
use Firebase\JWT\JWT;

class JwtCreator
{
    public static function createJwt(User $user, Consultation $consultation): string
    {
        $params = AntragsgruenApp::getInstance();
        if (!file_exists($params->jwtPrivateKey)) {
            throw new ConfigurationError('JWT Public key file not found');
        }
        $privateKey = (string)file_get_contents($params->jwtPrivateKey);

        $payload = [
            'iss' => $params->domainPlain,
            'iat' => time(),
            'exp' => time() + 600, // 10 minutes
            'sub' => $user->id,
            'payload' => [
                'consultation' => $consultation->urlPath,
                'site' => $consultation->site->subdomain,
            ],
        ];

        return JWT::encode($payload, $privateKey, 'RS256');
    }
}
