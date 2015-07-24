<?php

namespace app\models\initiatorForms;

use app\models\db\ConsultationMotionType;

class OnlyInitiator extends DefaultFormBase
{
    /**
     * @return string
     */
    public static function getTitle()
    {
        return 'Nur die AntragstellerIn';
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
    public static function hasSupporters()
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
