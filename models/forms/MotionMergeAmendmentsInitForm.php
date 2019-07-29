<?php

namespace app\models\forms;

use app\models\db\Amendment;
use app\models\db\Motion;
use app\models\db\MotionSection;
use app\models\sectionTypes\ISectionType;

class MotionMergeAmendmentsInitForm
{
    /** @var Motion */
    public $motion;

    private $toMergeMainIds;
    private $toMergeResolvedIds;

    public $amendmentVersions;
    public $amendmentStatuses;

    public $resumeDraft = null;
    public $draftData = null;

    /**
     * @param Motion $motion
     * @param array $postAmendIds
     * @param array $textVersions
     *
     * @return MotionMergeAmendmentsInitForm
     */
    public static function fromInitForm(Motion $motion, $postAmendIds, $textVersions)
    {
        $form                     = new MotionMergeAmendmentsInitForm();
        $form->motion             = $motion;
        $form->toMergeMainIds     = [];
        $form->toMergeResolvedIds = [];
        $form->amendmentVersions  = [];
        $form->amendmentStatuses  = [];
        foreach ($motion->getVisibleAmendments() as $amendment) {
            if (isset($postAmendIds[$amendment->id])) {
                $form->toMergeMainIds[] = $amendment->id;
            }

            if ($amendment->hasAlternativeProposaltext(false) && isset($textVersions[$amendment->id]) && $textVersions[$amendment->id] === 'proposal') {
                $form->amendmentVersions[$amendment->id] = 'prop';
                if (isset($postAmendIds[$amendment->id])) {
                    $form->toMergeResolvedIds[] = $amendment->proposalReference->id;
                }
            } else {
                $form->amendmentVersions[$amendment->id] = 'orig';
                if (isset($postAmendIds[$amendment->id])) {
                    $form->toMergeResolvedIds[] = $amendment->id;
                }
            }

            $form->amendmentStatuses[$amendment->id] = $amendment->status;
        }

        return $form;
    }

    public static function initFromDraft(Motion $motion, Motion $draft)
    {
        $form                     = new MotionMergeAmendmentsInitForm();
        $form->motion             = $motion;
        $form->draftData          = json_decode($draft->sections[0]->dataRaw, true);
        $form->toMergeMainIds     = [];
        $form->toMergeResolvedIds = [];
        $form->amendmentVersions  = $form->draftData['amendmentVersions'];
        $form->amendmentStatuses  = $form->draftData['amendmentStatuses'];

        return $form;
    }

    /**
     * @param MotionSection $section
     *
     * @return MotionSection
     */
    public function getRegularSection(MotionSection $section)
    {
        if ($this->draftData && $section->getSettings()->type === ISectionType::TYPE_TITLE) {
            $clone = new MotionSection();
            $clone->setAttributes($section->getAttributes(), false);
            $clone->data    = $this->draftData['sections'][$section->sectionId];
            $clone->dataRaw = $this->draftData['sections'][$section->sectionId];

            return $clone;
        } else {
            return $section;
        }
    }

    /**
     * @param MotionSection $section
     * @param int $paragraphNo
     *
     * @return \app\components\diff\amendmentMerger\ParagraphMerger
     */
    public function getMergerForParagraph(MotionSection $section, $paragraphNo)
    {
        if ($this->draftData) {
            $paragraphData = $this->draftData['paragraphs'][$section->sectionId . '_' . $paragraphNo];

            return $section->getAmendmentDiffMerger($paragraphData['amendmentToggles'])->getParagraphMerger($paragraphNo);
        } else {
            return $section->getAmendmentDiffMerger($this->toMergeResolvedIds)->getParagraphMerger($paragraphNo);
        }
    }

    public function getAllAmendmentIdsAffectingParagraph(MotionSection $section, $paragraphNo)
    {
        return $section->getAmendmentDiffMerger(null)->getAffectingAmendmentIds($paragraphNo);
    }

    /**
     * @param $allAmendingIds
     * @param $amendmentsById
     * @param $paragraphNo
     *
     * @return array
     */
    public function getAffectingAmendmentsForParagraph($allAmendingIds, $amendmentsById, $paragraphNo)
    {
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
            // ModUs that modify a paragraph unaffected by the original amendment
            $normalAmendments[$amendment->proposalReferencedBy->id] = $amendment->proposalReferencedBy;
        }
        if (count($normalAmendments) > 0) {
            $normalAmendments = array_values($normalAmendments);
            $normalAmendments = \app\components\MotionSorter::getSortedAmendments($normalAmendments[0]->getMyConsultation(), $normalAmendments);
        }

        return [$normalAmendments, $modUs];
    }

    /**
     * @param MotionSection $section
     * @param int $paragraphNo
     *
     * @return array
     */
    public function getParagraphTextCollisions(MotionSection $section, $paragraphNo)
    {
        $paragraphMerger = $this->getMergerForParagraph($section, $paragraphNo);

        return $paragraphMerger->getCollidingParagraphGroups();
    }

    /**
     * @param MotionSection $section
     * @param int $paragraphNo
     * @param Amendment[] $amendmentsById
     *
     * @return string
     */
    public function getParagraphText(MotionSection $section, $paragraphNo, $amendmentsById)
    {
        if ($this->draftData) {
            return $this->draftData['paragraphs'][$section->sectionId . '_' . $paragraphNo]['text'];
        } else {
            $paragraphMerger = $this->getMergerForParagraph($section, $paragraphNo);

            return $paragraphMerger->getFormattedDiffText($amendmentsById);
        }
    }

    /**
     * @param int $amendmentId
     * @param MotionSection $section
     * @param int $paragraphNo
     *
     * @return bool
     */
    public function isAmendmentActiveForParagraph($amendmentId, MotionSection $section, $paragraphNo)
    {
        if ($this->draftData) {
            return in_array($amendmentId, $this->draftData['paragraphs'][$section->sectionId . '_' . $paragraphNo]['amendmentToggles']);
        } else {
            return in_array($amendmentId, $this->toMergeMainIds);
        }
    }
}
