<?php

declare(strict_types=1);

namespace app\models\api\imotion;

use app\components\UrlHelper;
use app\models\api\proposedprocedure\AmendmentProposedProcedure;
use app\models\db\{Amendment, AmendmentSection as AmendmentSectionEntity, AmendmentSupporter, ISupporter};

class AmendmentDetails
{
    public function __construct(
        public ?string $type = null,
        public ?int $id = null,
        public ?string $prefix = null,
        public ?string $title = null,
        public ?string $titleWithPrefix = null,
        public ?int $firstLine = null,
        public ?int $statusId = null,
        public ?string $statusTitle = null,
        public ?string $datePublished = null,
        public ?MotionLink $motion = null,
        /** @var Supporter[]|null */
        public ?array $supporters = null,
        /** @var Supporter[]|null */
        public ?array $initiators = null,
        public ?string $initiatorsHtml = null,
        /** @var AmendmentSection[]|null */
        public ?array $sections = null,
        public ?\app\models\api\proposedprocedure\AmendmentProposedProcedure $proposedProcedure = null,
        public ?string $urlJson = null,
        public ?string $urlHtml = null,
    ) {
    }

    public static function fromEntity(Amendment $amendment): self
    {
        /** @var AmendmentSectionEntity[] $sortedSections */
        $sortedSections = $amendment->getSortedSections(true);

        return new self(
            type: 'amendment',
            id: $amendment->id,
            prefix: $amendment->titlePrefix,
            title: $amendment->getTitle(),
            titleWithPrefix: $amendment->getTitleWithPrefix(),
            firstLine: $amendment->getFirstDiffLine(),
            statusId: $amendment->status,
            statusTitle: $amendment->getFormattedStatus(),
            datePublished: $amendment->getPublicationDateTime()?->format('c'),
            motion: MotionLink::fromEntity($amendment->getMyMotion()),
            // @TODO Support non-public supporters
            supporters: array_map(
                fn(AmendmentSupporter $s) => Supporter::fromEntity($s),
                $amendment->getSupporters(false)
            ),
            initiators: array_map(
                fn(ISupporter $s) => Supporter::fromEntity($s),
                $amendment->getInitiators()
            ),
            initiatorsHtml: $amendment->getInitiatorsStr(),
            sections: array_map(
                fn(AmendmentSectionEntity $s) => AmendmentSection::fromEntity($s),
                $sortedSections
            ),
            proposedProcedure: AmendmentProposedProcedure::fromAmendment($amendment),
            urlJson: UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment, 'rest')),
            urlHtml: UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment)),
        );
    }
}
