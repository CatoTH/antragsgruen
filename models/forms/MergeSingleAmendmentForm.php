<?php

namespace app\models\forms;

use app\models\db\Amendment;
use app\models\db\Motion;
use app\models\db\MotionSection;
use app\models\db\MotionSupporter;
use app\models\exceptions\DB;
use app\models\sectionTypes\ISectionType;
use yii\base\Model;

class MergeSingleAmendmentForm extends Model
{
    /** @var Motion */
    public $oldMotion;
    /** @var null|Motion */
    public $newMotion = null;

    /** @var string */
    public $newTitlePrefix;

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
     * @param int $newTitlePrefix
     * @param array $paragraphs
     * @param array $otherAmendOverrides
     * @param array $otherAmendStati
     */
    public function __construct(
        Amendment $amendment,
        $newTitlePrefix,
        $newStatus,
        $paragraphs,
        $otherAmendOverrides,
        $otherAmendStati
    ) {
        parent::__construct();
        $this->newTitlePrefix      = $newTitlePrefix;
        $this->oldMotion           = $amendment->getMyMotion();
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
                    $amendmentParas[$paraNo] = $para;
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

        foreach ($this->oldMotion->getAmendmentsRelevantForCollissionDetection([$this->mergeAmendment]) as $amendment) {
            if (!isset($this->otherAmendStati[$amendment->id])) {
                continue;
            }
            if (in_array($this->otherAmendStati[$amendment->id], Amendment::getStatiMarkAsDoneOnRewriting())) {
                continue;
            }
            foreach ($amendment->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
                if (isset($overrides[$amendment->id]) && isset($overrides[$amendment->id][$section->sectionId])) {
                    $sectionOverrides = $overrides[$amendment->id][$section->sectionId];
                } else {
                    $sectionOverrides = [];
                }
                if (!$section->canRewrite($newSections[$section->sectionId], $sectionOverrides)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     */
    private function createNewMotion()
    {
        $this->newMotion                  = new Motion();
        $this->newMotion->consultationId  = $this->oldMotion->consultationId;
        $this->newMotion->motionTypeId    = $this->oldMotion->motionTypeId;
        $this->newMotion->parentMotionId  = $this->oldMotion->id;
        $this->newMotion->agendaItemId    = $this->oldMotion->agendaItemId;
        $this->newMotion->title           = $this->oldMotion->title;
        $this->newMotion->titlePrefix     = $this->newTitlePrefix;
        $this->newMotion->dateCreation    = date('Y-m-d H:i:s');
        $this->newMotion->datePublication = date('Y-m-d H:i:s');
        $this->newMotion->dateResolution  = $this->oldMotion->dateResolution;
        $this->newMotion->statusString    = $this->oldMotion->statusString;
        $this->newMotion->status          = $this->oldMotion->status;
        $this->newMotion->noteInternal    = $this->oldMotion->noteInternal;
        $this->newMotion->textFixed       = $this->oldMotion->textFixed;
        $this->newMotion->slug            = $this->oldMotion->slug;
        $this->newMotion->cache           = '';
        if (!$this->newMotion->save()) {
            throw new DB($this->newMotion->getErrors());
        }

        foreach ($this->oldMotion->motionSupporters as $supporter) {
            $newSupporter = new MotionSupporter();
            $newSupporter->setAttributes($supporter->getAttributes(), false);
            $newSupporter->id       = null;
            $newSupporter->motionId = $this->newMotion->id;
            if (!$newSupporter->save()) {
                throw new DB($this->newMotion->getErrors());
            }
        }
    }

    /**
     * @throws DB
     */
    private function createNewMotionSections()
    {
        $newSections = $this->getNewHtmlParas();

        foreach ($this->oldMotion->sections as $section) {
            $newSection = new MotionSection();
            $newSection->setAttributes($section->getAttributes(), false);
            $newSection->motionId = $this->newMotion->id;
            $newSection->cache = '';
            if ($section->getSettings()->type == ISectionType::TYPE_TEXT_SIMPLE) {
                if (isset($newSections[$section->sectionId])) {
                    $newSection->data    = $newSections[$section->sectionId];
                    $newSection->dataRaw = '';
                }
            }
            if (!$newSection->save()) {
                throw new DB($newSection->getErrors());
            }
        }
    }

    /**
     * @throws DB
     */
    private function rewriteOtherAmendments()
    {
        $newSections = $this->getNewHtmlParas();
        $overrides   = $this->otherAmendOverrides;

        foreach ($this->oldMotion->getAmendmentsRelevantForCollissionDetection([$this->mergeAmendment]) as $amendment) {
            if (!isset($this->otherAmendStati[$amendment->id])) {
                continue;
            }
            if (in_array($this->otherAmendStati[$amendment->id], Amendment::getStatiMarkAsDoneOnRewriting())) {
                continue;
            }
            foreach ($amendment->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
                if (isset($overrides[$amendment->id]) && isset($overrides[$amendment->id][$section->sectionId])) {
                    $sectionOverrides = $overrides[$amendment->id][$section->sectionId];
                } else {
                    $sectionOverrides = [];
                }
                $section->performRewrite($newSections[$section->sectionId], $sectionOverrides);
                $section->dataRaw = '';
                $section->cache   = '';
                if (!$section->save()) {
                    throw new DB($section->getErrors());
                }
            }
            $amendment->motionId = $this->newMotion->id;
            $amendment->cache    = '';
            $amendment->status   = $this->otherAmendStati[$amendment->id];
            if (!$amendment->save()) {
                throw new DB($amendment->getErrors());
            }
        }
    }

    /**
     */
    private function setDoneAmendmentsStatuses()
    {
        foreach ($this->oldMotion->getAmendmentsRelevantForCollissionDetection([$this->mergeAmendment]) as $amendment) {
            if (!isset($this->otherAmendStati[$amendment->id])) {
                continue;
            }
            if (!in_array($this->otherAmendStati[$amendment->id], Amendment::getStatiMarkAsDoneOnRewriting())) {
                continue;
            }
            $amendment->status = $this->otherAmendStati[$amendment->id];
            if (!$amendment->save()) {
                throw new DB($amendment->getErrors());
            }
        }
    }

    /**
     * @return Motion
     */
    public function performRewrite()
    {
        $this->oldMotion->slug = null;
        $this->oldMotion->save();
        
        $this->createNewMotion();
        $this->createNewMotionSections();
        $this->rewriteOtherAmendments();
        $this->setDoneAmendmentsStatuses();

        $this->mergeAmendment->status = $this->mergeAmendStatus;
        $this->mergeAmendment->save();

        $this->oldMotion->status = Motion::STATUS_MODIFIED;
        $this->oldMotion->save();

        return $this->newMotion;
    }
}
