<?php

namespace app\models\mergeAmendments;

use app\models\db\IMotion;
use app\models\db\Motion;
use app\models\db\MotionSection;
use app\models\settings\VotingData;

class Draft implements \JsonSerializable
{
    /** @var Motion */
    public $origMotion;
    /** @var Motion */
    public $draftMotion;

    /** @var boolean */
    public $public;

    /** @var \DateTime|null */
    public $time;

    /** @var int[] */
    public $amendmentStatuses;

    /** @var string[] */
    public $amendmentVersions;

    /** @var VotingData[] */
    public $amendmentVotingData;

    /** @var DraftParagraph[] */
    public $paragraphs;

    /** @var string[] */
    public $sections;

    public function jsonSerialize()
    {
        return [
            'amendmentStatuses'   => $this->amendmentStatuses,
            'amendmentVersions'   => $this->amendmentVersions,
            'amendmentVotingData' => $this->amendmentVotingData,
            'paragraphs'          => $this->paragraphs,
            'sections'            => $this->sections,
        ];
    }


    private function init($origMotion)
    {
        $this->origMotion  = $origMotion;
        $draftStatuses     = [Motion::STATUS_MERGING_DRAFT_PUBLIC, Motion::STATUS_MERGING_DRAFT_PRIVATE];
        $this->draftMotion = Motion::find()
                                   ->where(['parentMotionId' => $origMotion->id])
                                   ->andWhere(['status' => $draftStatuses])->one();
        if ($this->draftMotion) {
            $this->draftMotion->dateCreation = date('Y-m-d H:i:s');
        } else {
            $this->draftMotion = new Motion();
            $this->draftMotion->setAttributes($this->origMotion->getAttributes(), false);
            $this->draftMotion->id             = null;
            $this->draftMotion->dateCreation   = date('Y-m-d H:i:s');
            $this->draftMotion->status         = Motion::STATUS_MERGING_DRAFT_PRIVATE;
            $this->draftMotion->titlePrefix    = '';
            $this->draftMotion->parentMotionId = $this->origMotion->id;
            $this->draftMotion->slug           = null;
        }
    }


    /**
     * @param Motion $origMotion
     * @param boolean $public
     * @param \DateTime|null $time
     * @param string $data
     *
     * @return Draft
     */
    public static function initFromJson(Motion $origMotion, $public, $time, $data)
    {
        $draft = new Draft();
        $draft->init($origMotion);

        $json                       = json_decode($data, true);
        $draft->sections            = $json['sections'];
        $draft->paragraphs          = DraftParagraph::fromJsonArr($json['paragraphs']);
        $draft->amendmentVersions   = $json['amendmentVersions'];
        $draft->amendmentStatuses   = $json['amendmentStatuses'];
        $draft->amendmentVotingData = array_map(function ($data) {
            return new VotingData($data);
        }, $json['amendmentVotingData']);
        $draft->public              = $public;
        $draft->time                = $time;

        return $draft;
    }

    /**
     * @param Init $form
     * @param array $textVersions
     *
     * @return Draft
     */
    public static function initFromForm(Init $form, $textVersions)
    {
        $draft = new Draft();
        $draft->init($form->motion);

        $draft->sections = []; // Empty = default values
        $draft->amendmentStatuses = [];
        $draft->amendmentVersions = [];
        $draft->amendmentVotingData = [];

        $draft->paragraphs = [];
        foreach ($form->motion->getSortedSections(false) as $section) {
            if ($section->getSettings()->type !== \app\models\sectionTypes\ISectionType::TYPE_TEXT_SIMPLE) {
                continue;
            }

            $amendmentsById = [];
            foreach ($section->getAmendingSections(true, false, true) as $sect) {
                $amendmentsById[$sect->amendmentId] = $sect->getAmendment();
            }


            $paragraphs = $section->getTextParagraphObjects(false, false, false);
            foreach (array_keys($paragraphs) as $paragraphNo) {
                $allAmendingIds = $form->getAllAmendmentIdsAffectingParagraph($section, $paragraphNo);
                $paragraphText  = $form->getParagraphText($section, $paragraphNo, $amendmentsById);
                list($normalAmendments, $modUs) = $form->getAffectingAmendmentsForParagraph($allAmendingIds, $amendmentsById, $paragraphNo);

                $draftPara                   = new DraftParagraph();
                $draftPara->unchanged        = null;
                $draftPara->text             = $paragraphText;
                $draftPara->amendmentToggles = [];
                foreach ($normalAmendments as $amendment) {
                    if ($form->isAmendmentActiveForParagraph($amendment->id, $section, $paragraphNo)) {
                        $draftPara->amendmentToggles[] = $amendment->id;
                    }
                }

                $draft->paragraphs[$section->sectionId . '_' . $paragraphNo] = $draftPara;
            }
        }

        foreach ($form->motion->getVisibleAmendments() as $amendment) {
            $draft->amendmentStatuses[$amendment->id] = $amendment->status;

            if ($amendment->hasAlternativeProposaltext(false) && isset($textVersions[$amendment->id]) && $textVersions[$amendment->id] === 'proposal') {
                $draft->amendmentVersions[$amendment->id] = 'prop';
            } else {
                $draft->amendmentVersions[$amendment->id] = 'orig';
            }

            $draft->amendmentVotingData[$amendment->id] = $amendment->getVotingData();
        }

        return $draft;
    }

    /**
     */
    public function save()
    {
        if ($this->public) {
            $this->draftMotion->status = Motion::STATUS_MERGING_DRAFT_PUBLIC;
        } else {
            $this->draftMotion->status = Motion::STATUS_MERGING_DRAFT_PRIVATE;
        }
        $this->draftMotion->save();

        $section = null;
        foreach ($this->draftMotion->sections as $existingSection) {
            $section = $existingSection;
        }
        if (!$section) {
            $section = new MotionSection();
            $section->setAttributes($this->origMotion->sections[0]->getAttributes(), false);
            $section->motionId = $this->draftMotion->id;
        }
        $section->dataRaw = json_encode($this);
        $section->data    = '';
        $section->save();

        foreach ($this->draftMotion->sections as $oldSection) {
            if ($oldSection->sectionId !== $section->sectionId) {
                try {
                    $oldSection->delete();
                } catch (\Throwable $e) {
                    var_dump($e);
                    die();
                }
            }
        }
    }

    public function delete()
    {
        $this->draftMotion->status = IMotion::STATUS_DELETED;
        $this->draftMotion->save();
    }
}
