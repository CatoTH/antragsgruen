<?php

namespace app\plugins\opentelemetry;

use app\plugins\ModuleBase;
use yii\base\BootstrapInterface;

class Module extends ModuleBase implements BootstrapInterface
{
    public function bootstrap($app): void
    {
        if (getenv('OTEL_PHP_AUTOLOAD_ENABLED') !== 'true') {
            return;
        }
        $app->log->targets['otel'] = \Yii::createObject([
            'class' => OpenTelemetryLogTarget::class,
            'levels' => ['error', 'warning', 'info'],
        ]);
    }
}
