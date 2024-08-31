<?php

declare(strict_types=1);

namespace app\views\pdfLayouts;

interface IHtmlToPdfLayout {
    public function getAbsoluteHtmlTemplateLocation(): ?string;
    public function getAbsoluteCssLocation(): ?string;
}
