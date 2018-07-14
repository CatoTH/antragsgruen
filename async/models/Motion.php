<?php

namespace app\async\models;

class Motion extends TransferrableChannelObject
{
    public $id;
    public $consultationId;
    public $titlePrefix;
    public $title;
    public $status;
    public $statusString;
    public $statusFormatted;
    public $initiators;
    public $dateCreation;

    /**
     * @param \app\models\db\Motion $motion
     * @return Motion
     * @throws \Exception
     */
    public static function createFromDbObject(\app\models\db\Motion $motion)
    {
        $object                  = new Motion('');
        $object->id              = $motion->id;
        $object->consultationId  = $motion->consultationId;
        $object->titlePrefix     = $motion->titlePrefix;
        $object->title           = $motion->title;
        $object->status          = $motion->status;
        $object->statusString    = $motion->statusString;
        $object->statusFormatted = $motion->getFormattedStatus();
        $object->dateCreation    = $motion->dateCreation;
        return $object;
    }

    /** @return int */
    public function getConsultation()
    {
        return $this->consultationId;
    }

    /** @return string */
    public function getPublishChannel()
    {
        return 'motions';
    }
}
