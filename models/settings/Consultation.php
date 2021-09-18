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

    /** @var bool */
    public $maintenanceMode = false;
    /** @var bool */
    public $screeningMotions = false;
    /** @var bool */
    public $screeningAmendments = false;
    /** @var bool */
    public $lineNumberingGlobal = false;
    /** @var bool */
    public $iniatorsMayEdit = false;
    /** @var bool */
    public $hideTitlePrefix = false;
    /** @var bool */
    public $showFeeds = true; // @TODO Obsolete since 2019-09. Remove sometimes in the future.
    /** @var bool */
    public $commentNeedsEmail = false;
    /** @var bool */
    public $screeningComments = false;
    /** @var bool */
    public $initiatorConfirmEmails = false;
    /** @var bool */
    public $adminsMayEdit = true;
    /** @var bool */
    public $editorialAmendments = true;
    /** @var bool */
    public $globalAlternatives = true;
    /** @var bool */
    public $proposalProcedurePage = false;
    /** @var bool */
    public $collectingPage = false;
    /** @var bool */
    public $sidebarNewMotions = true;
    /** @var bool */
    public $forceLogin = false;
    /** @var bool */
    public $managedUserAccounts = false;
    /** @var bool */
    public $amendmentBookmarksWithNames = false;
    /** @var bool */
    public $hasSpeechLists = false;
    /** @var bool */
    public $speechRequiresLogin = false;
    /** @var bool */
    public $allowMultipleTags = false;
    /** @var bool */
    public $amendmentsHaveTags = false;
    /** @var bool */
    public $openslidesExportEnabled = false;

    /** @var null|int */
    public $forceMotion = null;

    /** @var null|string */
    public $accessPwd = null;
    /** @var null|string */
    public $translationService = null;

    /** @var null|string[] */
    public $organisations = null;
    /** @var null|string[] */
    public $speechListSubqueues = [];

    // SETTINGS WITHOUT TEST CASES

    /** @var bool */
    public $commentsSupportable = false;
    /** @var bool */
    public $screeningMotionsShown = false;
    /** @var bool */
    public $initiatorsMayReject = false;
    /** @var bool */
    public $odtExportHasLineNumers = true;
    /** @var bool */
    public $pProcedureExpandAll = true; // If false: only show max. 1 section in the internal proposed procedure
    /** @var bool */
    public $adminListFilerByMotion = false; // If true: the admin list is filtered by motion. To be activated manually.

    /** @var int */
    public $lineLength = 80;
    /** @var int */
    public $startLayoutType = 0;
    /** @var int */
    public $robotsPolicy = 1;
    /** @var int */
    public $motiondataMode = 0;
    /** @var int */
    public $discourseCategoryId = 0;

    /** @var string[] */
    public $adminListAdditionalFields = [];

    /** @var null|string */
    public $logoUrl = null;

    /** @var null|string */
    public $emailReplyTo = null;
    /** @var null|string */
    public $emailFromName = null;

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
            case Consultation::START_LAYOUT_AGENDA:
                return 'index_layout_agenda';
            case Consultation::START_LAYOUT_AGENDA_LONG:
                return 'index_layout_agenda';
            case Consultation::START_LAYOUT_AGENDA_HIDE_AMEND:
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
