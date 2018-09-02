<?php

namespace app\components\latex;

class Content
{
    public $template;
    public $author;
    public $title;
    public $titlePrefix       = '';
    public $titleLong;
    public $introductionBig;
    public $introductionSmall = '';
    public $motionDataTable   = '';
    public $textMain          = '';
    public $textRight         = '';
    public $imageData         = [];
    public $lineLength;
}
