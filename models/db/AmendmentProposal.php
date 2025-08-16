<?php

declare(strict_types=1);

namespace app\models\db;

use app\models\notifications\AmendmentProposedProcedure;
use app\models\SectionedParagraph;
use app\models\settings\AntragsgruenApp;
use app\components\{diff\AmendmentRewriter, diff\ArrayMatcher, diff\Diff, diff\DiffRenderer, HTMLTools, LineSplitter};
use app\models\exceptions\Internal;
use app\models\sectionTypes\ISectionType;
use yii\db\ActiveRecord;

/**
 * @property int $amendmentId
 */
class AmendmentProposal extends IProposal
{
    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'amendmentProposal';
    }

    public function getCachedConsultation(): ?Consultation
    {
        $current = Consultation::getCurrent();
        if ($current) {
            if ($this->amendmentId === null) {
                return $current;
            }
            $amend = $current->getAmendment($this->amendmentId);
            if ($amend) {
                return $current;
            }
        }
        $amendment = Amendment::findOne($this->amendmentId);

        return $amendment?->getMyConsultation();
    }

    function getMyIMotion(): IMotion
    {
        return $this->getAmendment();
    }

    public function hasAlternativeProposaltext(bool $includeOtherAmendments = false, int $internalNestingLevel = 0): bool
    {
        $consultation = $this->getCachedConsultation();

        // This amendment has a direct modification proposal
        if (in_array($this->proposalStatus, [Amendment::STATUS_MODIFIED_ACCEPTED, Amendment::STATUS_VOTE]) &&
            $this->proposalReferenceId && $consultation->getAmendment($this->proposalReferenceId)) {
            return true;
        }

        // This amendment is obsoleted by an amendment with a modification proposal
        if ($includeOtherAmendments && $this->proposalStatus === Amendment::STATUS_OBSOLETED_BY_AMENDMENT) {
            $obsoletedBy = $consultation->getAmendment(intval($this->comment));
            if ($obsoletedBy && $internalNestingLevel < 10) {
                return $obsoletedBy->getLatestProposal()->hasAlternativeProposaltext($includeOtherAmendments, $internalNestingLevel + 1);
            }
        }

        // It was proposed to move this amendment to another motion
        if ($includeOtherAmendments && $this->proposalStatus === Amendment::STATUS_PROPOSED_MOVE_TO_OTHER_MOTION) {
            $movedTo = $consultation->getAmendment(intval($this->comment));
            if ($movedTo) {
                return true;
            }
        }

        return false;
    }

    public function rules(): array
    {
        return [
            [['amendmentId'], 'required'],
            [['amendmentId'], 'number'],
        ];
    }

    public function getAmendment(): ?Amendment
    {
        if ($this->amendmentId === null) {
            return null;
        }
        return $this->getCachedConsultation()->getAmendment($this->amendmentId);
    }

    public static function createNew(Amendment $amendment, int $version): AmendmentProposal
    {
        $proposal = new AmendmentProposal();
        $proposal->version = $version;
        $proposal->amendmentId = $amendment->id;
        $proposal->publicToken = \Yii::$app->getSecurity()->generateRandomString(32);

        return $proposal;
    }



    /**
     * @return Amendment[]
     * @throws Internal
     */
    public function collidesWithOtherProposedAmendments(): array
    {
        $collidesWith = [];
        $myAmendment = $this->getAmendment();

        if ($this->getMyProposalReference()) {
            $sections = $this->getMyProposalReference()->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE);
        } else {
            $sections = $myAmendment->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE);
        }
        $newSections = [];
        foreach ($sections as $section) {
            $newSections[$section->sectionId] = $section->data;
        }

        foreach ($myAmendment->getMyMotion()->getAmendmentsForCollissionDetection([$myAmendment->id]) as $amendment) {
            if ($myAmendment->globalAlternative || $amendment->globalAlternative) {
                $collidesWith[] = $amendment;
                continue;
            }
            foreach ($amendment->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
                $coll = $section->getRewriteCollisions($newSections[$section->sectionId], false);
                if (count($coll) > 0) {
                    if (!in_array($amendment, $collidesWith, true)) {
                        $collidesWith[] = $amendment;
                    }
                }
            }
        }

        return $collidesWith;
    }

    /**
     * Returns the modification proposed and the amendment to which the modification was directly proposed
     * (which has not to be this very amendment, in case this amendment is obsoleted by another amendment)
     *
     * @return array{amendment: Amendment, modification: Amendment}|null
     */
    public function getAlternativeProposaltextReference(int $internalNestingLevel = 0): ?array
    {
        // This amendment has a direct modification proposal
        if (in_array($this->proposalStatus, [Amendment::STATUS_MODIFIED_ACCEPTED, Amendment::STATUS_VOTE]) && $this->getMyProposalReference()) {
            return [
                'amendment'    => $this->getAmendment(),
                'modification' => $this->getMyProposalReference(),
            ];
        }

        // This amendment is obsoleted by an amendment with a modification proposal
        if ($this->proposalStatus === Amendment::STATUS_OBSOLETED_BY_AMENDMENT) {
            $obsoletedBy = $this->getMyConsultation()->getAmendment(intval($this->comment));
            if ($obsoletedBy && $internalNestingLevel < 10) {
                return $obsoletedBy->getLatestProposal()->getAlternativeProposaltextReference($internalNestingLevel + 1);
            }
        }

        // It was proposed to move this amendment to another motion
        if ($this->proposalStatus === Amendment::STATUS_PROPOSED_MOVE_TO_OTHER_MOTION) {
            $movedTo = $this->getMyConsultation()->getAmendment(intval($this->comment));
            if ($movedTo) {
                return [
                    'amendment'    => $this->getAmendment(),
                    'modification' => $movedTo,
                ];
            }
        }

        return null;
    }
}
