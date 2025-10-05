<?php

namespace app\components\yii;

use yii\log\FileTarget;

class LoggerFileTarget extends FileTarget
{
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
