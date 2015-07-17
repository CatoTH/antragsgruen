<?php

namespace app\models;

class SearchResult
{
    const TYPE_MOTION            = 0;
    const TYPE_AMENDMENT         = 1;
    const TYPE_MOTION_COMMENT    = 2;
    const TYPE_AMENDMENT_COMMENT = 3;


    /** @var string */
    public $id;
    public $typeTitle;
    public $title;
    public $link;
    public $info;
    public $type;
    public $snippet;
}
