<?php

namespace app\models\supportTypes;

use app\models\db\ConsultationMotionType;
use app\models\db\User;

class GivenByInitiator extends DefaultTypeBase
{
    /**
     * @return string
     */
    public static function getTitle()
    {
        return \Yii::t('structure', 'supp_given_by_initiator');
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
        if (isset($json['hasOrganizations'])) {
            $this->hasOrganizations = ($json['hasOrganizations'] == true);
        }
        if (isset($json['allowMoreSupporters'])) {
            $this->allowMoreSupporters = ($json['allowMoreSupporters'] == true);
        }
    }

    /**
     * @return string|null
     */
    public function getSettings()
    {
        return json_encode([
            'minSupporters'       => $this->minSupporters,
            'hasOrganizations'    => $this->hasOrganizations,
            'allowMoreSupporters' => $this->allowMoreSupporters,
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
        $this->hasOrganizations    = (isset($settings['hasOrganizations']));
        $this->allowMoreSupporters = (isset($settings['allowMoreSupporters']));
    }

    /**
     * @return bool
     */
    public static function hasInitiatorGivenSupporters()
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
    public function allowMoreSupporters()
    {
        return $this->allowMoreSupporters;
    }

    /**
     * @return bool
     */
    public static function collectSupportersBeforePublication()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function hasFullTextSupporterField()
    {
        return User::currentUserHasPrivilege($this->motionType->getConsultation(), User::PRIVILEGE_ANY);
    }
}
