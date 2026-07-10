<?php

declare(strict_types=1);

namespace app\models\api\imotion;

use app\components\UrlHelper;
use app\models\db\Motion;

class MotionLink
{
    public function __construct(
        public ?int $id = null,
        public ?string $agendaItem = null,
        public ?string $prefix = null,
        public ?string $title = null,
        public ?string $titleWithIntro = null,
        public ?string $titleWithPrefix = null,
        public ?string $initiatorsHtml = null,
        public ?string $urlJson = null,
        public ?string $urlHtml = null,
    ) {
    }

    public static function fromEntity(Motion $motion): self
    {
        return new self(
            id: $motion->id,
            agendaItem: $motion->agendaItem?->title,
            prefix: $motion->titlePrefix,
            title: $motion->title,
            titleWithIntro: $motion->getTitleWithIntro(),
            titleWithPrefix: $motion->getTitleWithPrefix(),
            initiatorsHtml: $motion->getInitiatorsStr(),
            urlJson: UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion, 'rest')),
            urlHtml: UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($motion)),
        );
    }
}
