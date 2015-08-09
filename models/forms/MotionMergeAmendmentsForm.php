<?php

namespace app\models\forms;

use app\models\db\Motion;
use yii\base\Model;

class MotionMergeAmendmentsForm extends Model
{
    /** @var Motion */
    public $motion;

    /**
     * @param Motion $motion
     */
    public function __construct(Motion $motion)
    {
        parent::__construct();
        $this->motion = $motion;
    }

    public function saveMotion()
    {
        // @TODO
    }
}
