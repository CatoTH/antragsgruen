<?php

namespace app\components;

class SitePurger
{
    /**
     * @param int $amendId
     */
    public static function purgeAmendment($amendId)
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

    /**
     * @param int $motionId
     */
    public static function purgeMotion($motionId)
    {
        $connection = \Yii::$app->getDb();

        $amendmentIds = $connection->createCommand(
            'SELECT id FROM amendment WHERE motionId = :motionId',
            [':motionId' => $motionId]
        )->queryColumn();
        foreach ($amendmentIds as $amendmentId) {
            static::purgeAmendment($amendmentId);
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

    /**
     * @param int $motionTypeId
     */
    public static function purgeMotionType($motionTypeId)
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

    /**
     * @param int $consultationId
     */
    public static function purgeConsultation($consultationId)
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

        $connection->createCommand(
            'DELETE FROM consultationUserprivilege WHERE consultationId = :conId',
            [':conId' => $consultationId]
        )->execute();

        $motionIds = $connection->createCommand(
            'SELECT id FROM motion WHERE consultationId = :conId',
            [':conId' => $consultationId]
        )->queryColumn();
        foreach ($motionIds as $motionId) {
            static::purgeMotion($motionId);
        }

        $motionTypeIds = $connection->createCommand(
            'SELECT id FROM consultationMotionType WHERE consultationId = :conId',
            [':conId' => $consultationId]
        )->queryColumn();
        foreach ($motionTypeIds as $motionTypeId) {
            static::purgeMotionType($motionTypeId);
        }

        $connection->createCommand(
            'DELETE FROM consultationSettingsTag WHERE consultationId = :conId',
            [':conId' => $consultationId]
        )->execute();

        $connection->createCommand(
            'DELETE FROM consultationOdtTemplate WHERE consultationId = :conId',
            [':conId' => $consultationId]
        )->execute();

        $connection->createCommand(
            'DELETE FROM consultationAgendaItem WHERE consultationId = :conId',
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

    /**
     * @param int $siteId
     */
    public static function purgeSite($siteId)
    {
        $connection      = \Yii::$app->getDb();
        $consultationIds = $connection->createCommand(
            'SELECT id FROM consultation WHERE siteId = :siteId',
            [':siteId' => $siteId]
        )->queryColumn();

        foreach ($consultationIds as $consultationId) {
            static::purgeConsultation($consultationId);
        }

        $connection->createCommand(
            'DELETE FROM emailLog WHERE fromSiteId = :siteId',
            [':siteId' => $siteId]
        )->execute();

        $connection->createCommand(
            'DELETE FROM siteAdmin WHERE siteId = :siteId',
            [':siteId' => $siteId]
        )->execute();

        $connection->createCommand(
            'DELETE FROM site WHERE id = :siteId',
            [':siteId' => $siteId]
        )->execute();
    }
}
