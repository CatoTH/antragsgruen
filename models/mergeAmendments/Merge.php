<?php

namespace app\models\mergeAmendments;

use app\components\Tools;
use app\models\db\IMotion;
use app\models\db\Motion;
use app\models\db\MotionSection;
use app\models\db\MotionSupporter;
use app\models\events\MotionEvent;
use app\models\exceptions\Internal;
use app\models\sectionTypes\ISectionType;
use app\models\settings\VotingData;

class Merge
{
    /** @var Motion */
    public $origMotion;

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
     * @return Motion|null
     */
    public function getMergedMotionDraft()
    {
        $newTitlePrefix = $this->origMotion->getNewTitlePrefix();
        $newMotion      = Motion::find()
                                ->where(['parentMotionId' => $this->origMotion->id])
                                ->andWhere(['status' => Motion::STATUS_DRAFT])
                                ->andWhere(['titlePrefix' => $newTitlePrefix])->one();

        return $newMotion;
    }

    /**
     * @return Motion
     */
    private function createMotion()
    {
        $newMotion = $this->getMergedMotionDraft();
        if (!$newMotion) {
            $newMotion                 = new Motion();
            $newMotion->consultationId = $this->origMotion->consultationId;
            $newMotion->parentMotionId = $this->origMotion->id;
            $newMotion->motionTypeId   = $this->origMotion->motionTypeId;
            $newMotion->titlePrefix    = $this->origMotion->getNewTitlePrefix();
        }
        $newMotion->agendaItemId = $this->origMotion->agendaItemId;
        $newMotion->cache        = '';
        $newMotion->title        = '';
        $newMotion->dateCreation = date('Y-m-d H:i:s');
        $newMotion->status       = Motion::STATUS_DRAFT;
        if (!$newMotion->save()) {
            var_dump($newMotion->getErrors());
            throw new Internal();
        }

        $newMotion->refresh();

        return $newMotion;
    }

    /**
     * @param MotionSection $section
     * @param MotionSection $origSection
     * @param Draft $draft
     *
     * @throws \app\models\exceptions\FormError
     */
    private function mergeSimpleTextSection(MotionSection $section, MotionSection $origSection, Draft $draft)
    {
        $paragraphs = [];
        foreach ($origSection->getTextParagraphLines() as $paraNo => $para) {
            $consolidated = $draft->paragraphs[$section->sectionId . '_' . $paraNo]->text;
            $consolidated = str_replace('<li>&nbsp;</li>', '', $consolidated);
            $paragraphs[] = $consolidated;
        }
        $html = implode("\n", $paragraphs);
        $section->getSectionType()->setMotionData($html);
        $section->dataRaw = $html;
    }

    /**
     * @param Draft $draft
     *
     * @return Motion
     * @throws Internal
     * @throws \app\models\exceptions\FormError
     */
    public function createNewMotion(Draft $draft)
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
                $this->mergeSimpleTextSection($section, $origSection, $draft);
            } elseif (isset($draft->sections[$section->sectionId])) {
                $section->getSectionType()->setMotionData($draft->sections[$section->sectionId]);
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
     * @param Motion $newMotion
     * @param int[] $amendmentStatuses
     * @param string $resolutionMode
     * @param string $resolutionBody
     * @param array $votes
     * @param array $amendmentVotes
     *
     * @return Motion
     */
    public function confirm(Motion $newMotion, $amendmentStatuses, $resolutionMode, $resolutionBody, $votes, $amendmentVotes)
    {
        $oldMotion    = $this->origMotion;
        $consultation = $oldMotion->getMyConsultation();

        $invisible = $consultation->getInvisibleAmendmentStatuses();
        foreach ($oldMotion->getVisibleAmendments() as $amendment) {
            if (isset($amendmentStatuses[$amendment->id])) {
                $newStatus = IntVal($amendmentStatuses[$amendment->id]);
                if (!in_array($amendmentStatuses[$amendment->id], $invisible)) {
                    $amendment->status = $newStatus;
                }
            }
            if (isset($amendmentVotes[$amendment->id])) {
                $dat                        = $amendmentVotes[$amendment->id];
                $votesData                  = new VotingData(null);
                $votesData->votesYes        = (is_numeric($dat['yes']) ? IntVal($dat['yes']) : null);
                $votesData->votesNo         = (is_numeric($dat['no']) ? IntVal($dat['no']) : null);
                $votesData->votesAbstention = (is_numeric($dat['abstention']) ? IntVal($dat['abstention']) : null);
                $votesData->votesInvalid    = (is_numeric($dat['invalid']) ? IntVal($dat['invalid']) : null);
                $votesData->comment         = $dat['comment'];
                $amendment->setVotingData($votesData);
            }
            $amendment->save();
        }

        $newMotion->slug = $oldMotion->slug;

        $votesData = $newMotion->getVotingData();
        $votesData->setFromPostData($votes);
        $newMotion->setVotingData($votesData);

        $oldMotion->slug = null;
        $oldMotion->save();


        $isResolution = false;
        if ($newMotion->canCreateResolution()) {
            if ($resolutionMode === 'resolution_final') {
                $newMotion->status = IMotion::STATUS_RESOLUTION_FINAL;
                $isResolution      = true;
            } elseif ($resolutionMode === 'resolution_preliminary') {
                $newMotion->status = IMotion::STATUS_RESOLUTION_PRELIMINARY;
                $isResolution      = true;
            } else {
                $newMotion->status = $oldMotion->status;
            }
        } else {
            $newMotion->status = $oldMotion->status;
        }
        if ($isResolution) {
            $resolutionDate            = \Yii::$app->request->post('dateResolution', '');
            $resolutionDate            = Tools::dateBootstrapdate2sql($resolutionDate);
            $newMotion->dateResolution = ($resolutionDate ? $resolutionDate : null);
        } else {
            $newMotion->dateResolution = null;
        }
        $newMotion->save();

        // For resolutions, the state of the original motion should not be changed
        if (!$isResolution && $newMotion->replacedMotion->status === Motion::STATUS_SUBMITTED_SCREENED) {
            $oldMotion->status = Motion::STATUS_MODIFIED;
            $oldMotion->save();
        }

        if ($isResolution) {
            if (trim($resolutionBody) !== '') {
                $body                 = new MotionSupporter();
                $body->motionId       = $newMotion->id;
                $body->position       = 0;
                $body->dateCreation   = date('Y-m-d H:i:s');
                $body->personType     = MotionSupporter::PERSON_ORGANIZATION;
                $body->role           = MotionSupporter::ROLE_INITIATOR;
                $body->organization   = $resolutionBody;
                $resolutionDate       = \Yii::$app->request->post('dateResolution', '');
                $resolutionDate       = Tools::dateBootstrapdate2sql($resolutionDate);
                $body->resolutionDate = ($resolutionDate ? $resolutionDate : null);
                if (!$body->save()) {
                    var_dump($body->getErrors());
                    die();
                }
            }
        }

        foreach ($oldMotion->tags as $tag) {
            $newMotion->link('tags', $tag);
        }

        $mergingDraft = $oldMotion->getMergingDraft(false);
        if ($mergingDraft) {
            $mergingDraft->delete();
        }

        // If the old motion was the only / forced motion of the consultation, set the new one as the forced one.
        if ($consultation->getSettings()->forceMotion === $oldMotion->id) {
            $settings              = $consultation->getSettings();
            $settings->forceMotion = $newMotion->id;
            $consultation->setSettings($settings);
            $consultation->save();
        }

        $newMotion->trigger(Motion::EVENT_MERGED, new MotionEvent($newMotion));

        return $newMotion;
    }
}
