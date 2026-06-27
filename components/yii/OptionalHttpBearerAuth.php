<?php

declare(strict_types=1);

namespace app\components\yii;

use yii\filters\auth\AuthMethod;
use yii\web\IdentityInterface;

class OptionalHttpBearerAuth extends AuthMethod
{
    public string $header = 'Authorization';
    public string $pattern = '/^Bearer\s+(.*?)$/';
    public string $realm = 'api';
    public $optional = ['*'];

    /**
     * {@inheritdoc}
     */
    public function challenge($response): void
    {
        $response->getHeaders()->set('WWW-Authenticate', "Bearer realm=\"{$this->realm}\"");
    }

    public function authenticate($user, $request, $response): ?IdentityInterface
    {
        $authHeader = $request->getHeaders()->get($this->header);

        if ($authHeader !== null) {
            if (preg_match($this->pattern, $authHeader, $matches)) {
                $authHeader = $matches[1];
            } else {
                return null;
            }

            $identity = $user->loginByAccessToken($authHeader, get_class($this));
            if ($identity === null) {
                $this->challenge($response);
                $this->handleFailure($response);
            }

            return $identity;
        }

        return null;
    }
}
