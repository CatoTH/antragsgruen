<?php

declare(strict_types=1);

namespace app\commands;

use app\components\BackgroundJobProcessor;
use app\components\BackgroundJobScheduler;
use Yii;
use yii\console\Controller;

/**
 * Run background commends
 */
class BackgroundJobController extends Controller
{
    private const DEFAULT_MAX_EVENTS = 1000;
    private const DEFAULT_MAX_RUNTIME_SECONDS = 600;
    private const DEFAULT_MAX_MEMORY_USAGE = 64_000_000;

    private const MAX_RETENTION_PERIOD_HOURS = 24 * 3;

    protected int $maxEvents = self::DEFAULT_MAX_EVENTS;
    protected int $maxRuntimeSeconds = self::DEFAULT_MAX_RUNTIME_SECONDS;
    protected int $maxMemoryUsage = self::DEFAULT_MAX_MEMORY_USAGE;

    public function options($actionID): array
    {
        return ['maxEvents', 'maxRuntimeSeconds', 'maxMemoryUsage'];
    }

    /**
     * Runs the background job processor
     * Options:
     * --max-runtime-seconds 600
     * --max-events 1000
     * --max-memory-usage 64000000
     */
    public function actionRun(): void
    {
        echo "Starting background job processor at: " . (new \DateTimeImmutable())->format("Y-m-d H:i:s.u") . "\n";

        $connection = \Yii::$app->getDb();
        $connection->enableLogging = false;

        $processor = new BackgroundJobProcessor($connection);
        while (!$this->needsRestart($processor)) {
            $row = $processor->getJobAndSetStarted();
            if ($row) {
                $processor->processRow($row);
            } else {
                usleep(100_000);
            }
        }

        echo "Stopping background job processor at: " . (new \DateTimeImmutable())->format("Y-m-d H:i:s.u") . "\n";
    }

    private function needsRestart(BackgroundJobProcessor $processor): bool
    {
        if ($processor->getProcessedEvents() >= $this->maxEvents) {
            echo "Stopping because maximum number of processed events has been reached.\n";
            return true;
        }

        if ($processor->getRuntimeInSeconds() >= $this->maxRuntimeSeconds) {
            echo "Stopping because maximum runtime has been reached.\n";
            return true;
        }

        if (memory_get_peak_usage() >= $this->maxMemoryUsage) {
            echo "Stopping because maximum memory usage has been reached.\n";
            return true;
        }

        return false;
    }

    /**
     * Cleans up old tasks from database
     */
    public function actionCleanup(): void
    {
        $deletedJobs = BackgroundJobScheduler::cleanup(self::MAX_RETENTION_PERIOD_HOURS);

        echo "Deleted $deletedJobs jobs.\n";
    }
}
