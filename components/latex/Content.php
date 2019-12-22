<?php

namespace app\components\latex;

class Content
{
    public $template;
    public $author;
    public $title;
    public $titlePrefix       = '';
    public $titleLong;
    public $titleRaw          = '';
    public $introductionBig;
    public $introductionSmall = '';
    public $motionDataTable   = '';
    public $textMain          = '';
    public $textRight         = '';
    public $imageData         = [];
    public $attachedPdfs      = [];
    public $lineLength;
    public $agendaItemName    = '';
    public $publicationDate   = '';
    public $typeName          = '';
    public $logoData          = null;
}
