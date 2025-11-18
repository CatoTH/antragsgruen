<?php

declare(strict_types=1);

namespace app\models\db;

use app\components\UrlHelper;
use app\models\proposedProcedure\Agenda;
use app\models\settings\AntragsgruenApp;
use app\components\diff\AmendmentCollissionDetector;
use app\models\exceptions\Internal;
use app\models\sectionTypes\ISectionType;

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

    public function flushViewCaches(): void
    {
        $amendment = $this->getAmendment();
        if ($amendment?->getMyMotion()) {
            $amendment->getMyMotion()->flushViewCache();
            Agenda::getProposedAmendmentProcedureCache($amendment, $this)->flushCache();
        }
    }

    /**
     * @param bool $runValidation
     * @param null $attributeNames
     *
     * @return bool
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        $result = parent::save($runValidation, $attributeNames);
        $this->flushViewCaches();
        return $result;
    }

    public function getAmendment(): ?Amendment
    {
        return $this->getCachedConsultation()->getAmendment($this->amendmentId);
    }

    public static function createNew(Amendment $amendment, int $version): AmendmentProposal
    {
        $amendment->id !== null ?: throw new Internal("Amendment not initialized");
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

        $checkAgainstAmendments = AmendmentCollissionDetector::getHeuristicallyRelevantAmendments($myAmendment->getMyMotion(), [$myAmendment->id]);
        if ($myAmendment->globalAlternative) {
            // Global Alternatives are assumed to always collide with everything
            return $checkAgainstAmendments;
        }

        return AmendmentCollissionDetector::getAmendmentsCollidingWithSections($checkAgainstAmendments, $newSections);
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

    /**
     * @return array{title: string, url: string}
     */
    public static function getAmendmentTitleUrlConsideringProposals(Amendment $amendment): array
    {
        if ($amendment->status === Amendment::STATUS_PROPOSED_MODIFIED_MOTION) {
            $proposal = $amendment->proposalReferencedByMotion;
            /** @var Motion $originalMotion */
            $originalMotion = $proposal->getMyIMotion();
            $versionTitle = str_replace('%VERSION%', ((string) $proposal->version), \Yii::t('amend', 'proposal_version_x_long'));
            $title = ($originalMotion->getFormattedTitlePrefix() ?? '') . ' (' . $versionTitle . ')';
            $url   = UrlHelper::createMotionUrl($originalMotion);
        } elseif ($amendment->status === Amendment::STATUS_PROPOSED_MODIFIED_AMENDMENT) {
            $proposal = $amendment->proposalReferencedByAmendment;
            /** @var Amendment $originalAmendment */
            $originalAmendment = $proposal->getMyIMotion();
            $versionTitle = str_replace('%VERSION%', ((string) $proposal->version), \Yii::t('amend', 'proposal_version_x_long'));
            $title = $originalAmendment->getShortTitle() . ' (' . $versionTitle . ')';
            $url = UrlHelper::createAmendmentUrl($originalAmendment);
        } else {
            $title = $amendment->getShortTitle();
            $url = UrlHelper::createAmendmentUrl($amendment);
        }

        return [
            'title' => $title,
            'url' => $url,
        ];
    }

    public function setPublished(): void
    {
        if ($this->visibleFrom) {
            return;
        }
        $this->visibleFrom = date('Y-m-d H:i:s');
        $this->save();

        $consultation = $this->getMyConsultation();
        ConsultationLog::logCurrUser($consultation, ConsultationLog::AMENDMENT_PUBLISH_PROPOSAL, $this->id);
    }
}
