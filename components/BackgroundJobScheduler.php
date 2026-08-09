<?php

declare(strict_types=1);

namespace app\components;

use app\models\backgroundJobs\IBackgroundJob;
use app\models\settings\AntragsgruenApp;

class BackgroundJobScheduler
{
    public const HEALTH_MAX_AGE_SECONDS = 120;

    public static function executeOrScheduleJob(IBackgroundJob $job): void
    {
        $flagName = $job->getConfigFlagName();
        if (isset(AntragsgruenApp::getInstance()->backgroundJobs[$flagName]) && AntragsgruenApp::getInstance()->backgroundJobs[$flagName]) {
            \Yii::$app->getDb()->createCommand(
                'INSERT INTO `backgroundJob` (`siteId`, `consultationId`, `type`, `dateCreation`, `payload`) VALUES (:siteId, :consultationId, :type, NOW(), :payload)',
                [
                    ':siteId' => $job->getSite()?->id,
                    ':consultationId' => $job->getConsultation()?->id,
                    ':type' => $job->getTypeId(),
                    ':payload' => $job->toJson(),
                ]
            )->execute();
        } else {
            $job->execute();
        }
    }

    /**
     * @return array{healthy: bool|null, data: array<string, mixed>}
     */
    public static function getDiagnostics(): array
    {
        // The queries below count backgroundJob rows regardless of type, so this diagnostic is
        // meaningful as soon as any job type is configured to be queued rather than run inline - not
        // just when notifications specifically are.
        if (count(array_filter(AntragsgruenApp::getInstance()->backgroundJobs ?? [])) === 0) {
            return [
                'healthy' => null,
                'data' => [],
            ];
        }

        $command = \Yii::$app->getDb()->createCommand('SELECT MIN(dateCreation) minAge, COUNT(*) num FROM backgroundJob WHERE dateStarted IS NULL');
        $result = $command->queryAll()[0];
        $unstarted = [
            'num' => intval($result['num']),
            'age' => ($result['minAge'] ? (time() - Tools::dateSql2timestamp($result['minAge'])) : 0),
        ];

        $command = \Yii::$app->getDb()->createCommand('SELECT MIN(dateCreation) minAge, COUNT(*) num FROM backgroundJob WHERE dateFinished IS NULL');
        $result = $command->queryAll()[0];
        $unfinished = [
            'num' => intval($result['num']),
            'age' => ($result['minAge'] ? (time() - Tools::dateSql2timestamp($result['minAge'])) : 0),
        ];

        return [
            'healthy' => ($unstarted['age'] <= self::HEALTH_MAX_AGE_SECONDS && $unfinished['age'] <= self::HEALTH_MAX_AGE_SECONDS),
            'data' => [
                'unstarted' => $unstarted,
                'unfinished' => $unfinished,
            ],
        ];
    }

    public static function cleanup(int $maxHageHours): int
    {
        $command = \Yii::$app->getDb()->createCommand(
            'DELETE FROM backgroundJob WHERE dateFinished < NOW() - INTERVAL :hours HOUR',
            [':hours' => $maxHageHours]
        );

        return $command->execute();
    }
}
