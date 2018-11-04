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
    public $supporters;
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
        foreach ($amendment->getInitiators() as $initiator) {
            $object->initiators[] = Person::createFromDbIMotionObject($initiator);
        }
        $object->supporters = [];
        foreach ($amendment->getSupporters() as $supporter) {
            $object->supporters[] = Person::createFromDbIMotionObject($supporter);
        }

        $object->subdomain = $motion->getMyConsultation()->site->subdomain;
        $object->path      = $motion->getMyConsultation()->urlPath;

        return $object;
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
    public static function getCollection(Consultation $consultation)
    {
        $data = [];
        foreach ($consultation->motions as $motion) {
            if (in_array($motion->status, $motion->getStatusesInvisibleForAdmins())) {
                continue;
            }
            foreach ($motion->amendments as $amendment) {
                if (in_array($amendment->status, $amendment->getStatusesInvisibleForAdmins())) {
                    continue;
                }
                try {
                    $data[] = Amendment::createFromDbObject($amendment);
                } catch (\Exception $e) {
                }
            }
        }
        return $data;
    }
}
