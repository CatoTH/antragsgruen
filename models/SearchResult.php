<?php

namespace app\models;

class SearchResult
{
    public const TYPE_MOTION            = 0;
    public const TYPE_AMENDMENT         = 1;
    public const TYPE_MOTION_COMMENT    = 2;
    public const TYPE_AMENDMENT_COMMENT = 3;


    public string $id;
    public string $typeTitle;
    public string $title;
    public string $link;
    public string $info;
    public int $type;
    public string $snippet;
}
