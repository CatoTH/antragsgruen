<?php

namespace app\models\sitePresets;

use app\models\db\Consultation;
use app\models\db\ConsultationMotionType;
use app\models\db\Site;

class Elections implements ISitePreset
{
    use ApplicationTrait;

    /** @var ConsultationMotionType */
    private $typeApplication;

    /**
     * @return string
     */
    public static function getTitle()
    {
        return \Yii::t('structure', 'preset_election_name');
    }

    /**
     * @return string
     */
    public static function getDescription()
    {
        return \Yii::t('structure', 'preset_election_desc');
    }

    /**
     * @return array
     */
    public static function getDetailDefaults()
    {
        return [
            'comments'   => false,
            'amendments' => false,
            'openNow'    => true
        ];
    }

    /**
     * @param Consultation $consultation
     */
    public function setConsultationSettings(Consultation $consultation)
    {
        $settings                      = $consultation->getSettings();
        $settings->lineNumberingGlobal = false;
        $settings->screeningMotions    = false;
        $settings->screeningAmendments = false;
        $consultation->setSettings($settings);

        $consultation->wordingBase = 'de-bewerbung';
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param Site $site
     */
    public function setSiteSettings(Site $site)
    {

    }

    /**
     * @param Consultation $consultation
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function createMotionSections(Consultation $consultation)
    {
        static::doCreateApplicationSections($this->typeApplication);
        $this->typeApplication->refresh();
    }

    /**
     * @param Consultation $consultation
     */
    public function createMotionTypes(Consultation $consultation)
    {
        $this->typeApplication = static::doCreateApplicationType($consultation);
        $consultation->refresh();
    }

    /**
     * @param Consultation $consultation
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function createAgenda(Consultation $consultation)
    {
    }
}
