<?php

namespace app\models\settings;

class LaTeX
{
    /** @var string */
    public $templateFile;
    public $assetRoot;
    public $language = 'ngerman'; // english

    public $title;
    public $titlePrefix;
    public $titleLong;
    public $author;
    public $introductionBig;
    public $introductionSmall;
    public $motionDataTable;
    public $text;
}
