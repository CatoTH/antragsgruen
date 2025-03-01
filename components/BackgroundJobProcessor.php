<?php

declare(strict_types=1);

namespace app\components;

use app\components\yii\MessageSource;
use app\models\backgroundJobs\{IBackgroundJob, IBackgroundJobException};
use yii\db\Connection;

class BackgroundJobProcessor
{
    private Connection $connection;

    private int $processedEvents = 0;
    private \DateTimeImmutable $startedAt;

    public function __construct(Connection $connection) {
        $this->connection = $connection;
        $this->startedAt = new \DateTimeImmutable();
    }

    public function getJobAndSetStarted(): ?IBackgroundJob {
        $foundJob = null;

        $this->connection->transaction(function () use (&$foundJob) {
            $command = $this->connection->createCommand('SELECT * FROM backgroundJob WHERE dateStarted IS NULL ORDER BY id ASC LIMIT 0,1 FOR UPDATE');
            $foundRows = $command->queryAll();
            if (empty($foundRows)) {
                return;
            }

            $foundRow = $foundRows[0];
            $this->connection->createCommand('UPDATE backgroundJob SET dateStarted = NOW() WHERE id = :id', ['id' => $foundRow['id']])->execute();

            $foundJob = IBackgroundJob::fromJson(
                intval($foundRow['id']),
                $foundRow['type'],
                ($foundRow['siteId'] > 0 ? $foundRow['siteId'] : null),
                ($foundRow['consultationId'] > 0 ? $foundRow['consultationId'] : null),
                $foundRow['payload']
            );
        });

        return $foundJob;
    }

    private function resetApplicationContext(IBackgroundJob $job): void
    {
        UrlHelper::setCurrentConsultation($job->getConsultation());
        UrlHelper::setCurrentSite($job->getSite());

        MessageSource::clearTranslationCache();
    }

    public function processRow(IBackgroundJob $job): void
    {
        $this->connection->createCommand(
            'UPDATE backgroundJob SET dateUpdated = NOW() WHERE id = :id',
            ['id' => $job->getId()]
        )->execute();

        try {
            $this->resetApplicationContext($job);

            $job->execute();

            $this->connection->createCommand(
                'UPDATE backgroundJob SET dateFinished = NOW() WHERE id = :id',
                ['id' => $job->getId()]
            )->execute();
        } catch (\Throwable $exception) {
            if ($this->isCriticalException($exception)) {
                $this->connection->createCommand(
                    'UPDATE backgroundJob SET error = :error WHERE id = :id',
                    [':error' => $exception->getMessage() . PHP_EOL . $exception->getTraceAsString(), ':id' => $job->getId()]
                )->execute();
            } else {
                $this->connection->createCommand(
                    'UPDATE backgroundJob SET error = :error, dateFinished = NOW() WHERE id = :id',
                    [':error' => $exception->getMessage() . PHP_EOL . $exception->getTraceAsString(), ':id' => $job->getId()]
                )->execute();
            }
        }
    }

    private function isCriticalException(\Throwable $exception): bool
    {
        if ($exception instanceof IBackgroundJobException) {
            return $exception->isCritical();
        }

        return true;
    }

    public function getProcessedEvents(): int {
        return $this->processedEvents;
    }

    public function getRuntimeInSeconds(): int {
        return (new \DateTimeImmutable())->getTimestamp() - $this->startedAt->getTimestamp();
    }
}
