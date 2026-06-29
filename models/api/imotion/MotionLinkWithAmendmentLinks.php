<?php

declare(strict_types=1);

namespace app\models\api\imotion;

class MotionLinkWithAmendmentLinks
{
    public function __construct(
        public ?MotionLinkWithAmendmentLinksType $type = null,
        public ?int $id = null,
        public ?string $agendaItem = null,
        public ?string $prefix = null,
        public ?string $title = null,
        public ?string $titleWithIntro = null,
        public ?string $titleWithPrefix = null,
        public ?int $statusId = null,
        public ?string $statusTitle = null,
        public ?string $initiatorsHtml = null,
        /** @var AmendmentLink[]|null */
        public ?array $amendmentLinks = null,
        public ?string $urlJson = null,
        public ?string $urlHtml = null,
    ) {
    }
}
