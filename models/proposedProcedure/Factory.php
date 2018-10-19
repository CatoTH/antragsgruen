<?php

namespace app\models\proposedProcedure;

use app\models\db\Consultation;
use app\models\db\ConsultationAgendaItem;
use app\models\db\Motion;
use app\models\settings\Consultation as ConsultationSettings;

/**
 * Class Factory
 * @package app\models\proposedProcedure
 */
class Factory
{
    /** @var Consultation */
    public $consultation;

    /** @var ConsultationAgendaItem|null */
    public $agendaItem;

    /** @var bool */
    public $includeInvisible = false;

    /**
     * ProposedProcedureFactory constructor.
     * @param Consultation $consultation
     * @param bool $includeInvisible
     * @param null|ConsultationAgendaItem $agendaItem
     */
    public function __construct(Consultation $consultation, $includeInvisible, $agendaItem = null)
    {
        $this->consultation     = $consultation;
        $this->agendaItem       = $agendaItem;
        $this->includeInvisible = $includeInvisible;
    }

    /**
     * @return Agenda[]
     */
    protected function createFromAgenda()
    {
        $items   = [];
        $idCount = 1;

        $handledMotions = [];
        $handledAmends  = [];
        $handledVotings = [];

        $agendaItems = ConsultationAgendaItem::getSortedFromConsultation($this->consultation);

        foreach ($agendaItems as $agendaItem) {
            if ($this->agendaItem && $agendaItem !== $this->agendaItem) {
                continue;
            }
            $title = \Yii::t('con', 'proposal_table_voting') . ': ' . $agendaItem->title;
            $item  = new Agenda($idCount++, $title, $agendaItem);
            foreach ($agendaItem->getVisibleMotionsSorted(true) as $motion) {
                if (in_array($motion->id, $handledMotions)) {
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

                foreach ($motion->getVisibleAmendmentsSorted(true) as $amendment) {
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

            $block        = new AgendaVoting(\Yii::t('export', 'pp_unhandled'), null);
            $block->items = [];
            foreach ($agendaItem->getVisibleMotionsSorted(true) as $motion) {
                $block->items[]   = $motion;
                $handledMotions[] = $motion->id;
                foreach ($motion->getVisibleAmendmentsSorted(true) as $amendment) {
                    $block->items[]  = $amendment;
                    $handledAmends[] = $amendment->id;
                }
            }
            if (count($block->items) > 0) {
                $item->votingBlocks[] = $block;
            }

            $items[] = $item;
        }

        if ($this->agendaItem === null) {
            // Attach motions that haven't been found in the agenda at the end of the document (if no filter is set)

            $unhandledMotions = [];
            foreach ($this->consultation->getVisibleMotionsSorted(true) as $motion) {
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
     * @param array $handledVotings
     * @param array $handledAmends
     * @return Agenda[]
     */
    protected function createFromMotions($motions, $handledVotings = [], $handledAmends = [])
    {
        $items   = [];
        $idCount = 1;

        $handledMotions = [];

        foreach ($motions as $motion) {
            $title = \Yii::t('con', 'proposal_table_voting') . ': ' . $motion->getTitleWithPrefix();
            $item  = new Agenda($idCount++, $title, null);

            if (in_array($motion->id, $handledMotions)) {
                continue;
            }

            if ($motion->votingBlock) {
                $votingBlock = $motion->votingBlock;
                if (in_array($votingBlock->id, $handledVotings)) {
                    continue;
                }
                $item->addVotingBlock($votingBlock, $this->includeInvisible, $handledMotions, $handledAmends);
                $handledAmends[] = $votingBlock->id;
            }

            foreach ($motion->getVisibleAmendments(true) as $amendment) {
                if (in_array($amendment->id, $handledAmends)) {
                    continue;
                }
                if ($amendment->votingBlock) {
                    $votingBlock = $amendment->votingBlock;
                    if (in_array($votingBlock->id, $handledVotings)) {
                        continue;
                    }
                    $item->addVotingBlock($votingBlock, $this->includeInvisible, $handledMotions, $handledAmends);
                    $handledAmends[] = $votingBlock->id;
                }
            }

            $block        = new AgendaVoting(\Yii::t('export', 'pp_unhandled'), null);
            $block->items = [];
            if ($motion->isProposalPublic() || $this->includeInvisible) {
                $handledMotions[] = $motion->id;
                $block->items[] = $motion;
            }
            foreach ($motion->getVisibleAmendmentsSorted(true) as $amendment) {
                if ($amendment->isProposalPublic() || $this->includeInvisible) {
                    $handledAmends[] = $amendment->id;
                    $block->items[]  = $amendment;
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
    public function create()
    {
        switch ($this->consultation->getSettings()->startLayoutType) {
            case ConsultationSettings::START_LAYOUT_AGENDA:
            case ConsultationSettings::START_LAYOUT_AGENDA_LONG:
                return $this->createFromAgenda();
                break;

            case ConsultationSettings::START_LAYOUT_STD:
            case ConsultationSettings::START_LAYOUT_TAGS:
            default:
                $motions = $this->consultation->getVisibleMotionsSorted(true);
                return $this->createFromMotions($motions);
                break;
        }
    }
}
