<?php

namespace app\models\db;

use app\models\api\motionType\{MotionTypeInitiatorSettingsUpdateRequest, MotionTypeUpdateRequest};
use app\models\exceptions\FormError;
use app\models\forms\MotionDeepCopy;
use app\models\policies\Nobody;
use CatoTH\HTML2OpenDocument\Text;
use app\components\{DateTools, LanguageTools, Tools, UrlHelper};
use app\models\settings\{AntragsgruenApp, InitiatorForm, Layout, MotionType};
use app\models\policies\IPolicy;
use app\models\supportTypes\SupportBase;
use app\views\pdfLayouts\IPDFLayout;
use yii\db\{ActiveQuery, ActiveRecord};

/**
 * @property int|null $id
 * @property int $consultationId
 * @property string $titleSingular
 * @property string $titlePlural
 * @property string $createTitle
 * @property string|null $motionPrefix
 * @property int $position
 * @property int $amendmentsOnly
 * @property int $pdfLayout
 * @property int|null $texTemplateId
 * @property string $deadlines
 * @property string $policyMotions
 * @property string $policyAmendments
 * @property string $policyComments
 * @property string $policySupportMotions
 * @property string $policySupportAmendments
 * @property int $initiatorsCanMergeAmendments
 * @property int $motionLikesDislikes
 * @property int $amendmentLikesDislikes
 * @property string|null $supportTypeMotions
 * @property string|null $supportTypeAmendments
 * @property int $amendmentMultipleParagraphs
 * @property int $status
 * @property string $settings
 * @property int $sidebarCreateButton
 * @property int $pdfPageNumbers
 *
 * @property ConsultationSettingsMotionSection[] $motionSections
 * @property Motion[] $motions
 * @property ConsultationText[] $consultationTexts
 * @property ConsultationAgendaItem[] $agendaItems
 * @property TexTemplate $texTemplate
 */
class ConsultationMotionType extends ActiveRecord implements IHasPolicies
{
    public const STATUS_VISIBLE = 0;
    public const STATUS_DELETED = -1;

    public const INITIATORS_MERGE_NEVER          = 0;
    public const INITIATORS_MERGE_NO_COLLISION   = 1;
    public const INITIATORS_MERGE_WITH_COLLISION = 2;

    public const DEADLINE_MOTIONS                 = 'motions';
    public const DEADLINE_AMENDMENTS              = 'amendments';
    public const DEADLINE_COMMENTS                = 'comments';
    public const DEADLINE_MERGING                 = 'merging';
    public const DEADLINE_AMENDMENTS_TO_AMENDMENTS = 'amendmentsToAmendments';
    public const DEADLINE_TYPES = ['motions', 'amendments', 'comments', 'merging', 'amendmentsToAmendments'];

    // Keep in sync with AmendmentEdit.ts
    public const AMEND_PARAGRAPHS_MULTIPLE = 1;
    public const AMEND_PARAGRAPHS_SINGLE_PARAGRAPH = 0;
    public const AMEND_PARAGRAPHS_SINGLE_CHANGE = -1;

    protected ?array $deadlinesObject = null;

    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'consultationMotionType';
    }

    public function setAttributes($values, $safeOnly = true): void
    {
        parent::setAttributes($values, $safeOnly);
        if (mb_strlen($this->motionPrefix) > 0) {
            $this->motionPrefix = mb_substr($this->motionPrefix, 0, 10);
        }
        if (mb_strlen($this->titleSingular) > 100) {
            $this->titleSingular = mb_substr($this->titleSingular, 0, 100);
        }
        if (mb_strlen($this->titlePlural) > 100) {
            $this->titlePlural = mb_substr($this->titlePlural, 0, 100);
        }
        if (mb_strlen($this->createTitle) > 200) {
            $this->createTitle = mb_substr($this->createTitle, 0, 200);
        }
    }

    public function getConsultation(): Consultation
    {
        $current = Consultation::getCurrent();
        if ($current && $current->id === $this->consultationId) {
            return $current;
        } else {
            return Consultation::findOne($this->consultationId);
        }
    }

    /**
     * @return ActiveQuery<Motion>
     */
    public function getMotions(): ActiveQuery
    {
        return $this->hasMany(Motion::class, ['motionTypeId' => 'id'])
            ->andWhere(Motion::tableName() . '.status != ' . Motion::STATUS_DELETED);
    }

    /**
     * @return ActiveQuery<ConsultationText>
     */
    public function getConsultationTexts(): ActiveQuery
    {
        return $this->hasMany(ConsultationText::class, ['motionTypeId' => 'id']);
    }

    /**
     * @return ActiveQuery<TexTemplate>
     */
    public function getTexTemplate(): ActiveQuery
    {
        return $this->hasOne(TexTemplate::class, ['id' => 'texTemplateId']);
    }

    /**
     * @return ActiveQuery<ConsultationSettingsMotionSection>
     */
    public function getMotionSections(): ActiveQuery
    {
        return $this->hasMany(ConsultationSettingsMotionSection::class, ['motionTypeId' => 'id'])
            ->where('status = ' . ConsultationSettingsMotionSection::STATUS_VISIBLE)
            ->orderBy('position');
    }

    public function getSectionById(int $sectionId): ?ConsultationSettingsMotionSection
    {
        foreach ($this->motionSections as $section) {
            if ($section->id === $sectionId) {
                return $section;
            }
        }
        return null;
    }

    /**
     * The distinct, non-null languages used by this motion type's sections.
     *
     * @return string[]
     */
    public function getDefinedSectionLanguages(): array
    {
        $languages = [];
        foreach ($this->motionSections as $section) {
            $language = $section->getLanguage();
            if ($language !== null && !in_array($language, $languages, true)) {
                $languages[] = $language;
            }
        }
        return $languages;
    }

    /**
     * A motion type that defines no language-specific sections at all is available in every
     * language. Otherwise, it is only available in the languages it actually defines sections for.
     */
    public function isAvailableInLanguage(string $language): bool
    {
        $definedLanguages = $this->getDefinedSectionLanguages();

        return (count($definedLanguages) === 0 || in_array($language, $definedLanguages, true));
    }

    /**
     * The sections that are relevant for the given language: those without a language, plus those
     * matching it.
     *
     * @return ConsultationSettingsMotionSection[]
     */
    public function getMotionSectionsForLanguage(string $language): array
    {
        return array_values(array_filter(
            $this->motionSections,
            fn (ConsultationSettingsMotionSection $section): bool => $section->matchesLanguage($language)
        ));
    }

    /**
     * Non-blocking hints about likely mistakes in the language setup of this motion type's sections:
     * a language set without a language group, two sections of the same language sharing a group, or
     * a group mixing different section types.
     *
     * @return string[]
     */
    public function getLanguageSetupWarnings(): array
    {
        $warnings = [];

        /** @var array<string, ConsultationSettingsMotionSection[]> $byGrouping */
        $byGrouping = [];
        foreach ($this->motionSections as $section) {
            $grouping = $section->getLanguageGrouping();
            if ($grouping !== null) {
                $byGrouping[$grouping][] = $section;
            } elseif ($section->getLanguage() !== null) {
                $warnings[] = str_replace('%SECTION%', $section->title, \Yii::t('admin', 'motion_section_language_warn_no_group'));
            }
        }

        foreach ($byGrouping as $grouping => $sections) {
            $languagesSeen = [];
            $types         = [];
            foreach ($sections as $section) {
                $language = $section->getLanguage();
                if ($language !== null && in_array($language, $languagesSeen, true)) {
                    $warnings[] = str_replace('%GROUP%', $grouping, \Yii::t('admin', 'motion_section_language_warn_duplicate'));
                }
                if ($language !== null) {
                    $languagesSeen[] = $language;
                }
                $types[] = $section->type;
            }
            if (count(array_unique($types)) > 1) {
                $warnings[] = str_replace('%GROUP%', $grouping, \Yii::t('admin', 'motion_section_language_warn_mixed_types'));
            }
        }

        return $warnings;
    }

    /**
     * @return ActiveQuery<ConsultationAgendaItem>
     */
    public function getAgendaItems(): ActiveQuery
    {
        return $this->hasMany(ConsultationAgendaItem::class, ['motionTypeId' => 'id']);
    }


    public function getMotionPolicy(): IPolicy
    {
        return IPolicy::getInstanceFromDb($this->policyMotions, $this->getConsultation(), $this);
    }

    public function setMotionPolicy(IPolicy $policy): void
    {
        $this->policyMotions = $policy->serializeInstanceForDb();
    }

    public function getAmendmentPolicy(): IPolicy
    {
        return IPolicy::getInstanceFromDb($this->policyAmendments, $this->getConsultation(), $this);
    }

    public function setAmendmentPolicy(IPolicy $policy): void
    {
        $this->policyAmendments = $policy->serializeInstanceForDb();
    }

    public function getCommentPolicy(): IPolicy
    {
        return IPolicy::getInstanceFromDb($this->policyComments, $this->getConsultation(), $this);
    }

    public function setCommentPolicy(IPolicy $policy): void
    {
        $this->policyComments = $policy->serializeInstanceForDb();
    }

    public function getMotionSupportPolicy(): IPolicy
    {
        return IPolicy::getInstanceFromDb($this->policySupportMotions, $this->getConsultation(), $this);
    }

    public function setMotionSupportPolicy(IPolicy $policy): void
    {
        $this->policySupportMotions = $policy->serializeInstanceForDb();
    }

    public function getAmendmentSupportPolicy(): IPolicy
    {
        return IPolicy::getInstanceFromDb($this->policySupportAmendments, $this->getConsultation(), $this);
    }

    public function setAmendmentSupportPolicy(IPolicy $policy): void
    {
        $this->policySupportAmendments = $policy->serializeInstanceForDb();
    }

    public function getAmendmentSupporterSettings(): InitiatorForm
    {
        if ($this->supportTypeAmendments) {
            return new InitiatorForm($this->supportTypeAmendments);
        } else {
            return $this->getMotionSupporterSettings();
        }
    }

    public function getMotionSupporterSettings(): InitiatorForm
    {
        return new InitiatorForm($this->supportTypeMotions);
    }

    public function getMotionSupportTypeClass(): SupportBase
    {
        $settings = $this->getMotionSupporterSettings();
        return SupportBase::getImplementation($settings, $this);
    }

    public function getAmendmentSupportTypeClass(): SupportBase
    {
        $settings = $this->getAmendmentSupporterSettings();
        return SupportBase::getImplementation($settings, $this);
    }

    public function hasPdfLayout(): bool
    {
        $layout = IPDFLayout::getPdfLayoutForMotionType($this);
        return $layout->id !== IPDFLayout::LAYOUT_NONE;
    }

    public function getPDFLayoutClass(): ?IPDFLayout
    {
        $layout = IPDFLayout::getClassById($this->pdfLayout);
        if ($layout === null || $layout->className === null || !is_subclass_of($layout->className, IPDFLayout::class)) {
            return null;
        }
        return new $layout->className($this);
    }

    public function getOdtTemplateFile(): string
    {
        $layout    = $this->getConsultation()->site->getSettings()->siteLayout;
        $layoutDef = Layout::getLayoutPluginDef($layout);
        if ($layoutDef && isset($layoutDef['odtTemplate']) && $layoutDef['odtTemplate']) {
            return $layoutDef['odtTemplate'];
        } else {
            $dir = \Yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
            return $dir . 'OpenOffice-Template-Std.odt';
        }
    }

    public function createOdtTextHandler(): Text
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return new Text([
            'templateFile' => $this->getOdtTemplateFile(),
            'tmpPath'      => AntragsgruenApp::getInstance()->getTmpDir(),
            'trustHtml'    => true,
        ]);
    }

    public function getDeadlinesByType(string $type): array
    {
        if ($this->deadlinesObject === null) {
            $this->deadlinesObject = ($this->deadlines ? json_decode($this->deadlines, true) : []);
        }
        return $this->deadlinesObject[$type] ?? [];
    }

    public function setAllDeadlines(array $deadlines): void
    {
        $this->deadlines = json_encode($deadlines, JSON_THROW_ON_ERROR);
        $this->deadlinesObject = null;
    }

    public function setSimpleDeadlines(?string $deadlineMotions, ?string $deadlineAmendments): void
    {
        $this->setAllDeadlines([
            static::DEADLINE_MOTIONS    => [['start' => null, 'end' => $deadlineMotions, 'title' => null]],
            static::DEADLINE_AMENDMENTS => [['start' => null, 'end' => $deadlineAmendments, 'title' => null]],
        ]);
    }

    /**
     * Applies a partial settings update from the REST API - only the groups present in $dto are touched.
     * Corresponds to the "save" part of admin/MotionTypeController::actionType (not create/delete, and does
     * not touch section definitions).
     * @throws FormError
     */
    public function applySettingsUpdate(MotionTypeUpdateRequest $dto): void
    {
        $consultation = $this->getConsultation();

        if ($dto->labels !== null) {
            $this->titleSingular = $dto->labels->singular;
            $this->titlePlural   = $dto->labels->plural;
            $this->createTitle   = $dto->labels->create;
        }
        if ($dto->motionPrefix !== null) {
            $this->motionPrefix = $dto->motionPrefix;
        }
        if ($dto->sidebarCreateButton !== null) {
            $this->sidebarCreateButton = ($dto->sidebarCreateButton ? 1 : 0);
        }
        if ($dto->amendmentMultipleParagraphs !== null) {
            $this->amendmentMultipleParagraphs = $dto->amendmentMultipleParagraphs->toDbValue();
        }
        if ($dto->initiatorsCanMergeAmendments !== null) {
            $this->initiatorsCanMergeAmendments = $dto->initiatorsCanMergeAmendments->toDbValue();
        }

        if ($dto->settings !== null) {
            $settings                              = $this->getSettingsObj();
            $settings->pdfIntroduction              = $dto->settings->pdfIntroduction;
            $settings->motionTitleIntro             = $dto->settings->motionTitleIntro;
            $settings->hasProposedProcedure         = $dto->settings->hasProposedProcedure;
            $settings->proposedProcedureVersioning  = $dto->settings->proposedProcedureVersioning;
            $settings->hasResponsibilities          = $dto->settings->hasResponsibilities;
            $settings->commentsRestrictViewToWritables = $dto->settings->commentsRestrictViewToWritables;
            $settings->allowAmendmentsToAmendments  = $dto->settings->allowAmendmentsToAmendments;
            $settings->screeningMotions             = $dto->settings->screeningMotions;
            $settings->screeningAmendments          = $dto->settings->screeningAmendments;
            $settings->showProposalsInExports       = $dto->settings->showProposalsInExports;
            $this->setSettingsObj($settings);
        }

        if ($dto->policies !== null) {
            $this->setMotionPolicy($dto->policies->motions->toPolicy($consultation, $this));
            $this->setAmendmentPolicy($dto->policies->amendments->toPolicy($consultation, $this));
            $this->setCommentPolicy($dto->policies->comments->toPolicy($consultation, $this));
            $this->setMotionSupportPolicy($dto->policies->supportMotions->toPolicy($consultation, $this));
            $this->setAmendmentSupportPolicy($dto->policies->supportAmendments->toPolicy($consultation, $this));
        }

        if ($dto->deadlines !== null) {
            $this->setAllDeadlines([
                static::DEADLINE_MOTIONS                 => array_map(fn($e) => $e->toArray(), $dto->deadlines->motions),
                static::DEADLINE_AMENDMENTS               => array_map(fn($e) => $e->toArray(), $dto->deadlines->amendments),
                static::DEADLINE_MERGING                  => array_map(fn($e) => $e->toArray(), $dto->deadlines->merging),
                static::DEADLINE_COMMENTS                 => array_map(fn($e) => $e->toArray(), $dto->deadlines->comments),
                static::DEADLINE_AMENDMENTS_TO_AMENDMENTS => array_map(fn($e) => $e->toArray(), $dto->deadlines->amendmentsToAmendments),
            ]);
        }

        if ($dto->pdfLayoutId !== null) {
            $layout = null;
            foreach (IPDFLayout::getSelectablePdfLayouts() as $candidate) {
                if ($candidate->getHtmlId() === $dto->pdfLayoutId) {
                    $layout = $candidate;
                }
            }
            if ($layout === null) {
                throw new FormError('Unknown pdf_layout_id: ' . $dto->pdfLayoutId);
            }
            $this->pdfLayout      = $layout->id ?? 0;
            $this->texTemplateId  = $layout->latexId;
        }

        if ($dto->motionSupportTypes !== null) {
            $this->motionLikesDislikes = 0;
            foreach ($dto->motionSupportTypes as $flag) {
                $this->motionLikesDislikes |= $flag->toFlag();
            }
        }
        if ($dto->amendmentSupportTypes !== null) {
            $this->amendmentLikesDislikes = 0;
            foreach ($dto->amendmentSupportTypes as $flag) {
                $this->amendmentLikesDislikes |= $flag->toFlag();
            }
        }

        if ($dto->motionInitiatorSettings !== null) {
            $settings = $this->getMotionSupportTypeClass()->getSettingsObj();
            self::applyInitiatorSettingsUpdate($settings, $dto->motionInitiatorSettings, $consultation, $this);
            $this->supportTypeMotions = json_encode($settings, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        }

        if ($dto->amendmentInitiatorSettings !== null) {
            if ($dto->amendmentInitiatorSettings->sameAsMotion) {
                $this->supportTypeAmendments = null;
            } else {
                if ($dto->amendmentInitiatorSettings->settings === null) {
                    throw new FormError('amendment_initiator_settings.settings is required when same_as_motion is false');
                }
                $settings = $this->getAmendmentSupportTypeClass()->getSettingsObj();
                self::applyInitiatorSettingsUpdate($settings, $dto->amendmentInitiatorSettings->settings, $consultation, $this);
                $settings->maxPdfSupporters = $dto->amendmentInitiatorSettings->maxPdfSupporters;
                $this->supportTypeAmendments = json_encode($settings, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            }
        }
    }

    private static function applyInitiatorSettingsUpdate(InitiatorForm $settings, MotionTypeInitiatorSettingsUpdateRequest $dto, Consultation $consultation, ConsultationMotionType $motionType): void
    {
        $settings->type                        = $dto->type->toSupportBaseValue();
        $settings->initiatorCanBePerson        = $dto->initiatorCanBePerson;
        $settings->initiatorCanBeOrganization  = $dto->initiatorCanBeOrganization;
        if (!$settings->initiatorCanBePerson && !$settings->initiatorCanBeOrganization) {
            // Probably a mistake
            $settings->initiatorCanBePerson       = true;
            $settings->initiatorCanBeOrganization = true;
        }
        $settings->setInitiatorPersonPolicyObject($dto->personPolicy->toPolicy($consultation, $motionType));
        $settings->setInitiatorOrganizationPolicyObject($dto->organizationPolicy->toPolicy($consultation, $motionType));
        $settings->minSupporters                    = $dto->minSupporters;
        $settings->minSupportersFemale              = $dto->minSupportersFemale;
        $settings->allowMoreSupporters              = $dto->allowMoreSupporters;
        $settings->allowSupportingAfterPublication  = $dto->allowSupportingAfterPublication;
        $settings->offerNonPublicSupports           = $dto->offerNonPublicSupports;
        $settings->hasOrganizations                 = $dto->hasOrganizations;
        $settings->contactName                      = $dto->contactName->toDbValue();
        $settings->contactEmail                     = $dto->contactEmail->toDbValue();
        $settings->contactPhone                     = $dto->contactPhone->toDbValue();
        $settings->contactGender                    = $dto->contactGender->toDbValue();
        $settings->hasResolutionDate                = $dto->hasResolutionDate->toDbValue();
    }

    public static function isInDeadlineRange(array $deadline, ?int $timestamp = null): bool
    {
        if ($timestamp === null) {
            $timestamp = DateTools::getCurrentTimestamp();
        }
        if ($deadline['start']) {
            $startTs = Tools::dateSql2timestamp($deadline['start']);
            if ($startTs > $timestamp) {
                return false;
            }
        }
        if ($deadline['end']) {
            $endTs = Tools::dateSql2timestamp($deadline['end']);
            if ($endTs < $timestamp) {
                return false;
            }
        }
        return true;
    }

    public function getUpcomingDeadline(string $type): ?string
    {
        $deadlines = $this->getDeadlinesByType($type);
        foreach ($deadlines as $deadline) {
            if (static::isInDeadlineRange($deadline) && $deadline['end']) {
                return $deadline['end'];
            }
        }
        return null;
    }

    public function isInDeadline(string $type): bool
    {
        $deadlines = $this->getDeadlinesByType($type);
        if (count($deadlines) === 0) {
            return true;
        }
        foreach ($deadlines as $deadline) {
            if (static::isInDeadlineRange($deadline)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Amendments amending another amendment (rather than the base motion) are, by default, still
     * governed by DEADLINE_AMENDMENTS. Only once DEADLINE_AMENDMENTS_TO_AMENDMENTS is explicitly
     * configured does it take over for this case, so existing consultations keep their current
     * behavior until an admin opts into a separate deadline.
     */
    public function isInAmendmentDeadline(bool $isAmendmentToAmendment): bool
    {
        if ($isAmendmentToAmendment && count($this->getDeadlinesByType(self::DEADLINE_AMENDMENTS_TO_AMENDMENTS)) > 0) {
            return $this->isInDeadline(self::DEADLINE_AMENDMENTS_TO_AMENDMENTS);
        }
        return $this->isInDeadline(self::DEADLINE_AMENDMENTS);
    }

    public function getAllCurrentDeadlines(bool $onlyNamed = false): array
    {
        $found = [];
        foreach (static::DEADLINE_TYPES as $type) {
            foreach ($this->getDeadlinesByType($type) as $deadline) {
                if ($onlyNamed && !$deadline['title']) {
                    continue;
                }
                if (static::isInDeadlineRange($deadline)) {
                    $deadline['type'] = $type;
                    $found[]          = $deadline;
                }
            }
        }
        return $found;
    }

    public function isDeletable(): bool
    {
        foreach ($this->motions as $motion) {
            if ($motion->status !== Motion::STATUS_DELETED) {
                return false;
            }
        }
        return true;
    }

    public function rules(): array
    {
        return [
            [['consultationId', 'titleSingular', 'titlePlural', 'createTitle', 'sidebarCreateButton'], 'required'],
            [['policyMotions', 'policyAmendments', 'policyComments', 'policySupportMotions'], 'required'],
            [['policySupportAmendments', 'initiatorsCanMergeAmendments', 'status'], 'required'],
            [['amendmentMultipleParagraphs', 'position', 'amendmentsOnly'], 'required'],

            [['id', 'consultationId', 'position', 'amendmentsOnly'], 'number'],
            [['status', 'amendmentMultipleParagraphs', 'amendmentLikesDislikes', 'motionLikesDislikes'], 'number'],
            [['initiatorsCanMergeAmendments', 'pdfLayout', 'sidebarCreateButton'], 'number'],

            [['titleSingular', 'titlePlural', 'createTitle', 'motionLikesDislikes', 'amendmentLikesDislikes'], 'safe'],
            [['motionPrefix', 'position', 'amendmentsOnly', 'supportTypeMotions', 'supportTypeAmendments'], 'safe'],
            [['pdfLayout', 'policyMotions', 'policyAmendments', 'policyComments', 'policySupportMotions'], 'safe'],
            [['policySupportAmendments', 'initiatorsCanMergeAmendments'], 'safe'],
            [['sidebarCreateButton'], 'safe']
        ];
    }

    private ?MotionType $settingsObject = null;

    public function getSettingsObj(): MotionType
    {
        if (!is_object($this->settingsObject)) {
            $this->settingsObject = new MotionType($this->settings);
        }
        return $this->settingsObject;
    }

    public function setSettingsObj(MotionType $settings): void
    {
        $this->settingsObject = $settings;
        $this->settings = json_encode($settings, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    /**
     * $titleSingular/$titlePlural/$createTitle in the given (or, by default, the reader's current)
     * language - see MotionType::$labelTranslations. On a single-language site, or for the
     * consultation's primary language, or if no override is defined, these are always the DB column
     * value itself.
     */
    public function getTitleSingularForDisplay(?string $language = null): string
    {
        return $this->getLabelForDisplay('titleSingular', $this->titleSingular, $language);
    }

    public function getTitlePluralForDisplay(?string $language = null): string
    {
        return $this->getLabelForDisplay('titlePlural', $this->titlePlural, $language);
    }

    public function getCreateTitleForDisplay(?string $language = null): string
    {
        return $this->getLabelForDisplay('createTitle', $this->createTitle, $language);
    }

    private function getLabelForDisplay(string $field, string $mainLanguageValue, ?string $language): string
    {
        $language ??= LanguageTools::getCurrentLanguage();
        if ($language === LanguageTools::getPrimaryLanguage($this->getConsultation())) {
            return $mainLanguageValue;
        }

        $translation = $this->getSettingsObj()->labelTranslations[$language][$field] ?? '';

        return ($translation !== '' ? $translation : $mainLanguageValue);
    }

    /**
     * For prefilling the admin translation form - the raw override, without falling back to the
     * main-language value, so an empty field in the form means "no override" rather than repeating
     * the default.
     */
    public function getLabelTranslation(string $language, string $field): string
    {
        return $this->getSettingsObj()->labelTranslations[$language][$field] ?? '';
    }

    /**
     * @param array<string, array{titleSingular?: string, titlePlural?: string, createTitle?: string}> $translations
     */
    public function setLabelTranslations(array $translations): void
    {
        $settings = $this->getSettingsObj();
        $settings->labelTranslations = $translations;
        $this->setSettingsObj($settings);
    }

    /**
     * @return Motion[]
     */
    public function getVisibleMotions(bool $withdrawnAreVisible = true): array
    {
        $return = [];
        foreach ($this->motions as $motion) {
            if (!in_array($motion->status, $this->getConsultation()->getStatuses()->getInvisibleMotionStatuses($withdrawnAreVisible))) {
                $return[] = $motion;
            }
        }
        return $return;
    }

    /**
     * @return Motion[]
     */
    public function getAmendableOnlyMotions(bool $allowAdmins = true, bool $assumeLoggedIn = false, bool $sorted = true): array
    {
        $return = [];
        foreach ($this->motions as $motion) {
            if (in_array($motion->status, $this->getConsultation()->getStatuses()->getUnreadableStatuses())) {
                continue;
            }
            if (!$this->getAmendmentPolicy()->checkCurrUserAmendment($allowAdmins, $assumeLoggedIn)) {
                continue;
            }
            $return[] = $motion;
        }
        if ($sorted) {
            usort($return, function (Motion $motion1, Motion $motion2): int {
                return strnatcasecmp($motion1->title, $motion2->title);
            });
        }
        return $return;
    }

    public function mayCreateIMotion(bool $allowAdmins = true, bool $assumeLoggedIn = false): bool
    {
        if (!$this->isAvailableInLanguage(LanguageTools::getCurrentLanguage())) {
            return false;
        }
        if ($this->amendmentsOnly) {
            return $this->getAmendmentPolicy()->checkCurrUserAmendment($allowAdmins, $assumeLoggedIn);
        } else {
            return $this->getMotionPolicy()->checkCurrUserMotion($allowAdmins, $assumeLoggedIn);
        }
    }

    public function maySeeIComments(): bool
    {
        if ($this->getSettingsObj()->commentsRestrictViewToWritables) {
            return $this->getCommentPolicy()->checkCurrUserComment(false, false);
        } else {
            return $this->getCommentPolicy()->getPolicyID() !== Nobody::getPolicyID();
        }
    }

    public function getCreateLink(bool $allowAdmins = true, bool $assumeLoggedIn = false): ?string
    {
        if ($this->amendmentsOnly) {
            $motions = $this->getAmendableOnlyMotions($allowAdmins, $assumeLoggedIn);
            if (count($motions) === 1) {
                return UrlHelper::createUrl(['/amendment/create', 'motionSlug' => $motions[0]->getMotionSlug()]);
            } elseif (count($motions) > 1) {
                return UrlHelper::createUrl(['/motion/create-select-statutes', 'motionTypeId' => $motions[0]->motionTypeId]);
            } else {
                return null;
            }
        } else {
            return UrlHelper::createUrl(['/motion/create', 'motionTypeId' => $this->id]);
        }
    }

    public function isCompatibleTo(ConsultationMotionType $cmpMotionType, array $skip): bool
    {
        return (MotionDeepCopy::getMotionSectionMapping($this, $cmpMotionType, $skip) !== null);
    }

    /**
     * @return ConsultationMotionType[]
     */
    public function getCompatibleMotionTypes(array $skip): array
    {
        $compatible = [];
        foreach ($this->getConsultation()->motionTypes as $motionType) {
            if ($this->isCompatibleTo($motionType, $skip)) {
                $compatible[] = $motionType;
            }
        }
        return $compatible;
    }

    public function getConsultationTextWithFallback(string $category, string $key): ?string {
        foreach ($this->consultationTexts as $consultationText) {
            if ($consultationText->category === $category && $consultationText->textId === $key) {
                return $consultationText->text;
            }
        }

        return \Yii::t($category, $key);
    }
}
