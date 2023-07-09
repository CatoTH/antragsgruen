<?php declare(strict_types=1);

namespace app\models\forms;

use app\models\db\{Amendment,
    AmendmentAdminComment,
    AmendmentComment,
    AmendmentSection,
    AmendmentSupporter,
    ConsultationAgendaItem,
    ConsultationMotionType,
    Motion,
    MotionAdminComment,
    MotionComment,
    MotionSection,
    MotionSupporter};

class MotionDeepCopy
{
    public static function copyMotion(
        Motion $motion,
        ConsultationMotionType $motionType,
        ?ConsultationAgendaItem $agendaItem,
        string $newPrefix,
        string $newVersion,
        bool $linkMotions
    ): Motion
    {
        $newConsultation = $motionType->getConsultation();
        $slug = $motion->slug;

        if ($motion->consultationId === $newConsultation->id) {
            $motion->slug = null;
            $motion->save();
        }

        $newMotion = new Motion();
        $newMotion->setAttributes($motion->getAttributes(), false);
        $newMotion->id = null;
        $newMotion->agendaItemId = $agendaItem?->id;
        $newMotion->titlePrefix = $newPrefix;
        $newMotion->consultationId = $newConsultation->id;
        $newMotion->cache = '';
        $newMotion->slug = $slug;
        $newMotion->version = $newVersion;
        $newMotion->parentMotionId = ($linkMotions ? $motion->id : null);

        $newMotion->save();

        self::copyTags($motion, $newMotion);
        self::copyMotionSections($motion, $newMotion);
        self::copyMotionSupporters($motion, $newMotion);
        self::copyMotionAdminComments($motion, $newMotion);
        self::copyMotionComments($motion, $newMotion);
        self::copyAmendments($motion, $newMotion);

        if ($newMotion->motionTypeId !== $motionType->id) {
            $newMotion->setMotionType($motionType);
        }

        return $newMotion;
    }

    private static function copyTags(Motion $oldMotion, Motion $newMotion): void
    {
        if ($newMotion->consultationId !== $oldMotion->consultationId) {
            // Alternatively, we could link similar sounding tags,
            // but as there is no guarantee there would be matching tags, we skip it completely.
            return;
        }
        foreach ($oldMotion->getPublicTopicTags() as $tag) {
            $newMotion->link('tags', $tag);
        }
    }

    private static function copyMotionSections(Motion $oldMotion, Motion $newMotion): void
    {
        foreach ($oldMotion->sections as $section) {
            $newSection = new MotionSection();
            $newSection->setAttributes($section->getAttributes(), false);
            $newSection->setData($section->getData());
            $newSection->motionId = $newMotion->id;
            $newSection->cache    = '';
            $newSection->save();
        }
    }

    private static function copyMotionSupporters(Motion $oldMotion, Motion $newMotion): void
    {
        foreach ($oldMotion->motionSupporters as $supporter) {
            $newSupporter = new MotionSupporter();
            $newSupporter->setAttributes($supporter->getAttributes(), false);
            $newSupporter->id       = null;
            $newSupporter->motionId = $newMotion->id;
            $newSupporter->save();
        }
    }

    private static function copyMotionAdminComments(Motion $oldMotion, Motion $newMotion): void
    {
        foreach ($oldMotion->getAllAdminComments() as $comment) {
            $newComment = new MotionAdminComment();
            $newComment->setAttributes($comment->getAttributes(), false);
            $newComment->id       = null;
            $newComment->motionId = $newMotion->id;
            $newComment->save();
        }
    }

    private static function copyMotionComments(Motion $oldMotion, Motion $newMotion): void
    {
        foreach ($oldMotion->comments as $comment) {
            $newComment = new MotionComment();
            $newComment->setAttributes($comment->getAttributes(), false);
            $newComment->id       = null;
            $newComment->motionId = $newMotion->id;
            $newComment->save();
        }
    }

    private static function copyAmendments(Motion $oldMotion, Motion $newMotion): void
    {
        $amendmentIdMapping = [];
        $newAmendments      = [];

        foreach ($oldMotion->amendments as $amendment) {
            $newAmendment = new Amendment();
            $newAmendment->setAttributes($amendment->getAttributes(), false);
            $newAmendment->id       = null;
            $newAmendment->motionId = $newMotion->id;
            $newAmendment->cache    = '';

            $oldTitlePre = $oldMotion->titlePrefix . '-';
            $newTitlePre = $newMotion->titlePrefix . '-';
            if ($amendment->titlePrefix) {
                $newAmendment->titlePrefix = str_replace($oldTitlePre, $newTitlePre, $amendment->titlePrefix);
            } else {
                $newAmendment->titlePrefix = null;
            }

            $newAmendment->save();
            $amendmentIdMapping[$amendment->id] = $newAmendment->id;
            $newAmendments[]                    = $newAmendment;

            self::copyAmendmentSections($amendment, $newAmendment);
            self::copyAmendmentSupporters($amendment, $newAmendment);
            self::copyAmendmentComments($amendment, $newAmendment);
            self::copyAmendmentAdminComments($amendment, $newAmendment);
        }

        foreach ($newAmendments as $newAmendment) {
            if ($newAmendment->proposalReferenceId && isset($amendmentIdMapping[$newAmendment->proposalReferenceId])) {
                $newAmendment->proposalReferenceId = $amendmentIdMapping[$newAmendment->proposalReferenceId];
                $newAmendment->save();
            }
        }
    }

    private static function copyAmendmentSections(Amendment $oldAmendment, Amendment $newAmendment): void
    {
        foreach ($oldAmendment->sections as $section) {
            $newSection = new AmendmentSection();
            $newSection->setAttributes($section->getAttributes(), false);
            $newSection->amendmentId = $newAmendment->id;
            $newSection->cache       = '';
            $newSection->save();
        }
    }

    private static function copyAmendmentSupporters(Amendment $oldAmendment, Amendment $newAmendment): void
    {
        foreach ($oldAmendment->amendmentSupporters as $supporter) {
            $newSupporter = new AmendmentSupporter();
            $newSupporter->setAttributes($supporter->getAttributes(), false);
            $newSupporter->id          = null;
            $newSupporter->amendmentId = $newAmendment->id;
            $newSupporter->save();
        }
    }

    private static function copyAmendmentComments(Amendment $oldAmendment, Amendment $newAmendment): void
    {
        foreach ($oldAmendment->comments as $comment) {
            $newComment = new AmendmentComment();
            $newComment->setAttributes($comment->getAttributes(), false);
            $newComment->id          = null;
            $newComment->amendmentId = $newAmendment->id;
            $newComment->save();
        }
    }

    private static function copyAmendmentAdminComments(Amendment $oldAmendment, Amendment $newAmendment): void
    {
        foreach ($oldAmendment->getAllAdminComments() as $comment) {
            $newComment = new AmendmentAdminComment();
            $newComment->setAttributes($comment->getAttributes(), false);
            $newComment->id          = null;
            $newComment->amendmentId = $newAmendment->id;
            $newComment->save();
        }
    }
}
