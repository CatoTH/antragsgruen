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
}
