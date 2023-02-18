<?php

namespace app\models\forms;

use app\models\db\{Amendment, AmendmentSection, IMotion, Motion, MotionSection};
use app\models\sectionTypes\ISectionType;

class ProposedChangeForm
{
    protected IMotion $imotion;

    /** @var AmendmentSection[] */
    protected array $proposalSections;

    public function __construct(IMotion $imotion)
    {
        $this->imotion = $imotion;
        $this->initProposal();
    }

    protected function initProposal(): void
    {
        if ($this->imotion->getMyProposalReference() && in_array($this->imotion->getMyProposalReference()->status, [
            Amendment::STATUS_PROPOSED_MODIFIED_AMENDMENT,
            Motion::STATUS_PROPOSED_MODIFIED_MOTION,
        ])) {
            $this->proposalSections = $this->imotion->getMyProposalReference()->getActiveSections();
            return;
        }
        $this->proposalSections = [];
        foreach ($this->imotion->sections as $section) {
            if (is_a($section, MotionSection::class)) {
                $originalSection = $section;
            } else {
                /** @var AmendmentSection $section */
                $originalSection = $section->getOriginalMotionSection();
            }

            $newSection = new AmendmentSection();
            $newSection->setAttributes($section->getAttributes(), false);
            $newSection->setOriginalMotionSection($originalSection);
            $newSection->amendmentId  = null;
            $this->proposalSections[] = $newSection;
        }
    }

    /**
     * @return AmendmentSection[]
     */
    public function getProposalSections(): array
    {
        return $this->proposalSections;
    }

    /**
     * @throws \app\models\exceptions\FormError
     */
    private function setSectionData(array $postParams, array $files): void
    {
        foreach ($this->proposalSections as $section) {
            if (isset($postParams['sections'][$section->getSettings()->id])) {
                $type = $section->getSectionType();
                if ($section->getSettings()->type === ISectionType::TYPE_TEXT_SIMPLE) {
                    /** @var \app\models\sectionTypes\TextSimple $type */
                    $type->forceMultipleParagraphMode(true);
                }
                if (!$type->isFileUploadType()) {
                    $type->setAmendmentData($postParams['sections'][$section->getSettings()->id]);
                }
            }
            if (isset($files['sections']) && isset($files['sections']['tmp_name'])) {
                if (!empty($files['sections']['tmp_name'][$section->getSettings()->id])) {
                    $data = [];
                    foreach ($files['sections'] as $key => $vals) {
                        if (isset($vals[$section->getSettings()->id])) {
                            $data[$key] = $vals[$section->getSettings()->id];
                        }
                    }
                    $section->getSectionType()->setAmendmentData($data);
                }
            }
        }
    }

    private function getProposalAmendmentObject(): Amendment
    {
        $reference = $this->imotion->getMyProposalReference();
        if ($reference && in_array($reference->status, [
            Amendment::STATUS_PROPOSED_MODIFIED_AMENDMENT,
            Motion::STATUS_PROPOSED_MODIFIED_MOTION,
        ])) {
            return $reference;
        }

        $reference = new Amendment();
        if (is_a($this->imotion, Motion::class)) {
            $reference->status = Amendment::STATUS_PROPOSED_MODIFIED_MOTION;
            $motionId = $this->imotion->id;
        } else {
            $reference->status = Amendment::STATUS_PROPOSED_MODIFIED_AMENDMENT;
            /** @var Amendment $amendment */
            $amendment = $this->imotion;
            $motionId = $amendment->motionId;
        }

        $reference->motionId = $motionId;
        $reference->dateCreation = date('Y-m-d H:i:s');
        $reference->changeEditorial = '';
        $reference->changeText = '';
        $reference->changeExplanation = '';
        $reference->cache = '';
        $reference->statusString = '';
        $reference->dateContentModification = date('Y-m-d H:i:s');

        return $reference;
    }

    public function save(array $postParams, array $files): void
    {
        $this->setSectionData($postParams, $files);
        $propAmend = $this->getProposalAmendmentObject();
        if (!$propAmend->save()) {
            var_dump($propAmend->getErrors());
            die();
        }
        foreach ($this->proposalSections as $section) {
            $section->amendmentId = $propAmend->id;
            if (!$section->save()) {
                var_dump($section->getErrors());
                die();
            }
        }
        $this->imotion->proposalReferenceId = $propAmend->id;
        $this->imotion->proposalStatus = Amendment::STATUS_MODIFIED_ACCEPTED;
        $this->imotion->save();
    }
}
