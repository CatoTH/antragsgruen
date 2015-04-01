<?php
namespace app\models\db;

use app\models\db\MotionComment;

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
