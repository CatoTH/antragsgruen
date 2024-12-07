<?php

declare(strict_types=1);

namespace app\components;

use app\models\backgroundJobs\IBackgroundJob;
use app\models\settings\AntragsgruenApp;

class BackgroundJobScheduler
{
    public static function executeOrScheduleJob(IBackgroundJob $job): void
    {
        if (AntragsgruenApp::getInstance()->backgroundJobs) {
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
}
