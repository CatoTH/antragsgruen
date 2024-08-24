<?php

declare(strict_types=1);

namespace app\plugins\gruene_bw_pdf;

use app\plugins\ModuleBase;
use app\views\pdfLayouts\PdfLayoutDescription;

class Module extends ModuleBase
{
    public static function getProvidedPdfLayouts(array $default): array
    {
        $default[] = new PdfLayoutDescription(
            101,
            PdfLayoutDescription::RENDERER_WEASYPRINT,
            null,
            'Grüne BaWü',
            null,
            HtmlToPdfLayout::class
        );

        return $default;
    }
}
