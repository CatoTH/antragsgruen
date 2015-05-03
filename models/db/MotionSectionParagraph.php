<?php
namespace app\models\db;

class MotionSectionParagraph
{
    /** @var int */
    public $paragraphNo;

    /** @var string[] */
    public $lines;

    /** MotionComment[] */
    public $comments;

    /** @var Amendment[] */
    public $amendments;
}
