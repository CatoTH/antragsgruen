<?php

namespace app\components;

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
     * @param array $handledAmendments
     */
    protected static function addVotingBlock($item, $votingBlock, &$handledMotions, &$handledAmendments)
    {
        $block = new ProposedProcedureAgendaVoting($votingBlock->title, $votingBlock);
        foreach ($votingBlock->motions as $motion) {
            $block->items[]   = $motion;
            $handledMotions[] = $motion->id;

            foreach ($motion->getVisibleAmendmentsSorted(true) as $amendment) {
                if (in_array($amendment->id, $handledAmendments)) {
                    continue;
                }
                $block->items[]      = $amendment;
                $handledAmendments[] = $amendment->id;
            }
        }
        foreach ($votingBlock->amendments as $vAmendment) {
            $block->items[]      = $vAmendment;
            $handledAmendments[] = $vAmendment->id;
        }
        $item->votingBlocks[] = $block;
    }

    /**
     * @param Consultation $consultation
     * @return ProposedProcedureAgenda[]
     */
    protected static function createFromAgenda(Consultation $consultation)
    {
        $items   = [];
        $idCount = 1;

        $handledMotions    = [];
        $handledAmendments = [];
        $handledVotings    = [];

        foreach ($consultation->agendaItems as $agendaItem) {
            $item           = new ProposedProcedureAgenda($idCount++, $agendaItem->title, $agendaItem);
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
                    static::addVotingBlock($item, $votingBlock, $handledMotions, $handledAmendments);
                    $handledVotings[] = $votingBlock->id;
                } else {
                    $unhandledItems[] = $motion;
                }

                foreach ($motion->getVisibleAmendments(true) as $amendment) {
                    if (in_array($amendment->id, $handledAmendments)) {
                        continue;
                    }
                    if ($amendment->votingBlock) {
                        $votingBlock = $amendment->votingBlock;
                        if (in_array($votingBlock->id, $handledVotings)) {
                            continue;
                        }
                        static::addVotingBlock($item, $votingBlock, $handledMotions, $handledAmendments);
                        $handledAmendments[] = $votingBlock->id;
                    } else {
                        $unhandledItems[] = $amendment;
                    }
                }
            }
            if (count($unhandledItems) > 0) {
                $block                = new ProposedProcedureAgendaVoting('Weitere Verfahrensvorschläge', null);
                $block->items         = $unhandledItems;
                $item->votingBlocks[] = $block;
            }

            $items[] = $item;
        }

        $unhandledMotions = [];
        foreach ($consultation->getVisibleMotions(true) as $motion) {
            if (!in_array($motion->id, $handledMotions)) {
                $unhandledMotions[] = $motion;
            }
        }
        $items = array_merge($items, static::createFromMotions($unhandledMotions, $handledVotings, $handledAmendments));

        return $items;
    }

    /**
     * @param Motion[] $motions
     * @param array $handledVotings
     * @param array $handledAmendments
     * @return ProposedProcedureAgenda[]
     */
    protected static function createFromMotions($motions, $handledVotings = [], $handledAmendments = [])
    {
        $items   = [];
        $idCount = 1;

        $handledMotions = [];

        foreach ($motions as $motion) {
            $item = new ProposedProcedureAgenda($idCount++, $motion->getTitleWithPrefix(), null);

            if (in_array($motion->id, $handledMotions)) {
                continue;
            }

            $unhandledItems = [];
            if ($motion->votingBlock) {
                $votingBlock = $motion->votingBlock;
                if (in_array($votingBlock->id, $handledVotings)) {
                    continue;
                }
                static::addVotingBlock($item, $votingBlock, $handledMotions, $handledAmendments);
                $handledAmendments[] = $votingBlock->id;
            } else {
                $unhandledItems[] = $motion;
            }

            foreach ($motion->getVisibleAmendments(true) as $amendment) {
                if (in_array($amendment->id, $handledAmendments)) {
                    continue;
                }
                if ($amendment->votingBlock) {
                    $votingBlock = $amendment->votingBlock;
                    if (in_array($votingBlock->id, $handledVotings)) {
                        continue;
                    }
                    static::addVotingBlock($item, $votingBlock, $handledMotions, $handledAmendments);
                    $handledAmendments[] = $votingBlock->id;
                } else {
                    $unhandledItems[] = $amendment;
                }
            }

            if (count($unhandledItems) > 0) {
                $block                = new ProposedProcedureAgendaVoting('Weitere Verfahrensvorschläge', null);
                $block->items         = $unhandledItems;
                $item->votingBlocks[] = $block;
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * @param Consultation $consultation
     * @return ProposedProcedureAgenda[]
     */
    public static function createProposedProcedureAgenda(Consultation $consultation)
    {
        switch ($consultation->getSettings()->startLayoutType) {
            case ConsultationSettings::START_LAYOUT_AGENDA:
            case ConsultationSettings::START_LAYOUT_AGENDA_LONG:
                return static::createFromAgenda($consultation);
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
