<?php

namespace app\models\proposedProcedure;

use app\models\db\{Amendment, AmendmentSection, ConsultationAgendaItem, IMotion, IProposal, VotingBlock};
use app\components\HashedStaticCache;
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

    public static function getProposedAmendmentProcedureCache(IMotion $imotion, IProposal $proposal): HashedStaticCache
    {
        return HashedStaticCache::getInstance('formatProposedAmendmentProcedure', [$imotion->id, $proposal->id]);
    }

    public static function formatProposedAmendmentProcedure(IMotion $imotion, IProposal $proposal, int $format): string
    {
        $cache = self::getProposedAmendmentProcedureCache($imotion, $proposal);
        if ($format !== self::FORMAT_HTML || $proposal->proposalStatus === IMotion::STATUS_OBSOLETED_BY_AMENDMENT) {
            $cache->setSkipCache(true);
        }

        return $cache->getCached(function () use ($imotion, $proposal, $format) {
            /** @var Amendment|null $toShowAmendment */
            $toShowAmendment = null;
            if ($proposal->hasAlternativeProposaltext()) {
                $toShowAmendment = $proposal->getMyProposalReference();
            }
            if ($imotion->status === Amendment::STATUS_PROPOSED_MOVE_TO_OTHER_MOTION && is_a($imotion, Amendment::class)) {
                $toShowAmendment = $imotion;
            }

            $proposalStr = '';
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
                            $proposalStr .= TextSimpleCommon::formatAmendmentForOds($originalData, $newData, $firstLine, $lineLength);
                        } else {
                            $proposalStr .= $section->getSectionType()->getAmendmentPlainHtml();
                        }
                    } catch (Internal $e) {
                        $proposalStr .= '<p>@INTERNAL ERROR@</p>';
                    }
                }
            }
            return $proposalStr;
        });
    }

    public static function formatProposedProcedure(IMotion $imotion, IProposal $proposal, int $format): string
    {
        $proposalStr = '<p>' . trim($proposal->getFormattedProposalStatus()) . '</p>';
        if ($proposal->explanation) {
            $proposalStr .= '<p class="explanation">' . Html::encode($proposal->explanation) . '</p>';
        }

        if ($imotion->status !== IMotion::STATUS_WITHDRAWN) {
            $proposalStr .= static::formatProposedAmendmentProcedure($imotion, $proposal, $format);
        }

        return $proposalStr;
    }
}
