<?php

declare(strict_types=1);

namespace app\components\yii;

use app\controllers\rest\RestBase;

class RestSessionTester extends \yii\web\Session
{
    public function set($key, $value): void
    {
        $controller = \Yii::$app->controller;
        if (is_subclass_of($controller, RestBase::class)) {
            //var_dump(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5));
            die("Invalid session usage from within the API");
        }

        parent::set($key, $value);
    }

    protected function updateFlashCounters(): void
    {
        $controller = \Yii::$app->controller;
        if (is_subclass_of($controller, RestBase::class)) {
            //var_dump(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5));
            die("Invalid session usage from within the API");
        }

        parent::updateFlashCounters();
    }
}
