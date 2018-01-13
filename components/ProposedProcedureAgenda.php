<?php

namespace app\components;

use app\models\db\Amendment;
use app\models\db\AmendmentSection;
use app\models\db\Consultation;
use app\models\db\ConsultationAgendaItem;
use app\models\db\IMotion;
use app\models\db\Motion;
use app\models\db\VotingBlock;
use app\models\exceptions\Internal;
use app\models\sectionTypes\TextSimple;
use app\models\settings\Consultation as ConsultationSettings;
use yii\helpers\Html;

class ProposedProcedureAgenda
{
    const FORMAT_HTML = 0;
    const FORMAT_ODS  = 1;

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
                    static::addVotingBlock($item, $votingBlock, $handledMotions, $handledAmends);
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
                        static::addVotingBlock($item, $votingBlock, $handledMotions, $handledAmends);
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

            if ($motion->votingBlock) {
                $votingBlock = $motion->votingBlock;
                if (in_array($votingBlock->id, $handledVotings)) {
                    continue;
                }
                static::addVotingBlock($item, $votingBlock, $handledMotions, $handledAmends);
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
                    static::addVotingBlock($item, $votingBlock, $handledMotions, $handledAmends);
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

    /**
     * @param Amendment $amendment
     * @param int $format
     * @return string
     */
    public static function formatProposedAmendmentProcedure(Amendment $amendment, $format)
    {
        if (!$amendment->hasAlternativeProposaltext()) {
            return '';
        }

        $proposal  = '';
        $reference = $amendment->proposalReference;
        /** @var AmendmentSection[] $sections */
        $sections = $reference->getSortedSections(false);
        foreach ($sections as $section) {
            try {
                $firstLine    = $section->getFirstLineNumber();
                $lineLength   = $section->getCachedConsultation()->getSettings()->lineLength;
                $originalData = $section->getOriginalMotionSection()->data;
                $newData      = $section->data;
                if ($originalData == $newData) {
                    continue;
                }
                if ($format === static::FORMAT_ODS) {
                    $proposal .= TextSimple::formatAmendmentForOds($originalData, $newData, $firstLine, $lineLength);
                } else {
                    $proposal .= $section->getSectionType()->getAmendmentPlainHtml();
                }
            } catch (Internal $e) {
                $proposal .= '<p>@INTERNAL ERROR@</p>';
            }
        }

        return $proposal;
    }

    /**
     * @param IMotion $item
     * @param int $format
     * @return string
     */
    public static function formatProposedProcedure(IMotion $item, $format)
    {
        $proposal = '<p>' . trim($item->getFormattedProposalStatus()) . '</p>';
        if ($item->proposalExplanation) {
            $proposal .= '<p class="explanation">' . Html::encode($item->proposalExplanation) . '</p>';
        }

        if (is_a($item, Amendment::class)) {
            /** @var Amendment $item */
            $proposal .= static::formatProposedAmendmentProcedure($item, $format);
        }

        return $proposal;
    }
}
