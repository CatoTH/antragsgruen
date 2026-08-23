<?php

namespace app\components;

/**
 * Irreversibly removes sites, consultations, motions and amendments, including every row referencing them.
 *
 * Almost all foreign keys of the schema are RESTRICT / NO ACTION, so the rows have to be deleted in
 * dependency order, and the reference cycles (motion <-> amendment <-> votingBlock) as well as the
 * self-references (motion.parentMotionId, amendment.amendingAmendmentId, agenda items, tags, file
 * groups) have to be broken by setting the referencing column to NULL first.
 *
 * Forgetting a table here does not silently leave orphans behind - it aborts the purge with a foreign
 * key error, half-way through. The order below is therefore derived from the constraints listed at the
 * end of assets/db/create.sql and has to be revisited whenever a table gains a reference to one of the
 * purged entities. SitePurgerTest guards this by purging the whole test fixture.
 */
class SitePurger
{
    /**
     * @param array<string, int> $params
     */
    private static function exec(string $sql, array $params = []): void
    {
        \Yii::$app->getDb()->createCommand($sql, $params)->execute();
    }

    /**
     * @param array<string, int> $params
     * @return int[]
     */
    private static function queryIds(string $sql, array $params = []): array
    {
        return array_map(intval(...), \Yii::$app->getDb()->createCommand($sql, $params)->queryColumn());
    }

    /**
     * Speaking lists are only detached, not deleted: they belong to the consultation, not to the
     * amendment, and are removed by purgeConsultation().
     */
    public static function purgeAmendment(int $amendId): void
    {
        $params = [':amendId' => $amendId];

        // References that would otherwise block the delete: amendments amending or proposing this one,
        // and motions/amendments carrying it as their proposed-procedure reference.
        self::exec('UPDATE amendment SET amendingAmendmentId = NULL WHERE amendingAmendmentId = :amendId', $params);
        self::exec('UPDATE amendment SET proposalReferenceId = NULL WHERE proposalReferenceId = :amendId', $params);
        self::exec('UPDATE motion SET proposalReferenceId = NULL WHERE proposalReferenceId = :amendId', $params);
        self::exec('UPDATE speechQueue SET amendmentId = NULL WHERE amendmentId = :amendId', $params);

        self::exec('DELETE FROM amendmentProposal WHERE amendmentId = :amendId OR proposalReferenceId = :amendId', $params);
        self::exec('DELETE FROM motionProposal WHERE proposalReferenceId = :amendId', $params);
        self::exec('DELETE FROM debateItem WHERE amendmentId = :amendId', $params);
        self::exec('DELETE FROM vote WHERE amendmentId = :amendId', $params);

        self::exec('DELETE FROM amendmentAdminComment WHERE amendmentId = :amendId', $params);
        self::exec('DELETE FROM amendmentComment WHERE amendmentId = :amendId', $params);
        self::exec('DELETE FROM amendmentSection WHERE amendmentId = :amendId', $params);
        self::exec('DELETE FROM amendmentSupporter WHERE amendmentId = :amendId', $params);
        self::exec('DELETE FROM amendmentTag WHERE amendmentId = :amendId', $params);

        self::exec('DELETE FROM amendment WHERE id = :amendId', $params);
    }

    /**
     * Speaking lists are only detached, not deleted - see purgeAmendment().
     */
    public static function purgeMotion(int $motionId): void
    {
        $params = [':motionId' => $motionId];

        foreach (self::queryIds('SELECT id FROM amendment WHERE motionId = :motionId', $params) as $amendmentId) {
            static::purgeAmendment($amendmentId);
        }

        self::exec('UPDATE motion SET parentMotionId = NULL WHERE parentMotionId = :motionId', $params);
        self::exec('UPDATE votingBlock SET assignedToMotionId = NULL WHERE assignedToMotionId = :motionId', $params);
        self::exec('UPDATE speechQueue SET motionId = NULL WHERE motionId = :motionId', $params);

        self::exec('DELETE FROM motionProposal WHERE motionId = :motionId', $params);
        self::exec('DELETE FROM debateItem WHERE motionId = :motionId', $params);
        self::exec('DELETE FROM vote WHERE motionId = :motionId', $params);
        self::exec('DELETE FROM motionSubscription WHERE motionId = :motionId', $params);

        self::exec(
            'DELETE FROM motionCommentSupporter WHERE motionCommentId IN (SELECT id FROM motionComment WHERE motionId = :motionId)',
            $params
        );
        self::exec('DELETE FROM motionAdminComment WHERE motionId = :motionId', $params);
        self::exec('DELETE FROM motionComment WHERE motionId = :motionId', $params);
        self::exec('DELETE FROM motionSection WHERE motionId = :motionId', $params);
        self::exec('DELETE FROM motionSupporter WHERE motionId = :motionId', $params);
        self::exec('DELETE FROM motionTag WHERE motionId = :motionId', $params);

        self::exec('DELETE FROM motion WHERE id = :motionId', $params);
    }

    public static function purgeMotionType(int $motionTypeId): void
    {
        $params = [':typeId' => $motionTypeId];

        self::exec('DELETE FROM consultationSettingsMotionSection WHERE motionTypeId = :typeId', $params);
        self::exec('DELETE FROM consultationMotionType WHERE id = :typeId', $params);
    }

    public static function purgeConsultation(int $consultationId): void
    {
        $params = [':conId' => $consultationId];

        // Debates and speaking lists first: they point at motions, amendments, agenda items and
        // votings, while nothing points at them.
        self::exec('DELETE FROM debateItem WHERE consultationId = :conId', $params);
        self::exec('DELETE FROM speechQueueItem WHERE queueId IN (SELECT id FROM speechQueue WHERE consultationId = :conId)', $params);
        self::exec('DELETE FROM speechSubqueue WHERE queueId IN (SELECT id FROM speechQueue WHERE consultationId = :conId)', $params);
        self::exec('DELETE FROM speechQueue WHERE consultationId = :conId', $params);

        // Cast votes, before the blocks, questions and items they refer to. A vote is not scoped to a
        // consultation itself, so it is addressed through everything it can point at.
        self::exec(
            'DELETE FROM vote
              WHERE votingBlockId IN (SELECT id FROM votingBlock WHERE consultationId = :conId)
                 OR questionId IN (SELECT id FROM votingQuestion WHERE consultationId = :conId)
                 OR motionId IN (SELECT id FROM motion WHERE consultationId = :conId)
                 OR amendmentId IN (SELECT a.id FROM amendment a JOIN motion m ON a.motionId = m.id WHERE m.consultationId = :conId)',
            $params
        );

        self::exec('DELETE FROM userConsultationScreening WHERE consultationId = :conId', $params);
        self::exec('DELETE FROM userNotification WHERE consultationId = :conId', $params);
        self::exec('DELETE FROM userGroup WHERE groupId IN (SELECT id FROM consultationUserGroup WHERE consultationId = :conId)', $params);
        self::exec('DELETE FROM consultationUserGroup WHERE consultationId = :conId', $params);

        // Uploaded documents before the texts, as a file group can belong to a consultation text
        self::exec('DELETE FROM consultationFile WHERE consultationId = :conId', $params);
        self::exec(
            'UPDATE consultationFile SET fileGroupId = NULL WHERE fileGroupId IN (SELECT id FROM consultationFileGroup WHERE consultationId = :conId)',
            $params
        );
        self::exec('UPDATE consultationFileGroup SET parentGroupId = NULL WHERE consultationId = :conId', $params);
        self::exec('DELETE FROM consultationFileGroup WHERE consultationId = :conId', $params);
        self::exec('DELETE FROM consultationText WHERE consultationId = :conId', $params);

        self::exec('DELETE FROM consultationLog WHERE consultationId = :conId', $params);

        foreach (self::queryIds('SELECT id FROM motion WHERE consultationId = :conId', $params) as $motionId) {
            static::purgeMotion($motionId);
        }

        // Votings only once no motion or amendment points at them anymore: motion.votingBlockId and
        // amendment.votingBlockId are ON DELETE CASCADE, so deleting a block while its motion still
        // exists would try to take that motion row along and fail on the rows referencing it.
        self::exec('DELETE FROM votingQuestion WHERE consultationId = :conId', $params);
        self::exec('DELETE FROM votingBlock WHERE consultationId = :conId', $params);

        foreach (self::queryIds('SELECT id FROM consultationMotionType WHERE consultationId = :conId', $params) as $motionTypeId) {
            static::purgeMotionType($motionTypeId);
        }

        self::exec('UPDATE consultationSettingsTag SET parentTagId = NULL WHERE consultationId = :conId', $params);
        self::exec('DELETE FROM consultationSettingsTag WHERE consultationId = :conId', $params);

        self::exec('UPDATE consultationAgendaItem SET parentItemId = NULL WHERE consultationId = :conId', $params);
        self::exec('DELETE FROM consultationAgendaItem WHERE consultationId = :conId', $params);

        self::exec('DELETE FROM backgroundJob WHERE consultationId = :conId', $params);

        self::exec('UPDATE site SET currentConsultationId = NULL WHERE currentConsultationId = :conId', $params);
        self::exec('DELETE FROM consultation WHERE id = :conId', $params);
    }

    public static function purgeSite(int $siteId): void
    {
        $params = [':siteId' => $siteId];

        foreach (self::queryIds('SELECT id FROM consultation WHERE siteId = :siteId', $params) as $consultationId) {
            static::purgeConsultation($consultationId);
        }

        // Rows belonging to the site as a whole rather than to one of its consultations
        self::exec('DELETE FROM consultationFile WHERE siteId = :siteId', $params);
        self::exec('DELETE FROM consultationText WHERE siteId = :siteId', $params);
        self::exec('DELETE FROM userGroup WHERE groupId IN (SELECT id FROM consultationUserGroup WHERE siteId = :siteId)', $params);
        self::exec('DELETE FROM consultationUserGroup WHERE siteId = :siteId', $params);

        // Only reachable now that the motion types referencing them are gone with the consultations
        self::exec('DELETE FROM texTemplate WHERE siteId = :siteId', $params);

        self::exec('DELETE FROM emailLog WHERE fromSiteId = :siteId', $params);
        self::exec('DELETE FROM backgroundJob WHERE siteId = :siteId', $params);

        self::exec('DELETE FROM site WHERE id = :siteId', $params);
    }
}
