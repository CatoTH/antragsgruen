<?php

namespace app\plugins\opentelemetry;

use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Logs\Severity;
use yii\log\Logger;
use yii\log\Target;

class OpenTelemetryLogTarget extends Target
{
    private const LEVEL_MAP = [
        Logger::LEVEL_ERROR => Severity::ERROR,
        Logger::LEVEL_WARNING => Severity::WARN,
        Logger::LEVEL_INFO => Severity::INFO,
        Logger::LEVEL_TRACE => Severity::DEBUG,
        Logger::LEVEL_PROFILE => Severity::DEBUG,
        Logger::LEVEL_PROFILE_BEGIN => Severity::DEBUG,
        Logger::LEVEL_PROFILE_END => Severity::DEBUG,
    ];

    public function export(): void
    {
        $logger = Globals::loggerProvider()->getLogger('yii2');

        foreach ($this->messages as $message) {
            [$text, $level, $category, $timestamp] = $message;

            if (!is_string($text)) {
                $text = var_export($text, true);
            }

            $severity = self::LEVEL_MAP[$level] ?? Severity::UNSPECIFIED;

            $logger->emit(
                (new \OpenTelemetry\API\Logs\LogRecord($text))
                    ->setSeverityNumber($severity->value)
                    ->setSeverityText($severity->name)
                    ->setTimestamp((int)($timestamp * 1_000_000_000))
                    ->setAttribute('yii.category', $category)
            );
        }
    }
}
