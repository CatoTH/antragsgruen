<?php

declare(strict_types=1);

namespace app\models\api\consultation;

class ConsultationWithLinks
{
    public function __construct(
        public ?string $title = null,
        public ?string $titleShort = null,
        /** @var \app\models\api\imotion\MotionLinkWithAmendmentLinks[]|null */
        public ?array $motionLinks = null,
        /** @var \app\models\api\speakingList\SpeakingList[]|null */
        public ?array $speakingLists = null,
        /** @var \app\models\api\PageLinks[]|null */
        public ?array $pageLinks = null,
        public ?string $urlJson = null,
        public ?string $urlHtml = null,
    ) {
    }
}
