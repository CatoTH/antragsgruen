<?php

namespace app\models\db;

use app\components\MotionSorter;
use app\components\UrlHelper;
use app\models\amendmentNumbering\IAmendmentNumbering;
use app\models\exceptions\Internal;
use app\models\exceptions\NotFound;
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
 * @property string $eventDateFrom
 * @property string $eventDateTo
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
        /** @var \app\models\settings\AntragsgruenApp $app */
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
            [['title', 'titleShort', 'eventDateFrom', 'eventDateTo'], 'safe'],
            [['adminEmail', 'wordingBase', 'amendmentNumbering'], 'safe'],
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
                ->with(
                    'amendments', 'motionSupporters', 'amendments.amendmentSupporters',
                    'tags', 'motionSupporters.user', 'amendments.amendmentSupporters.user'
                )
                ->andWhere(Motion::tableName() . '.status != ' . Motion::STATUS_DELETED);
        } elseif ($this->preloadedAllMotionData === static::PRELOAD_ONLY_AMENDMENTS) {
            return $this->hasMany(Motion::class, ['consultationId' => 'id'])
                ->with('amendments')
                ->andWhere(Motion::tableName() . '.status != ' . Motion::STATUS_DELETED);
        } else {
            return $this->hasMany(Motion::class, ['consultationId' => 'id'])
                ->andWhere(Motion::tableName() . '.status != ' . Motion::STATUS_DELETED);
        }
    }

    /** @var Motion[] */
    private $motionCache = [];

    /**
     * @param string|null $motionSlug
     * @return Motion|null
     */
    public function getMotion($motionSlug)
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
            if (!is_numeric($motionSlug) && $motion->slug === $motionSlug && $motion->status !== Motion::STATUS_DELETED) {
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

    /**
     * @return Motion|null
     */
    public function getForcedMotion()
    {
        if ($this->getSettings()->forceMotion === null) {
            return null;
        }
        return $this->getMotion($this->getSettings()->forceMotion);
    }


    /** @var Amendment[] */
    private $amendmentCache = [];

    /**
     * @param int $amendmentId
     * @return Amendment|null
     */
    public function getAmendment($amendmentId)
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

    /**
     * @param int $amendmentId
     * @return bool
     */
    public function isMyAmendment($amendmentId)
    {
        if ($this->preloadedAllMotionData !== '') {
            return in_array($amendmentId, $this->preloadedAmendmentIds);
        } else {
            $amendment = $this->getAmendment($amendmentId);
            return ($amendment !== null);
        }
    }

    /**
     * @param $agendaItemId
     * @return ConsultationAgendaItem|null
     */
    public function getAgendaItem($agendaItemId)
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
    public function getMotionType($motionTypeId)
    {
        foreach ($this->motionTypes as $motionType) {
            if ($motionType->id == $motionTypeId) {
                return $motionType;
            }
        }
        throw new NotFound('Motion Type not found');
    }

    /** @var null|\app\models\settings\Consultation */
    private $settingsObject = null;

    /**
     * @return \app\models\settings\Consultation
     */
    public function getSettings()
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

    /**
     * @param \app\models\settings\Consultation $settings
     */
    public function setSettings($settings)
    {
        $this->settingsObject = $settings;
        $this->settings       = json_encode($settings, JSON_PRETTY_PRINT);
    }

    /**
     * @return IAmendmentNumbering
     */
    public function getAmendmentNumbering()
    {
        $numberings = IAmendmentNumbering::getNumberings();
        return new $numberings[$this->amendmentNumbering]();
    }

    /**
     * @param bool $withdrawnAreVisible
     * @param bool $includeResolutions
     * @return Motion[]
     */
    public function getVisibleMotions($withdrawnAreVisible = true, $includeResolutions = true)
    {
        $return            = [];
        $invisibleStatuses = $this->getInvisibleMotionStatuses($withdrawnAreVisible);
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

    /**
     * @param bool $withdrawnAreVisible
     *
     * @return int[]
     */
    public function getInvisibleMotionStatuses($withdrawnAreVisible = true)
    {
        $invisible = [
            IMotion::STATUS_DELETED,
            IMotion::STATUS_DRAFT,
            IMotion::STATUS_COLLECTING_SUPPORTERS,
            IMotion::STATUS_DRAFT_ADMIN,
            IMotion::STATUS_WITHDRAWN_INVISIBLE,
            IMotion::STATUS_MERGING_DRAFT_PRIVATE,
            IMotion::STATUS_MERGING_DRAFT_PUBLIC,
            IMotion::STATUS_PROPOSED_MODIFIED_AMENDMENT,
            IMotion::STATUS_INLINE_REPLY,
        ];
        if (!$this->getSettings()->screeningMotionsShown) {
            $invisible[] = IMotion::STATUS_SUBMITTED_UNSCREENED;
            $invisible[] = IMotion::STATUS_SUBMITTED_UNSCREENED_CHECKED;
        }
        if (!$withdrawnAreVisible) {
            $invisible[] = IMotion::STATUS_WITHDRAWN;
            //$invisible[] = IMotion::STATUS_MOVED;
            $invisible[] = IMotion::STATUS_MODIFIED;
            $invisible[] = IMotion::STATUS_MODIFIED_ACCEPTED;
            $invisible[] = IMotion::STATUS_PROCESSED;
        }
        return $invisible;
    }

    /**
     * @return int[]
     */
    public function getUnreadableStatuses()
    {
        $invisible = [
            IMotion::STATUS_DELETED,
            IMotion::STATUS_DRAFT,
            IMotion::STATUS_MERGING_DRAFT_PRIVATE,
            IMotion::STATUS_MERGING_DRAFT_PUBLIC,
            IMotion::STATUS_PROPOSED_MODIFIED_AMENDMENT,
        ];
        return $invisible;
    }

    /**
     * @param bool $withdrawnAreVisible
     * @return int[]
     */
    public function getInvisibleAmendmentStatuses($withdrawnAreVisible = true)
    {
        return $this->getInvisibleMotionStatuses($withdrawnAreVisible);
    }

    /**
     * @param int $motionTypeId
     * @return string
     */
    public function getNextMotionPrefix($motionTypeId)
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

    /**
     *
     */
    public function flushCacheWithChildren()
    {
        foreach ($this->motions as $motion) {
            $motion->flushCacheWithChildren();
        }
    }


    /**
     * @param string $text
     * @param array $backParams
     * @return \app\models\SearchResult[]
     * @throws \app\models\exceptions\Internal
     */
    public function fulltextSearch($text, $backParams)
    {
        $results = [];
        foreach ($this->motions as $motion) {
            if (in_array($motion->status, $this->getInvisibleMotionStatuses())) {
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
                    if (in_array($amend->status, $this->getInvisibleAmendmentStatuses())) {
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

    /**
     * @return bool
     */
    public function cacheOneMotionAffectsOthers()
    {
        if ($this->getSettings()->lineNumberingGlobal) {
            return true;
        }
        return false;
    }

    /**
     * @param string $prefix
     * @param null|Motion $ignore
     * @return null|Motion
     */
    public function findMotionWithPrefix($prefix, $ignore = null)
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

    /**
     * @return array
     */
    public function getAgendaWithMotions()
    {
        $ids    = [];
        $result = [];
        /**
         * @param $motion Motion
         */
        $addMotion = function ($motion) use (&$result) {
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

    /**
     * @return null|ConsultationFile
     */
    public function getAbsolutePdfLogo()
    {
        $logoUrl = $this->getSettings()->logoUrl;
        if ($logoUrl === '' || $logoUrl === null || $logoUrl[0] !== '/') {
            return null;
        }
        return ConsultationFile::findFileByName($this, urldecode(basename($logoUrl)));
    }


    /**
     * @return array
     */
    public function getPdfLogoData()
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

    /**
     */
    public function setDeleted()
    {
        $this->urlPath      = null;
        $this->dateDeletion = date('Y-m-d H:i:s');
        $this->save(false);
    }
}
