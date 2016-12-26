<?php

namespace app\models\forms;

use app\models\db\Amendment;
use app\models\db\Motion;
use yii\base\Model;

class MergeSingleAmendmentForm extends Model
{
    /** @var Motion */
    public $motion;

    /** @var Amendment */
    public $mergeAmendment;

    /** @var int */
    public $mergeAmendStatus;

    /** @var array */
    public $otherAmendStati;
    public $otherAmendOverrides;
    public $paragraphs;

    /**
     * @param Amendment $amendment
     * @param int $newStatus
     * @param array $paragraphs
     * @param array $otherAmendOverrides
     * @param array $otherAmendStati
     */
    public function __construct(Amendment $amendment, $newStatus, $paragraphs, $otherAmendOverrides, $otherAmendStati)
    {
        parent::__construct();
        $this->motion              = $amendment->getMyMotion();
        $this->mergeAmendment      = $amendment;
        $this->mergeAmendStatus    = $newStatus;
        $this->paragraphs          = $paragraphs;
        $this->otherAmendStati     = $otherAmendStati;
        $this->otherAmendOverrides = $otherAmendOverrides;
    }

    /**
     * @return bool
     */
    public function checkConsistency()
    {
        var_dump($this->paragraphs);
        var_dump($this->otherAmendStati);
        var_dump($this->otherAmendOverrides);
        return false;
    }
}