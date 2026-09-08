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
 * for the user than an API endpoint erroring out over it. The logged report names the request and the
 * call stack that reached the session, so that occurrences seen only on a production installation can
 * be traced back to the code responsible - see describeOccurrence().
 */
trait RestSessionGuard
{
    /**
     * How much of the call stack is collected, and how much of it is reported. A few frames at the
     * top are always the guard and Session itself and get dropped; the paths that reach a session
     * from the API are indirect enough - a policy check nested inside a DTO, a layout rendered by an
     * error page - that what remains still has to be deep enough to reach the controller action.
     */
    private const TRACE_COLLECT_FRAMES = 30;
    private const TRACE_REPORT_FRAMES = 12;

    private function isForbiddenRestSessionWrite(string $what): bool
    {
        if (!RequestContext::isRestApiRequest()) {
            return false;
        }

        $message = 'Invalid session usage from within the API: ' . $what;
        if (YII_DEBUG) {
            // No logging needed here: the exception carries its own stack trace and the error
            // handler shows it in full, which is more than the report below could say.
            throw new \RuntimeException($message);
        }
        \Yii::warning($message . "\n" . self::describeOccurrence(), __METHOD__);

        return true;
    }

    /**
     * What a report needs in order to still be actionable weeks later: which request it was, and
     * which code in that request asked for the session. Neither follows from the key alone - a
     * "set(_csrf)" says nothing about the endpoint that ended up rendering an HTML page - and the
     * message has to carry both itself rather than rely on the log target: OpenTelemetryLogTarget
     * emits the message text and nothing else, so the per-request $_SERVER dump that the file log
     * appends never reaches an installation that collects its logs that way.
     */
    private static function describeOccurrence(): string
    {
        $lines = [];

        $request = \Yii::$app->request;
        if ($request instanceof \yii\web\Request) {
            $lines[] = 'Request: ' . $request->getMethod() . ' ' . $request->getAbsoluteUrl();
        }
        if (\Yii::$app->controller) {
            $lines[] = 'Route: ' . \Yii::$app->controller->getRoute();
        }
        $lines[] = 'Session touched from:';

        return implode("\n", array_merge($lines, self::describeCallStack()));
    }

    /**
     * The call stack in the format PHP uses for exception traces, minus the frames of the guard and
     * of Session itself, which are the same in every report. The last of those frames is kept, as it
     * is the only one naming the line that actually touched the session - the frame below it merely
     * says which function that line sits in.
     *
     * @return string[]
     */
    private static function describeCallStack(): array
    {
        $frames = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, self::TRACE_COLLECT_FRAMES);
        while (count($frames) > 1 && self::isSessionFrame($frames[1])) {
            array_shift($frames);
        }

        $basePath = \Yii::getAlias('@app') . DIRECTORY_SEPARATOR;

        $described = [];
        foreach (array_slice($frames, 0, self::TRACE_REPORT_FRAMES) as $frame) {
            $file = $frame['file'] ?? '[internal]';
            if (str_starts_with($file, $basePath)) {
                $file = substr($file, strlen($basePath));
            }
            $callee = ($frame['class'] ?? '') . ($frame['type'] ?? '') . $frame['function'];

            $described[] = '  ' . $file . '(' . ($frame['line'] ?? '?') . '): ' . $callee . '()';
        }

        return $described;
    }

    /**
     * @param array{function: string, class?: class-string, file?: string, line?: int} $frame
     */
    private static function isSessionFrame(array $frame): bool
    {
        return isset($frame['class']) && is_a($frame['class'], \yii\web\Session::class, true);
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
