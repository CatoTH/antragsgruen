<?php

namespace app\models\proposedProcedure;

use app\models\db\{Amendment, AmendmentSection, ConsultationAgendaItem, IMotion, VotingBlock};
use app\models\exceptions\Internal;
use app\models\IMotionList;
use app\models\sectionTypes\TextSimpleCommon;
use yii\helpers\Html;

class Agenda
{
    public const FORMAT_HTML = 0;
    public const FORMAT_ODS  = 1;

    /** @var AgendaVoting[] */
    public array $votingBlocks = [];

    public function __construct(
        public int $blockId,
        public string $title,
        public ?ConsultationAgendaItem $agendaItem = null
    ) {
    }

    public function addVotingBlock(VotingBlock $votingBlock, bool $includeInvisible, IMotionList $handledMotions): void
    {
        $title = \Yii::t('con', 'proposal_table_voting') . ': ' . $votingBlock->title;
        $block = new AgendaVoting($title, $votingBlock);
        $block->addItemsFromBlock($includeInvisible);
        $handledMotions->addIMotionList($block->itemIds);
        if (count($block->items) > 0) {
            $this->votingBlocks[] = $block;
        }
    }

    public static function formatProposedAmendmentProcedure(IMotion $imotion, int $format): string
    {
        if ($format === Agenda::FORMAT_HTML && $imotion->proposalStatus !== IMotion::STATUS_OBSOLETED_BY_AMENDMENT) {
            // Flushing an amendment's cache does not work when a modified version of an amendment is edited
            // that is replacing this one -> we disable the cache in this case
            $cached = $imotion->getCacheItem('procedure.formatted');
            if ($cached !== null) {
                return $cached;
            }
        }

        /** @var Amendment|null $toShowAmendment */
        $toShowAmendment = null;
        if ($imotion->hasAlternativeProposaltext()) {
            $toShowAmendment = $imotion->getMyProposalReference();
        }
        if ($imotion->status === Amendment::STATUS_PROPOSED_MOVE_TO_OTHER_MOTION && is_a($imotion, Amendment::class)) {
            $toShowAmendment = $imotion;
        }

        $proposal  = '';
        if ($toShowAmendment) {
            /** @var AmendmentSection[] $sections */
            $sections = $toShowAmendment->getSortedSections(false);
            foreach ($sections as $section) {
                try {
                    $firstLine    = $section->getFirstLineNumber();
                    $lineLength   = $section->getCachedConsultation()->getSettings()->lineLength;
                    $originalData = $section->getOriginalMotionSection()?->getData() ?? '';
                    $newData      = $section->data;
                    if ($originalData === $newData) {
                        continue;
                    }
                    if ($format === static::FORMAT_ODS) {
                        $proposal .= TextSimpleCommon::formatAmendmentForOds($originalData, $newData, $firstLine, $lineLength);
                    } else {
                        $proposal .= $section->getSectionType()->getAmendmentPlainHtml();
                    }
                } catch (Internal $e) {
                    $proposal .= '<p>@INTERNAL ERROR@</p>';
                }
            }
        }

        if ($format === Agenda::FORMAT_HTML && $imotion->proposalStatus !== Amendment::STATUS_OBSOLETED_BY_AMENDMENT) {
            $imotion->setCacheItem('procedure.formatted', $proposal);
        }

        return $proposal;
    }

    public static function formatProposedProcedure(IMotion $item, int $format): string
    {
        $proposal = '<p>' . trim($item->getFormattedProposalStatus()) . '</p>';
        if ($item->proposalExplanation) {
            $proposal .= '<p class="explanation">' . Html::encode($item->proposalExplanation) . '</p>';
        }

        if ($item->status !== IMotion::STATUS_WITHDRAWN) {
            $proposal .= static::formatProposedAmendmentProcedure($item, $format);
        }

        return $proposal;
    }
}
