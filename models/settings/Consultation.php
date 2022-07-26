<?php

namespace app\models\settings;

use app\models\exceptions\Internal;

class Consultation implements \JsonSerializable
{
    use JsonConfigTrait;

    const START_LAYOUT_STD = 0;
    const START_LAYOUT_TAGS = 2;
    const START_LAYOUT_AGENDA = 3;
    const START_LAYOUT_AGENDA_LONG = 4;
    const START_LAYOUT_AGENDA_HIDE_AMEND = 5;
    const START_LAYOUT_DISCUSSION_TAGS = 6;

    const ROBOTS_NONE = 0;
    const ROBOTS_ONLY_HOME = 1;
    const ROBOTS_ALL = 2;

    const MOTIONDATA_ALL = 0;
    const MOTIONDATA_MINI = 1;
    const MOTIONDATA_NONE = 2;

    // SETTINGS WITH TEST CASES

    public bool $maintenanceMode = false;
    public bool $screeningMotions = false;
    public bool $screeningAmendments = false;
    public bool $lineNumberingGlobal = false;
    public bool $iniatorsMayEdit = false;
    public bool $hideTitlePrefix = false;
    public bool $commentNeedsEmail = false;
    public bool $screeningComments = false;
    public bool $initiatorConfirmEmails = false;
    public bool $adminsMayEdit = true;
    public bool $editorialAmendments = true;
    public bool $globalAlternatives = true;
    public bool $proposalProcedurePage = false;
    public bool $collectingPage = false;
    public bool $sidebarNewMotions = true;
    public bool $forceLogin = false;
    public bool $managedUserAccounts = false;
    public bool $amendmentBookmarksWithNames = false;
    public bool $hasSpeechLists = false;
    public bool $speechRequiresLogin = false;
    public bool $allowMultipleTags = false;
    public bool $amendmentsHaveTags = false;
    public bool $openslidesExportEnabled = false;

    public ?int $forceMotion = null;

    public ?string $accessPwd = null;
    public ?string $translationService = null;

    /** @var null|string[] */
    public ?array $organisations = null;
    /** @var null|string[] */
    public ?array $speechListSubqueues = [];

    // SETTINGS WITHOUT TEST CASES

    public bool $commentsSupportable = false;
    public bool $screeningMotionsShown = false;
    public bool $odtExportHasLineNumers = true;
    public bool $pProcedureExpandAll = true; // If false: only show max. 1 section in the internal proposed procedure
    public bool $adminListFilerByMotion = false; // If true: the admin list is filtered by motion. To be activated manually.

    public int $lineLength = 80;
    public int $startLayoutType = 0;
    public int $robotsPolicy = 1;
    public int $motiondataMode = 0;
    public int $discourseCategoryId = 0;

    public array $adminListAdditionalFields = [];

    public ?string $logoUrl = null;

    public ?string $emailReplyTo = null;
    public ?string $emailFromName = null;

    public bool $documentPage = false;
    public bool $votingPage = false;
    public bool $speechPage = false;

    /**
     * @return string[]
     */
    public static function getStartLayouts(): array
    {
        return [
            static::START_LAYOUT_STD               => \Yii::t('structure', 'home_layout_std'),
            static::START_LAYOUT_TAGS              => \Yii::t('structure', 'home_layout_tags'),
            static::START_LAYOUT_AGENDA            => \Yii::t('structure', 'home_layout_agenda'),
            static::START_LAYOUT_AGENDA_LONG       => \Yii::t('structure', 'home_layout_agenda_long'),
            static::START_LAYOUT_AGENDA_HIDE_AMEND => \Yii::t('structure', 'home_layout_agenda_hide_amend'),
            static::START_LAYOUT_DISCUSSION_TAGS   => \Yii::t('structure', 'home_layout_discussion_tags'),
        ];
    }

    /**
     * @return string[]
     */
    public static function getMotiondataModes(): array
    {
        return [
            static::MOTIONDATA_ALL  => \Yii::t('structure', 'motiondata_all'),
            static::MOTIONDATA_MINI => \Yii::t('structure', 'motiondata_mini'),
            static::MOTIONDATA_NONE => \Yii::t('structure', 'motiondata_none'),
        ];
    }

    /**
     * @return string[]
     */
    public static function getRobotPolicies(): array
    {
        return [
            static::ROBOTS_NONE      => \Yii::t('structure', 'robots_policy_none'),
            static::ROBOTS_ONLY_HOME => \Yii::t('structure', 'robots_policy_only_home'),
            static::ROBOTS_ALL       => \Yii::t('structure', 'robots_policy_all'),
        ];
    }

    public function setOrganisationsFromInput(?array $organisationField): void
    {
        if ($organisationField) {
            $this->organisations = $organisationField;
        } else {
            $this->organisations = null;
        }
    }

    public function getStartLayoutView(): string
    {
        switch ($this->startLayoutType) {
            case Consultation::START_LAYOUT_STD:
                return 'index_layout_std';
            case Consultation::START_LAYOUT_TAGS:
                return 'index_layout_tags';
            case Consultation::START_LAYOUT_AGENDA_LONG:
            case Consultation::START_LAYOUT_AGENDA_HIDE_AMEND:
            case Consultation::START_LAYOUT_AGENDA:
                return 'index_layout_agenda';
            case Consultation::START_LAYOUT_DISCUSSION_TAGS:
                return 'index_layout_discussion_tags';
            default:
                throw new Internal('Unknown layout: ' . $this->startLayoutType);
        }
    }

    public function getConsultationSidebar(): ?string
    {
        return '@app/views/consultation/sidebar';
    }

    /**
     * @return null|string|Layout
     */
    public function getSpecializedLayoutClass(): ?string
    {
        return null;
    }

    public function saveConsultationForm(array $formdata, array $affectedFields): void
    {
        if (in_array('forceMotion', $affectedFields)) {
            if (isset($formdata['singleMotionMode'])) {
                $formdata['forceMotion'] = (int)$formdata['forceMotion'];
            } else {
                $formdata['forceMotion'] = null;
            }
        }
        $this->saveForm($formdata, $affectedFields);
    }
}
