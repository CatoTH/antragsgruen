<?php

namespace app\models\sitePresets;

use app\models\db\Consultation;
use app\models\db\ConsultationMotionType;
use app\models\db\Site;

class Motions implements ISitePreset
{
    use MotionTrait;

    /** @var ConsultationMotionType */
    private $typeMotion;


    /**
     * @return string
     */
    public static function getTitle()
    {
        return \Yii::t('structure', 'preset_motions_name');
    }

    /**
     * @return string
     */
    public static function getDescription()
    {
        return \Yii::t('structure', 'preset_motions_desc');
    }

    /**
     * @return array
     */
    public static function getDetailDefaults()
    {
        return [
            'comments'   => true,
            'amendments' => true,
            'openNow'    => true,
        ];
    }

    /**
     * @param Consultation $consultation
     */
    public function setConsultationSettings(Consultation $consultation)
    {
        $consultation->wordingBase = 'de-parteitag';

        $settings                      = $consultation->getSettings();
        $settings->lineNumberingGlobal = false;
        $settings->screeningMotions    = true;
        $settings->screeningAmendments = true;
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
        static::doCreateMotionSections($this->typeMotion);
        $this->typeMotion->refresh();
    }

    /**
     * @param Consultation $consultation
     */
    public function createMotionTypes(Consultation $consultation)
    {
        $this->typeMotion = static::doCreateMotionType($consultation);
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
