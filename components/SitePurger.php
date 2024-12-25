<?php

namespace app\components;

class SitePurger
{
    public static function purgeAmendment(int $amendId): void
    {
        $connection = \Yii::$app->getDb();

        $connection->createCommand(
            'DELETE FROM amendmentAdminComment WHERE amendmentId = :amendId',
            [':amendId' => $amendId]
        )->execute();

        $connection->createCommand(
            'DELETE FROM amendmentComment WHERE amendmentId = :amendId',
            [':amendId' => $amendId]
        )->execute();

        $connection->createCommand(
            'DELETE FROM amendmentSection WHERE amendmentId = :amendId',
            [':amendId' => $amendId]
        )->execute();

        $connection->createCommand(
            'DELETE FROM amendmentSupporter WHERE amendmentId = :amendId',
            [':amendId' => $amendId]
        )->execute();

        $connection->createCommand(
            'DELETE FROM amendment WHERE id = :amendId',
            [':amendId' => $amendId]
        )->execute();
    }

    public static function purgeMotion(int $motionId): void
    {
        $connection = \Yii::$app->getDb();

        $amendmentIds = $connection->createCommand(
            'SELECT id FROM amendment WHERE motionId = :motionId',
            [':motionId' => $motionId]
        )->queryColumn();
        foreach ($amendmentIds as $amendmentId) {
            static::purgeAmendment((int)$amendmentId);
        }

        $connection->createCommand(
            'DELETE FROM motionAdminComment WHERE motionId = :motionId',
            [':motionId' => $motionId]
        )->execute();

        $connection->createCommand(
            'DELETE FROM motionComment WHERE motionId = :motionId',
            [':motionId' => $motionId]
        )->execute();

        $connection->createCommand(
            'DELETE FROM motionSection WHERE motionId = :motionId',
            [':motionId' => $motionId]
        )->execute();

        $connection->createCommand(
            'DELETE FROM motionSupporter WHERE motionId = :motionId',
            [':motionId' => $motionId]
        )->execute();

        $connection->createCommand(
            'DELETE FROM motionTag WHERE motionId = :motionId',
            [':motionId' => $motionId]
        )->execute();

        $connection->createCommand(
            'DELETE FROM motion WHERE id = :motionId',
            [':motionId' => $motionId]
        )->execute();
    }

    public static function purgeMotionType(int $motionTypeId): void
    {
        $connection = \Yii::$app->getDb();

        $connection->createCommand(
            'DELETE FROM consultationSettingsMotionSection WHERE motionTypeId = :typeId',
            [':typeId' => $motionTypeId]
        )->execute();

        $connection->createCommand(
            'DELETE FROM consultationMotionType WHERE id = :typeId',
            [':typeId' => $motionTypeId]
        )->execute();
    }

    public static function purgeConsultation(int $consultationId): void
    {
        $connection = \Yii::$app->getDb();
        $connection->createCommand(
            'DELETE FROM consultationText WHERE consultationId = :conId',
            [':conId' => $consultationId]
        )->execute();

        $connection->createCommand(
            'DELETE FROM consultationLog WHERE consultationId = :conId',
            [':conId' => $consultationId]
        )->execute();

        $connection->createCommand(
            'DELETE FROM consultationAdmin WHERE consultationId = :conId',
            [':conId' => $consultationId]
        )->execute();

        $motionIds = $connection->createCommand(
            'SELECT id FROM motion WHERE consultationId = :conId',
            [':conId' => $consultationId]
        )->queryColumn();
        foreach ($motionIds as $motionId) {
            static::purgeMotion((int)$motionId);
        }

        $motionTypeIds = $connection->createCommand(
            'SELECT id FROM consultationMotionType WHERE consultationId = :conId',
            [':conId' => $consultationId]
        )->queryColumn();
        foreach ($motionTypeIds as $motionTypeId) {
            static::purgeMotionType((int)$motionTypeId);
        }

        $connection->createCommand(
            'DELETE FROM consultationSettingsTag WHERE consultationId = :conId',
            [':conId' => $consultationId]
        )->execute();

        $connection->createCommand(
            'DELETE FROM consultationAgendaItem WHERE consultationId = :conId',
            [':conId' => $consultationId]
        )->execute();

        $connection->createCommand(
            'DELETE FROM backgroundJob WHERE consultationId = :conId',
            [':conId' => $consultationId]
        )->execute();

        $connection->createCommand(
            'UPDATE site SET currentConsultationId = NULL WHERE currentConsultationId = :conId',
            [':conId' => $consultationId]
        )->execute();

        $connection->createCommand(
            'DELETE FROM consultation WHERE id = :conId',
            [':conId' => $consultationId]
        )->execute();
    }

    public static function purgeSite(int $siteId): void
    {
        $connection      = \Yii::$app->getDb();
        $consultationIds = $connection->createCommand(
            'SELECT id FROM consultation WHERE siteId = :siteId',
            [':siteId' => $siteId]
        )->queryColumn();

        foreach ($consultationIds as $consultationId) {
            static::purgeConsultation((int)$consultationId);
        }

        $connection->createCommand(
            'DELETE FROM emailLog WHERE fromSiteId = :siteId',
            [':siteId' => $siteId]
        )->execute();

        $connection->createCommand(
            'DELETE FROM backgroundJob WHERE siteId = :siteId',
            [':siteId' => $siteId]
        )->execute();

        $connection->createCommand(
            'DELETE FROM site WHERE id = :siteId',
            [':siteId' => $siteId]
        )->execute();
    }
}
