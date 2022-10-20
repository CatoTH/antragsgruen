<?php

namespace app\components\latex;

class Content
{
    public string $template;
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
    public array $attachedPdfs = [];
    public int $lineLength;
    public string $agendaItemName = '';
    public string $publicationDate = '';
    public string $typeName = '';
    public ?array $logoData = null;
    public ?string $replacingPdf = null;
}
