<?php

declare(strict_types=1);

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
use app\components\UrlHelper;
use app\models\exceptions\FormError;

class MotionDeepCopy
{
    public const SKIP_NON_AMENDABLE = 'non_amendable';
    public const SKIP_SUPPORTERS = 'supporters';
    public const SKIP_COMMENTS = 'comments';
    public const SKIP_AMENDMENTS = 'amendments';
    public const SKIP_PROPOSED_PROCEDURE = 'proposed_procedure';

    /**
     * @throws FormError
     */
    public static function copyMotion(
        Motion $motion,
        ConsultationMotionType $motionType,
        ?ConsultationAgendaItem $agendaItem,
        string $newPrefix,
        string $newVersion,
        bool $linkMotions,
        array $skip = []
    ): Motion {
        $newConsultation = $motionType->getConsultation();
        $sectionMapping = self::getMotionSectionMapping($motion->getMyMotionType(), $motionType, $skip);
        if (!$sectionMapping) {
            throw new FormError('No possible way to map the motion sections was found');
        }

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
        $newMotion->votingBlockId = null;
        $newMotion->parentMotionId = ($linkMotions ? $motion->id : null);

        if (in_array(self::SKIP_PROPOSED_PROCEDURE, $skip)) {
            self::resetProposedProcedure($newMotion);
        }

        $newMotion->save();

        self::copyTags($motion, $newMotion);
        self::copyMotionSections($motion, $newMotion, $skip);
        if (!in_array(self::SKIP_SUPPORTERS, $skip)) {
            self::copyMotionSupporters($motion, $newMotion);
        }
        if (!in_array(self::SKIP_COMMENTS, $skip)) {
            self::copyMotionAdminComments($motion, $newMotion);
            self::copyMotionComments($motion, $newMotion);
        }
        if (!in_array(self::SKIP_AMENDMENTS, $skip)) {
            self::copyAmendments($motion, $newMotion, $skip);
        }

        if ($newMotion->motionTypeId !== $motionType->id) {
            $newMotion->setMotionType($motionType, $sectionMapping);
        }

        $motion->getMyConsultation()->refresh();
        $newConsultation->refresh();
        UrlHelper::getCurrentConsultation()?->refresh();

        return $newMotion;
    }

    private static function resetProposedProcedure(Motion $newMotion): void
    {
        $newMotion->proposalStatus = null;
        $newMotion->proposalReferenceId = null;
        $newMotion->proposalVisibleFrom = null;
        $newMotion->proposalComment = null;
        $newMotion->proposalNotification = null;
        $newMotion->proposalUserStatus = null;
        $newMotion->proposalExplanation = null;
        $newMotion->votingBlockId = null;
        $newMotion->votingData = null;
        $newMotion->votingStatus = null;
        $newMotion->responsibilityId = null;
    }

    private static function copyTags(Motion $oldMotion, Motion $newMotion): void
    {
        if ($newMotion->consultationId === $oldMotion->consultationId) {
            foreach ($oldMotion->getPublicTopicTags() as $tag) {
                $newMotion->link('tags', $tag);
            }
        } else {
            $newConsultation = $newMotion->getMyConsultation();
            foreach ($oldMotion->getPublicTopicTags() as $tag) {
                $newTag = $newConsultation->getExistingTag($tag->type, $tag->title);
                if ($newTag) {
                    $newMotion->link('tags', $newTag);
                }
            }
        }
    }

    private static function copyMotionSections(Motion $oldMotion, Motion $newMotion, array $skip): void
    {
        $sectionMapping = self::getMotionSectionMapping($oldMotion->getMyMotionType(), $newMotion->getMyMotionType(), $skip);

        foreach ($oldMotion->sections as $section) {
            if (!isset($sectionMapping[$section->sectionId])) {
                continue;
            }
            $newSection = new MotionSection();
            $newSection->setAttributes($section->getAttributes(), false);
            $newSection->setData($section->getData());
            $newSection->motionId = $newMotion->id;
            $newSection->cache = '';
            $newSection->save();
        }
    }

    private static function copyMotionSupporters(Motion $oldMotion, Motion $newMotion): void
    {
        foreach ($oldMotion->motionSupporters as $supporter) {
            $newSupporter = new MotionSupporter();
            $newSupporter->setAttributes($supporter->getAttributes(), false);
            $newSupporter->id = null;
            $newSupporter->motionId = $newMotion->id;
            $newSupporter->save();
        }
    }

    private static function copyMotionAdminComments(Motion $oldMotion, Motion $newMotion): void
    {
        foreach ($oldMotion->getAllAdminComments() as $comment) {
            $newComment = new MotionAdminComment();
            $newComment->setAttributes($comment->getAttributes(), false);
            $newComment->id = null;
            $newComment->motionId = $newMotion->id;
            $newComment->save();
        }
    }

    private static function copyMotionComments(Motion $oldMotion, Motion $newMotion): void
    {
        foreach ($oldMotion->comments as $comment) {
            $newComment = new MotionComment();
            $newComment->setAttributes($comment->getAttributes(), false);
            $newComment->id = null;
            $newComment->motionId = $newMotion->id;
            $newComment->save();
        }
    }

    private static function copyAmendments(Motion $oldMotion, Motion $newMotion, array $skip): void
    {
        $amendmentIdMapping = [];
        $newAmendments = [];

        foreach ($oldMotion->amendments as $amendment) {
            $newAmendment = new Amendment();
            $newAmendment->setAttributes($amendment->getAttributes(), false);
            $newAmendment->id = null;
            $newAmendment->motionId = $newMotion->id;
            $newAmendment->cache = '';
            $newAmendment->votingBlockId = null;

            $oldTitlePre = $oldMotion->titlePrefix . '-';
            $newTitlePre = $newMotion->titlePrefix . '-';
            if ($amendment->titlePrefix) {
                $newAmendment->titlePrefix = str_replace($oldTitlePre, $newTitlePre, $amendment->titlePrefix);
            } else {
                $newAmendment->titlePrefix = null;
            }

            $newAmendment->save();
            $amendmentIdMapping[$amendment->id] = $newAmendment->id;
            $newAmendments[] = $newAmendment;

            self::copyAmendmentSections($amendment, $newAmendment, $skip);
            self::copyAmendmentSupporters($amendment, $newAmendment);
            self::copyAmendmentComments($amendment, $newAmendment);
            self::copyAmendmentAdminComments($amendment, $newAmendment);
        }

        foreach ($newAmendments as $newAmendment) {
            if ($newAmendment->proposalReferenceId && isset($amendmentIdMapping[$newAmendment->proposalReferenceId])) {
                $newAmendment->proposalReferenceId = $amendmentIdMapping[$newAmendment->proposalReferenceId];
                $newAmendment->save();
            }
            if ($newAmendment->amendingAmendmentId && isset($amendmentIdMapping[$newAmendment->amendingAmendmentId])) {
                $newAmendment->amendingAmendmentId = $amendmentIdMapping[$newAmendment->amendingAmendmentId];
                $newAmendment->save();
            }
        }

        if ($newMotion->proposalReferenceId && isset($amendmentIdMapping[$newMotion->proposalReferenceId])) {
            $newMotion->proposalReferenceId = $amendmentIdMapping[$newMotion->proposalReferenceId];
            $newMotion->save();
        }
    }

    private static function copyAmendmentSections(Amendment $oldAmendment, Amendment $newAmendment, array $skip): void
    {
        $sectionMapping = self::getMotionSectionMapping($oldAmendment->getMyMotionType(), $newAmendment->getMyMotionType(), []);

        foreach ($oldAmendment->sections as $section) {
            if (!isset($sectionMapping[$section->sectionId])) {
                continue;
            }
            $newSection = new AmendmentSection();
            $newSection->setAttributes($section->getAttributes(), false);
            $newSection->amendmentId = $newAmendment->id;
            $newSection->cache = '';
            $newSection->save();
        }
    }

    private static function copyAmendmentSupporters(Amendment $oldAmendment, Amendment $newAmendment): void
    {
        foreach ($oldAmendment->amendmentSupporters as $supporter) {
            $newSupporter = new AmendmentSupporter();
            $newSupporter->setAttributes($supporter->getAttributes(), false);
            $newSupporter->id = null;
            $newSupporter->amendmentId = $newAmendment->id;
            $newSupporter->save();
        }
    }

    private static function copyAmendmentComments(Amendment $oldAmendment, Amendment $newAmendment): void
    {
        foreach ($oldAmendment->comments as $comment) {
            $newComment = new AmendmentComment();
            $newComment->setAttributes($comment->getAttributes(), false);
            $newComment->id = null;
            $newComment->amendmentId = $newAmendment->id;
            $newComment->save();
        }
    }

    private static function copyAmendmentAdminComments(Amendment $oldAmendment, Amendment $newAmendment): void
    {
        foreach ($oldAmendment->getAllAdminComments() as $comment) {
            $newComment = new AmendmentAdminComment();
            $newComment->setAttributes($comment->getAttributes(), false);
            $newComment->id = null;
            $newComment->amendmentId = $newAmendment->id;
            $newComment->save();
        }
    }

    /**
     * @param string[] $skip
     * @return array<int|string, int>|null
     */
    public static function getMotionSectionMapping(ConsultationMotionType $typeFrom, ConsultationMotionType $typeTo, array $skip): ?array
    {
        $mappings = [];
        $toIdx = 0;

        for ($fromIdx = 0; $fromIdx < count($typeFrom->motionSections); $fromIdx++) {
            $fromSection = $typeFrom->motionSections[$fromIdx];
            if (!$fromSection->hasAmendments && in_array(self::SKIP_NON_AMENDABLE, $skip)) {
                continue;
            }
            if (!isset($typeTo->motionSections[$toIdx])) {
                return null;
            }

            $foundTo = null;

            do {
                $toSection = $typeTo->motionSections[$toIdx];

                if ($fromSection->type === $toSection->type) {
                    $foundTo = $toSection->id;
                }
                $toIdx++;
            } while (!$foundTo && isset($typeTo->motionSections[$toIdx]));

            if ($foundTo) {
                $mappings[$fromSection->id] = $foundTo;
            } else {
                return null; // No mapping found -> not mappable
            }
        }

        return $mappings;
    }
}
