<?php

declare(strict_types=1);

namespace app\components\live;

use app\models\exceptions\ConfigurationError;
use app\models\settings\AntragsgruenApp;
use app\models\db\{Consultation, User};
use Firebase\JWT\JWT;

class JwtCreator
{
    public static function createJwt(User $user, Consultation $consultation): string
    {
        if (!file_exists(AntragsgruenApp::getInstance()->livePublicKey)) {
            throw new ConfigurationError('JWT Public key file not found');
        }
        $privateKey = (string)file_get_contents(AntragsgruenApp::getInstance()->livePublicKey);

        $payload = [
            'iss' => 'antragsgruen.de',
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
