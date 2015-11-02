<?php

namespace app\models\sitePresets;

use app\models\amendmentNumbering\ByLine;
use app\models\db\Consultation;
use app\models\db\ConsultationMotionType;
use app\models\db\Site;
use app\models\settings\Consultation as ConsultationSettings;
use app\models\initiatorForms\IInitiatorForm;
use app\models\policies\IPolicy;

class BDK implements ISitePreset
{
    use MotionTrait;

    /** @var ConsultationMotionType */
    private $typeMotion;


    /**
     * @return string
     */
    public static function getTitle()
    {
        return 'BDK';
    }

    /**
     * @return string
     */
    public static function getDescription()
    {
        return 'Nur Anträge, min. 20 AntragstallerInnen oder eine Organisation, ' .
        'Grünes CI, nur Wurzelwerk-Accounts';
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
        $consultation->wordingBase        = 'de-parteitag';
        $consultation->amendmentNumbering = ByLine::getID();

        $settings                      = $consultation->getSettings();
        $settings->lineNumberingGlobal = false;
        $settings->lineLength          = 92;
        $settings->screeningMotions    = true;
        $settings->screeningAmendments = true;
        $settings->startLayoutType     = ConsultationSettings::START_LAYOUT_AGENDA_LONG;
        $consultation->setSettings($settings);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param Site $site
     */
    public function setSiteSettings(Site $site)
    {
        $settings               = $site->getSettings();
        $settings->siteLayout   = 'layout-gruenes-ci';
        $settings->loginMethods = [\app\models\settings\Site::LOGIN_WURZELWERK];
        $site->setSettings($settings);
    }

    /**
     * @param Consultation $consultation
     * @return ConsultationMotionType
     */
    public function doCreateMotionType(Consultation $consultation)
    {
        $type                              = new ConsultationMotionType();
        $type->consultationId              = $consultation->id;
        $type->titleSingular               = 'Antrag';
        $type->titlePlural                 = 'Anträge';
        $type->createTitle                 = 'Antrag stellen';
        $type->position                    = 0;
        $type->pdfLayout                   = 1;
        $type->texTemplateId               = 1;
        $type->policyMotions               = IPolicy::POLICY_LOGGED_IN;
        $type->policyAmendments            = IPolicy::POLICY_LOGGED_IN;
        $type->policyComments              = IPolicy::POLICY_LOGGED_IN;
        $type->policySupport               = IPolicy::POLICY_NOBODY;
        $type->contactPhone                = ConsultationMotionType::CONTACT_OPTIONAL;
        $type->contactEmail                = ConsultationMotionType::CONTACT_REQUIRED;
        $type->initiatorForm               = IInitiatorForm::WITH_SUPPORTER;
        $type->initiatorFormSettings       = json_encode([
            'minSupporters'               => 19,
            'supportersHaveOrganizations' => true,
        ]);
        $type->amendmentMultipleParagraphs = 1;
        $type->save();

        return $type;
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
