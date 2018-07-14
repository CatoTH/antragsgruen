<?php

namespace app\async\models;

class Motion extends TransferrableObject
{
    public $id;
    public $consultationId;
    public $titlePrefix;
    public $title;
    public $status;
    public $statusString;
    public $initiators;
    public $create;
}
