<?php

declare(strict_types=1);

namespace app\commands;

use app\components\BackgroundJobProcessor;
use yii\console\Controller;

/**
 * Run background commends
 */
class BackgroundJobController extends Controller
{
    private const MAX_EVENTS = 1000;
    private const MAX_RUNTIME_SECONDS = 600;
    private const MAX_MEMORY_USAGE = 64_000_000;

    /**
     * Runs the background job processor
     *
     * @throws \yii\db\Exception
     */
    public function actionRun(): void
    {
        $connection = \Yii::$app->getDb();
        $connection->enableLogging = false;

        $processor = new BackgroundJobProcessor($connection);
        while (!$this->needsRestart($processor)) {
            $row = $processor->getJobAndSetStarted();
            if ($row) {
                $processor->processRow($row);
            } else {
                usleep(100000);
            }

            file_put_contents('/tmp/memory_usage.log', date("Y-m-d H:i:s") . ": " . memory_get_peak_usage() . "\n", FILE_APPEND);
        }
    }

    private function needsRestart(BackgroundJobProcessor $processor): bool
    {
        return $processor->getProcessedEvents() >= self::MAX_EVENTS
            || $processor->getRuntimeInSeconds() >= self::MAX_RUNTIME_SECONDS
            || memory_get_peak_usage() >= self::MAX_MEMORY_USAGE;
    }
}
