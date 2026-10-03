<?php

namespace app\components\yii;

use yii\log\FileTarget;

class LoggerFileTarget extends FileTarget
{
    /**
     * Matched case-insensitively against the flattened context keys; "*" is a wildcard.
     */
    public $maskVars = [
        '_SERVER.HTTP_AUTHORIZATION',
        '_SERVER.PHP_AUTH_USER',
        '_SERVER.PHP_AUTH_PW',
        '_SESSION.settingUp2FAKey.secret',
        '_SESSION.oauth2pkce',
        '_SESSION.oauth2state',
        '_POST.*password*',
        '_POST.*pwd*',
        '_POST.*secret*',
    ];

    protected function getContextMessage(): string
    {
        $contextMessage = parent::getContextMessage();

        if (str_contains($_SERVER["CONTENT_TYPE"] ?? null, 'application/json')) {
            $json = file_get_contents('php://input');
            if ($json) {
                $contextMessage = "\n\nJSON PAYLOAD:\n" . $json . "\n\n" . $contextMessage;
            }
        }

        $contextMessage .= "\n\n======================\n";

        return $contextMessage;
    }
}
