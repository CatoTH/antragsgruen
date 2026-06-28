<?php

declare(strict_types=1);

namespace app\models\api\motionType;

enum MotionTypeSectionDefinitionType: string
{
    case TITLE = 'Title';
    case TEXTSIMPLE = 'TextSimple';
    case TEXTHTML = 'TextHTML';
    case TEXTEDITORIAL = 'TextEditorial';
    case IMAGE = 'Image';
    case TABULARDATA = 'TabularData';
    case PDFATTACHMENT = 'PDFAttachment';
    case PDFALTERNATIVE = 'PDFAlternative';
    case VIDEOEMBED = 'VideoEmbed';
    case CHOICE = 'Choice';

    public static function fromTypeId(int $typeId): self
    {
        return match ($typeId) {
            \app\models\sectionTypes\ISectionType::TYPE_TITLE => self::TITLE,
            \app\models\sectionTypes\ISectionType::TYPE_TEXT_SIMPLE => self::TEXTSIMPLE,
            \app\models\sectionTypes\ISectionType::TYPE_TEXT_HTML => self::TEXTHTML,
            \app\models\sectionTypes\ISectionType::TYPE_TEXT_EDITORIAL => self::TEXTEDITORIAL,
            \app\models\sectionTypes\ISectionType::TYPE_IMAGE => self::IMAGE,
            \app\models\sectionTypes\ISectionType::TYPE_TABULAR => self::TABULARDATA,
            \app\models\sectionTypes\ISectionType::TYPE_PDF_ATTACHMENT => self::PDFATTACHMENT,
            \app\models\sectionTypes\ISectionType::TYPE_PDF_ALTERNATIVE => self::PDFALTERNATIVE,
            \app\models\sectionTypes\ISectionType::TYPE_VIDEO_EMBED => self::VIDEOEMBED,
            \app\models\sectionTypes\ISectionType::TYPE_CHOICE => self::CHOICE,
            default => throw new \InvalidArgumentException('Unknown section type id: ' . $typeId),
        };
    }
}
