<?php

declare(strict_types=1);

namespace app\models\api\imotion;

use app\models\db\AmendmentSection as AmendmentSectionEntity;
use app\models\sectionTypes\{ISectionType, TextSimple};

class AmendmentSection
{
    public function __construct(
        public ?AmendmentSectionType $type = null,
        public ?string $title = null,
        public ?string $html = null,
    ) {
    }

    public static function fromEntity(AmendmentSectionEntity $section, ?string $titlePrefix = null): self
    {
        $sectionType = $section->getSectionType();
        if ($sectionType instanceof TextSimple) {
            $html = $sectionType->getAmendmentPlainHtml(true);
        } else {
            $html = $sectionType->getAmendmentPlainHtml();
        }
        if ($html) {
            $html = '<div class="text motionTextFormattings textOrig">' . $html . '</div>';
        }

        $title = $section->getSettings()->title;
        if ($titlePrefix !== null) {
            $title = $titlePrefix . ': ' . $title;
        }

        return new self(
            type: AmendmentSectionType::from(ISectionType::typeIdToApi($section->getSettings()->type)),
            title: $title,
            html: $html,
        );
    }
}
