<?php

namespace app\models\forms;

use app\models\db\Amendment;
use app\models\db\AmendmentSection;
use app\models\sectionTypes\ISectionType;

class AmendmentProposedChangeForm
{
    /** @var Amendment */
    protected $amendment;

    /** @var AmendmentSection[] */
    protected $proposalSections;


    /**
     * AmendmentProposedChangeForm constructor.
     * @param Amendment $amendment
     */
    public function __construct(Amendment $amendment)
    {
        $this->amendment = $amendment;
        $this->initProposal();
    }

    /**
     */
    protected function initProposal()
    {
        if ($this->amendment->getMyProposalReference()) {
            if ($this->amendment->getMyProposalReference()->status === Amendment::STATUS_PROPOSED_MODIFIED_AMENDMENT) {
                $this->proposalSections = $this->amendment->getMyProposalReference()->getActiveSections();
                return;
            }
        }
        $this->proposalSections = [];
        foreach ($this->amendment->sections as $section) {
            $newSection = new AmendmentSection();
            $newSection->setAttributes($section->getAttributes(), false);
            $newSection->setOriginalMotionSection($section->getOriginalMotionSection());
            $newSection->amendmentId  = null;
            $this->proposalSections[] = $newSection;
        }
    }

    /**
     * @return AmendmentSection[]
     */
    public function getProposalSections()
    {
        return $this->proposalSections;
    }

    /**
     * @param array $postParams
     * @param array $files
     * @throws \app\models\exceptions\FormError
     */
    private function setSectionData($postParams, $files)
    {
        foreach ($this->proposalSections as $section) {
            if (isset($postParams['sections'][$section->getSettings()->id])) {
                $type = $section->getSectionType();
                if ($section->getSettings()->type === ISectionType::TYPE_TEXT_SIMPLE) {
                    /** @var \app\models\sectionTypes\TextSimple $type */
                    $type->forceMultipleParagraphMode(true);
                }
                $type->setAmendmentData($postParams['sections'][$section->getSettings()->id]);
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

    /**
     * @return Amendment
     */
    private function getProposalAmendmentObject()
    {
        $reference = $this->amendment->getMyProposalReference();
        if ($reference) {
            if ($reference->status === Amendment::STATUS_PROPOSED_MODIFIED_AMENDMENT) {
                return $reference;
            }
        }
        $reference                    = new Amendment();
        $reference->status            = Amendment::STATUS_PROPOSED_MODIFIED_AMENDMENT;
        $reference->motionId          = $this->amendment->motionId;
        $reference->dateCreation      = date('Y-m-d H:i:s');
        $reference->changeEditorial   = '';
        $reference->changeText        = '';
        $reference->changeExplanation = '';
        $reference->cache             = '';
        $reference->statusString      = '';
        return $reference;
    }

    /**
     * @param array $postParams
     * @param array $files
     */
    public function save($postParams, $files)
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
        $this->amendment->proposalReferenceId = $propAmend->id;
        $this->amendment->proposalStatus      = Amendment::STATUS_MODIFIED_ACCEPTED;
        $this->amendment->save();
    }
}
