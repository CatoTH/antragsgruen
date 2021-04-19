<?php

namespace app\models\db;

use app\models\settings\IMotionStatusEngine;
use app\components\{MotionSorter, UrlHelper};
use app\models\amendmentNumbering\IAmendmentNumbering;
use app\models\exceptions\{Internal, NotFound};
use app\models\SearchResult;
use app\models\settings\AntragsgruenApp;
use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $siteId
 * @property int $amendmentNumbering
 *
 * @property string $urlPath
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
 * @property ConsultationOdtTemplate[] $odtTemplates
 * @property ConsultationSettingsTag[] $tags
 * @property ConsultationMotionType[] $motionTypes
 * @property ConsultationAgendaItem[] $agendaItems
 * @property ConsultationUserPrivilege[] $userPrivileges
 * @property ConsultationFile[] $files
 * @property ConsultationLog[] $logEntries
 * @property SpeechQueue[] $speechQueues
 * @property UserNotification[] $userNotifications
 * @property VotingBlock[] $votingBlocks
 */
class Consultation extends ActiveRecord
{
    const TITLE_SHORT_MAX_LEN = 45;

    /** @var null|Consultation */
    private static $current = null;

    /**
     * @param Consultation $consultation
     * @throws Internal
     */
    public static function setCurrent(Consultation $consultation)
    {
        if (static::$current) {
            throw new Internal('Current consultation already set');
        }
        static::$current = $consultation;
    }

    /**
     * @return Consultation|null
     */
    public static function getCurrent()
    {
        return static::$current;
    }

    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'consultation';
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['title', 'dateCreation'], 'required'],
            [['title', 'titleShort', 'adminEmail', 'wordingBase', 'amendmentNumbering'], 'safe'],
            ['!urlPath', 'match', 'pattern' => '/^[\w_-]+$/i'],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSite()
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }

    const PRELOAD_ONLY_AMENDMENTS = 'amendments';
    const PRELOAD_ALL = 'all';
    private $preloadedAllMotionData = '';
    private $preloadedAmendmentIds  = null;
    private $preloadedMotionIds     = null;

    public function preloadAllMotionData(string $preloadType)
    {
        $this->preloadedAllMotionData = $preloadType;
        foreach ($this->motions as $motion) {
            $this->preloadedMotionIds[] = $motion->id;
            foreach ($motion->amendments as $amendment) {
                $this->preloadedAmendmentIds[] = $amendment->id;
            }
        }
    }

    public function hasPreloadedMotionData(): string
    {
        return $this->preloadedAllMotionData;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotions()
    {
        if ($this->preloadedAllMotionData === static::PRELOAD_ALL) {
            return $this->hasMany(Motion::class, ['consultationId' => 'id'])
                ->with('amendments', 'tags', 'motionSupporters', 'amendments.amendmentSupporters')
                ->andWhere(Motion::tableName() . '.status != ' . Motion::STATUS_DELETED);
        } elseif ($this->preloadedAllMotionData === static::PRELOAD_ONLY_AMENDMENTS) {
            return $this->hasMany(Motion::class, ['consultationId' => 'id'])
                ->with('amendments', 'tags')
                ->andWhere(Motion::tableName() . '.status != ' . Motion::STATUS_DELETED);
        } else {
            return $this->hasMany(Motion::class, ['consultationId' => 'id'])
                ->andWhere(Motion::tableName() . '.status != ' . Motion::STATUS_DELETED);
        }
    }

    /**
     * @param ConsultationMotionType $type
     *
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

    /** @var Motion[]|null[] */
    private $motionCache = [];

    /**
     * @param string|null $motionSlug
     */
    public function getMotion($motionSlug): ?Motion
    {
        if (is_null($motionSlug)) {
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
            if (!is_numeric($motionSlug) && mb_strtolower($motion->slug) === mb_strtolower($motionSlug) && $motion->status !== Motion::STATUS_DELETED) {
                return $motion;
            }
        }
        $this->motionCache[$motionSlug] = null;
        return null;
    }

    public function flushMotionCache()
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
    private $amendmentCache = [];

    /**
     * @param int $amendmentId
     */
    public function getAmendment($amendmentId): ?Amendment
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
        if ($this->preloadedAllMotionData !== '') {
            return in_array($amendmentId, $this->preloadedAmendmentIds);
        } else {
            $amendment = $this->getAmendment($amendmentId);
            return ($amendment !== null);
        }
    }

    /**
     * @param int $agendaItemId
     */
    public function getAgendaItem($agendaItemId): ?ConsultationAgendaItem
    {
        foreach ($this->agendaItems as $agendaItem) {
            if ($agendaItem->id == $agendaItemId) {
                return $agendaItem;
            }
        }
        return null;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTexts()
    {
        return $this->hasMany(ConsultationText::class, ['consultationId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOdtTemplates()
    {
        return $this->hasMany(ConsultationOdtTemplate::class, ['consultationId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAgendaItems()
    {
        return $this->hasMany(ConsultationAgendaItem::class, ['consultationId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserPrivileges()
    {
        return $this->hasMany(ConsultationUserPrivilege::class, ['consultationId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFiles()
    {
        return $this->hasMany(ConsultationFile::class, ['consultationId' => 'id']);
    }

    /**
     * @param User $user
     * @return ConsultationUserPrivilege
     */
    public function getUserPrivilege(User $user)
    {
        foreach ($this->userPrivileges as $priv) {
            if ($priv->userId == $user->id) {
                return $priv;
            }
        }
        $priv                   = new ConsultationUserPrivilege();
        $priv->consultationId   = $this->id;
        $priv->userId           = $user->id;
        $priv->privilegeCreate  = 0;
        $priv->privilegeView    = 0;
        $priv->adminContentEdit = 0;
        $priv->adminScreen      = 0;
        $priv->adminSuper       = 0;
        $priv->adminProposals   = 0;
        return $priv;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTags()
    {
        return $this->hasMany(ConsultationSettingsTag::class, ['consultationId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVotingBlocks()
    {
        return $this->hasMany(VotingBlock::class, ['consultationId' => 'id']);
    }

    /**
     * @param int $votingBlockId
     * @return VotingBlock|null
     */
    public function getVotingBlock($votingBlockId)
    {
        foreach ($this->votingBlocks as $votingBlock) {
            if ($votingBlock->id == $votingBlockId) {
                return $votingBlock;
            }
        }
        return null;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLogEntries()
    {
        return $this->hasMany(ConsultationLog::class, ['consultationId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotionTypes()
    {
        return $this->hasMany(ConsultationMotionType::class, ['consultationId' => 'id'])
            ->andWhere(ConsultationMotionType::tableName() . '.status != ' . ConsultationMotionType::STATUS_DELETED);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSpeechQueues()
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

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserNotifications()
    {
        return $this->hasMany(UserNotification::class, ['consultationId' => 'id']);
    }

    /**
     * @param int $type
     * @return UserNotification[]
     */
    public function getUserNotificationsType($type)
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
     * @param int $motionTypeId
     * @return ConsultationMotionType
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

    /** @var null|\app\models\settings\Consultation */
    private $settingsObject = null;

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

    public function setSettings(?\app\models\settings\Consultation $settings)
    {
        $this->settingsObject = $settings;
        $this->settings       = json_encode($settings, JSON_PRETTY_PRINT);
    }

    public function getAmendmentNumbering(): IAmendmentNumbering
    {
        $numberings = IAmendmentNumbering::getNumberings();
        return new $numberings[$this->amendmentNumbering]();
    }

    /** @var null|IMotionStatusEngine */
    private $statusEngine = null;

    public function getStatuses(): IMotionStatusEngine
    {
        if ($this->statusEngine === null) {
            $this->statusEngine = new IMotionStatusEngine($this);
        }
        return $this->statusEngine;
    }

    /**
     * @param bool $withdrawnAreVisible
     * @param bool $includeResolutions
     * @return Motion[]
     */
    public function getVisibleMotions($withdrawnAreVisible = true, $includeResolutions = true)
    {
        $return            = [];
        $invisibleStatuses = $this->getStatuses()->getInvisibleMotionStatuses($withdrawnAreVisible);
        if (!$includeResolutions) {
            $invisibleStatuses[] = IMotion::STATUS_RESOLUTION_PRELIMINARY;
            $invisibleStatuses[] = IMotion::STATUS_RESOLUTION_FINAL;
        }
        foreach ($this->motions as $motion) {
            if (!in_array($motion->status, $invisibleStatuses)) {
                $return[] = $motion;
            }
        }
        return $return;
    }

    /**
     * @param bool $includeWithdrawn
     * @return Motion[]
     */
    public function getVisibleMotionsSorted($includeWithdrawn = true)
    {
        $motions   = [];
        $motionIds = [];
        $items     = ConsultationAgendaItem::getSortedFromConsultation($this);
        foreach ($items as $agendaItem) {
            $newMotions = MotionSorter::getSortedMotionsFlat($this, $agendaItem->getVisibleMotions($includeWithdrawn));
            foreach ($newMotions as $newMotion) {
                $motions[]   = $newMotion;
                $motionIds[] = $newMotion->id;
            }
        }
        $noAgendaMotions = [];
        foreach ($this->getVisibleMotions($includeWithdrawn) as $motion) {
            if (!in_array($motion->id, $motionIds)) {
                $noAgendaMotions[] = $motion;
                $motionIds[]       = $motion->id;
            }
        }
        $noAgendaMotions = MotionSorter::getSortedMotionsFlat($this, $noAgendaMotions);
        $motions         = array_merge($motions, $noAgendaMotions);
        return $motions;
    }

    /**
     * @param int|int[] $privilege
     * @return bool
     *
     */
    public function havePrivilege($privilege)
    {
        $user = User::getCurrentUser();
        if (!$user) {
            return false;
        }
        return $user->hasPrivilege($this, $privilege);
    }


    /**
     * @return ConsultationSettingsTag[]
     */
    public function getSortedTags()
    {
        $tags = $this->tags;
        usort(
            $tags,
            function ($tag1, $tag2) {
                /** @var ConsultationSettingsTag $tag1 */
                /** @var ConsultationSettingsTag $tag2 */
                if ($tag1->position < $tag2->position) {
                    return -1;
                }
                if ($tag1->position > $tag2->position) {
                    return 1;
                }
                return 0;
            }
        );
        return $tags;
    }

    public function getNextMotionPrefix(int $motionTypeId): string
    {
        $max_rev = 0;
        /** @var ConsultationMotionType $motionType */
        $motionType = null;
        foreach ($this->motionTypes as $t) {
            if ($t->id == $motionTypeId) {
                $motionType = $t;
            }
        }
        $prefix = $motionType->motionPrefix;
        if ($prefix == '') {
            $prefix = 'A';
        }
        foreach ($this->motions as $motion) {
            if ($motion->status != Motion::STATUS_DELETED) {
                if (mb_substr($motion->titlePrefix, 0, mb_strlen($prefix)) !== $prefix) {
                    continue;
                }
                $revs  = mb_substr($motion->titlePrefix, mb_strlen($prefix));
                $revnr = IntVal($revs);
                if ($revnr > $max_rev) {
                    $max_rev = $revnr;
                }
            }
        }
        return $prefix . ($max_rev + 1);
    }

    public function flushCacheWithChildren(?array $items): void
    {
        foreach ($this->motions as $motion) {
            $motion->flushCacheWithChildren($items);
        }
    }


    /**
     * @param string $text
     * @param array $backParams
     * @return SearchResult[]
     * @throws Internal
     */
    public function fulltextSearch($text, $backParams)
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

    public function findMotionWithPrefix(string $prefix, ?Motion $ignore = null): ?Motion
    {
        $prefixNorm = trim(mb_strtoupper($prefix));
        foreach ($this->motions as $mot) {
            $motPrefixNorm = trim(mb_strtoupper($mot->titlePrefix));
            if ($motPrefixNorm != '' && $motPrefixNorm === $prefixNorm && $mot->status != Motion::STATUS_DELETED) {
                if ($ignore === null || $ignore->id != $mot->id) {
                    return $mot;
                }
            }
        }
        return null;
    }

    public function getAgendaWithMotions(): array
    {
        $ids    = [];
        $result = [];
        $addMotion = function (Motion $motion) use (&$result) {
            $result[] = $motion;
            $result   = array_merge($result, MotionSorter::getSortedAmendments($this, $motion->getVisibleAmendments()));
        };

        $items = ConsultationAgendaItem::getSortedFromConsultation($this);
        foreach ($items as $agendaItem) {
            $result[] = $agendaItem;
            $motions  = MotionSorter::getSortedMotionsFlat($this, $agendaItem->getVisibleMotions());
            foreach ($motions as $motion) {
                $ids[] = $motion->id;
                $addMotion($motion);
            }
        }
        $result[] = null;

        foreach ($this->getVisibleMotions() as $motion) {
            if (!(in_array($motion->id, $ids) || count($motion->getVisibleReplacedByMotions()) > 0)) {
                $addMotion($motion);
            }
        }
        return $result;
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
            $file = ConsultationFile::findFileByUrl($this, $this->getSettings()->logoUrl);
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
    public function getDownloadableFiles(): array
    {
        $files = array_filter($this->files, function(ConsultationFile $file) {
            return $file->downloadPosition !== null;
        });
        usort($files, function (ConsultationFile $file1, ConsultationFile $file2) {
           return $file1 <=> $file2;
        });
        return $files;
    }

    /**
     * @return string[]
     */
    public function getAdminEmails()
    {
        $mails        = preg_split('/[,;]/', $this->adminEmail);
        $filtered     = [];
        foreach ($mails as $mail) {
            if (trim($mail) !== '') {
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
