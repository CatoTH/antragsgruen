<?php

declare(strict_types=1);

namespace app\components\html2pdf;

use app\views\pdfLayouts\IHtmlToPdfLayout;

class Content
{
    public ?IHtmlToPdfLayout $layout;
    public string $author;
    public string $title;
    public string $titlePrefix = '';
    public string $titleLong;
    public string $titleRaw = '';
    public string $introductionBig;
    public string $introductionSmall = '';
    public string $motionDataTable = '';
    public string $textMain = '';
    public string $textRight = '';
    public array $imageData = [];
    public int $lineLength;
    public string $agendaItemName = '';
    public string $publicationDate = '';
    public string $typeName = '';
    public ?array $logoData = null;
}
