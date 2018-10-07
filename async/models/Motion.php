<?php

namespace app\async\models;

use app\models\db\Consultation;

class Motion extends TransferrableChannelObject
{
    public $id;
    public $consultationId;
    public $titlePrefix;
    public $title;
    public $slug;
    public $status;
    public $statusString;
    public $statusFormatted;
    public $initiators;
    public $supporters;
    public $tags;
    public $dateCreation;

    /**
     * @param \app\models\db\Motion $motion
     * @return Motion
     * @throws \Exception
     */
    public static function createFromDbObject(\app\models\db\Motion $motion)
    {
        $object                  = new Motion('');
        $object->id              = IntVal($motion->id);
        $object->consultationId  = IntVal($motion->consultationId);
        $object->titlePrefix     = $motion->titlePrefix;
        $object->title           = $motion->title;
        $object->slug            = $motion->slug;
        $object->status          = IntVal($motion->status);
        $object->statusString    = $motion->statusString;
        $object->statusFormatted = $motion->getFormattedStatus();
        $object->dateCreation    = $motion->dateCreation;
        $object->initiators      = [];
        foreach ($motion->getInitiators() as $initiator) {
            $object->initiators[] = Person::createFromDbIMotionObject($initiator);
        }
        $object->supporters      = [];
        foreach ($motion->getSupporters() as $supporter) {
            $object->supporters[] = Person::createFromDbIMotionObject($supporter);
        }
        $object->tags = [];
        foreach ($motion->tags as $tag) {
            $object->tags[] = MotionTag::createFromDbMotionObject($tag);
        }
        return $object;
    }

    /** @return int */
    public function getDomain()
    {
        return $this->consultationId;
    }

    /** @return string */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Consultation $consultation
     * @return TransferrableChannelObject[]
     */
    public static function getCollection($consultation)
    {
        $data = [];
        foreach ($consultation->motions as $motion) {
            if (in_array($motion->status, $motion->getStatusesInvisibleForAdmins())) {
                continue;
            }
            try {
                $data[] = Motion::createFromDbObject($motion);
            } catch (\Exception $e) {
            }
        }
        return $data;
    }
}
