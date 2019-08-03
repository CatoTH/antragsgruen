<?php

namespace app\models\forms;

use app\models\db\Motion;
use app\models\db\MotionSection;
use app\models\exceptions\Internal;
use app\models\sectionTypes\ISectionType;
use app\models\sectionTypes\TextSimple;

class MotionMergeAmendmentsForm
{
    /** @var Motion */
    public $origMotion;

    /** @var array */
    public $sections;
    public $amendStatus;

    /** @var MotionSection[] */
    public $motionSections;

    /**
     * @param Motion $origMotion
     */
    public function __construct(Motion $origMotion)
    {
        $this->origMotion = $origMotion;
    }

    /**
     * @return Motion
     */
    private function createMotion()
    {
        $newMotion                 = new Motion();
        $newMotion->motionTypeId   = $this->origMotion->motionTypeId;
        $newMotion->agendaItemId   = $this->origMotion->agendaItemId;
        $newMotion->consultationId = $this->origMotion->consultationId;
        $newMotion->parentMotionId = $this->origMotion->id;
        $newMotion->titlePrefix    = $this->origMotion->getNewTitlePrefix();
        $newMotion->cache          = '';
        $newMotion->title          = '';
        $newMotion->dateCreation   = date('Y-m-d H:i:s');
        $newMotion->status         = Motion::STATUS_DRAFT;
        if (!$newMotion->save()) {
            var_dump($newMotion->getErrors());
            throw new Internal();
        }

        $newMotion->refresh();

        foreach ($this->origMotion->tags as $tag) {
            $newMotion->link('tags', $tag);
        }

        return $newMotion;
    }

    /**
     * @param MotionSection $section
     * @param MotionSection $origSection
     * @param array $post
     *
     * @throws \app\models\exceptions\FormError
     */
    private function mergeSimpleTextSection(MotionSection $section, MotionSection $origSection, $post)
    {
        $paragraphs = [];
        foreach ($origSection->getTextParagraphLines() as $paraNo => $para) {
            $consolidated = $post['sections'][$section->sectionId][$paraNo]['consolidated'];
            $consolidated = str_replace('<li>&nbsp;</li>', '', $consolidated);
            $paragraphs[] = $consolidated;
        }
        $html = implode("\n", $paragraphs);
        $section->getSectionType()->setMotionData($html);
        $section->dataRaw = $html;
    }

    /**
     * @param array $post
     *
     * @return Motion
     * @throws Internal
     * @throws \app\models\exceptions\FormError
     */
    public function createNewMotion($post)
    {
        $newMotion = $this->createMotion();

        foreach ($this->origMotion->getActiveSections() as $origSection) {
            $section            = new MotionSection();
            $section->sectionId = $origSection->sectionId;
            $section->motionId  = $newMotion->id;
            $section->cache     = '';
            $section->data      = '';
            $section->dataRaw   = '';
            $section->refresh();

            if ($section->getSettings()->type === ISectionType::TYPE_TEXT_SIMPLE) {
                $this->mergeSimpleTextSection($section, $origSection, $post);
            } elseif (isset($this->sections[$section->sectionId])) {
                $section->getSectionType()->setMotionData($this->sections[$section->sectionId]);
            } else {
                // @TODO Images etc.
            }

            if (!$section->save()) {
                var_dump($section->getErrors());
                throw new Internal();
            }
            $this->motionSections[] = $section;
        }


        $newMotion->refreshTitle();
        $newMotion->save();

        return $newMotion;
    }

    /**
     * @param array $post
     *
     * @return string
     */
    public function encodeAmendmentStatuses($post)
    {
        $statuses = [];
        if (isset($post['amendmentStatus'])) {
            foreach ($post['amendmentStatus'] as $key => $val) {
                $statuses[IntVal($key)] = IntVal($val);
            }
        }

        return json_encode($statuses);
    }
}
