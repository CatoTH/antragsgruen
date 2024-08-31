<?php

declare(strict_types=1);

namespace app\plugins\gruene_bw_pdf;

use app\views\pdfLayouts\IHtmlToPdfLayout;

class HtmlToPdfLayout implements IHtmlToPdfLayout
{
    public function getAbsoluteHtmlTemplateLocation(): ?string
    {
        return __DIR__ . '/application.html';
    }

    public function getAbsoluteCssLocation(): ?string
    {
        return __DIR__ . '/application.css';
    }
}
