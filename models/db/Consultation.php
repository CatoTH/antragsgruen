<?php

namespace app\models\db;

use app\models\db\repostory\MotionRepository;
use app\models\settings\{IMotionStatusEngine, PrivilegeQueryContext, AntragsgruenApp};
use app\components\UrlHelper;
use app\models\amendmentNumbering\IAmendmentNumbering;
use app\models\exceptions\{Internal, NotFound};
use app\models\SearchResult;
use yii\db\{ActiveQuery, ActiveRecord};

/**
 * @property int $id
 * @property int $siteId
 * @property int $amendmentNumbering
 *
 * @property string|null $urlPath
 * @property string $title
 * @property string $titleShort
 * @property string $wordingBase
 * @property string $adminEmail
 * @property string $dateCreation
 * @property string $dateDeletion
 * @property string $settings
 *
 * @property Site $site
 * @property Motion[] $motions
 * @property ConsultationText[] $texts
 * @property ConsultationSettingsTag[] $tags
 * @property ConsultationMotionType[] $motionTypes
 * @property ConsultationAgendaItem[] $agendaItems
 * @property ConsultationUserGroup[] $userGroups
 * @property ConsultationFile[] $files
 * @property ConsultationFileGroup[] $fileGroups
 * @property ConsultationLog[] $logEntries
 * @property SpeechQueue[] $speechQueues
 * @property UserNotification[] $userNotifications
 * @property VotingBlock[] $votingBlocks
 * @property VotingQuestion[] $votingQuestions
 * @property UserConsultationScreening[] $screeningUsers
 */
class Consultation extends ActiveRecord
{
    public const TITLE_SHORT_MAX_LEN = 45;

    public const BLOCKED_URL_PATHS = ['login', 'logout', 'rest', 'token'];

    private static ?Consultation $current = null;

    /**
     * @throws Internal
     */
    public static function setCurrent(Consultation $consultation): void
    {
        if (self::$current) {
            throw new Internal('Current consultation already set');
        }
        self::$current = $consultation;
    }

    public static function getCurrent(): ?Consultation
    {
        return self::$current;
    }

    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'consultation';
    }

    public function rules(): array
    {
        return [
            [['title', 'dateCreation'], 'required'],
            [['title', 'titleShort', 'adminEmail', 'wordingBase', 'amendmentNumbering'], 'safe'],
            ['!urlPath', 'match', 'pattern' => '/^[\w_-]+$/i'],
        ];
    }

    public function refresh(): bool
    {
        $this->preloadedAllMotionData = null;
        $this->preloadedAmendmentIds  = null;
        MotionRepository::flushCaches();

        return parent::refresh();
    }

    public function getSite(): ActiveQuery
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }

    public const PRELOAD_ONLY_AMENDMENTS = 'amendments';
    public const PRELOAD_ALL = 'all';
    private ?string $preloadedAllMotionData = null;
    private ?array $preloadedAmendmentIds  = null;

    public function preloadAllMotionData(string $preloadType): void
    {
        $this->preloadedAllMotionData = $preloadType;
        foreach ($this->motions as $motion) {
            foreach ($motion->amendments as $amendment) {
                $this->preloadedAmendmentIds[] = $amendment->id;
            }
        }
    }

    public function hasPreloadedMotionData(): ?string
    {
        return $this->preloadedAllMotionData;
    }

    public function getMotions(): ActiveQuery
    {
        if ($this->preloadedAllMotionData === self::PRELOAD_ALL) {
            return $this->hasMany(Motion::class, ['consultationId' => 'id'])
                ->with('amendments', 'tags', 'motionSupporters', 'amendments.amendmentSupporters')
                ->andWhere(Motion::tableName() . '.status != ' . Motion::STATUS_DELETED);
        } elseif ($this->preloadedAllMotionData === self::PRELOAD_ONLY_AMENDMENTS) {
            return $this->hasMany(Motion::class, ['consultationId' => 'id'])
                ->with('amendments', 'tags')
                ->andWhere(Motion::tableName() . '.status != ' . Motion::STATUS_DELETED);
        } else {
            return $this->hasMany(Motion::class, ['consultationId' => 'id'])
                ->andWhere(Motion::tableName() . '.status != ' . Motion::STATUS_DELETED);
        }
    }

    /**
     * @return Motion[]
     */
    public function getMotionsOfType(ConsultationMotionType $type): array
    {
        $motions = [];
        foreach ($this->motions as $motion) {
            if ($motion->motionTypeId === $type->id) {
                $motions[] = $motion;
            }
        }

        return $motions;
    }

    /**
     * @return Motion[]
     */
    public function getMotionsOfTag(ConsultationSettingsTag $tag): array
    {
        $motions = [];
        foreach ($this->motions as $motion) {
            $foundTag = false;
            foreach ($motion->getPublicTopicTags() as $_tag) {
                if ($_tag->id === $tag->id) {
                    $foundTag = true;
                }
            }
            if ($foundTag) {
                $motions[] = $motion;
            }
        }

        return $motions;
    }

    /** @var Motion[]|null[] */
    private array $motionCache = [];

    public function getMotion(string|int|null $motionSlug): ?Motion
    {
        if (is_null($motionSlug) || $motionSlug === 0) {
            return null;
        }
        if (isset($this->motionCache[$motionSlug])) {
            return $this->motionCache[$motionSlug];
        }
        foreach ($this->motions as $motion) {
            $this->motionCache[$motion->id] = $motion;
            if ($motion->slug) {
                $this->motionCache[$motion->slug] = $motion;
            }
            if (is_numeric($motionSlug) && $motion->id === intval($motionSlug) && $motion->status !== Motion::STATUS_DELETED) {
                return $motion;
            }
            if (!is_numeric($motionSlug) && mb_strtolower($motion->slug ?: '') === mb_strtolower($motionSlug ?: '') && $motion->status !== Motion::STATUS_DELETED) {
                return $motion;
            }
        }
        $this->motionCache[$motionSlug] = null;
        return null;
    }

    public function flushMotionCache(): void
    {
        $this->motionCache = [];
    }

    public function getForcedMotion(): ?Motion
    {
        if ($this->getSettings()->forceMotion === null) {
            return null;
        }
        return $this->getMotion($this->getSettings()->forceMotion);
    }


    /** @var Amendment[]|null[] */
    private array $amendmentCache = [];

    public function getAmendment(int $amendmentId): ?Amendment
    {
        $amendmentId = IntVal($amendmentId);
        if (isset($this->amendmentCache[$amendmentId])) {
            return $this->amendmentCache[$amendmentId];
        }
        foreach ($this->motions as $motion) {
            if ($motion->status === Motion::STATUS_DELETED) {
                continue;
            }
            foreach ($motion->amendments as $amendment) {
                $this->amendmentCache[$amendment->id] = $amendment;
                if ($amendment->id === $amendmentId && $amendment->status !== Amendment::STATUS_DELETED) {
                    return $amendment;
                }
            }
        }
        $this->amendmentCache[$amendmentId] = null;
        return null;
    }

    public function isMyAmendment(int $amendmentId): bool
    {
        if ($this->preloadedAllMotionData) {
            return in_array($amendmentId, $this->preloadedAmendmentIds);
        } else {
            $amendment = $this->getAmendment($amendmentId);
            return ($amendment !== null);
        }
    }

    /**
     * @param int $agendaItemId
     */
    public function getAgendaItem(int $agendaItemId): ?ConsultationAgendaItem
    {
        foreach ($this->agendaItems as $agendaItem) {
            if ($agendaItem->id === $agendaItemId) {
                return $agendaItem;
            }
        }
        return null;
    }

    public function getTexts(): ActiveQuery
    {
        return $this->hasMany(ConsultationText::class, ['consultationId' => 'id']);
    }

    public function getAgendaItems(): ActiveQuery
    {
        return $this->hasMany(ConsultationAgendaItem::class, ['consultationId' => 'id']);
    }

    public function getUserGroups(): ActiveQuery
    {
        return $this->hasMany(ConsultationUserGroup::class, ['consultationId' => 'id']);
    }

    public function getScreeningUsers(): ActiveQuery
    {
        return $this->hasMany(UserConsultationScreening::class, ['consultationId' => 'id']);
    }

    private array $availableUserGroupCache = [];

    /**
     * @param int[] $additionalIds
     * @return ConsultationUserGroup[]
     */
    public function getAllAvailableUserGroups(array $additionalIds = [], bool $allowCache = false): array
    {
        sort($additionalIds);
        $cacheKey = (count($additionalIds) > 0 ? implode('-', $additionalIds) : 'default');
        if ($allowCache && isset($this->availableUserGroupCache[$cacheKey])) {
            return $this->availableUserGroupCache[$cacheKey];
        }

        $this->availableUserGroupCache[$cacheKey] = ConsultationUserGroup::findByConsultation($this, $additionalIds);

        return $this->availableUserGroupCache[$cacheKey];
    }

    public function getUserGroupById(int $groupId, bool $allowCache = false): ?ConsultationUserGroup
    {
        foreach ($this->getAllAvailableUserGroups([], $allowCache) as $group) {
            if ($group->id === $groupId) {
                return $group;
            }
        }
        return null;
    }

    public function getFiles(): ActiveQuery
    {
        return $this->hasMany(ConsultationFile::class, ['consultationId' => 'id']);
    }

    public function getFileGroups(): ActiveQuery
    {
        return $this->hasMany(ConsultationFileGroup::class, ['consultationId' => 'id']);
    }

    /**
     * @return User[]
     */
    public function getUsersInAnyGroup(): array
    {
        $users = [];
        foreach ($this->getAllAvailableUserGroups([], true) as $userGroup) {
            foreach ($userGroup->users as $user) {
                if (!isset($users[$user->id])) {
                    $users[$user->id] = $user;
                }
            }
        }
        return array_values($users);
    }

    public function getTags(): ActiveQuery
    {
        return $this->hasMany(ConsultationSettingsTag::class, ['consultationId' => 'id']);
    }

    public function getVotingBlocks(): ActiveQuery
    {
        return $this->hasMany(VotingBlock::class, ['consultationId' => 'id'])
            ->andWhere(VotingBlock::tableName() . '.votingStatus != ' . VotingBlock::STATUS_DELETED);
    }

    public function getVotingBlock(int $votingBlockId): ?VotingBlock
    {
        foreach ($this->votingBlocks as $votingBlock) {
            if ($votingBlock->id == $votingBlockId) {
                return $votingBlock;
            }
        }
        return null;
    }

    public function getVotingQuestions(): ActiveQuery
    {
        return $this->hasMany(VotingQuestion::class, ['consultationId' => 'id']);
    }

    public function getVotingQuestion(int $questionId): ?VotingQuestion
    {
        foreach ($this->votingQuestions as $question) {
            if ($question->id == $questionId) {
                return $question;
            }
        }
        return null;
    }

    public function getLogEntries(): ActiveQuery
    {
        return $this->hasMany(ConsultationLog::class, ['consultationId' => 'id']);
    }

    public function getMotionTypes(): ActiveQuery
    {
        return $this->hasMany(ConsultationMotionType::class, ['consultationId' => 'id'])
            ->andWhere(ConsultationMotionType::tableName() . '.status != ' . ConsultationMotionType::STATUS_DELETED);
    }

    public function getSpeechQueues(): ActiveQuery
    {
        return $this->hasMany(SpeechQueue::class, ['consultationId' => 'id']);
    }

    public function getActiveSpeechQueue(): ?SpeechQueue
    {
        $firstActive = null;
        $firstActiveWithNoAssignment = null;
        foreach ($this->speechQueues as $speechQueue) {
            if ($speechQueue->isActive) {
                if ($firstActive === null) {
                    $firstActive = $speechQueue;
                }
                if ($firstActiveWithNoAssignment === null && $speechQueue->motionId === null || $speechQueue->agendaItemId === null) {
                    $firstActiveWithNoAssignment = $speechQueue;
                }
            }
        }

        if ($firstActiveWithNoAssignment) {
            return $firstActiveWithNoAssignment;
        } else {
            return null;
        }
    }

    public function getUserNotifications(): ActiveQuery
    {
        return $this->hasMany(UserNotification::class, ['consultationId' => 'id']);
    }

    /**
     * @return UserNotification[]
     */
    public function getUserNotificationsType(int $type): array
    {
        $notis = [];
        foreach ($this->userNotifications as $userNotification) {
            if ($userNotification->notificationType === $type && $userNotification->user) {
                $notis[] = $userNotification;
            }
        }
        return $notis;
    }

    /**
     * @throws NotFound
     */
    public function getMotionType(int $motionTypeId): ConsultationMotionType
    {
        foreach ($this->motionTypes as $motionType) {
            if ($motionType->id === $motionTypeId) {
                return $motionType;
            }
        }
        throw new NotFound('Motion Type not found');
    }

    private ?\app\models\settings\Consultation $settingsObject = null;

    public function getSettings(): \app\models\settings\Consultation
    {
        if (!is_object($this->settingsObject)) {
            $settingsClass = \app\models\settings\Consultation::class;

            foreach (AntragsgruenApp::getActivePlugins() as $pluginClass) {
                if ($pluginClass::getConsultationSettingsClass($this)) {
                    $settingsClass = $pluginClass::getConsultationSettingsClass($this);
                }
            }

            $this->settingsObject = new $settingsClass($this->settings);
        }
        return $this->settingsObject;
    }

    public function setSettings(?\app\models\settings\Consultation $settings): void
    {
        $this->settingsObject = $settings;
        $this->settings = json_encode($settings, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    public function getAmendmentNumbering(): IAmendmentNumbering
    {
        $numberings = IAmendmentNumbering::getNumberings();
        /** @var IAmendmentNumbering $numbering */
        $numbering = new $numberings[$this->amendmentNumbering]();

        return $numbering;
    }

    private ?IMotionStatusEngine $statusEngine = null;

    public function getStatuses(): IMotionStatusEngine
    {
        if ($this->statusEngine === null) {
            $this->statusEngine = new IMotionStatusEngine($this);
        }
        return $this->statusEngine;
    }

    public function havePrivilege(int $privilege, ?PrivilegeQueryContext $context): bool
    {
        $user = User::getCurrentUser();
        if (!$user) {
            return false;
        }
        return $user->hasPrivilege($this, $privilege, $context);
    }

    public function createDefaultUserGroups(): void
    {
        $this->link('userGroups', ConsultationUserGroup::createDefaultGroupConsultationAdmin($this));
        $this->link('userGroups', ConsultationUserGroup::createDefaultGroupProposedProcedure($this));
        $this->link('userGroups', ConsultationUserGroup::createDefaultGroupParticipant($this));
        $this->link('userGroups', ConsultationUserGroup::createDefaultGroupProgressReport($this));

        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            $plugin::createDefaultUserGroups($this);
        }
    }

    /**
     * Hint: $parentTagId === null => all tags are returned (not only root-level)
     * @return ConsultationSettingsTag[]
     */
    public function getSortedTags(int $type, ?int $parentTagId = null): array
    {
        $tags = array_filter($this->tags, function(ConsultationSettingsTag $tag) use ($type, $parentTagId): bool {
            if ($parentTagId && $tag->parentTagId !== $parentTagId) {
                return false;
            }
            return $tag->type === $type;
        });
        usort($tags, function (ConsultationSettingsTag $tag1, ConsultationSettingsTag $tag2): int {
            if ($tag1->position < $tag2->position) {
                return -1;
            }
            if ($tag1->position > $tag2->position) {
                return 1;
            }
            return strnatcasecmp($tag1->getNormalizedName(), $tag2->getNormalizedName());
        });
        return $tags;
    }

    public function getExistingTag(int $type, string $nameUnnormalized): ?ConsultationSettingsTag
    {
        $nameNormalized = ConsultationSettingsTag::normalizeName($nameUnnormalized);
        foreach ($this->tags as $tag) {
            if ($tag->type === $type && $tag->getNormalizedName() === $nameNormalized) {
                return $tag;
            }
        }
        return null;
    }

    public function getExistingTagOrCreate(int $type, string $nameUnnormalized, int $position): ConsultationSettingsTag
    {
        if ($tag = $this->getExistingTag($type, $nameUnnormalized)) {
            return $tag;
        }

        $tag = new ConsultationSettingsTag();
        $tag->type = $type;
        $tag->title = $nameUnnormalized;
        $tag->consultationId = $this->id;
        $tag->position = $position;
        $tag->save();

        return $tag;
    }

    public function getTagById(int $id): ?ConsultationSettingsTag
    {
        foreach ($this->tags as $tag) {
            if ($tag->id === $id) {
                return $tag;
            }
        }
        return null;
    }

    /**
     * @param ConsultationSettingsTag[] $tags
     * @throws NotFound
     */
    public function getNextMotionPrefix(int $motionTypeId, array $tags, ?string $forcePrefix = null): string
    {
        $max_rev = 0;
        $motionType = $this->getMotionType($motionTypeId);
        $prefix = $motionType->motionPrefix;

        // Tag-specific prefixes have higher priorities. However, if two tags with prefixes are assigned, any is taken.
        // So tag-specific prefixes this should only be used when only one tag per motion is allowed.
        foreach ($tags as $tag) {
            if ($tag->getSettingsObj()->motionPrefix) {
                $prefix = $tag->getSettingsObj()->motionPrefix;
            }
        }
        if ($prefix === '' || $prefix === null) {
            $prefix = 'A';
        }
        if ($forcePrefix) {
            $prefix = $forcePrefix;
        }

        $prefixLen = (int)grapheme_strlen($prefix);
        foreach ($this->motions as $motion) {
            if ($motion->status !== Motion::STATUS_DELETED) {
                if (grapheme_substr($motion->titlePrefix, 0, $prefixLen) === $prefix) {
                    $revs = grapheme_substr($motion->titlePrefix, $prefixLen);
                    $revnr = intval($revs);
                    if ($revnr > $max_rev) {
                        $max_rev = $revnr;
                    }
                }
                foreach ($motion->amendments as $amendment) {
                    if ($motion->status !== Amendment::STATUS_DELETED && grapheme_substr($amendment->titlePrefix ?: '', 0, $prefixLen) === $prefix) {
                        $revs = grapheme_substr($amendment->titlePrefix, $prefixLen);
                        $revnr = intval($revs);
                        if ($revnr > $max_rev) {
                            $max_rev = $revnr;
                        }
                    }
                }
            }
        }

        $newNr = (string)($max_rev + 1);
        while (strlen($newNr) < $this->getSettings()->motionTitlePrefixNumMaxLen) {
            $newNr = '0' . $newNr;
        }

        return $prefix . $newNr;
    }

    public function flushCacheWithChildren(?array $items): void
    {
        foreach ($this->motions as $motion) {
            $motion->flushCacheWithChildren($items);
        }
    }


    /**
     * @return SearchResult[]
     * @throws Internal
     */
    public function fulltextSearch(string $text, array $backParams): array
    {
        $results = [];
        foreach ($this->motions as $motion) {
            if (in_array($motion->status, $this->getStatuses()->getInvisibleMotionStatuses())) {
                continue;
            }
            $found = false;
            foreach ($motion->getActiveSections() as $section) {
                if (!$found && $section->getSectionType()->matchesFulltextSearch($text)) {
                    $found             = true;
                    $result            = new SearchResult();
                    $result->id        = 'motion' . $motion->id;
                    $result->typeTitle = $motion->motionType->titleSingular;
                    $result->type      = SearchResult::TYPE_MOTION;
                    $result->title     = $motion->getTitleWithPrefix();
                    $result->link      = UrlHelper::createMotionUrl($motion, 'view', $backParams);
                    $results[]         = $result;
                }
            }
            if (!$found) {
                foreach ($motion->amendments as $amend) {
                    if (in_array($amend->status, $this->getStatuses()->getInvisibleAmendmentStatuses())) {
                        continue;
                    }
                    foreach ($amend->getActiveSections() as $section) {
                        if (!$found && $section->getSectionType()->matchesFulltextSearch($text)) {
                            $found             = true;
                            $result            = new SearchResult();
                            $result->id        = 'amendment' . $amend->id;
                            $result->typeTitle = \Yii::t('amend', 'amendment');
                            $result->type      = SearchResult::TYPE_AMENDMENT;
                            $result->title     = $amend->getTitle();
                            $result->link      = UrlHelper::createAmendmentUrl($amend, 'view', $backParams);
                            $results[]         = $result;
                        }
                    }
                }
            }
        }
        /*
         * @TODO: - Comments
         */
        return $results;
    }

    public function cacheOneMotionAffectsOthers(): bool
    {
        if ($this->getSettings()->lineNumberingGlobal) {
            return true;
        }
        return false;
    }

    public function findMotionWithPrefixAndVersion(string $prefix, string $version, ?Motion $ignore = null): ?Motion
    {
        $prefixNorm = trim(mb_strtoupper($prefix));
        $versionNorm = trim(mb_strtoupper($version));
        foreach ($this->motions as $mot) {
            $motPrefixNorm = trim(mb_strtoupper($mot->titlePrefix));
            $motVersionNorm = trim(mb_strtoupper($mot->version));
            if ($motPrefixNorm !== '' && $motPrefixNorm === $prefixNorm && $motVersionNorm === $versionNorm && $mot->status !== Motion::STATUS_DELETED) {
                if ($ignore === null || $ignore->id !== $mot->id) {
                    return $mot;
                }
            }
        }
        return null;
    }

    public function getAbsolutePdfLogo(): ?ConsultationFile
    {
        $logoUrl = $this->getSettings()->logoUrl;
        if ($logoUrl === '' || $logoUrl === null || $logoUrl[0] !== '/') {
            return null;
        }
        return ConsultationFile::findFileByName($this, urldecode(basename($logoUrl)));
    }

    public function getPdfLogoData(): array
    {
        if ($this->getSettings()->logoUrl) {
            $file = ConsultationFile::findFileByName($this, urldecode(basename($this->getSettings()->logoUrl)));
        } else {
            $file = null;
        }
        if ($file) {
            return [$file->mimetype, $file->data];
        } else {
            foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
                if ($plugin::getDefaultLogo()) {
                    $logo = $plugin::getDefaultLogo();
                    $logo[1] = file_get_contents($logo[1]);
                    return $logo;
                }
            }
        }
        return [
            'image/png',
            file_get_contents(\Yii::$app->basePath . '/web/img/logo.png')
        ];
    }

    /**
     * @return ConsultationFile[]
     */
    public function getDownloadableFiles(?int $groupId): array
    {
        $files = array_filter($this->files, function(ConsultationFile $file) use ($groupId) {
            return $file->downloadPosition !== null && $file->fileGroupId === $groupId;
        });
        usort($files, function (ConsultationFile $file1, ConsultationFile $file2) {
           return $file1 <=> $file2;
        });
        return $files;
    }

    /**
     * @return string[]
     */
    public function getAdminEmails(): array
    {
        $mails        = preg_split('/[,;]/', $this->adminEmail);
        $filtered     = [];
        foreach ($mails as $mail) {
            if (filter_var(trim($mail), FILTER_VALIDATE_EMAIL)) {
                $filtered[] = trim($mail);
            }
        }
        return $filtered;
    }

    public function setDeleted(): void
    {
        $this->urlPath      = null;
        $this->dateDeletion = date('Y-m-d H:i:s');
        $this->save(false);
    }

    public function hasProposedProcedures(): bool
    {
        foreach ($this->motionTypes as $motionType) {
            if ($motionType->getSettingsObj()->hasProposedProcedure) {
                return true;
            }
        }
        return false;
    }

    public function getDateTime(): ?\DateTime
    {
        if ($this->dateCreation) {
            return \DateTime::createFromFormat('Y-m-d H:i:s', $this->dateCreation);
        } else {
            return null;
        }
    }
}
