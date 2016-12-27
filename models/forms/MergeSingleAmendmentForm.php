<?php

namespace app\models\forms;

use app\models\db\Amendment;
use app\models\db\Motion;
use app\models\sectionTypes\ISectionType;
use yii\base\Model;

class MergeSingleAmendmentForm extends Model
{
    /** @var Motion */
    public $motion;

    /** @var Amendment */
    public $mergeAmendment;

    /** @var int */
    public $mergeAmendStatus;

    /** @var array */
    public $otherAmendStati;
    public $otherAmendOverrides;
    public $paragraphs;

    /**
     * @param Amendment $amendment
     * @param int $newStatus
     * @param array $paragraphs
     * @param array $otherAmendOverrides
     * @param array $otherAmendStati
     */
    public function __construct(Amendment $amendment, $newStatus, $paragraphs, $otherAmendOverrides, $otherAmendStati)
    {
        parent::__construct();
        $this->motion              = $amendment->getMyMotion();
        $this->mergeAmendment      = $amendment;
        $this->mergeAmendStatus    = $newStatus;
        $this->paragraphs          = $paragraphs;
        $this->otherAmendStati     = $otherAmendStati;
        $this->otherAmendOverrides = $otherAmendOverrides;
    }

    /**
     * @return array
     */
    private function getNewHtmlParas()
    {
        $newSections = [];
        foreach ($this->mergeAmendment->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
            $amendmentParas = $section->getParagraphsRelativeToOriginal();
            if (isset($this->paragraphs[$section->sectionId])) {
                foreach ($this->paragraphs[$section->sectionId] as $paraNo => $para) {
                    $amendmentParas[$paraNo] = $para['modified'];
                }
            }
            $newSections[$section->sectionId] = implode("\n", $amendmentParas);
        }
        return $newSections;
    }

    /**
     * @return bool
     */
    public function checkConsistency()
    {
        $newSections = $this->getNewHtmlParas();
        $overrides   = $this->otherAmendOverrides;

        foreach ($this->mergeAmendment->getMyMotion()->getAmendmentsRelevantForCollissionDetection() as $amendment) {
            if ($this->mergeAmendment->id == $amendment->id) {
                continue;
            }
            foreach ($amendment->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
                if (isset($overrides[$amendment->id]) && isset($overrides[$amendment->id][$section->sectionId])) {
                    $sectionOverrides = $overrides[$amendment->id][$section->sectionId];
                } else {
                    $sectionOverrides = [];
                }
                if (!$section->canRewrite($newSections[$section->sectionId], $sectionOverrides)) {
                    var_dump($section->data);
                    echo "\n\n";
                    var_dump($newSections[$section->sectionId]);
                    echo "\n\n";


                    return false;
                }
            }
        }

        return true;
    }
}