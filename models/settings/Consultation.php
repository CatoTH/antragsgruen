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
    public $screeningMotions = false;
    public $screeningAmendments = false;
    public $lineNumberingGlobal = false;
    public $iniatorsMayEdit = false;
    public $hideTitlePrefix = false;
    public $showFeeds = true; // @TODO Obsolete since 2019-09. Remove sometimes in the future.
    public $commentNeedsEmail = false;
    public $screeningComments = false;
    public $initiatorConfirmEmails = false;
    public $adminsMayEdit = true;
    public $editorialAmendments = true;
    public $globalAlternatives = true;
    public $proposalProcedurePage = false;
    public $collectingPage = false;
    public $sidebarNewMotions = true;
    public $forceLogin = false;
    public $managedUserAccounts = false;
    public $amendmentBookmarksWithNames = false;

    /** @var null|int */
    public $forceMotion = null;

    /** @var null|string */
    public $accessPwd = null;
    public $translationService = null;

    /** @var null|string[] */
    public $organisations = null;

    // SETTINGS WITHOUT TEST CASES

    /** @var bool */
    public $commentsSupportable = false;
    public $screeningMotionsShown = false;
    public $initiatorsMayReject = false;
    public $allowMultipleTags = false;
    public $odtExportHasLineNumers = true;
    public $pProcedureExpandAll = true; // If false: only show max. 1 section in the internal proposed procedure

    /** @var int */
    public $lineLength = 80;
    public $startLayoutType = 0;
    public $robotsPolicy = 1;
    public $motiondataMode = 0;
    public $discourseCategoryId = 0;

    /** @var null|string */
    public $logoUrl = null;

    /** @var null|string */
    public $emailReplyTo = null;
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

    public function setOrganisationsFromInput(?string $organisationField): void
    {
        if ($organisationField) {
            $arr                 = json_decode($organisationField, true);
            $this->organisations = [];
            foreach ($arr as $orga) {
                $this->organisations[] = trim($orga);
            }
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
}
