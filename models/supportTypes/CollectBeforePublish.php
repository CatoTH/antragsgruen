<?php

namespace app\models\supportTypes;

use app\models\db\ConsultationMotionType;
use app\models\db\User;

class CollectBeforePublish extends DefaultTypeBase
{
    /**
     * @return string
     */
    public static function getTitle()
    {
        return \Yii::t('structure', 'supp_collect_before');
    }

    /** @var int */
    protected $minSupporters = 1;

    /** @var bool */
    protected $skipForOrganizations = true;

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
        if (isset($json['allowMoreSupporters'])) {
            $this->allowMoreSupporters = ($json['allowMoreSupporters'] == true);
        }
        if (isset($json['hasOrganizations'])) {
            $this->hasOrganizations = ($json['hasOrganizations'] == true);
        }
        if (isset($json['skipForOrganizations'])) {
            $this->skipForOrganizations = $json['skipForOrganizations'];
        }
    }

    /**
     * @return string|null
     */
    public function getSettings()
    {
        return json_encode([
            'minSupporters'        => $this->minSupporters,
            'allowMoreSupporters'  => $this->allowMoreSupporters,
            'skipForOrganizations' => $this->skipForOrganizations,
            'hasOrganizations'     => $this->hasOrganizations,
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
        if (isset($settings['skipForOrganizations'])) {
            $this->skipForOrganizations = $settings['skipForOrganizations'];
        }
        $this->hasOrganizations    = isset($settings['hasOrganizations']);
        $this->allowMoreSupporters = isset($settings['allowMoreSupporters']);
    }

    /**
     * @return bool
     */
    public static function hasInitiatorGivenSupporters()
    {
        return false;
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
        return true;
    }

    /**
     * @return bool
     */
    public function hasFullTextSupporterField()
    {
        return User::currentUserHasPrivilege($this->motionType->getConsultation(), User::PRIVILEGE_ANY);
    }

    /**
     * @return bool
     */
    public function getSkipForOrganizations()
    {
        return $this->skipForOrganizations;
    }
}
