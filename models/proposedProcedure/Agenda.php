<?php

namespace app\models\proposedProcedure;

use app\models\db\{Amendment, AmendmentSection, ConsultationAgendaItem, IMotion, VotingBlock};
use app\models\exceptions\Internal;
use app\models\IMotionList;
use app\models\sectionTypes\TextSimple;
use yii\helpers\Html;

class Agenda
{
    public const FORMAT_HTML = 0;
    public const FORMAT_ODS  = 1;

    public string $title;
    public int $blockId;
    public ?ConsultationAgendaItem $agendaItem;

    /** @var AgendaVoting[] */
    public array $votingBlocks = [];

    public function __construct(int $blockId, string $title, ?ConsultationAgendaItem $agendaItem = null)
    {
        $this->blockId    = $blockId;
        $this->title      = $title;
        $this->agendaItem = $agendaItem;
    }

    public function addVotingBlock(VotingBlock $votingBlock, bool $includeInvisible, IMotionList $handledMotions)
    {
        $title = \Yii::t('con', 'proposal_table_voting') . ': ' . $votingBlock->title;
        $block = new AgendaVoting($title, $votingBlock);
        $block->addItemsFromBlock($includeInvisible);
        $handledMotions->addIMotionList($block->itemIds);
        if (count($block->items) > 0) {
            $this->votingBlocks[] = $block;
        }
    }

    public static function formatProposedAmendmentProcedure(Amendment $amendment, int $format): string
    {
        if ($format === Agenda::FORMAT_HTML && $amendment->proposalStatus !== Amendment::STATUS_OBSOLETED_BY) {
            // Flushing this amendment's cache does not work when a modified version of an amendment is edited
            // that is replacing this one -> we disable the cache in this case
            $cached = $amendment->getCacheItem('procedure.formatted');
            if ($cached !== null) {
                return $cached;
            }
        }

        /** @var Amendment|null $toShowAmendment */
        $toShowAmendment = null;
        if ($amendment->hasAlternativeProposaltext()) {
            $toShowAmendment = $amendment->getMyProposalReference();
        }
        if ($amendment->status === Amendment::STATUS_PROPOSED_MOVE_TO_OTHER_MOTION) {
            $toShowAmendment = $amendment;
        }

        $proposal  = '';
        if ($toShowAmendment) {
            /** @var AmendmentSection[] $sections */
            $sections = $toShowAmendment->getSortedSections(false);
            foreach ($sections as $section) {
                try {
                    $firstLine    = $section->getFirstLineNumber();
                    $lineLength   = $section->getCachedConsultation()->getSettings()->lineLength;
                    $originalData = $section->getOriginalMotionSection()->getData();
                    $newData      = $section->data;
                    if ($originalData === $newData) {
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
        }

        if ($format === Agenda::FORMAT_HTML && $amendment->proposalStatus !== Amendment::STATUS_OBSOLETED_BY) {
            $amendment->setCacheItem('procedure.formatted', $proposal);
        }

        return $proposal;
    }

    public static function formatProposedProcedure(IMotion $item, int $format): string
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
