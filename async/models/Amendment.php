<?php

namespace app\async\models;

use app\models\db\Consultation;

class Amendment extends TransferrableChannelObject
{
    public $id;
    public $consultationId;
    public $motionId;
    public $motionSlug;
    public $motionTitle;
    public $motionTitlePrefix;
    public $titlePrefix;
    public $status;
    public $statusString;
    public $statusFormatted;
    public $initiators;
    public $dateCreation;

    /**
     * @param \app\models\db\Amendment $amendment
     * @return Amendment
     * @throws \Exception
     */
    public static function createFromDbObject(\app\models\db\Amendment $amendment)
    {
        $motion = $amendment->getMyMotion();

        $object                    = new Amendment('');
        $object->id                = IntVal($amendment->id);
        $object->consultationId    = IntVal($motion->consultationId);
        $object->motionId          = IntVal($motion->id);
        $object->motionSlug        = $motion->slug;
        $object->motionTitle       = $motion->title;
        $object->motionTitlePrefix = $motion->titlePrefix;
        $object->titlePrefix       = $amendment->titlePrefix;
        $object->status            = IntVal($amendment->status);
        $object->statusString      = $amendment->statusString;
        $object->statusFormatted   = $motion->getFormattedStatus();
        $object->dateCreation      = $motion->dateCreation;
        $object->initiators        = [];
        foreach ($motion->getInitiators() as $initiator) {
            $object->initiators[] = Person::createFromDbMotionObject($initiator);
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
            foreach ($motion->amendments as $amendment) {
                try {
                    $data[] = Amendment::createFromDbObject($amendment);
                } catch (\Exception $e) {
                }
            }
        }
        return $data;
    }
}
