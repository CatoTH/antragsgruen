<?php

declare(strict_types=1);

namespace app\models\api\imotion;

use app\components\UrlHelper;
use app\models\api\proposedprocedure\MotionProposedProcedure;
use app\models\db\{Amendment, ISupporter, Motion, MotionSection as MotionSectionEntity, MotionSupporter};
use app\views\motion\LayoutHelper;

class MotionDetails
{
    public function __construct(
        public ?string $type = null,
        public ?int $id = null,
        public ?string $agendaItem = null,
        public ?string $prefix = null,
        public ?string $title = null,
        public ?string $titleWithIntro = null,
        public ?string $titleWithPrefix = null,
        public ?int $statusId = null,
        public ?string $statusTitle = null,
        public ?string $datePublished = null,
        /** @var Supporter[]|null */
        public ?array $supporters = null,
        /** @var Supporter[]|null */
        public ?array $initiators = null,
        public ?string $initiatorsHtml = null,
        /** @var MotionSection[]|null */
        public ?array $sections = null,
        public ?\app\models\api\proposedprocedure\MotionProposedProcedure $proposedProcedure = null,
        /** @var AmendmentLink[]|null */
        public ?array $amendmentLinks = null,
        public ?string $urlJson = null,
        public ?string $urlHtml = null,
        public ?MotionPagination $pagination = null,
    ) {
    }

    private static function buildPagination(Motion $motion): ?MotionPagination
    {
        if (!$motion->getMyConsultation()->getSettings()->motionPrevNextLinks) {
            return null;
        }

        $links = LayoutHelper::getPrevNextLinks($motion);

        return new MotionPagination(
            prev: $links['prev'] ? UrlHelper::absolutizeLink(UrlHelper::createIMotionUrl($links['prev'], 'rest')) : null,
            next: $links['next'] ? UrlHelper::absolutizeLink(UrlHelper::createIMotionUrl($links['next'], 'rest')) : null,
        );
    }

    public static function fromEntity(Motion $motion, bool $lineNumbers): self
    {
        /** @var MotionSectionEntity[] $sortedSections */
        $sortedSections = $motion->getSortedSections(true);

        return new self(
            type: 'motion',
            id: $motion->id,
            agendaItem: $motion->agendaItem?->title,
            prefix: $motion->titlePrefix,
            title: $motion->title,
            titleWithIntro: $motion->getTitleWithIntro(),
            titleWithPrefix: $motion->getTitleWithPrefix(),
            statusId: $motion->status,
            statusTitle: $motion->getFormattedStatus(),
            datePublished: $motion->getPublicationDateTime()?->format('c'),
            supporters: array_map(
                fn(MotionSupporter $s) => Supporter::fromEntity($s),
                $motion->getSupporters(false)
            ),
            initiators: array_map(
                fn(ISupporter $s) => Supporter::fromEntity($s),
                $motion->getInitiators()
            ),
            initiatorsHtml: $motion->getInitiatorsStr(),
            sections: array_map(
                fn(MotionSectionEntity $s) => MotionSection::fromEntity($s, $lineNumbers),
                $sortedSections
            ),
            proposedProcedure: MotionProposedProcedure::fromMotion($motion),
            amendmentLinks: array_map(
                fn(Amendment $a) => AmendmentLink::fromEntity($a),
                $motion->getVisibleAmendmentsSorted()
            ),
            urlJson: UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion, 'rest')),
            urlHtml: UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion)),
            pagination: self::buildPagination($motion),
        );
    }
}
