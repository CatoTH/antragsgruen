<?php

namespace app\components;

use app\models\db\Amendment;
use app\models\db\Consultation;
use app\models\db\ConsultationAgendaItem;
use app\models\db\Motion;
use app\models\db\VotingBlock;
use app\models\settings\Consultation as ConsultationSettings;

class ProposedProcedureAgenda
{
    /** @var string */
    public $title;
    public $blockId;

    /** @var ProposedProcedureAgendaVoting[] */
    public $votingBlocks = [];

    /** @var ConsultationAgendaItem|null */
    public $agendaItem;

    /**
     * ProposedProcedureAgenda constructor.
     * @param string $blockId
     * @param string $title
     * @param ConsultationAgendaItem|null $agendaItem
     */
    public function __construct($blockId, $title, $agendaItem = null)
    {
        $this->blockId    = $blockId;
        $this->title      = $title;
        $this->agendaItem = $agendaItem;
    }

    /**
     * @param ProposedProcedureAgenda $item
     * @param VotingBlock $votingBlock
     * @param array $handledMotions
     * @param array $handledAmends
     */
    protected static function addVotingBlock($item, $votingBlock, &$handledMotions, &$handledAmends)
    {
        $title = \Yii::t('con', 'proposal_table_voting') . ': ' . $votingBlock->title;
        $block = new ProposedProcedureAgendaVoting($title, $votingBlock);
        foreach ($votingBlock->motions as $motion) {
            $block->items[]   = $motion;
            $handledMotions[] = $motion->id;

            foreach ($motion->getVisibleAmendmentsSorted(true) as $amendment) {
                if (in_array($amendment->id, $handledAmends)) {
                    continue;
                }
                $block->items[]  = $amendment;
                $handledAmends[] = $amendment->id;
            }
        }
        foreach ($votingBlock->amendments as $vAmendment) {
            $block->items[]  = $vAmendment;
            $handledAmends[] = $vAmendment->id;
        }
        $item->votingBlocks[] = $block;
    }

    /**
     * @param Consultation $consultation
     * @param ConsultationAgendaItem $onlyAgendaItem
     * @return ProposedProcedureAgenda[]
     */
    protected static function createFromAgenda(Consultation $consultation, $onlyAgendaItem = null)
    {
        $items   = [];
        $idCount = 1;

        $handledMotions = [];
        $handledAmends  = [];
        $handledVotings = [];

        $agendaItems = ConsultationAgendaItem::getSortedFromConsultation($consultation);

        foreach ($agendaItems as $agendaItem) {
            if ($onlyAgendaItem && $agendaItem !== $onlyAgendaItem) {
                continue;
            }
            $title          = \Yii::t('con', 'proposal_table_voting') . ': ' . $agendaItem->title;
            $item           = new ProposedProcedureAgenda($idCount++, $title, $agendaItem);
            $unhandledItems = [];
            foreach ($agendaItem->getVisibleMotions(true) as $motion) {
                if (in_array($motion->id, $handledMotions)) {
                    continue;
                }
                if ($motion->votingBlock) {
                    $votingBlock = $motion->votingBlock;
                    if (in_array($votingBlock->id, $handledVotings)) {
                        continue;
                    }
                    static::addVotingBlock($item, $votingBlock, $handledMotions, $handledAmends);
                    $handledVotings[] = $votingBlock->id;
                } else {
                    $unhandledItems[] = $motion;
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
                        static::addVotingBlock($item, $votingBlock, $handledMotions, $handledAmends);
                        $handledAmends[] = $votingBlock->id;
                    } else {
                        $unhandledItems[] = $amendment;
                    }
                }
            }
            if (count($unhandledItems) > 0) {
                $block        = new ProposedProcedureAgendaVoting(\Yii::t('export', 'pp_unhandled'), null);
                $block->items = $unhandledItems;
                foreach ($unhandledItems as $unhandledItem) {
                    if (is_a($unhandledItem, Amendment::class)) {
                        $handledAmends[] = $unhandledItem->id;
                    } else {
                        $handledMotions[] = $unhandledItem->id;
                    }
                }
                $item->votingBlocks[] = $block;
            }

            $items[] = $item;
        }

        if ($onlyAgendaItem === null) {
            // Attach motions that haven't been found in the agenda at the end of the document (if no filter is set)

            $unhandledMotions = [];
            foreach ($consultation->getVisibleMotions(true) as $motion) {
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
    protected static function createFromMotions($motions, $handledVotings = [], $handledAmends = [])
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

            $unhandledItems = [];
            if ($motion->votingBlock) {
                $votingBlock = $motion->votingBlock;
                if (in_array($votingBlock->id, $handledVotings)) {
                    continue;
                }
                static::addVotingBlock($item, $votingBlock, $handledMotions, $handledAmends);
                $handledAmends[] = $votingBlock->id;
            } else {
                $unhandledItems[] = $motion;
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
                    static::addVotingBlock($item, $votingBlock, $handledMotions, $handledAmends);
                    $handledAmends[] = $votingBlock->id;
                } else {
                    $unhandledItems[] = $amendment;
                }
            }

            if (count($unhandledItems) > 0) {
                $block        = new ProposedProcedureAgendaVoting(\Yii::t('export', 'pp_unhandled'), null);
                $block->items = $unhandledItems;
                foreach ($unhandledItems as $unhandledItem) {
                    if (is_a($unhandledItem, Amendment::class)) {
                        $handledAmends[] = $unhandledItem->id;
                    } else {
                        $handledMotions[] = $unhandledItem->id;
                    }
                }
                $item->votingBlocks[] = $block;
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * @param Consultation $consultation
     * @param ConsultationAgendaItem $agendaItem
     * @return ProposedProcedureAgenda[]
     */
    public static function createProposedProcedureAgenda(Consultation $consultation, $agendaItem = null)
    {
        switch ($consultation->getSettings()->startLayoutType) {
            case ConsultationSettings::START_LAYOUT_AGENDA:
            case ConsultationSettings::START_LAYOUT_AGENDA_LONG:
                return static::createFromAgenda($consultation, $agendaItem);
                break;

            case ConsultationSettings::START_LAYOUT_STD:
            case ConsultationSettings::START_LAYOUT_TAGS:
            default:
                $motions = $consultation->getVisibleMotions(true);
                return static::createFromMotions($motions);
                break;
        }
    }
}
