<?php

namespace app\models\forms;

use app\models\db\Amendment;
use app\models\db\AmendmentSection;

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
        if ($this->amendment->proposalStatus == Amendment::STATUS_MODIFIED_ACCEPTED) {
            if ($this->amendment->proposalReferencedBy) {
                if ($this->amendment->proposalReference->status == Amendment::STATUS_PROPOSED_MODIFIED_AMENDMENT) {
                    $this->proposalSections = $this->amendment->proposalReference->getActiveSections();
                    return;
                }
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
     */
    public function save($postParams)
    {
        var_dump($postParams);
        die();
    }
}
