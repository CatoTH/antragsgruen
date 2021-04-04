<?php

namespace app\models\mergeAmendments;

use app\components\diff\amendmentMerger\ParagraphDiff;
use app\components\UrlHelper;
use app\components\diff\amendmentMerger\ParagraphMerger;
use app\models\db\{Amendment, Motion, MotionSection};
use app\models\sectionTypes\ISectionType;

class Init
{
    const TEXT_VERSION_ORIGINAL = 'orig';
    const TEXT_VERSION_PROPOSAL = 'prop';

    /** @var Motion */
    public $motion;

    private $toMergeMainIds;
    private $toMergeResolvedIds;

    /** @var Draft */
    public $draftData;

    public static function fromInitForm(Motion $motion, array $postAmendIds, array $textVersions): Init
    {
        $form                     = new Init();
        $form->motion             = $motion;
        $form->toMergeMainIds     = [];
        $form->toMergeResolvedIds = [];
        foreach ($motion->getVisibleAmendments() as $amendment) {
            if (isset($postAmendIds[$amendment->id])) {
                $form->toMergeMainIds[] = $amendment->id;
            }

            if ($amendment->hasAlternativeProposaltext(false) && isset($textVersions[$amendment->id]) &&
                $textVersions[$amendment->id] === static::TEXT_VERSION_PROPOSAL) {
                if (isset($postAmendIds[$amendment->id])) {
                    $form->toMergeResolvedIds[] = $amendment->getMyProposalReference()->id;
                }
            } else {
                if (isset($postAmendIds[$amendment->id])) {
                    $form->toMergeResolvedIds[] = $amendment->id;
                }
            }
        }

        $form->draftData = Draft::initFromForm($form, $textVersions);

        return $form;
    }

    public static function initFromDraft(Motion $motion, Draft $draft)
    {
        $form                     = new Init();
        $form->motion             = $motion;
        $form->draftData          = $draft;
        $form->toMergeMainIds     = [];
        $form->toMergeResolvedIds = [];

        // If a new amendment was created after the draft was created,
        // the status and text version arrays are missing the data about this new amendment.
        // Thus we add it here. The amendmentToggles attributes of the paragraphs stay as they are,
        //  as the new amendment is not embedded into the text automatically.
        $unchangedDraft = static::fromInitForm($motion, [], []);
        foreach ($unchangedDraft->draftData->amendmentStatuses as $amendmentId => $amendmentStatus) {
            if (!isset($form->draftData->amendmentStatuses[$amendmentId])) {
                $form->draftData->amendmentStatuses[$amendmentId] = $amendmentStatus;
            }
        }
        foreach ($unchangedDraft->draftData->amendmentVersions as $amendmentId => $amendmentVersion) {
            if (!isset($form->draftData->amendmentVersions[$amendmentId])) {
                $form->draftData->amendmentVersions[$amendmentId] = $amendmentVersion;
            }
        }
        foreach ($unchangedDraft->draftData->amendmentVotingData as $amendmentId => $amendmentVotingData) {
            if (!isset($form->draftData->amendmentVotingData[$amendmentId])) {
                $amendment = $motion->getMyConsultation()->getAmendment($amendmentId);
                $form->draftData->amendmentVotingData[$amendmentId] = $amendment->getVotingData();
            }
        }

        return $form;
    }

    public function resolveAmendmentToProposalId(int $amendmentId): ?int
    {
        foreach ($this->motion->getVisibleAmendments() as $amendment) {
            if ($amendment->id === $amendmentId && $amendment->getMyProposalReference()) {
                return $amendment->getMyProposalReference()->id;
            }
        }

        return null;
    }

    public function getRegularSection(MotionSection $section): MotionSection
    {
        if ($this->draftData && isset($this->draftData->sections[$section->sectionId]) && $section->getSettings()->type === ISectionType::TYPE_TITLE) {
            $clone = new MotionSection();
            $clone->setAttributes($section->getAttributes(), false);
            $clone->setData($this->draftData->sections[$section->sectionId]);
            $clone->dataRaw = $this->draftData->sections[$section->sectionId];

            return $clone;
        } else {
            return $section;
        }
    }

    public function getMergerForParagraph(MotionSection $section, int $paragraphNo): ParagraphMerger
    {
        if ($this->draftData) {
            $paragraphData = $this->draftData->paragraphs[$section->sectionId . '_' . $paragraphNo];
            $amendmentIds  = $paragraphData->getActiveResolvedAmendmentIds($this->motion);

            return $section->getAmendmentDiffMerger($amendmentIds)->getParagraphMerger($paragraphNo);
        } else {
            return $section->getAmendmentDiffMerger($this->toMergeResolvedIds)->getParagraphMerger($paragraphNo);
        }
    }

    public function getAllAmendmentIdsAffectingParagraph(MotionSection $section, $paragraphNo, ?array $onlyAmendments = null)
    {
        return $section->getAmendmentDiffMerger($onlyAmendments)->getAffectingAmendmentIds($paragraphNo);
    }

    public function getAffectingAmendments(array $allAmendingIds, array $amendmentsById): array
    {
        // Must match merging.php: $amendments = $motion->getVisibleAmendmentsSorted();
        $hiddenStatuses = $this->motion->getMyConsultation()->getInvisibleAmendmentStatuses(true);

        /** @var Amendment[] $modUs */
        $modUs = [];
        /** @var Amendment[] $normalAmendments */
        $normalAmendments = [];
        foreach ($allAmendingIds as $amendingId) {
            $amendment = $amendmentsById[$amendingId];
            if ($amendment->status === Amendment::STATUS_PROPOSED_MODIFIED_AMENDMENT) {
                $modUs[$amendment->id] = $amendment;
            } else {
                $normalAmendments[$amendment->id] = $amendment;
            }
        }
        foreach ($modUs as $amendment) {
            // ModUs that modify a paragraph unaffected by the original amendment.
            // We need to check that the original amendment is not deleted though.
            if ($amendment->proposalReferencedBy && !in_array($amendment->proposalReferencedBy->status, $hiddenStatuses)) {
                $normalAmendments[$amendment->proposalReferencedBy->id] = $amendment->proposalReferencedBy;
            }
        }
        if (count($normalAmendments) > 0) {
            $normalAmendments = array_values($normalAmendments);
            $normalAmendments = \app\components\MotionSorter::getSortedAmendments($normalAmendments[0]->getMyConsultation(), $normalAmendments);
        }

        return [$normalAmendments, $modUs];
    }

    public function getParagraphTextCollisions(MotionSection $section, int $paragraphNo): array
    {
        $paragraphMerger = $this->getMergerForParagraph($section, $paragraphNo);

        return $paragraphMerger->getCollidingParagraphGroups();
    }

    public function getParagraphCollidingAmendments(MotionSection $section, int $paragraphNo): array
    {
        $paragraphMerger = $this->getMergerForParagraph($section, $paragraphNo);

        return $paragraphMerger->getCollidingAmendments();
    }

    /**
     * @param MotionSection $section
     * @param int $paragraphNo
     * @param Amendment[] $amendmentsById
     *
     * @return string
     */
    public function getParagraphText(MotionSection $section, int $paragraphNo, $amendmentsById): string
    {
        if ($this->draftData) {
            return $this->draftData->paragraphs[$section->sectionId . '_' . $paragraphNo]->text;
        } else {
            $paragraphMerger = $this->getMergerForParagraph($section, $paragraphNo);

            return $paragraphMerger->getFormattedDiffText($amendmentsById);
        }
    }

    public function isAmendmentActiveForParagraph(int $amendmentId, MotionSection $section, int $paragraphNo): bool
    {
        if ($this->draftData) {
            return in_array($amendmentId, $this->draftData->paragraphs[$section->sectionId . '_' . $paragraphNo]->amendmentToggles);
        } else {
            return in_array($amendmentId, $this->toMergeMainIds);
        }
    }

    public static function getJsAmendmentStaticData(Amendment $amendment): array
    {
        $statusesAllNames = Amendment::getStatusNames();

        return [
            'id'            => $amendment->id,
            'titlePrefix'   => $amendment->titlePrefix,
            'bookmarkName'  => \app\models\layoutHooks\Layout::getAmendmentBookmarkName($amendment),
            'url'           => UrlHelper::createAmendmentUrl($amendment),
            'oldStatusId'   => $amendment->status,
            'oldStatusName' => $statusesAllNames[$amendment->status],
            'hasProposal'   => ($amendment->getMyProposalReference() !== null),
        ];
    }

    public function getJsParagraphStatusData(MotionSection $section, int $paragraphNo, array $amendmentsById): array
    {
        $allAmendingIds = $this->getAllAmendmentIdsAffectingParagraph($section, $paragraphNo, array_keys($amendmentsById));
        /** @noinspection PhpUnusedLocalVariableInspection */
        list($normalAmendments, $modUs) = $this->getAffectingAmendments($allAmendingIds, $amendmentsById);
        $type = $section->getSettings();

        $vueData = [];
        foreach ($normalAmendments as $amendment) {
            /** @var Amendment $amendment */
            $vueData[] = [
                'amendmentId' => $amendment->id,
                'nameBase'    => 'sections[' . $type->id . '][' . $paragraphNo . ']',
                'idAdd'       => $type->id . '_' . $paragraphNo . '_' . $amendment->id,
                'active'      => $this->isAmendmentActiveForParagraph($amendment->id, $section, $paragraphNo),
            ];
        }

        return $vueData;
    }

    /**
     * @return ParagraphDiff[]
     */
    public function getParagraphDiffs(MotionSection $section, int $paragraphNo, array $amendmentsById): array
    {
        return $section->getAmendmentDiffMerger(array_keys($amendmentsById))->getAllParagraphDiffs($paragraphNo);
    }
}
