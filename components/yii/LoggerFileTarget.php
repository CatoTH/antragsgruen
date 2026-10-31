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
        '_COOKIE.*',
    ];

    private const MASKED_JSON_KEYS = '/password|pwd|secret|token/i';

    /**
     * Masks values of credential-like keys in a JSON request body, like maskVars does for $_POST
     */
    private static function maskJsonPayload(string $json): string
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            // Not parseable, but credentials should not end up in the log anyway
            return (string)preg_replace('/("[^"]*(?:password|pwd|secret|token)[^"]*"\s*:\s*)"(?:[^"\\\\]|\\\\.)*"/i', '$1"***"', $json);
        }

        $mask = function (mixed $value) use (&$mask): mixed {
            if (!is_array($value)) {
                return $value;
            }
            foreach ($value as $key => $subValue) {
                if (is_string($key) && preg_match(self::MASKED_JSON_KEYS, $key)) {
                    $value[$key] = '***';
                } else {
                    $value[$key] = $mask($subValue);
                }
            }
            return $value;
        };

        return (string)json_encode($mask($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    protected function getContextMessage(): string
    {
        $contextMessage = parent::getContextMessage();

        if (str_contains($_SERVER["CONTENT_TYPE"] ?? null, 'application/json')) {
            $json = file_get_contents('php://input');
            if ($json) {
                $contextMessage = "\n\nJSON PAYLOAD:\n" . self::maskJsonPayload($json) . "\n\n" . $contextMessage;
            }
        }

        $contextMessage .= "\n\n======================\n";

        return $contextMessage;
    }
}
