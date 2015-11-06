<?php

namespace app\models\sitePresets;

use app\models\db\Consultation;
use app\models\db\ConsultationMotionType;
use app\models\db\Site;

class Manifesto implements ISitePreset
{
    use ManifestoTrait;

    /** @var ConsultationMotionType */
    private $typeMotion;


    /**
     * @return string
     */
    public static function getTitle()
    {
        return \Yii::t('structure', 'preset_manifesto_name');
    }

    /**
     * @return string
     */
    public static function getDescription()
    {
        return \Yii::t('structure', 'preset_manifesto_desc');
    }

    /**
     * @return array
     */
    public static function getDetailDefaults()
    {
        return [
            'comments'   => true,
            'amendments' => true,
            'openNow'    => false,
        ];
    }

    /**
     * @param Consultation $consultation
     */
    public function setConsultationSettings(Consultation $consultation)
    {
        $consultation->wordingBase = 'de-programm';

        $settings                      = $consultation->getSettings();
        $settings->lineNumberingGlobal = false;
        $settings->screeningMotions    = false; // Only Admins
        $settings->screeningAmendments = true;
        $settings->screeningComments   = false;
        $settings->hideTitlePrefix     = true;
        $consultation->setSettings($settings);
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
        static::doCreateManifestoSections($this->typeMotion);
        $this->typeMotion->refresh();
    }

    /**
     * @param Consultation $consultation
     */
    public function createMotionTypes(Consultation $consultation)
    {
        $this->typeMotion = static::doCreateManifestoType($consultation);
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
