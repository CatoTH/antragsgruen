<?php

namespace app\components;

class JwtAuthenticationProvider extends \Thruway\Authentication\AbstractAuthProviderClient
{
    /**
     * JwtAuthenticationProvider constructor.
     * @param array $authRealms
     */
    public function __construct(Array $authRealms)
    {
        parent::__construct($authRealms);
    }

    /**
     * @return string
     */
    public function getMethodName()
    {
        return 'jwt';
    }

    /**
     * @param string $signature
     * @return null|string
     */
    public static function decodeSignature($signature)
    {
        $jwt = \Firebase\JWT\JWT::decode($signature, \Yii::$app->params->randomSeed, ['HS256']);

        if (isset($jwt->authid)) {
            return $jwt->authid;
        } else {
            return null;
        }
    }

    /**
     * @param string $signature
     * @param null $extra
     * @return array
     */
    public function processAuthenticate($signature, $extra = null)
    {
        $authid = static::decodeSignature($signature);

        if ($authid) {
            return ["SUCCESS", ["authid" => $authid]];
        } else {
            return ["FAILURE"];
        }
    }
}
