<?php

namespace app\models\supportTypes;

use app\models\db\ConsultationMotionType;

class OnlyInitiator extends DefaultTypeBase
{
    /**
     * @return string
     */
    public static function getTitle()
    {
        return \Yii::t('structure', 'supp_only_initiators');
    }

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

        if (isset($json['hasOrganizations'])) {
            $this->hasOrganizations = ($json['hasOrganizations'] == true);
        }
    }

    /**
     * @return bool
     */
    public static function hasInitiatorGivenSupporters()
    {
        return false;
    }

    /**
     * @return string|null
     */
    public function getSettings()
    {
        return json_encode([
            'hasOrganizations' => $this->hasOrganizations
        ]);
    }

    /**
     * @param array $settings
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setSettings($settings)
    {
        $this->hasOrganizations = (isset($settings['hasOrganizations']));
    }
}
