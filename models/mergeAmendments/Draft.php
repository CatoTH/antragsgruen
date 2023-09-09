<?php

namespace app\models\mergeAmendments;

use app\models\settings\JsonConfigTrait;
use app\models\db\{Amendment, IAdminComment, IMotion, Motion, MotionSection};
use app\models\settings\VotingData;

class Draft implements \JsonSerializable
{
    public Motion $origMotion;
    public ?Motion $draftMotion;

    public bool $public = false;
    public ?\DateTime $time = null;

    /** @var int[] */
    public array $amendmentStatuses;

    /** @var string[] */
    public array $amendmentVersions;

    /** @var VotingData[] */
    public array $amendmentVotingData;

    /** @var DraftParagraph[] */
    public array $paragraphs;

    /** @var string[] */
    public array $sections;

    /** @var int[] */
    public array $removedSections;

    public bool $protocolPublic;
    public string $protocol;

    public function jsonSerialize(): array
    {
        return [
            'amendmentStatuses'   => $this->amendmentStatuses,
            'amendmentVersions'   => $this->amendmentVersions,
            'amendmentVotingData' => $this->amendmentVotingData,
            'paragraphs'          => $this->paragraphs,
            'sections'            => $this->sections,
            'removedSections'     => $this->removedSections,
            'protocolPublic'      => $this->protocolPublic,
            'protocol'            => $this->protocol,
        ];
    }

    private function init(Motion $origMotion): void
    {
        $this->origMotion  = $origMotion;
        $draftStatuses     = [Motion::STATUS_MERGING_DRAFT_PUBLIC, Motion::STATUS_MERGING_DRAFT_PRIVATE];

        /** @var Motion|null $draftMotion */
        $draftMotion = Motion::find()->where(['parentMotionId' => $origMotion->id])
                                     ->andWhere(['status' => $draftStatuses])->one();
        $this->draftMotion = $draftMotion;

        if ($this->draftMotion) {
            $this->draftMotion->dateCreation = date('Y-m-d H:i:s');
        } else {
            $this->draftMotion = new Motion();
            $this->draftMotion->setAttributes($this->origMotion->getAttributes(), false);
            $this->draftMotion->id = null;
            $this->draftMotion->dateCreation = date('Y-m-d H:i:s');
            $this->draftMotion->dateContentModification = date('Y-m-d H:i:s');
            $this->draftMotion->status = Motion::STATUS_MERGING_DRAFT_PRIVATE;
            $this->draftMotion->titlePrefix = '';
            $this->draftMotion->version = Motion::VERSION_DEFAULT;
            $this->draftMotion->parentMotionId = $this->origMotion->id;
            $this->draftMotion->slug = null;
        }
    }


    public static function initFromJson(Motion $origMotion, bool $public, ?\DateTime $time, string $data): Draft
    {
        $draft = new Draft();
        $draft->init($origMotion);

        $json                       = json_decode($data, true);
        $draft->sections            = $json['sections'];
        $draft->removedSections     = $json['removedSections'] ?? [];
        $draft->paragraphs          = DraftParagraph::fromJsonArr($json['paragraphs']);

        // If the merging page is reloaded and an amendment has been deleted in the meanwhile,
        // its status information should be removed. (Text changes already made remain)
        $draft->amendmentVersions = [];
        $draft->amendmentStatuses = [];
        $draft->amendmentVotingData = [];

        $amendments = Init::getMotionAmendmentsForMerging($origMotion);
        foreach ($amendments as $amendment) {
            if (isset($json['amendmentVersions'][$amendment->id])) {
                $draft->amendmentVersions[$amendment->id] = $json['amendmentVersions'][$amendment->id];
            }
            if (isset($json['amendmentStatuses'][$amendment->id])) {
                $draft->amendmentStatuses[$amendment->id] = $json['amendmentStatuses'][$amendment->id];
            }
            if (isset($json['amendmentVotingData'][$amendment->id])) {
                $draft->amendmentVotingData[$amendment->id] = new VotingData($json['amendmentVotingData'][$amendment->id]);
            }
        }

        $draft->public = $public;
        $draft->time = $time;

        $draft->protocol = $json['protocol'] ?? '';
        $draft->protocolPublic = $json['protocolPublic'] ?? false;

        return $draft;
    }

    public static function initFromForm(Init $form, array $textVersions): Draft
    {
        $draft = new Draft();
        $draft->init($form->motion);

        $draft->sections = []; // Empty = default values
        $draft->removedSections = [];
        $draft->amendmentStatuses = [];
        $draft->amendmentVersions = [];
        $draft->amendmentVotingData = [];

        $proposedAlternative = $form->motion->getAlternativeProposaltextReference();
        if ($proposedAlternative && $proposedAlternative['motion']->id === $form->motion->id) {
            $proposedAlternativeAmendment = $proposedAlternative['modification'];
            $draft->amendmentStatuses[$proposedAlternativeAmendment->id] = Amendment::STATUS_MERGING_DRAFT_PRIVATE;
            $draft->amendmentVersions[$proposedAlternativeAmendment->id] = Init::TEXT_VERSION_ORIGINAL;
            $draft->amendmentVotingData[$proposedAlternativeAmendment->id] = null;
        }

        $amendments = Init::getMotionAmendmentsForMerging($form->motion);
        foreach ($amendments as $amendment) {
            $draft->amendmentStatuses[$amendment->id] = $amendment->status;

            if ($amendment->hasAlternativeProposaltext(false) && isset($textVersions[$amendment->id])) {
                $draft->amendmentVersions[$amendment->id] = $textVersions[$amendment->id];
            } else {
                $draft->amendmentVersions[$amendment->id] = Init::TEXT_VERSION_ORIGINAL;
            }

            $draft->amendmentVotingData[$amendment->id] = $amendment->getVotingData();
        }

        $draft->paragraphs = [];
        foreach ($form->motion->getSortedSections(false) as $section) {
            /** @var MotionSection $section */
            if ($section->getSettings()->type !== \app\models\sectionTypes\ISectionType::TYPE_TEXT_SIMPLE) {
                continue;
            }

            $amendmentsById = [];
            foreach ($section->getMergingAmendingSections(false, true) as $sect) {
                $amendmentsById[$sect->amendmentId] = $sect->getAmendment();
            }

            $paragraphs = $section->getTextParagraphObjects(false, false, false, true);
            foreach (array_keys($paragraphs) as $paragraphNo) {
                $allAmendingIds = $form->getAllAmendmentIdsAffectingParagraph($section, $paragraphNo);
                $paragraphText  = $form->getParagraphText($section, $paragraphNo, $amendmentsById);
                /** @noinspection PhpUnusedLocalVariableInspection */
                list($normalAmendments, $modUs) = $form->getAffectingAmendments($allAmendingIds, $amendmentsById);

                $draftPara                    = new DraftParagraph();
                $draftPara->unchanged         = null;
                $draftPara->text              = $paragraphText;
                $draftPara->amendmentToggles  = [];
                $draftPara->handledCollisions = [];
                $draftPara->textVersions      = [];
                foreach ($normalAmendments as $amendment) {
                    if ($form->isAmendmentActiveForParagraph($amendment->id, $section, $paragraphNo)) {
                        $draftPara->amendmentToggles[] = $amendment->id;
                        $draftPara->textVersions[$amendment->id] = $draft->amendmentVersions[$amendment->id];
                    }
                }

                $draft->paragraphs[$section->sectionId . '_' . $paragraphNo] = $draftPara;
            }
        }

        $origProtocol = $form->motion->getProtocol();
        if ($origProtocol) {
            $draft->protocol = $origProtocol->text;
            $draft->protocolPublic = ($origProtocol->status === IAdminComment::TYPE_PROTOCOL_PUBLIC);
        } else {
            $draft->protocol = '';
            $draft->protocolPublic = false;
        }

        return $draft;
    }

    public function save(): void
    {
        if ($this->public) {
            $this->draftMotion->status = Motion::STATUS_MERGING_DRAFT_PUBLIC;
        } else {
            $this->draftMotion->status = Motion::STATUS_MERGING_DRAFT_PRIVATE;
        }
        $this->draftMotion->save();

        $section = null;
        foreach ($this->draftMotion->getActiveSections() as $existingSection) {
            $section = $existingSection;
        }
        if (!$section) {
            $section = new MotionSection();
            $section->setAttributes($this->origMotion->getActiveSections()[0]->getAttributes(), false);
            $section->motionId = $this->draftMotion->id;
        }
        $section->dataRaw = json_encode($this, JSON_THROW_ON_ERROR);
        $section->setData('');
        $section->save();

        foreach ($this->draftMotion->getActiveSections() as $oldSection) {
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

    public function delete(): void
    {
        $this->draftMotion->status = IMotion::STATUS_DELETED;
        $this->draftMotion->save();
    }
}
