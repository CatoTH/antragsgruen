<?php

namespace app\models\db;

use app\components\{LanguageTools, UrlHelper};
use app\models\policies\{All, IPolicy};
use app\models\settings\AntragsgruenApp;
use yii\db\{ActiveQuery, ActiveRecord};

/**
 * @property int|null $id
 * @property int|null $motionTypeId
 * @property int|null $consultationId
 * @property int $siteId
 * @property string $category
 * @property string $textId
 * @property string|null $language
 * @property int|null $menuPosition
 * @property string|null $policyRead
 * @property string|null $title
 * @property string|null $breadcrumb
 * @property string|null $text
 * @property string $editDate
 *
 * @property ConsultationMotionType|null $motionType
 * @property Consultation|null $consultation
 * @property Site|null $site
 */
class ConsultationText extends ActiveRecord implements IHasPolicies
{
    public const DEFAULT_CATEGORY = 'pagedata';

    public const DEFAULT_PAGE_WELCOME = 'welcome';
    public const DEFAULT_PAGE_DOCUMENTS = 'documents';
    public const DEFAULT_PAGE_MAINTENANCE = 'maintenance';
    public const DEFAULT_PAGE_HELP = 'help';
    public const DEFAULT_PAGE_PRIVACY = 'privacy';
    public const DEFAULT_PAGE_LEGAL = 'legal';
    public const DEFAULT_PAGE_FEEDS = 'feeds';
    public const DEFAULT_PAGE_LOGIN_PRE = 'login_pre';
    public const DEFAULT_PAGE_LOGIN_POST = 'login_post';

    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'consultationText';
    }

    /**
     * @return ActiveQuery<ConsultationMotionType>
     */
    public function getMotionType(): ActiveQuery
    {
        return $this->hasOne(ConsultationMotionType::class, ['id' => 'motionTypeId']);
    }

    /**
     * @return ActiveQuery<Consultation>
     */
    public function getConsultation(): ActiveQuery
    {
        return $this->hasOne(Consultation::class, ['id' => 'consultationId']);
    }

    public function getMyConsultation(): ?Consultation
    {
        $current = Consultation::getCurrent();
        if ($current && $current->id === $this->consultationId) {
            return $current;
        } else {
            return $this->consultation;
        }
    }

    /**
     * @return ActiveQuery<Site>
     */
    public function getSite(): ActiveQuery
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }

    public function rules(): array
    {
        return [
            [['category', 'textId'], 'required'],
            [['category', 'textId', 'text', 'breadcrumb', 'title'], 'safe'],
            [['menuPosition'], 'number'],
        ];
    }

    public function isCustomPage(): bool
    {
        $defaultPages = array_keys(self::getDefaultPages());

        return !in_array($this->textId, $defaultPages);
    }

    public function getReadPolicy(): IPolicy
    {
        if ($this->consultationId === null && $this->site?->currentConsultation) {
            // Using the first consultation is not entirely correct, but good enough: "All" really is the relevant information here;
            // the consultation only carries deadlines etc., which are not relevant for consultation texts.
            return new All($this->site->currentConsultation, $this, null);
        }

        if ($this->policyRead === null) {
            $policy = (string)IPolicy::POLICY_ALL;
        } else {
            $policy = $this->policyRead;
        }
        return IPolicy::getInstanceFromDb($policy, $this->getMyConsultation(), $this);
    }

    public function setReadPolicy(IPolicy $policy): void
    {
        $this->policyRead = $policy->serializeInstanceForDb();
    }

    public function getUrl(): string
    {
        $params = ['/pages/show-page', 'pageSlug' => $this->textId];
        if ($this->consultationId) {
            $params['consultationPath'] = $this->getMyConsultation()->urlPath;
        }

        if ($this->textId === self::DEFAULT_PAGE_FEEDS) {
            return UrlHelper::createUrl(['consultation/feeds']);
        } else {
            return UrlHelper::createUrl($params);
        }
    }

    public function getJsonUrl(): string {
        $params = ['/pages/get-rest', 'pageSlug' => $this->textId];
        if ($this->consultationId) {
            $params['consultationPath'] = $this->getMyConsultation()->urlPath;
        }
        return UrlHelper::createUrl($params);
    }

    public function getSaveUrl(): string
    {
        $saveParams = ['/pages/save-page', 'pageSlug' => $this->textId];
        if ($this->consultationId) {
            $saveParams['consultationPath'] = $this->getMyConsultation()->urlPath;
        }
        if ($this->id) {
            $saveParams['pageId'] = $this->id;
        }

        return UrlHelper::createUrl($saveParams);
    }

    public function getUploadUrl(): ?string
    {
        if ($this->consultationId) {
            $saveParams = ['/pages/upload', 'consultationPath' => $this->getMyConsultation()->urlPath];
        } elseif ($this->site) {
            $saveParams = ['/pages/upload', 'consultationPath' => $this->site->currentConsultation->urlPath];
        } else {
            return null;
        }

        return UrlHelper::createUrl($saveParams);
    }

    public function getFileDeleteUrl(): ?string
    {
        if ($this->consultationId) {
            $saveParams = ['/pages/delete-file', 'consultationPath' => $this->getMyConsultation()->urlPath];
        } elseif ($this->site) {
            $saveParams = ['/pages/delete-file', 'consultationPath' => $this->site->currentConsultation->urlPath];
        } else {
            return null;
        }

        return UrlHelper::createUrl($saveParams);
    }

    /**
     * Downloadable files are shared by all language variants of a page,
     * so the file group may be attached to any of them.
     */
    public function getMyFileGroup(): ?ConsultationFileGroup
    {
        if (!$this->getMyConsultation()) {
            return null;
        }
        $textIds = array_map(fn (ConsultationText $text) => $text->id, $this->getOtherLanguageVariants());
        if ($this->id) {
            $textIds[] = $this->id;
        }
        foreach ($this->getMyConsultation()->fileGroups as $fileGroup) {
            if (in_array($fileGroup->consultationTextId, $textIds, true)) {
                return $fileGroup;
            }
        }
        return null;
    }

    /**
     * The language page texts are read in and saved as: the current language on multi-language sites,
     * and null (= language-independent) otherwise.
     */
    public static function getContentLanguage(?Site $site): ?string
    {
        if ($site && LanguageTools::isMultiLanguageSite($site)) {
            return LanguageTools::getCurrentLanguage();
        }
        return null;
    }

    /**
     * Which stored UI wording overrides (all categories except page data) apply to the given language:
     * - Single-language sites only use language-independent rows.
     * - On multi-language sites, a row in the given language wins. Language-independent rows serve as
     *   fallback only for the consultation's primary language, as they are written in that language.
     *   An empty language-specific row blocks that fallback, restoring the built-in text.
     *
     * @param ConsultationText[] $texts
     * @return array<string, string> textId => text, only non-empty overrides
     */
    public static function getWordingOverrides(array $texts, Consultation $consultation, string $language, string $category): array
    {
        $language = explode('-', $language)[0];
        if (LanguageTools::isMultiLanguageSite($consultation->site)) {
            $specificLanguage = $language;
            $useLanguageIndependent = ($language === LanguageTools::getPrimaryLanguage($consultation));
        } else {
            $specificLanguage = null;
            $useLanguageIndependent = true;
        }

        $overrides = [];
        $hasSpecific = [];
        foreach ($texts as $text) {
            if ($text->category !== $category) {
                continue;
            }
            if ($specificLanguage !== null && $text->language === $specificLanguage) {
                $overrides[$text->textId] = (string)$text->text;
                $hasSpecific[$text->textId] = true;
            } elseif ($text->language === null && $useLanguageIndependent && !isset($hasSpecific[$text->textId])) {
                $overrides[$text->textId] = (string)$text->text;
            }
        }

        return array_filter($overrides, fn (string $text): bool => $text !== '');
    }

    /**
     * Reduces a list of texts to one entry per page, picking the variant in the given language
     * and falling back to the language-independent one. Pages only existing in other languages are dropped.
     *
     * @param ConsultationText[] $texts
     * @return ConsultationText[]
     */
    public static function filterForLanguage(array $texts, ?string $language): array
    {
        $byPage = [];
        foreach ($texts as $text) {
            if ($text->language !== null && $text->language !== $language) {
                continue;
            }
            $key = $text->siteId . '-' . $text->consultationId . '-' . $text->category . '-' . mb_strtolower($text->textId);
            if (!isset($byPage[$key]) || $text->language !== null) {
                $byPage[$key] = $text;
            }
        }

        return array_values($byPage);
    }

    /**
     * All other rows representing the same page in other languages (including the language-independent one)
     *
     * @return ConsultationText[]
     */
    public function getOtherLanguageVariants(): array
    {
        $query = ConsultationText::find()->where([
            'siteId'         => $this->siteId,
            'consultationId' => $this->consultationId,
            'category'       => $this->category,
            'textId'         => $this->textId,
        ]);
        if ($this->id) {
            $query->andWhere(['!=', 'id', $this->id]);
        }

        return $query->all();
    }

    /**
     * Returns the row of this page that holds the given language, creating an (unsaved) copy of this one if none exists yet.
     */
    public function getVariantForLanguage(?string $language): ConsultationText
    {
        if ($this->language === $language) {
            return $this;
        }
        foreach ($this->getOtherLanguageVariants() as $variant) {
            if ($variant->language === $language) {
                return $variant;
            }
        }

        $variant = new ConsultationText();
        $variant->setAttributes($this->getAttributes(), false);
        $variant->id = null;
        $variant->language = $language;

        return $variant;
    }

    /**
     * Settings like the URL, menu position or read policy apply to the page as a whole, not only to one language.
     *
     * @param ConsultationText[] $variants
     */
    public function syncSharedSettingsToVariants(array $variants): void
    {
        foreach ($variants as $variant) {
            $variant->textId = $this->textId;
            $variant->consultationId = $this->consultationId;
            $variant->menuPosition = $this->menuPosition;
            $variant->policyRead = $this->policyRead;
            $variant->save();
        }
    }

    public function getImageBrowseUrl(): string
    {
        return UrlHelper::createUrl(['/pages/browse-images']);
    }

    /**
     * @return string[]
     */
    public static function getDefaultPages(): array
    {
        return [
            self::DEFAULT_PAGE_MAINTENANCE => \Yii::t('pages', 'content_maint_title'),
            self::DEFAULT_PAGE_HELP => \Yii::t('pages', 'content_help_title'),
            self::DEFAULT_PAGE_LEGAL => \Yii::t('pages', 'content_imprint_title'),
            self::DEFAULT_PAGE_PRIVACY => \Yii::t('pages', 'content_privacy_title'),
            self::DEFAULT_PAGE_WELCOME => \Yii::t('pages', 'content_welcome'),
            self::DEFAULT_PAGE_LOGIN_PRE => \Yii::t('pages', 'content_login_pre'),
            self::DEFAULT_PAGE_LOGIN_POST => \Yii::t('pages', 'content_login_post'),
            self::DEFAULT_PAGE_FEEDS => \Yii::t('pages', 'content_feeds_title'),
        ];
    }

    /**
     * @return string[]
     */
    public static function getSitewidePages(): array
    {
        return [self::DEFAULT_PAGE_LEGAL, self::DEFAULT_PAGE_PRIVACY, self::DEFAULT_PAGE_LOGIN_PRE, self::DEFAULT_PAGE_LOGIN_POST];
    }

    /**
     * Pages that have a fallback for the whole system. Only relevant in multi-site-setups.
     *
     * @return string[]
     */
    public static function getSystemwidePages(): array
    {
        return [self::DEFAULT_PAGE_LEGAL, self::DEFAULT_PAGE_PRIVACY];
    }

    public static function getDefaultPage(string $pageKey): ConsultationText
    {
        $data           = new ConsultationText();
        $data->textId   = $pageKey;
        $data->category = self::DEFAULT_CATEGORY;
        switch ($pageKey) {
            case self::DEFAULT_PAGE_MAINTENANCE:
                $data->title      = \Yii::t('pages', 'content_maint_title');
                $data->breadcrumb = \Yii::t('pages', 'content_maint_bread');
                $data->text       = \Yii::t('pages', 'content_maint_text');
                break;
            case self::DEFAULT_PAGE_HELP:
                $data->title      = \Yii::t('pages', 'content_help_title');
                $data->breadcrumb = \Yii::t('pages', 'content_help_bread');
                $data->text       = \Yii::t('pages', 'content_help_place');
                break;
            case self::DEFAULT_PAGE_LEGAL:
                $data->title      = \Yii::t('pages', 'content_imprint_title');
                $data->breadcrumb = \Yii::t('pages', 'content_imprint_bread');
                $data->text       = '<p>' . \Yii::t('pages', 'content_imprint_title') . '</p>';
                break;
            case self::DEFAULT_PAGE_PRIVACY:
                $data->title      = \Yii::t('pages', 'content_privacy_title');
                $data->breadcrumb = \Yii::t('pages', 'content_privacy_bread');
                $data->text       = '';
                break;
            case self::DEFAULT_PAGE_WELCOME:
                $data->title      = \Yii::t('pages', 'content_welcome');
                $data->breadcrumb = \Yii::t('pages', 'content_welcome');
                $data->text       = \Yii::t('pages', 'content_welcome_text');
                break;
            case self::DEFAULT_PAGE_LOGIN_PRE:
                $data->title      = \Yii::t('pages', 'content_login_pre');
                $data->breadcrumb = \Yii::t('pages', 'content_login_pre');
                $data->text       = '';
                break;
            case self::DEFAULT_PAGE_LOGIN_POST:
                $data->title      = \Yii::t('pages', 'content_login_post');
                $data->breadcrumb = \Yii::t('pages', 'content_login_post');
                $data->text       = '';
                break;
            case self::DEFAULT_PAGE_FEEDS:
                $data->title      = \Yii::t('pages', 'content_feeds_title');
                $data->breadcrumb = \Yii::t('pages', 'content_feeds_title');
                $data->text       = \Yii::t('pages', 'content_feeds_text');
                break;
            case self::DEFAULT_PAGE_DOCUMENTS:
                $data->text = \Yii::t('pages', 'documents_intro_text');
                break;
        }

        return $data;
    }

    /**
     * @return ConsultationText[]
     */
    public static function getMenuEntries(?Site $site, ?Consultation $consultation): array
    {
        $pages = [];
        if ($site) {
            $pages = array_merge($pages, ConsultationText::findAll(['siteId' => $site->id, 'consultationId' => null]));
        }
        if ($consultation) {
            $pages = array_merge($pages, $consultation->texts);
        }
        $pages = self::filterForLanguage($pages, self::getContentLanguage($site));
        $pages = array_filter($pages, function (ConsultationText $page) {
            if ($page->textId === 'help' && $page->text === \Yii::t('pages', 'content_help_place')) {
                return false;
            }

            if ($page->consultationId !== null && !$page->getReadPolicy()->checkCurrUser()) {
                return false;
            }

            return $page->menuPosition !== null;
        });
        usort($pages, function (ConsultationText $page1, ConsultationText $page2) {
            return $page1->menuPosition <=> $page2->menuPosition;
        });

        return $pages;
    }

    public static function getPageData(?Site $site, ?Consultation $consultation, string $pageKey): ConsultationText
    {
        $language = self::getContentLanguage($site);
        $foundText = null;
        if (!in_array($pageKey, static::getSitewidePages()) && $consultation) {
            $candidates = array_filter(
                $consultation->texts,
                fn (ConsultationText $text) => $text->category === self::DEFAULT_CATEGORY && mb_strtolower($text->textId) === mb_strtolower($pageKey)
            );
            $foundText = self::filterForLanguage($candidates, $language)[0] ?? null;
        }
        if (!$foundText) {
            $siteId = $site?->id;
            $candidates = ConsultationText::findAll([
                'siteId'         => $siteId,
                'consultationId' => null,
                'category'       => self::DEFAULT_CATEGORY,
                'textId'         => $pageKey,
            ]);
            $foundText = self::filterForLanguage($candidates, $language)[0] ?? null;
        }
        if (!$foundText && in_array($pageKey, static::getSystemwidePages())) {
            $candidates = ConsultationText::findAll([
                'siteId' => null,
                'category' => self::DEFAULT_CATEGORY,
                'textId' => $pageKey,
            ]);
            $template = self::filterForLanguage($candidates, $language)[0] ?? null;
            if (!$template) {
                $template = static::getDefaultPage($pageKey);
            }
            $foundText = new ConsultationText();
            $foundText->category = self::DEFAULT_CATEGORY;
            $foundText->textId = $pageKey;
            $foundText->text = $template->text;
            $foundText->breadcrumb = $template->breadcrumb;
            $foundText->title = $template->title;
            if ($site) {
                $foundText->siteId = $site->id;
            }
        }
        $defaultPage = static::getDefaultPage($pageKey);
        if (!$foundText) {
            $foundText = $defaultPage;
            if (!in_array($pageKey, static::getSystemwidePages())) {
                $foundText->siteId = $site?->id;
            }
            if (!in_array($pageKey, static::getSitewidePages())) {
                $foundText->consultationId = $consultation?->id;
            }
        } else {
            if (!$foundText->breadcrumb) {
                $foundText->breadcrumb = $defaultPage->breadcrumb;
            }
            if (!$foundText->title) {
                $foundText->title = $defaultPage->title;
            }
        }

        return $foundText;
    }

    /**
     * @return ConsultationText[]
     */
    public static function getAllPages(Site $site, ?Consultation $consultation): array
    {
        $pages = ConsultationText::findAll(['siteId' => $site->id, 'consultationId' => null, 'category' => self::DEFAULT_CATEGORY]);
        if ($consultation) {
            $pages = array_merge(
                $pages,
                ConsultationText::findAll(['consultationId' => $consultation->id, 'category' => self::DEFAULT_CATEGORY])
            );
        }
        $pages = self::filterForLanguage($pages, self::getContentLanguage($site));
        usort($pages, function ($page1, $page2) {
            return strnatcasecmp($page1->title ?? '', $page2->title ?? '');
        });

        return $pages;
    }

    // Hint: deadlines for votings are not implemented yet
    public function isInDeadline(string $type): bool
    {
        return true;
    }

    public function getDeadlinesByType(string $type): array
    {
        return [];
    }
}
