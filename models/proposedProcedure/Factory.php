<?php

namespace app\models\proposedProcedure;

use app\components\IMotionStatusFilter;
use app\models\IMotionList;
use app\models\settings\IMotionStatusEngine;
use app\models\db\{Consultation, ConsultationAgendaItem, IMotion, Motion, VotingBlock};
use app\models\settings\Consultation as ConsultationSettings;

class Factory
{
    public function __construct(
        public Consultation $consultation,
        public bool $includeInvisible,
        public ?ConsultationAgendaItem $agendaItem = null
    ) {
    }

    /**
     * @return Agenda[]
     */
    protected function createFromAgenda(): array
    {
        $items   = [];
        $idCount = 1;

        $handledIMotions = new IMotionList();
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
                if ($handledIMotions->hasVotingItem($motion)) {
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
                    $item->addVotingBlock($votingBlock, $this->includeInvisible, $handledIMotions);
                    $handledVotings[] = $votingBlock->id;
                }

                if (is_a($motion, Motion::class)) {
                    $amendments = IMotionStatusEngine::filterAmendmentsByForbiddenStatuses($motion->amendments, $forbiddenStatuses, true);
                    foreach ($amendments as $amendment) {
                        if ($handledIMotions->hasVotingItem($amendment)) {
                            continue;
                        }
                        if ($amendment->votingBlockId > 0 && $amendment->votingBlock) {
                            $votingBlock = $amendment->votingBlock;
                            if (in_array($votingBlock->id, $handledVotings)) {
                                continue;
                            }
                            $item->addVotingBlock($votingBlock, $this->includeInvisible, $handledIMotions);
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
                $handledIMotions->addVotingItem($motion);

                if (is_a($motion, Motion::class)) {
                    $amendments = IMotionStatusEngine::filterAmendmentsByForbiddenStatuses($motion->amendments, $forbiddenStatuses, true);
                    foreach ($amendments as $amendment) {
                        $block->items[] = $amendment;
                        $handledIMotions->addAmendment($amendment);
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

            $filter = IMotionStatusFilter::onlyUserVisible($this->consultation, true);
            $unhandledMotions = [];
            foreach ($filter->getFilteredConsultationIMotionsSorted() as $motion) {
                if (!$handledIMotions->hasVotingItem($motion)) {
                    $unhandledMotions[] = $motion;
                }
            }
            $items = array_merge($items, static::createFromMotions($unhandledMotions, $handledVotings, $handledIMotions));
        }

        return $items;
    }

    /**
     * @param IMotion[] $imotions
     *
     * @return Agenda[]
     */
    protected function createFromMotions(array $imotions, array $handledVotings, IMotionList $handledIMotions): array
    {
        $items   = [];
        $idCount = 1;

        $forbiddenStatuses = $this->consultation->getStatuses()->getStatusesInvisibleForProposedProcedure();

        foreach ($imotions as $imotion) {
            $title = \Yii::t('con', 'proposal_table_voting') . ': ' . $imotion->getTitleWithPrefix();
            $item  = new Agenda($idCount++, $title, null);

            if ($handledIMotions->hasVotingItem($imotion)) {
                continue;
            }
            if (!$imotion->getMyMotionType()->getSettingsObj()->hasProposedProcedure) {
                continue;
            }

            if ($imotion->votingBlockId && $imotion->votingBlock) {
                $votingBlock = $imotion->votingBlock;
                if (in_array($votingBlock->id, $handledVotings)) {
                    continue;
                }
                $item->addVotingBlock($votingBlock, $this->includeInvisible, $handledIMotions);
                $handledVotings[] = $votingBlock->id;
            }

            if (is_a($imotion, Motion::class)) {
                $amendments = IMotionStatusEngine::filterAmendmentsByForbiddenStatuses($imotion->amendments, $forbiddenStatuses, true);
                foreach ($amendments as $amendment) {
                    if ($handledIMotions->hasVotingItem($amendment)) {
                        continue;
                    }
                    if ($amendment->votingBlockId && $amendment->votingBlock) {
                        $votingBlock = $amendment->votingBlock;
                        if (in_array($votingBlock->id, $handledVotings)) {
                            continue;
                        }
                        $item->addVotingBlock($votingBlock, $this->includeInvisible, $handledIMotions);
                        $handledVotings[] = $votingBlock->id;
                    }
                }
            }

            $block        = new AgendaVoting(\Yii::t('export', 'pp_unhandled'), null);
            $block->items = [];
            if ($imotion->isProposalPublic() || $this->includeInvisible) {
                $handledIMotions->addVotingItem($imotion);
                $block->items[] = $imotion;
            }
            if (is_a($imotion, Motion::class)) {
                $amendments = IMotionStatusEngine::filterAmendmentsByForbiddenStatuses($imotion->amendments, $forbiddenStatuses, true);
                foreach ($amendments as $amendment) {
                    if ($amendment->isProposalPublic() || $this->includeInvisible) {
                        $handledIMotions->addAmendment($amendment);
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
                $filter = IMotionStatusFilter::onlyUserVisible($this->consultation, true);
                return $this->createFromMotions($filter->getFilteredConsultationIMotionsSorted(), [], new IMotionList());
        }
    }

    /**
     * @param AgendaVoting[] $votings
     * @return AgendaVoting[]
     */
    private static function sortVotingBlocks(array $votings): array
    {
        usort($votings, function (AgendaVoting $voting1, AgendaVoting $voting2): int {
            if ($voting1->voting->position < $voting2->voting->position) {
                return 1;
            }
            if ($voting1->voting->position > $voting2->voting->position) {
                return -1;
            }
            return $voting2->voting->id <=> $voting1->voting->id;
        });

        return $votings;
    }


    /**
     * @return AgendaVoting[]
     * Hint: AgendaVoting objects returned here are guaranteed to have a VotingBlock object in the voting property
     */
    public static function getAllVotingBlocks(Consultation $consultation): array
    {
        $votings = array_map(function (VotingBlock $votingBlock): AgendaVoting {
            $voting = new AgendaVoting($votingBlock->title, $votingBlock);
            $voting->addItemsFromBlock(true);
            return $voting;
        }, $consultation->votingBlocks);

        return self::sortVotingBlocks($votings);
    }

    /**
     * @return AgendaVoting[]
     * Hint: AgendaVoting objects returned here are guaranteed to have a VotingBlock object in the voting property
     */
    public static function getOpenVotingBlocks(Consultation $consultation, bool $showAllOpen, ?Motion $assignedToMotion): array
    {
        $openBlocks = array_values(array_filter($consultation->votingBlocks, function (VotingBlock $voting) use ($showAllOpen, $assignedToMotion): bool {
            if ($voting->votingStatus !== VotingBlock::STATUS_OPEN) {
                return false;
            }
            if ($showAllOpen) {
                return true;
            } elseif ($assignedToMotion) {
                return $voting->assignedToMotionId === $assignedToMotion->id;
            } else {
                return $voting->assignedToMotionId === null;
            }
        }));
        $votings = array_map(function (VotingBlock $votingBlock): AgendaVoting {
            $voting = new AgendaVoting($votingBlock->title, $votingBlock);
            $voting->addItemsFromBlock(true);
            return $voting;
        }, $openBlocks);

        return self::sortVotingBlocks($votings);
    }

    /**
     * @return AgendaVoting[]
     * Hint: AgendaVoting objects returned here are guaranteed to have a VotingBlock object in the voting property
     */
    public static function getPublishedClosedVotingBlocks(Consultation $consultation): array
    {
        $closedBlocks = VotingBlock::getPublishedClosedVotings($consultation);
        $votings = array_map(function (VotingBlock $votingBlock): AgendaVoting {
            $voting = new AgendaVoting($votingBlock->title, $votingBlock);
            $voting->addItemsFromBlock(true);
            return $voting;
        }, $closedBlocks);

        return self::sortVotingBlocks($votings);
    }

    public static function hasOnlineVotingBlocks(Consultation $consultation): bool
    {
        $onlineVotings = array_values(array_filter($consultation->votingBlocks, function (VotingBlock $voting): bool {
            return $voting->votingStatus !== VotingBlock::STATUS_OFFLINE;
        }));
        return count($onlineVotings) > 0;
    }
}
