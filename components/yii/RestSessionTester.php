<?php

declare(strict_types=1);

namespace app\components\yii;

use app\components\RequestContext;

class RestSessionTester extends \yii\web\Session
{
    public function set($key, $value): void
    {
        if (RequestContext::isRestRequest()) {
            //var_dump(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5));
            die("Invalid session usage from within the API");
        }

        parent::set($key, $value);
    }

    protected function updateFlashCounters(): void
    {
        if (RequestContext::isRestRequest()) {
            //var_dump(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5));
            die("Invalid session usage from within the API");
        }

        parent::updateFlashCounters();
    }
}
