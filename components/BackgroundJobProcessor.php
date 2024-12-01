<?php

declare(strict_types=1);

namespace app\components;

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

    public function getJobAndSetStarted(): ?array {
        $foundRow = null;

        $this->connection->transaction(function () use (&$foundRow) {
            $command = $this->connection->createCommand('SELECT * FROM backgroundJob WHERE dateStarted IS NULL ORDER BY id ASC LIMIT 0,1 FOR UPDATE');
            $foundRows = $command->queryAll();
            if (empty($foundRows)) {
                return;
            }

            $foundRow = $foundRows[0];
            $this->connection->createCommand('UPDATE backgroundJob SET dateStarted = NOW() WHERE id = :id', ['id' => $foundRow['id']])->execute();
        });

        return $foundRow;
    }

    public function processRow(array $row): void
    {
        echo "Processing row: " . $row['id'] . "\n";
        sleep(2);
        $this->connection->createCommand('UPDATE backgroundJob SET dateFinished = NOW() WHERE id = :id', ['id' => $row['id']])->execute();
    }

    public function getProcessedEvents(): int {
        return $this->processedEvents;
    }

    public function getRuntimeInSeconds(): int {
        return (new \DateTimeImmutable())->getTimestamp() - $this->startedAt->getTimestamp();
    }
}
