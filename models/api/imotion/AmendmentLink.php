<?php

declare(strict_types=1);

namespace app\models\api\imotion;

use app\components\UrlHelper;
use app\models\db\Amendment;

class AmendmentLink
{
    public function __construct(
        public ?int $id = null,
        public ?string $prefix = null,
        public ?int $statusId = null,
        public ?string $statusTitle = null,
        public ?string $initiatorsHtml = null,
        public ?string $urlJson = null,
        public ?string $urlHtml = null,
    ) {
    }

    public static function fromEntity(Amendment $amendment): self
    {
        return new self(
            id: $amendment->id,
            prefix: $amendment->titlePrefix,
            statusId: $amendment->status,
            statusTitle: $amendment->getFormattedStatus(),
            initiatorsHtml: $amendment->getInitiatorsStr(),
            urlJson: UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment, 'rest')),
            urlHtml: UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($amendment)),
        );
    }
}
