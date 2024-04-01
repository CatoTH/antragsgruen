<?php

namespace app\models\mergeAmendments;

use app\models\forms\MotionDeepCopy;
use app\components\{MotionNumbering, RequestContext, Tools};
use app\models\sectionTypes\TextSimple;
use app\models\db\{IMotion, Motion, MotionSection, MotionSupporter};
use app\models\events\MotionEvent;
use app\models\exceptions\Internal;
use app\models\sectionTypes\ISectionType;
use app\models\settings\VotingData;

class Merge
{
    /** @var MotionSection[] */
    public array $motionSections = [];

    public function __construct(
        public Motion $origMotion
    ) {
    }

    public function getMergedMotionDraft(): ?Motion
    {
        $newVersion = MotionNumbering::getNewVersion($this->origMotion->version);
        /** @var Motion|null $newMotion */
        $newMotion = Motion::find()
            ->where(['parentMotionId' => $this->origMotion->id])
            ->andWhere(['status' => Motion::STATUS_DRAFT])
            ->andWhere(['titlePrefix' => $this->origMotion->titlePrefix])
            ->andWhere(['version' => $newVersion])
            ->one();

        return $newMotion;
    }

    private function createMotion(): Motion
    {
        $newMotion = $this->getMergedMotionDraft();
        if (!$newMotion) {
            $newMotion = new Motion();
            $newMotion->consultationId = $this->origMotion->consultationId;
            $newMotion->parentMotionId = $this->origMotion->id;
            $newMotion->motionTypeId = $this->origMotion->motionTypeId;
            $newMotion->titlePrefix = $this->origMotion->titlePrefix;
            $newMotion->version = MotionNumbering::getNewVersion($this->origMotion->version);
        }
        $newMotion->agendaItemId = $this->origMotion->agendaItemId;
        $newMotion->cache = '';
        $newMotion->title = '';
        $newMotion->dateCreation = date('Y-m-d H:i:s');
        $newMotion->dateContentModification = date('Y-m-d H:i:s');
        $newMotion->status = Motion::STATUS_DRAFT;
        if (!$newMotion->save()) {
            var_dump($newMotion->getErrors());
            throw new Internal();
        }

        $newMotion->refresh();

        return $newMotion;
    }

    private function mergeSimpleTextSection(MotionSection $section, MotionSection $origSection, Draft $draft): void
    {
        $paragraphs = [];
        foreach ($origSection->getTextParagraphLines(true) as $para) {
            $consolidated = $draft->paragraphs[$section->sectionId . '_' . $para->paragraphWithLineSplit]->text;
            $consolidated = str_replace('<li>&nbsp;</li>', '', $consolidated);
            $paragraphs[] = $consolidated;
        }
        $html = implode("\n", $paragraphs);
        /** @var TextSimple $simpleTextSection */
        $simpleTextSection = $section->getSectionType();
        $simpleTextSection->setMotionData($html, true);
        $section->dataRaw = $html;
    }

    public function createNewMotion(Draft $draft): Motion
    {
        $newMotion = $this->createMotion();

        foreach ($this->origMotion->getActiveSections() as $origSection) {
            $section = MotionSection::createEmpty($origSection->sectionId, $origSection->public, $newMotion->id);

            if (!in_array($origSection->sectionId, $draft->removedSections)) {
                if ($section->getSettings()->type === ISectionType::TYPE_TEXT_SIMPLE) {
                    $this->mergeSimpleTextSection($section, $origSection, $draft);
                } elseif (isset($draft->sections[$section->sectionId])) {
                    $section->getSectionType()->setMotionData($draft->sections[$section->sectionId]);
                } else {
                    // @TODO Images etc.
                }
            }

            if (!$section->save()) {
                var_dump($section->getErrors());
                throw new Internal();
            }
            $this->motionSections[] = $section;
        }

        $newMotion->refreshTitle();
        $newMotion->save();

        $newMotion->setProtocol($draft->protocol, $draft->protocolPublic);

        return $newMotion;
    }

    /**
     * @param int[] $amendmentStatuses
     */
    public function confirm(Motion $newMotion, array $amendmentStatuses, ?string $resolutionMode, ?string $resolutionSubstatus, string $resolutionBody, ?string $newMotionTypeId, array $votes, ?array $amendmentVotes): Motion
    {
        $oldMotion    = $this->origMotion;
        $consultation = $oldMotion->getMyConsultation();

        $invisible = $consultation->getStatuses()->getInvisibleAmendmentStatuses();
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
            } elseif (intval($resolutionSubstatus) === IMotion::STATUS_ACCEPTED) {
                $newMotion->status = IMotion::STATUS_ACCEPTED;
            } elseif (intval($resolutionSubstatus) === IMotion::STATUS_MODIFIED_ACCEPTED) {
                $newMotion->status = IMotion::STATUS_MODIFIED_ACCEPTED;
            } else {
                $newMotion->status = $oldMotion->status;
            }
        } else {
            $newMotion->status = $oldMotion->status;
        }
        if ($isResolution) {
            $resolutionDate            = RequestContext::getWebRequest()->post('dateResolution', '');
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
                $resolutionDate       = RequestContext::getWebRequest()->post('dateResolution', '');
                $resolutionDate       = Tools::dateBootstrapdate2sql($resolutionDate);
                $body->resolutionDate = ($resolutionDate ?: null);
                if (!$body->save()) {
                    var_dump($body->getErrors());
                    die();
                }
            }
        }

        foreach ($oldMotion->getPublicTopicTags() as $tag) {
            $newMotion->link('tags', $tag);
        }

        if ($newMotionTypeId) {
            $newMotionType = $newMotion->getMyConsultation()->getMotionType((int) $newMotionTypeId);
            $sectionMapping = MotionDeepCopy::getMotionSectionMapping($newMotion->getMyMotionType(), $newMotionType, [MotionDeepCopy::SKIP_NON_AMENDABLE]);
            if ($sectionMapping) {
                foreach ($newMotion->sections as $section) {
                    if (!$section->getSettings()->hasAmendments && !isset($sectionMapping[$section->sectionId])) {
                        $section->delete();
                    }
                }
                $newMotion->refresh();
                $newMotion->setMotionType($newMotionType, $sectionMapping);
            }
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

    /**
     * @param int[] $amendmentStatuses
     */
    public function updateDraftOnBackToModify(array $amendmentStatuses, array $amendmentVotes): void
    {
        $draft = $this->origMotion->getMergingDraft(false);

        $draft->amendmentStatuses = $amendmentStatuses;
        foreach ($amendmentVotes as $amendmentId => $data) {
            if (!isset($draft->amendmentVotingData[$amendmentId])) {
                $draft->amendmentVotingData[$amendmentId] = new VotingData(null);
            }
            $draft->amendmentVotingData[$amendmentId]->setFromPostData($data);
        }
        $draft->save();
    }
}
