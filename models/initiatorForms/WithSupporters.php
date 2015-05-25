<?php

namespace app\models\initiatorForms;

use app\models\db\ConsultationMotionType;

class WithSupporters extends DefaultFormBase
{
    /**
     * @return string
     */
    public static function getTitle()
    {
        return 'Mit UnterstützerInnen';
    }

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
     * @return string|null
     */
    public function getSettings()
    {
        return json_encode([
            'minSupporters' => $this->minSupporters
        ]);
    }

    /**
     * @param array $settings
     */
    public function setSettings($settings)
    {
        if (isset($settings['minSupporters']) && $settings['minSupporters'] >= 0) {
            $this->minSupporters = IntVal($settings['minSupporters']);
        }
    }

    /**
     * @return bool
     */
    public static function hasSupporters()
    {
        return true;
    }

    /**
     * @return int
     */
    public function getMinNumberOfSupporters()
    {
        return $this->minSupporters;
    }

    /**
     * @param int $num
     */
    public function setMinNumberOfSupporters($num)
    {
        $this->minSupporters = $num;
    }

    /**
     * @return bool
     */
    public function hasFullTextSupporterField()
    {
        return true;
    }
}
