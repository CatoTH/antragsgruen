<?php

declare(strict_types=1);

namespace app\models\api\imotion;

enum MotionSectionType: string
{
    case TITLE = 'Title';
    case TEXTSIMPLE = 'TextSimple';
    case TEXTHTML = 'TextHTML';
    case IMAGE = 'Image';
    case TABULARDATA = 'TabularData';
    case PDFATTACHMENT = 'PDFAttachment';
    case PDFALTERNATIVE = 'PDFAlternative';
    case VIDEOEMBED = 'VideoEmbed';
    case CHOICE = 'Choice';
}
