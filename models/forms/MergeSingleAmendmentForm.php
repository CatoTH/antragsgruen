<?php

namespace app\models\forms;

use app\models\db\{Amendment, ISupporter, Motion, MotionSection, MotionSupporter};
use app\models\events\MotionEvent;
use app\models\exceptions\DB;
use app\models\sectionTypes\ISectionType;

class MergeSingleAmendmentForm
{
    public Motion $oldMotion;
    public ?Motion $newMotion = null;

    public function __construct(
        public Amendment $mergeAmendment,
        public string $newTitlePrefix,
        public string $newVersion,
        public int $mergeAmendStatus,
        public array $paragraphs,
        public array $otherAmendOverrides,
        public array $otherAmendStatuses
    )
    {
        $this->oldMotion = $mergeAmendment->getMyMotion();
    }

    private function getNewHtmlParas(): array
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

    public function checkConsistency(): bool
    {
        $newSections = $this->getNewHtmlParas();
        $overrides   = $this->otherAmendOverrides;

        foreach ($this->oldMotion->getAmendmentsRelevantForCollisionDetection([$this->mergeAmendment]) as $amendment) {
            if (!isset($this->otherAmendStatuses[$amendment->id])) {
                continue;
            }
            if (in_array($this->otherAmendStatuses[$amendment->id], $amendment->getMyConsultation()->getStatuses()->getStatusesMarkAsDoneOnRewriting())) {
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
     * @throws DB
     */
    private function createNewMotion(?string $previousSlug): void
    {
        $this->newMotion = new Motion();
        $this->newMotion->consultationId = $this->oldMotion->consultationId;
        $this->newMotion->motionTypeId = $this->oldMotion->motionTypeId;
        $this->newMotion->parentMotionId = $this->oldMotion->id;
        $this->newMotion->agendaItemId = $this->oldMotion->agendaItemId;
        $this->newMotion->title = $this->oldMotion->title;
        $this->newMotion->titlePrefix = $this->newTitlePrefix;
        $this->newMotion->version = $this->newVersion;
        $this->newMotion->dateCreation = date('Y-m-d H:i:s');
        $this->newMotion->datePublication = date('Y-m-d H:i:s');
        $this->newMotion->dateContentModification = date('Y-m-d H:i:s');
        $this->newMotion->dateResolution = $this->oldMotion->dateResolution;
        $this->newMotion->statusString = $this->oldMotion->statusString;
        $this->newMotion->status = $this->oldMotion->status;
        $this->newMotion->noteInternal = $this->oldMotion->noteInternal;
        $this->newMotion->textFixed = $this->oldMotion->textFixed;
        $this->newMotion->slug = $previousSlug;
        $this->newMotion->cache = '';
        if (!$this->newMotion->save()) {
            throw new DB($this->newMotion->getErrors());
        }

        foreach ($this->oldMotion->motionSupporters as $supporter) {
            $newSupporter = new MotionSupporter();
            $newSupporter->setAttributes($supporter->getAttributes(), false);
            $newSupporter->dateCreation = date('Y-m-d H:i:s');
            $newSupporter->id = null;
            $newSupporter->motionId = $this->newMotion->id;
            if ($supporter->isNonPublic()) {
                $newSupporter->setExtraDataEntry(ISupporter::EXTRA_DATA_FIELD_NON_PUBLIC, true);
            }
            if (!$newSupporter->save()) {
                throw new DB($this->newMotion->getErrors());
            }
        }
    }

    /**
     * @throws DB
     */
    private function createNewMotionSections(): void
    {
        $newSections = $this->getNewHtmlParas();

        foreach ($this->oldMotion->getActiveSections(null, true) as $section) {
            $newSection = new MotionSection();
            $newSection->setAttributes($section->getAttributes(), false);
            $newSection->motionId = $this->newMotion->id;
            $newSection->cache    = '';
            if ($section->getSettings()->type === ISectionType::TYPE_TEXT_SIMPLE) {
                if (isset($newSections[$section->sectionId])) {
                    $newSection->setData($newSections[$section->sectionId]);
                    $newSection->dataRaw = '';
                }
            }
            if ($section->getSettings()->type === ISectionType::TYPE_TITLE && $this->mergeAmendment->getSection($section->sectionId)) {
                $newSection->setData($this->mergeAmendment->getSection($section->sectionId)->getData());
            }
            if (!$newSection->save()) {
                throw new DB($newSection->getErrors());
            }
        }
    }

    /**
     * @throws DB
     */
    private function rewriteOtherAmendments(): void
    {
        $newSections = $this->getNewHtmlParas();
        $overrides   = $this->otherAmendOverrides;

        foreach ($this->oldMotion->getAmendmentsRelevantForCollisionDetection([$this->mergeAmendment]) as $amendment) {
            if (!isset($this->otherAmendStatuses[$amendment->id])) {
                continue;
            }
            if (in_array($this->otherAmendStatuses[$amendment->id], $amendment->getMyConsultation()->getStatuses()->getStatusesMarkAsDoneOnRewriting())) {
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
            foreach ($amendment->getActiveSections(ISectionType::TYPE_TITLE) as $section) {
                foreach ($this->oldMotion->sections as $motionSection) {
                    if ($motionSection->sectionId === $section->sectionId && $motionSection->getData() === $section->getData() && $this->mergeAmendment->getSection($section->sectionId)) {
                        // Change the title of amendments that didn't try to change the title of the motion
                        $section->setData($this->mergeAmendment->getSection($section->sectionId)->getData());
                        $section->save();
                    }
                }
            }
            $amendment->motionId = $this->newMotion->id;
            $amendment->cache    = '';
            $amendment->status   = $this->otherAmendStatuses[$amendment->id];
            if (!$amendment->save()) {
                throw new DB($amendment->getErrors());
            }
        }
    }

    /**
     * @throws DB
     */
    private function setDoneAmendmentsStatuses(): void
    {
        foreach ($this->oldMotion->getAmendmentsRelevantForCollisionDetection([$this->mergeAmendment]) as $amendment) {
            if (!isset($this->otherAmendStatuses[$amendment->id])) {
                continue;
            }
            if (!in_array($this->otherAmendStatuses[$amendment->id], $amendment->getMyConsultation()->getStatuses()->getStatusesMarkAsDoneOnRewriting())) {
                continue;
            }
            $amendment->status = $this->otherAmendStatuses[$amendment->id];
            if (!$amendment->save()) {
                throw new DB($amendment->getErrors());
            }
        }
    }

    /**
     * @return Motion
     * @throws DB
     */
    public function performRewrite(): Motion
    {
        $previousSlug          = $this->oldMotion->slug;
        $this->oldMotion->slug = null;
        $this->oldMotion->save();

        $this->createNewMotion($previousSlug);
        $this->createNewMotionSections();
        $this->rewriteOtherAmendments();
        $this->setDoneAmendmentsStatuses();

        $this->mergeAmendment->status = $this->mergeAmendStatus;
        $this->mergeAmendment->save();

        $this->oldMotion->status = Motion::STATUS_MODIFIED;
        $this->oldMotion->save();

        $consultation = $this->oldMotion->getMyConsultation();
        $conSettings = $consultation->getSettings();
        if ($conSettings->forceMotion === $this->oldMotion->id) {
            $conSettings->forceMotion = $this->newMotion->id;
            $consultation->setSettings($conSettings);
            $consultation->save();
        }

        $this->newMotion->refreshTitle();
        $this->newMotion->save();
        $this->newMotion->trigger(Motion::EVENT_MERGED, new MotionEvent($this->newMotion));

        return $this->newMotion;
    }
}
