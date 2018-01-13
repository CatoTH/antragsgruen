<?php

namespace app\components;

use app\models\db\Consultation;
use app\models\db\ConsultationAgendaItem;
use app\models\db\Motion;
use app\plugins\memberPetitions\ConsultationSettings;

/**
 * Class ProposedProcedureFactory
 * @package app\components
 */
class ProposedProcedureFactory
{
    /** @var Consultation */
    public $consultation;

    /** @var ConsultationAgendaItem|null */
    public $agendaItem;

    /**
     * ProposedProcedureFactory constructor.
     * @param Consultation $consultation
     * @param null|ConsultationAgendaItem $agendaItem
     */
    public function __construct(Consultation $consultation, $agendaItem = null)
    {
        $this->consultation = $consultation;
        $this->agendaItem   = $agendaItem;
    }

    /**
     * @return ProposedProcedureAgenda[]
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
            $item  = new ProposedProcedureAgenda($idCount++, $title, $agendaItem);
            foreach ($agendaItem->getVisibleMotions(true) as $motion) {
                if (in_array($motion->id, $handledMotions)) {
                    continue;
                }
                if ($motion->votingBlock) {
                    $votingBlock = $motion->votingBlock;
                    if (in_array($votingBlock->id, $handledVotings)) {
                        continue;
                    }
                    $item->addVotingBlock($votingBlock, $handledMotions, $handledAmends);
                    $handledVotings[] = $votingBlock->id;
                }

                foreach ($motion->getVisibleAmendmentsSorted(true) as $amendment) {
                    if (in_array($amendment->id, $handledAmends)) {
                        continue;
                    }
                    if ($amendment->votingBlock) {
                        $votingBlock = $amendment->votingBlock;
                        if (in_array($votingBlock->id, $handledVotings)) {
                            continue;
                        }
                        $item->addVotingBlock($votingBlock, $handledMotions, $handledAmends);
                    }
                }
            }

            $block        = new ProposedProcedureAgendaVoting(\Yii::t('export', 'pp_unhandled'), null);
            $block->items = [];
            foreach ($agendaItem->getVisibleMotions(true) as $motion) {
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
            foreach ($this->consultation->getVisibleMotions(true) as $motion) {
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
     * @return ProposedProcedureAgenda[]
     */
    protected function createFromMotions($motions, $handledVotings = [], $handledAmends = [])
    {
        $items   = [];
        $idCount = 1;

        $handledMotions = [];

        foreach ($motions as $motion) {
            $title = \Yii::t('con', 'proposal_table_voting') . ': ' . $motion->getTitleWithPrefix();
            $item  = new ProposedProcedureAgenda($idCount++, $title, null);

            if (in_array($motion->id, $handledMotions)) {
                continue;
            }

            if ($motion->votingBlock) {
                $votingBlock = $motion->votingBlock;
                if (in_array($votingBlock->id, $handledVotings)) {
                    continue;
                }
                $item->addVotingBlock($votingBlock, $handledMotions, $handledAmends);
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
                    $item->addVotingBlock($votingBlock, $handledMotions, $handledAmends);
                    $handledAmends[] = $votingBlock->id;
                }
            }

            $block        = new ProposedProcedureAgendaVoting(\Yii::t('export', 'pp_unhandled'), null);
            $block->items = [];
            foreach ($motion->getVisibleAmendmentsSorted(true) as $amendment) {
                $handledAmends[] = $amendment->id;
                $block->items[]  = $amendment;
            }
            if (count($block->items) > 0) {
                $item->votingBlocks[] = $block;
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * @return ProposedProcedureAgenda[]
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
                $motions = $this->consultation->getVisibleMotions(true);
                return $this->createFromMotions($motions);
                break;
        }
    }
}
