<?php

namespace app\models\sitePresets;

use app\models\db\Consultation;
use app\models\db\ConsultationAgendaItem;
use app\models\db\ConsultationMotionType;
use app\models\db\Site;

class PartyCongress implements ISitePreset
{
    use ApplicationTrait;
    use MotionTrait;

    /** @var ConsultationMotionType */
    private $typeApplication;
    private $typeMotion;

    /**
     * @return string
     */
    public static function getTitle()
    {
        return \Yii::t('structure', 'preset_party_name');
    }

    /**
     * @return string
     */
    public static function getDescription()
    {
        return \Yii::t('structure', 'preset_party_desc');
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
        $settings                      = $consultation->getSettings();
        $settings->lineNumberingGlobal = false;
        $settings->screeningMotions    = true;
        $settings->screeningAmendments = true;
        $settings->startLayoutType     = \app\models\settings\Consultation::START_LAYOUT_AGENDA;
        $consultation->setSettings($settings);

        $consultation->wordingBase = 'de-parteitag';
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
        static::doCreateMotionSections($this->typeMotion);
    }

    /**
     * @param Consultation $consultation
     */
    public function createMotionTypes(Consultation $consultation)
    {
        $this->typeMotion      = static::doCreateMotionType($consultation);
        $this->typeApplication = static::doCreateApplicationType($consultation);
        $consultation->refresh();
    }

    /**
     * @param Consultation $consultation
     */
    public function createAgenda(Consultation $consultation)
    {
        $item                 = new ConsultationAgendaItem();
        $item->consultationId = $consultation->id;
        $item->parentItemId   = null;
        $item->position       = 0;
        $item->code           = '0.';
        $item->title          = \Yii::t('structure', 'preset_party_top');
        $item->save();

        $wahlItem                 = new ConsultationAgendaItem();
        $wahlItem->consultationId = $consultation->id;
        $wahlItem->parentItemId   = null;
        $wahlItem->position       = 1;
        $wahlItem->code           = '#';
        $wahlItem->title          = \Yii::t('structure', 'preset_party_elections');
        $wahlItem->save();

        $item                 = new ConsultationAgendaItem();
        $item->consultationId = $consultation->id;
        $item->parentItemId   = $wahlItem->id;
        $item->position       = 0;
        $item->code           = '#';
        $item->title          = \Yii::t('structure', 'preset_party_1leader');
        $item->motionTypeId   = $this->typeApplication->id;
        $item->save();

        $item                 = new ConsultationAgendaItem();
        $item->consultationId = $consultation->id;
        $item->parentItemId   = $wahlItem->id;
        $item->position       = 1;
        $item->code           = '#';
        $item->title          = \Yii::t('structure', 'preset_party_2leader');
        $item->motionTypeId   = $this->typeApplication->id;
        $item->save();

        $item                 = new ConsultationAgendaItem();
        $item->consultationId = $consultation->id;
        $item->parentItemId   = $wahlItem->id;
        $item->position       = 2;
        $item->code           = '#';
        $item->title          = \Yii::t('structure', 'preset_party_treasure');
        $item->motionTypeId   = $this->typeApplication->id;
        $item->save();

        $item                 = new ConsultationAgendaItem();
        $item->consultationId = $consultation->id;
        $item->parentItemId   = null;
        $item->position       = 2;
        $item->code           = '#';
        $item->title          = \Yii::t('structure', 'preset_party_motions');
        $item->motionTypeId   = $this->typeMotion->id;
        $item->save();

        $item                 = new ConsultationAgendaItem();
        $item->consultationId = $consultation->id;
        $item->parentItemId   = null;
        $item->position       = 3;
        $item->code           = '#';
        $item->title          = \Yii::t('structure', 'preset_party_misc');
        $item->save();
    }
}
