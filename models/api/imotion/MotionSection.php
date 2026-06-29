<?php

declare(strict_types=1);

namespace app\models\api\imotion;

use app\models\db\MotionSection as MotionSectionEntity;
use app\models\sectionTypes\TextSimple;

class MotionSection
{
    public function __construct(
        public ?MotionSectionType $type = null,
        public ?string $title = null,
        public ?string $html = null,
        public ?bool $layoutRight = null,
    ) {
    }

    public static function fromEntity(MotionSectionEntity $section, bool $lineNumbers): self
    {
        $sectionType = $section->getSectionType();
        if ($sectionType instanceof TextSimple && $lineNumbers) {
            $html = $sectionType->getMotionPlainHtmlWithLineNumbers();
        } else {
            $html = $sectionType->getMotionPlainHtml();
            if ($html) {
                $html = '<div class="text motionTextFormattings textOrig">' . $html . '</div>';
            }
        }

        return new self(
            type: MotionSectionType::from(\app\models\sectionTypes\ISectionType::typeIdToApi($section->getSettings()->type)),
            title: $section->getSettings()->title,
            html: $html,
            layoutRight: $section->isLayoutRight(),
        );
    }
}
