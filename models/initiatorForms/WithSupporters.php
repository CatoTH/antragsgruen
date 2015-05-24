<?php

namespace app\models\initiatorForms;

use app\models\db\ConsultationMotionType;

class WithSupporters extends DefaultFormBase
{
    /** @var int */
    protected $minSupporters = 1;

    /**
     * @param ConsultationMotionType $motionType
     * @param string $settings
     */
    public function __construct(ConsultationMotionType $motionType, $settings)
    {
        parent::__construct($motionType);
        $json = [];
        try {
            if ($settings != '') {
                $json = json_decode($settings, true);
            }
        } catch (\Exception $e) {
        }

        if (isset($json['minSupporters'])) {
            $this->minSupporters = IntVal($json['minSupporters']);
        }
    }

    /**
     * @return bool
     */
    protected function hasSupporters()
    {
        return true;
    }

    /**
     * @return int
     */
    protected function getMinNumberOfSupporters()
    {
        return $this->minSupporters;
    }

    /**
     * @return bool
     */
    protected function hasFullTextSupporterField()
    {
        return true;
    }
}
