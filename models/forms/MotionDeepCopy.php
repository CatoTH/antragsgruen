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
    public static function copyMotion(Motion $motion, ConsultationMotionType $motionType, ?ConsultationAgendaItem $agendaItem, string $newPrefix): Motion
    {
        $newConsultation = $motionType->getMyConsultation();
        $slug = $motion->slug;

        if ($motion->consultationId === $newConsultation->id) {
            $motion->slug = null;
            $motion->save();
        }

        $newMotion = new Motion();
        $newMotion->setAttributes($motion->getAttributes(), false);
        $newMotion->id             = null;
        $newMotion->agendaItemId   = ($agendaItem ? $agendaItem->id : null);
        $newMotion->titlePrefix    = $newPrefix;
        $newMotion->consultationId = $newConsultation->id;
        $newMotion->cache          = '';
        $newMotion->slug           = $slug;
        $newMotion->save();

        static::copyTags($motion, $newMotion);
        static::copyMotionSections($motion, $newMotion);
        static::copyMotionSupporters($motion, $newMotion);
        static::copyMotionAdminComments($motion, $newMotion);
        static::copyMotionComments($motion, $newMotion);
        static::copyAmendments($motion, $newMotion);

        if ($newMotion->motionTypeId !== $motionType->id) {
            $newMotion->setMotionType($motionType);
        }

        return $newMotion;
    }

    private static function copyTags(Motion $oldMotion, Motion $newMotion)
    {
        foreach ($oldMotion->tags as $tag) {
            $newMotion->link('tags', $tag);
        }
    }

    private static function copyMotionSections(Motion $oldMotion, Motion $newMotion)
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

    private static function copyMotionSupporters(Motion $oldMotion, Motion $newMotion)
    {
        foreach ($oldMotion->motionSupporters as $supporter) {
            $newSupporter = new MotionSupporter();
            $newSupporter->setAttributes($supporter->getAttributes(), false);
            $newSupporter->id       = null;
            $newSupporter->motionId = $newMotion->id;
            $newSupporter->save();
        }
    }

    private static function copyMotionAdminComments(Motion $oldMotion, Motion $newMotion)
    {
        foreach ($oldMotion->getAllAdminComments() as $comment) {
            $newComment = new MotionAdminComment();
            $newComment->setAttributes($comment->getAttributes(), false);
            $newComment->id       = null;
            $newComment->motionId = $newMotion->id;
            $newComment->save();
        }
    }

    private static function copyMotionComments(Motion $oldMotion, Motion $newMotion)
    {
        foreach ($oldMotion->comments as $comment) {
            $newComment = new MotionComment();
            $newComment->setAttributes($comment->getAttributes(), false);
            $newComment->id       = null;
            $newComment->motionId = $newMotion->id;
            $newComment->save();
        }
    }

    private static function copyAmendments(Motion $oldMotion, Motion $newMotion)
    {
        $amendmentIdMapping = [];
        $newAmendments      = [];

        foreach ($oldMotion->amendments as $amendment) {
            $newAmendment = new Amendment();
            $newAmendment->setAttributes($amendment->getAttributes(), false);
            $newAmendment->id       = null;
            $newAmendment->motionId = $newMotion->id;
            $newAmendment->cache    = '';

            $oldTitlePre               = $oldMotion->titlePrefix . '-';
            $newTitlePre               = $newMotion->titlePrefix . '-';
            $newAmendment->titlePrefix = str_replace($oldTitlePre, $newTitlePre, $amendment->titlePrefix);

            $newAmendment->save();
            $amendmentIdMapping[$amendment->id] = $newAmendment->id;
            $newAmendments[]                    = $newAmendment;

            static::copyAmendmentSections($amendment, $newAmendment);
            static::copyAmendmentSupporters($amendment, $newAmendment);
            static::copyAmendmentComments($amendment, $newAmendment);
            static::copyAmendmentAdminComments($amendment, $newAmendment);
        }

        foreach ($newAmendments as $newAmendment) {
            if ($newAmendment->proposalReferenceId && isset($amendmentIdMapping[$newAmendment->proposalReferenceId])) {
                $newAmendment->proposalReferenceId = $amendmentIdMapping[$newAmendment->proposalReferenceId];
                $newAmendment->save();
            }
        }
    }

    private static function copyAmendmentSections(Amendment $oldAmendment, Amendment $newAmendment)
    {
        foreach ($oldAmendment->sections as $section) {
            $newSection = new AmendmentSection();
            $newSection->setAttributes($section->getAttributes(), false);
            $newSection->amendmentId = $newAmendment->id;
            $newSection->cache       = '';
            $newSection->save();
        }
    }

    private static function copyAmendmentSupporters(Amendment $oldAmendment, Amendment $newAmendment)
    {
        foreach ($oldAmendment->amendmentSupporters as $supporter) {
            $newSupporter = new AmendmentSupporter();
            $newSupporter->setAttributes($supporter->getAttributes(), false);
            $newSupporter->id          = null;
            $newSupporter->amendmentId = $newAmendment->id;
            $newSupporter->save();
        }
    }

    private static function copyAmendmentComments(Amendment $oldAmendment, Amendment $newAmendment)
    {
        foreach ($oldAmendment->comments as $comment) {
            $newComment = new AmendmentComment();
            $newComment->setAttributes($comment->getAttributes(), false);
            $newComment->id          = null;
            $newComment->amendmentId = $newAmendment->id;
            $newComment->save();
        }
    }

    private static function copyAmendmentAdminComments(Amendment $oldAmendment, Amendment $newAmendment)
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
