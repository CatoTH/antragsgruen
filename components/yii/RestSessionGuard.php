<?php

declare(strict_types=1);

namespace app\components\yii;

use app\components\RequestContext;

/**
 * The REST API is stateless: it authenticates by JWT alone and must not touch the session (see
 * RestBase::beforeAction()). Writing to it from an API request is always a bug - it would take PHP's
 * session lock on every poll, and it would try to persist state for clients that never send a cookie.
 *
 * This guard makes that bug visible. It is mixed into every session backend rather than just one, so
 * that an installation using Redis behaves like one using files: previously the tripwire only existed
 * for file sessions, which meant the whole class of bug was invisible on Redis installations.
 *
 * In debug mode the write fails loudly, so it gets noticed while developing. In production it is
 * logged and skipped: dropping the write is what was intended anyway, and it is a far better outcome
 * for the user than an API endpoint erroring out over it.
 */
trait RestSessionGuard
{
    private function isForbiddenRestSessionWrite(string $what): bool
    {
        if (!RequestContext::isRestApiRequest()) {
            return false;
        }

        $message = 'Invalid session usage from within the API: ' . $what;
        if (YII_DEBUG) {
            throw new \RuntimeException($message);
        }
        \Yii::warning($message, __METHOD__);

        return true;
    }

    public function set($key, $value): void
    {
        if ($this->isForbiddenRestSessionWrite('set(' . $key . ')')) {
            return;
        }

        parent::set($key, $value);
    }

    protected function updateFlashCounters(): void
    {
        if ($this->isForbiddenRestSessionWrite('flash message')) {
            return;
        }

        parent::updateFlashCounters();
    }
}
