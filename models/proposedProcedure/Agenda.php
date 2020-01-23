<?php

namespace app\models\proposedProcedure;

use app\models\db\{Amendment, AmendmentSection, ConsultationAgendaItem, IMotion, VotingBlock};
use app\models\exceptions\Internal;
use app\models\sectionTypes\TextSimple;
use yii\helpers\Html;

class Agenda
{
    const FORMAT_HTML = 0;
    const FORMAT_ODS  = 1;

    /** @var string */
    public $title;

    /** @var int */
    public $blockId;

    /** @var AgendaVoting[] */
    public $votingBlocks = [];

    /** @var ConsultationAgendaItem|null */
    public $agendaItem;

    /**
     * ProposedProcedureAgenda constructor.
     * @param int $blockId
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
     * @param VotingBlock $votingBlock
     * @param bool $includeInvisible
     * @param array $handledMotions
     * @param array $handledAmends
     */
    public function addVotingBlock($votingBlock, $includeInvisible, &$handledMotions, &$handledAmends)
    {
        $title = \Yii::t('con', 'proposal_table_voting') . ': ' . $votingBlock->title;
        $block = new AgendaVoting($title, $votingBlock);
        foreach ($votingBlock->motions as $motion) {
            if ($motion->isProposalPublic() || $includeInvisible) {
                $block->items[]   = $motion;
                $handledMotions[] = $motion->id;

                foreach ($motion->getVisibleAmendmentsSorted(true) as $amendment) {
                    if (in_array($amendment->id, $handledAmends)) {
                        continue;
                    }
                    if ($amendment->isProposalPublic() || $includeInvisible) {
                        $block->items[]  = $amendment;
                        $handledAmends[] = $amendment->id;
                    }
                }
            }
        }
        foreach ($votingBlock->amendments as $vAmendment) {
            if (!in_array($vAmendment->id, $handledAmends) && ($vAmendment->isProposalPublic() || $includeInvisible)) {
                $block->items[]  = $vAmendment;
                $handledAmends[] = $vAmendment->id;
            }
        }
        if (count($block->items) > 0) {
            $this->votingBlocks[] = $block;
        }
    }

    /**
     * @param Amendment $amendment
     * @param int $format
     * @return string
     */
    public static function formatProposedAmendmentProcedure(Amendment $amendment, $format)
    {
        // @TODO Caching
        if (!$amendment->hasAlternativeProposaltext()) {
            return '';
        }

        $proposal  = '';
        $reference = $amendment->getMyProposalReference();
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

        if (is_a($item, Amendment::class) && $item->status !== IMotion::STATUS_WITHDRAWN) {
            /** @var Amendment $item */
            $proposal .= static::formatProposedAmendmentProcedure($item, $format);
        }

        return $proposal;
    }
}
