<?php

namespace app\models\settings;

use app\models\exceptions\Internal;

class Consultation implements \JsonSerializable
{
    use JsonConfigTrait;

    public const START_LAYOUT_STD = 0;
    public const START_LAYOUT_TAGS = 2;
    public const START_LAYOUT_AGENDA = 3;
    public const START_LAYOUT_AGENDA_LONG = 4;
    public const START_LAYOUT_AGENDA_HIDE_AMEND = 5;
    public const START_LAYOUT_DISCUSSION_TAGS = 6;

    public const START_LAYOUT_RESOLUTIONS_ABOVE = 0;
    public const START_LAYOUT_RESOLUTIONS_SEPARATE = 1; // On separate page
    public const START_LAYOUT_RESOLUTIONS_DEFAULT = 2; // On separate page - being the default page

    public const ROBOTS_NONE = 0;
    public const ROBOTS_ONLY_HOME = 1;
    public const ROBOTS_ALL = 2;

    public const MOTIONDATA_ALL = 0;
    public const MOTIONDATA_MINI = 1;
    public const MOTIONDATA_NONE = 2;

    public const DATE_FORMAT_DEFAULT = 'default';
    public const DATE_FORMAT_DMY_DOT = 'dmy-dot'; // 13.01.2022
    public const DATE_FORMAT_DMY_SLASH = 'dmy-slash'; // 13/01/2022
    public const DATE_FORMAT_MDY_SLASH = 'mdy-slash'; // 01/13/2022
    public const DATE_FORMAT_YMD_DASH = 'ymd-dash'; // 2022-01-13
    public const DATE_FORMAT_DMY_DASH = 'dmy-dash'; // 13-01-2022

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
    public bool $proposalProcedureInline = false;
    public bool $collectingPage = false;
    public bool $sidebarNewMotions = true;
    public bool $forceLogin = false;
    public bool $managedUserAccounts = false;
    public bool $amendmentBookmarksWithNames = false;
    public bool $hasSpeechLists = false;
    public bool $speechRequiresLogin = false;
    public bool $allowMultipleTags = false;
    public bool $allowUsersToSetTags = true;
    public bool $amendmentsHaveTags = false;
    public bool $openslidesExportEnabled = false;
    public bool $showPrivateNotes = true;

    public ?int $forceMotion = null;

    public ?string $accessPwd = null;
    public ?string $translationService = null;

    /** @var null|ConsultationUserOrganisation[] */
    public ?array $organisations = null;
    /** @var null|string[] */
    public ?array $speechListSubqueues = [];

    // SETTINGS WITHOUT TEST CASES

    public bool $commentsSupportable = false;
    public bool $screeningMotionsShown = false;
    public bool $obsoletedByMotionsShown = true;
    public bool $odtExportHasLineNumers = true;
    public bool $pProcedureExpandAll = true; // If false: only show max. 1 section in the internal proposed procedure
    public bool $adminListFilerByMotion = false; // If true: the admin list is filtered by motion. To be activated manually.
    public bool $showIMotionEditDate = false;
    public bool $ppEditableAfterPublication = true;
    public bool $homepageTagsList = true;
    public bool $homepageByTag = false;
    public bool $homepageDeadlineCircle = true;
    public bool $externalLinksNewWindow = false;
    public bool $motionPrevNextLinks = false;
    public bool $allowRequestingAccess = true;

    public int $lineLength = 80;
    public int $motionTitlePrefixNumMaxLen = 1;
    public int $startLayoutType = 0;
    public int $startLayoutResolutions = 0;
    public int $robotsPolicy = 1;
    public int $motiondataMode = 0;
    public int $discourseCategoryId = 0;

    public array $adminListAdditionalFields = [];

    public ?string $logoUrl = null;
    public ?string $dateFormat = null;

    public ?string $emailReplyTo = null;
    public ?string $emailFromName = null;

    public bool $documentPage = false;
    public bool $votingPage = false;
    public bool $speechPage = false;


    public function setOrganisations(?array $orgas): void
    {
        $this->organisations = array_map(
            fn(string|array $orga): ConsultationUserOrganisation => ConsultationUserOrganisation::fromJson($orga),
            $orgas ?? []
        );
    }

    /**
     * @return string[]
     */
    public static function getStartLayouts(): array
    {
        return [
            self::START_LAYOUT_STD               => \Yii::t('structure', 'home_layout_std'),
            self::START_LAYOUT_TAGS              => \Yii::t('structure', 'home_layout_tags'),
            self::START_LAYOUT_AGENDA            => \Yii::t('structure', 'home_layout_agenda'),
            self::START_LAYOUT_AGENDA_LONG       => \Yii::t('structure', 'home_layout_agenda_long'),
            self::START_LAYOUT_AGENDA_HIDE_AMEND => \Yii::t('structure', 'home_layout_agenda_hide_amend'),
            self::START_LAYOUT_DISCUSSION_TAGS   => \Yii::t('structure', 'home_layout_discussion_tags'),
        ];
    }

    /**
     * @return string[]
     */
    public static function getMotiondataModes(): array
    {
        return [
            self::MOTIONDATA_ALL  => \Yii::t('structure', 'motiondata_all'),
            self::MOTIONDATA_MINI => \Yii::t('structure', 'motiondata_mini'),
            self::MOTIONDATA_NONE => \Yii::t('structure', 'motiondata_none'),
        ];
    }

    public static function getDateFormats(): array
    {
        return [
            self::DATE_FORMAT_DEFAULT => \Yii::t('structure', 'dateformat_default'),
            self::DATE_FORMAT_DMY_DOT => \Yii::t('structure', 'dateformat_dmy_dot'),
            self::DATE_FORMAT_DMY_SLASH => \Yii::t('structure', 'dateformat_dmy_slash'),
            self::DATE_FORMAT_MDY_SLASH => \Yii::t('structure', 'dateformat_mdy_slash'),
            self::DATE_FORMAT_YMD_DASH => \Yii::t('structure', 'dateformat_ymd_dash'),
            self::DATE_FORMAT_DMY_DASH => \Yii::t('structure', 'dateformat_dmy_dash'),
        ];
    }

    /**
     * @return string[]
     */
    public static function getRobotPolicies(): array
    {
        return [
            self::ROBOTS_NONE => \Yii::t('structure', 'robots_policy_none'),
            self::ROBOTS_ONLY_HOME => \Yii::t('structure', 'robots_policy_only_home'),
            self::ROBOTS_ALL => \Yii::t('structure', 'robots_policy_all'),
        ];
    }

    public function getStartLayoutViewFromId(int $id): string
    {
        return match ($id) {
            Consultation::START_LAYOUT_STD => 'index_layout_std',
            Consultation::START_LAYOUT_TAGS => 'index_layout_tags',
            Consultation::START_LAYOUT_AGENDA_LONG, Consultation::START_LAYOUT_AGENDA_HIDE_AMEND, Consultation::START_LAYOUT_AGENDA => 'index_layout_agenda',
            Consultation::START_LAYOUT_DISCUSSION_TAGS => 'index_layout_discussion_tags',
            default => throw new Internal('Unknown layout: ' . $id),
        };
    }

    public function getStartLayoutView(): string
    {
        return $this->getStartLayoutViewFromId($this->startLayoutType);
    }

    public function getConsultationSidebar(): ?string
    {
        return '@app/views/consultation/sidebar';
    }

    /**
     * @return class-string<Layout>|null
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
