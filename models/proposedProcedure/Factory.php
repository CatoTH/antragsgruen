<?php

namespace app\models\proposedProcedure;

use app\models\settings\IMotionStatusEngine;
use app\models\db\{Consultation, ConsultationAgendaItem, Motion, VotingBlock};
use app\models\settings\Consultation as ConsultationSettings;

class Factory
{
    /** @var Consultation */
    public $consultation;

    /** @var ConsultationAgendaItem|null */
    public $agendaItem;

    /** @var bool */
    public $includeInvisible = false;

    public function __construct(Consultation $consultation, bool $includeInvisible, ?ConsultationAgendaItem $agendaItem = null)
    {
        $this->consultation     = $consultation;
        $this->agendaItem       = $agendaItem;
        $this->includeInvisible = $includeInvisible;
    }

    /**
     * @return Agenda[]
     */
    protected function createFromAgenda(): array
    {
        $items   = [];
        $idCount = 1;

        $handledMotions = [];
        $handledAmends  = [];
        $handledVotings = [];

        $forbiddenStatuses = $this->consultation->getStatuses()->getStatusesInvisibleForProposedProcedure();
        $agendaItems = ConsultationAgendaItem::getSortedFromConsultation($this->consultation);

        foreach ($agendaItems as $agendaItem) {
            if ($this->agendaItem && $agendaItem !== $this->agendaItem) {
                continue;
            }
            $title = \Yii::t('con', 'proposal_table_voting') . ': ' . $agendaItem->title;
            $item  = new Agenda($idCount++, $title, $agendaItem);

            $imotions = IMotionStatusEngine::filterIMotionsByForbiddenStatuses($agendaItem->getMyIMotions(), $forbiddenStatuses, true);
            foreach ($imotions as $motion) {
                if (in_array($motion->id, $handledMotions)) {
                    continue;
                }
                if (!$motion->getMyMotionType()->getSettingsObj()->hasProposedProcedure) {
                    continue;
                }
                if ($motion->votingBlockId > 0 && $motion->votingBlock) {
                    $votingBlock = $motion->votingBlock;
                    if (in_array($votingBlock->id, $handledVotings)) {
                        continue;
                    }
                    $item->addVotingBlock($votingBlock, $this->includeInvisible, $handledMotions, $handledAmends);
                    $handledVotings[] = $votingBlock->id;
                }

                if (is_a($motion, Motion::class)) {
                    $amendments = IMotionStatusEngine::filterAmendmentsByForbiddenStatuses($motion->amendments, $forbiddenStatuses, true);
                    foreach ($amendments as $amendment) {
                        if (in_array($amendment->id, $handledAmends)) {
                            continue;
                        }
                        if ($amendment->votingBlockId > 0 && $amendment->votingBlock) {
                            $votingBlock = $amendment->votingBlock;
                            if (in_array($votingBlock->id, $handledVotings)) {
                                continue;
                            }
                            $item->addVotingBlock($votingBlock, $this->includeInvisible, $handledMotions, $handledAmends);
                        }
                    }
                }
            }

            $block        = new AgendaVoting(\Yii::t('export', 'pp_unhandled'), null);
            $block->items = [];
            foreach ($imotions as $motion) {
                if (!$motion->getMyMotionType()->getSettingsObj()->hasProposedProcedure) {
                    continue;
                }
                $block->items[]   = $motion;
                $handledMotions[] = $motion->id;

                if (is_a($motion, Motion::class)) {
                    $amendments = IMotionStatusEngine::filterAmendmentsByForbiddenStatuses($motion->amendments, $forbiddenStatuses, true);
                    foreach ($amendments as $amendment) {
                        $block->items[] = $amendment;
                        $handledAmends[] = $amendment->id;
                    }
                }
            }
            if (count($block->items) > 0) {
                $item->votingBlocks[] = $block;
            }

            if ($agendaItem->getSettingsObj()->inProposedProcedures) {
                $items[] = $item;
            }
        }

        if ($this->agendaItem === null) {
            // Attach motions that haven't been found in the agenda at the end of the document (if no filter is set)

            $unhandledMotions = [];
            foreach ($this->consultation->getVisibleIMotionsSorted(true) as $motion) {
                if (!in_array($motion->id, $handledMotions)) {
                    $unhandledMotions[] = $motion;
                }
            }
            $items = array_merge($items, static::createFromMotions($unhandledMotions, $handledVotings, $handledAmends));
        }

        return $items;
    }

    /**
     * @param Motion[] $motions
     * @return Agenda[]
     */
    protected function createFromMotions(array $motions, array $handledVotings = [], array $handledAmends = []): array
    {
        $items   = [];
        $idCount = 1;

        $handledMotions = [];
        $forbiddenStatuses = $this->consultation->getStatuses()->getStatusesInvisibleForProposedProcedure();

        foreach ($motions as $motion) {
            $title = \Yii::t('con', 'proposal_table_voting') . ': ' . $motion->getTitleWithPrefix();
            $item  = new Agenda($idCount++, $title, null);

            if (in_array($motion->id, $handledMotions)) {
                continue;
            }
            if (!$motion->getMyMotionType()->getSettingsObj()->hasProposedProcedure) {
                continue;
            }

            if ($motion->votingBlockId && $motion->votingBlock) {
                $votingBlock = $motion->votingBlock;
                if (in_array($votingBlock->id, $handledVotings)) {
                    continue;
                }
                $item->addVotingBlock($votingBlock, $this->includeInvisible, $handledMotions, $handledAmends);
                $handledAmends[] = $votingBlock->id;
            }

            if (is_a($motion, Motion::class)) {
                $amendments = IMotionStatusEngine::filterAmendmentsByForbiddenStatuses($motion->amendments, $forbiddenStatuses, true);
                foreach ($amendments as $amendment) {
                    if (in_array($amendment->id, $handledAmends)) {
                        continue;
                    }
                    if ($amendment->votingBlockId && $amendment->votingBlock) {
                        $votingBlock = $amendment->votingBlock;
                        if (in_array($votingBlock->id, $handledVotings)) {
                            continue;
                        }
                        $item->addVotingBlock($votingBlock, $this->includeInvisible, $handledMotions, $handledAmends);
                        $handledAmends[] = $votingBlock->id;
                    }
                }
            }

            $block        = new AgendaVoting(\Yii::t('export', 'pp_unhandled'), null);
            $block->items = [];
            if ($motion->isProposalPublic() || $this->includeInvisible) {
                $handledMotions[] = $motion->id;
                $block->items[] = $motion;
            }
            if (is_a($motion, Motion::class)) {
                $amendments = IMotionStatusEngine::filterAmendmentsByForbiddenStatuses($motion->amendments, $forbiddenStatuses, true);
                foreach ($amendments as $amendment) {
                    if ($amendment->isProposalPublic() || $this->includeInvisible) {
                        $handledAmends[] = $amendment->id;
                        $block->items[] = $amendment;
                    }
                }
            }
            if (count($block->items) > 0) {
                $item->votingBlocks[] = $block;
            }

            if (count($item->votingBlocks) > 0) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * @return Agenda[]
     */
    public function create(): array
    {
        switch ($this->consultation->getSettings()->startLayoutType) {
            case ConsultationSettings::START_LAYOUT_AGENDA:
            case ConsultationSettings::START_LAYOUT_AGENDA_LONG:
            case ConsultationSettings::START_LAYOUT_AGENDA_HIDE_AMEND:
                return $this->createFromAgenda();

            case ConsultationSettings::START_LAYOUT_STD:
            case ConsultationSettings::START_LAYOUT_TAGS:
            default:
                $motions = $this->consultation->getVisibleIMotionsSorted(true);
                return $this->createFromMotions($motions);
        }
    }


    /**
     * @return AgendaVoting[]
     * Hint: AgendaVoting objects returned here are guaranteed to have a VotingBlock object in the voting property
     */
    public static function getAllVotingBlocks(Consultation $consultation): array
    {
        // There is probably a more efficient way to create this, without having to build the whole agenda first
        $proposalFactory = new Factory($consultation, true);
        $agenda = $proposalFactory->create();

        $votingBlocks = [];
        foreach ($agenda as $agendaItem) {
            foreach ($agendaItem->votingBlocks as $votingBlock) {
                if ($votingBlock->voting) {
                    $votingBlocks[] = $votingBlock;
                }
            }
        }

        return $votingBlocks;
    }

    /**
     * @return AgendaVoting[]
     * Hint: AgendaVoting objects returned here are guaranteed to have a VotingBlock object in the voting property
     */
    public static function getOpenVotingBlocks(Consultation $consultation): array
    {
        // There is probably a more efficient way to create this, without having to build the whole agenda first
        $votingBlocks = [];
        foreach (Factory::getAllVotingBlocks($consultation) as $votingBlock) {
            if ($votingBlock->voting->votingStatus === VotingBlock::STATUS_OPEN) {
                $votingBlocks[] = $votingBlock;
            }
        }

        return $votingBlocks;
    }
}
