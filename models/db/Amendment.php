<?php

namespace app\models\db;

use app\components\diff\AmendmentSectionFormatter;
use app\components\diff\Diff;
use app\components\diff\Diff2;
use app\components\RSSExporter;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\exceptions\MailNotSent;
use app\models\policies\All;
use app\models\sectionTypes\ISectionType;
use app\models\sectionTypes\TextSimple;
use yii\db\ActiveQuery;
use yii\helpers\Html;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $motionId
 * @property string $titlePrefix
 * @property string $changeEditorial
 * @property string $changeText
 * @property string $changeExplanation
 * @property int $changeExplanationHtml
 * @property string $cache
 * @property string $dateCreation
 * @property string $datePublication
 * @property string $dateResolution
 * @property int $status
 * @property string $statusString
 * @property string $noteInternal
 * @property int $textFixed
 *
 * @property AmendmentComment[] $comments
 * @property AmendmentAdminComment[] $adminComments
 * @property AmendmentSupporter[] $amendmentSupporters
 * @property AmendmentSection[] $sections
 */
class Amendment extends IMotion implements IRSSItem
{
    use CacheTrait;

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'amendment';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getComments()
    {
        return $this->hasMany(AmendmentComment::class, ['amendmentId' => 'id'])
            ->andWhere(AmendmentComment::tableName() . '.status != ' . AmendmentComment::STATUS_DELETED);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAdminComments()
    {
        return $this->hasMany(AmendmentAdminComment::class, ['amendmentId' => 'id'])
            ->andWhere(AmendmentAdminComment::tableName() . '.status != ' . AmendmentAdminComment::STATUS_DELETED);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAmendmentSupporters()
    {
        return $this->hasMany(AmendmentSupporter::class, ['amendmentId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSections()
    {
        return $this->hasMany(AmendmentSection::class, ['amendmentId' => 'id']);
    }

    /**
     * @param int $sectionId
     * @return AmendmentSection|null
     */
    public function getSection($sectionId)
    {
        foreach ($this->sections as $section) {
            if ($section->sectionId == $sectionId) {
                return $section;
            }
        }
        return null;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['motionId'], 'required'],
            [['id', 'motionId', 'status', 'textFixed'], 'number'],
        ];
    }


    /**
     * @return string
     */
    public function getTitle()
    {
        $motion = $this->getMyMotion();
        if ($motion->titlePrefix != '') {
            $showMotionPrefix = (mb_stripos($this->titlePrefix, $motion->titlePrefix) === false);
        } else {
            $showMotionPrefix = false;
        }
        $prefix = ($this->titlePrefix != '' ? $this->titlePrefix : \yii::t('amend', 'amendment'));
        if ($this->getMyConsultation()->getSettings()->hideTitlePrefix) {
            return $prefix . \yii::t('amend', 'amend_for') . $motion->title;
        } else {
            if ($this->getMyMotion()->titlePrefix != '') {
                if ($showMotionPrefix) {
                    $str = $prefix . \yii::t('amend', 'amend_for');
                    $str .= $motion->titlePrefix . ': ' . $motion->title;
                    return $str;
                } else {
                    return $prefix . ': ' . $motion->title;
                }
            } else {
                return $prefix . \yii::t('amend', 'amend_for') . $motion->title;
            }
        }
    }

    /**
     * @return string
     */
    public function getShortTitle()
    {
        if ($this->getMyMotion()->titlePrefix != '') {
            $showMotionPrefix = (mb_stripos($this->titlePrefix, $this->getMyMotion()->titlePrefix) === false);
        } else {
            $showMotionPrefix = false;
        }
        if ($this->getMyConsultation()->getSettings()->hideTitlePrefix) {
            return $this->titlePrefix . \Yii::t('amend', 'amend_for') . $this->getMyMotion()->title;
        } else {
            if ($this->getMyMotion()->titlePrefix != '') {
                if ($showMotionPrefix) {
                    return $this->titlePrefix . \Yii::t('amend', 'amend_for') . $this->getMyMotion()->titlePrefix;
                } else {
                    return $this->titlePrefix;
                }
            } else {
                return $this->titlePrefix . \Yii::t('amend', 'amend_for') . $this->getMyMotion()->title;
            }
        }
    }

    /**
     * @return Consultation
     */
    public function getMyConsultation()
    {
        $current = Consultation::getCurrent();
        if ($current && $current->getAmendment($this->id)) {
            return $current;
        } else {
            /** @var Motion $motion */
            $motion = Motion::findOne($this->motionId);
            return Consultation::findOne($motion->consultationId);
        }
    }

    private $myMotion = null;

    /**
     * @return Motion
     */
    public function getMyMotion()
    {
        if (!$this->myMotion) {
            $current = Consultation::getCurrent();
            if ($current) {
                $motion = $current->getMotion($this->motionId);
                if ($motion) {
                    $this->myMotion = $motion;
                } else {
                    $this->myMotion = Motion::findOne($this->motionId);
                }
            }
            $this->myMotion = Motion::findOne($this->motionId);
        }
        return $this->myMotion;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotionJoin()
    {
        return $this->hasOne(Motion::class, ['id' => 'motionId'])
            ->andWhere(Motion::tableName() . '.status != ' . Motion::STATUS_DELETED);
    }

    /**
     * @return ConsultationSettingsMotionSection
     */
    public function getMySections()
    {

        return $this->getMyMotion()->motionType->motionSections;
    }

    /**
     * @param string $changeId
     * @return string
     */
    public function getLiteChangeData($changeId)
    {
        $time       = Tools::dateSql2timestamp($this->dateCreation) * 1000;
        $changeData = ' data-cid="' . Html::encode($changeId) . '" data-userid="" ';
        $changeData .= 'data-username="' . Html::encode($this->getInitiatorsStr()) . '" ';
        $changeData .= 'data-changedata="" data-time="' . $time . '" data-last-change-time="' . $time . '" ';
        $changeData .= 'data-append-hint="[' . Html::encode($this->titlePrefix) . ']"';
        return $changeData;
    }


    /**
     * @return int
     */
    public function getFirstDiffLine()
    {

        $cached = $this->getCacheItem('getFirstDiffLine');
        if ($cached !== null) {
            return $cached;
        }
        $firstLine = $this->getMyMotion()->getFirstLineNumber();
        $lineLength = $this->getMyConsultation()->getSettings()->lineLength;

        foreach ($this->sections as $section) {
            if ($section->getSettings()->type != ISectionType::TYPE_TEXT_SIMPLE) {
                continue;
            }
            $formatter = new AmendmentSectionFormatter();
            $formatter->setTextOriginal($section->getOriginalMotionSection()->data);
            $formatter->setTextNew($section->data);
            $formatter->setFirstLineNo($firstLine);
            $diffGroups = $formatter->getDiffLinesWithNumbers($lineLength, Diff2::FORMATTING_CLASSES, true);

            if (count($diffGroups) > 0) {
                $firstLine = $diffGroups[0]['lineFrom'];
                $this->setCacheItem('getFirstDiffLine', $firstLine);
                return $firstLine;
            }
        }

        // Nothing changed in a simple text section
        $firstLine = $this->getMyMotion()->getFirstLineNumber();
        $this->setCacheItem('getFirstDiffLine', $firstLine);
        return $firstLine;
    }

    /**
     * @param Amendment $ae1
     * @param Amendment $ae2
     * @return int
     */
    public static function sortVisibleByLineNumbersSort($ae1, $ae2)
    {
        $first1 = $ae1->getFirstDiffLine();
        $first2 = $ae2->getFirstDiffLine();

        if ($first1 < $first2) {
            return -1;
        }
        if ($first1 > $first2) {
            return 1;
        }

        $tit1 = explode('-', $ae1->titlePrefix);
        $tit2 = explode('-', $ae2->titlePrefix);
        if (count($tit1) == 3 && count($tit2) == 3) {
            if ($tit1[2] < $tit2[2]) {
                return -1;
            }
            if ($tit1[2] > $tit2[2]) {
                return 1;
            }
            return 0;
        } else {
            return strcasecmp($ae1->titlePrefix, $ae2->titlePrefix);
        }
    }


    /**
     * @param Consultation $consultation
     * @param Amendment[] $amendments
     * @return Amendment[]
     */
    public static function sortVisibleByLineNumbers(Consultation $consultation, $amendments)
    {
        $ams = [];
        foreach ($amendments as $am) {
            if (!in_array($am->status, $consultation->getInvisibleAmendmentStati())) {
                $am->getFirstDiffLine();
                $ams[] = $am;
            }
        }

        usort($ams, [Amendment::class, 'sortVisibleByLineNumbersSort']);

        return $ams;
    }

    /**
     * @param Consultation $consultation
     * @param int $limit
     * @return Amendment[]
     */
    public static function getNewestByConsultation(Consultation $consultation, $limit = 5)
    {
        $invisibleStati = array_map('IntVal', $consultation->getInvisibleMotionStati());
        $query          = Amendment::find();
        $query->where('amendment.status NOT IN (' . implode(', ', $invisibleStati) . ')');
        $query->joinWith(
            [
                'motionJoin' => function ($query) use ($invisibleStati, $consultation) {
                    /** @var ActiveQuery $query */
                    $query->andWhere('motion.status NOT IN (' . implode(', ', $invisibleStati) . ')');
                    $query->andWhere('motion.consultationId = ' . IntVal($consultation->id));
                }
            ]
        );
        $query->orderBy("amendment.dateCreation DESC");
        $query->offset(0)->limit($limit);

        return $query->all();
    }


    /**
     * @param Consultation $consultation
     * @return Amendment[]
     */
    public static function getScreeningAmendments(Consultation $consultation)
    {
        $query = Amendment::find();
        $query->where('amendment.status = ' . static::STATUS_SUBMITTED_UNSCREENED);
        $query->joinWith(
            [
                'motionJoin' => function ($query) use ($consultation) {
                    $invisibleStati = array_map('IntVal', $consultation->getInvisibleMotionStati());
                    /** @var ActiveQuery $query */
                    $query->andWhere('motion.status NOT IN (' . implode(', ', $invisibleStati) . ')');
                    $query->andWhere('motion.consultationId = ' . IntVal($consultation->id));
                }
            ]
        );
        $query->orderBy("dateCreation DESC");

        return $query->all();
    }

    /**
     * @return AmendmentSupporter[]
     */
    public function getInitiators()
    {
        $return = [];
        foreach ($this->amendmentSupporters as $supp) {
            if ($supp->role == AmendmentSupporter::ROLE_INITIATOR) {
                $return[] = $supp;
            }
        };
        return $return;
    }

    /**
     * @return AmendmentSupporter[]
     */
    public function getSupporters()
    {
        $return = [];
        foreach ($this->amendmentSupporters as $supp) {
            if ($supp->role == AmendmentSupporter::ROLE_SUPPORTER) {
                $return[] = $supp;
            }
        };
        return $return;
    }

    /**
     * @return AmendmentSupporter[]
     */
    public function getLikes()
    {
        $return = [];
        foreach ($this->amendmentSupporters as $supp) {
            if ($supp->role == AmendmentSupporter::ROLE_LIKE) {
                $return[] = $supp;
            }
        };
        return $return;
    }

    /**
     * @return AmendmentSupporter[]
     */
    public function getDislikes()
    {
        $return = [];
        foreach ($this->amendmentSupporters as $supp) {
            if ($supp->role == AmendmentSupporter::ROLE_DISLIKE) {
                $return[] = $supp;
            }
        };
        return $return;
    }


    /**
     * @return bool
     */
    public function iAmInitiator()
    {
        $user = \Yii::$app->user;
        if ($user->isGuest) {
            return false;
        }

        foreach ($this->amendmentSupporters as $supp) {
            if ($supp->role == AmendmentSupporter::ROLE_INITIATOR && $supp->userId == $user->id) {
                return true;
            }
        }
        return false;
    }


    /**
     * @return bool
     */
    public function canEdit()
    {
        if ($this->status == static::STATUS_DRAFT) {
            $hadLoggedInUser = false;
            foreach ($this->amendmentSupporters as $supp) {
                if ($supp->role == AmendmentSupporter::ROLE_INITIATOR && $supp->userId > 0) {
                    $hadLoggedInUser = true;
                    $currUser        = User::getCurrentUser();
                    if ($currUser && $currUser->id == $supp->userId) {
                        return true;
                    }
                }
            }
            if ($hadLoggedInUser) {
                return false;
            } else {
                if ($this->getMyMotion()->motionType->getAmendmentPolicy()->getPolicyID() == All::getPolicyID()) {
                    return true;
                } else {
                    return false;
                }
            }
        }

        if ($this->textFixed) {
            return false;
        }

        if ($this->getMyConsultation()->getSettings()->iniatorsMayEdit && $this->iAmInitiator()) {
            if ($this->getMyMotion()->motionType->amendmentDeadlineIsOver()) {
                return false;
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function canWithdraw()
    {
        if (!in_array($this->status, [Amendment::STATUS_SUBMITTED_SCREENED, Amendment::STATUS_SUBMITTED_UNSCREENED])) {
            return false;
        }
        return $this->iAmInitiator();
    }

    /** @var null|MotionSectionParagraphAmendment[] */
    private $_changedParagraphCache = null;

    /**
     * @param MotionSection[] $motionSections
     * @param bool $lineNumbers
     * @return MotionSectionParagraphAmendment[]
     */
    public function getChangedParagraphs($motionSections, $lineNumbers)
    {
        if ($lineNumbers && $this->_changedParagraphCache !== null) {
            return $this->_changedParagraphCache;
        }
        $paragraphs = [];
        foreach ($motionSections as $section) {
            if ($section->getSettings()->type != ISectionType::TYPE_TEXT_SIMPLE) {
                continue;
            }
            $paras = $section->getTextParagraphObjects($lineNumbers, true, true);
            foreach ($paras as $para) {
                foreach ($para->amendmentSections as $amSec) {
                    if ($amSec->amendmentId == $this->id) {
                        $paragraphs[] = $amSec;
                    }
                }
            }
        }
        if ($lineNumbers) {
            $this->_changedParagraphCache = $paragraphs;
        }
        return $paragraphs;
    }

    /**
     */
    public function withdraw()
    {
        if (in_array($this->status, $this->getMyConsultation()->getInvisibleMotionStati())) {
            $this->status = static::STATUS_DELETED;
        } else {
            $this->status = static::STATUS_WITHDRAWN;
        }
        $this->save();
        $this->getMyMotion()->flushCacheStart();

        ConsultationLog::logCurrUser($this->getMyConsultation(), ConsultationLog::AMENDMENT_WITHDRAW, $this->id);
    }

    /**
     */
    public function setScreened()
    {
        $this->status = Amendment::STATUS_SUBMITTED_SCREENED;
        if ($this->titlePrefix == '') {
            $numbering         = $this->getMyConsultation()->getAmendmentNumbering();
            $this->titlePrefix = $numbering->getAmendmentNumber($this, $this->getMyMotion());
        }
        $this->save(true);
        $this->onPublish();
        ConsultationLog::logCurrUser($this->getMyConsultation(), ConsultationLog::AMENDMENT_SCREEN, $this->id);
    }

    /**
     */
    public function setUnscreened()
    {
        $this->status = Amendment::STATUS_SUBMITTED_UNSCREENED;
        $this->save();
        ConsultationLog::logCurrUser($this->getMyConsultation(), ConsultationLog::AMENDMENT_UNSCREEN, $this->id);
    }

    /**
     */
    public function setDeleted()
    {
        $this->status = Amendment::STATUS_DELETED;
        $this->save();
        ConsultationLog::logCurrUser($this->getMyConsultation(), ConsultationLog::AMENDMENT_DELETE, $this->id);
    }

    /**
     */
    public function onPublish()
    {
        $this->flushCacheWithChildren();
        $this->setTextFixedIfNecessary();

        $init   = $this->getInitiators();
        $initId = (count($init) > 0 ? $init[0]->userId : null);
        ConsultationLog::log($this->getMyConsultation(), $initId, ConsultationLog::AMENDMENT_PUBLISH, $this->id);

        if ($this->datePublication === null) {
            $motionType = UserNotification::NOTIFICATION_NEW_AMENDMENT;
            $notified   = [];
            foreach ($this->getMyConsultation()->userNotifications as $noti) {
                if ($noti->notificationType == $motionType && !in_array($noti->userId, $notified)) {
                    $noti->user->notifyAmendment($this);
                    $notified[]             = $noti->userId;
                    $noti->lastNotification = date('Y-m-d H:i:s');
                    $noti->save();
                }
            }
            $this->datePublication = date('Y-m-d H:i:s');
            $this->save();

            if ($this->getMyConsultation()->getSettings()->initiatorConfirmEmails) {
                $initiator = $this->getInitiators();
                if (count($initiator) > 0 && $initiator[0]->contactEmail != '') {
                    try {
                        $text          = \Yii::t('amend', 'published_email_body');
                        $amendmentLink = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($this));
                        \app\components\mail\Tools::sendWithLog(
                            EMailLog::TYPE_MOTION_SUBMIT_CONFIRM,
                            $this->getMyConsultation()->site,
                            trim($initiator[0]->contactEmail),
                            null,
                            \Yii::t('amend', 'published_email_title'),
                            str_replace('%LINK%', $amendmentLink, $text)
                        );
                    } catch (MailNotSent $e) {
                        $errMsg = \Yii::t('base', 'err_email_not_sent') . ': ' . $e->getMessage();
                        \yii::$app->session->setFlash('error', $errMsg);
                    }
                }
            }
        }
    }

    /**
     * @param bool $save
     */
    public function setTextFixedIfNecessary($save = true)
    {
        if ($this->getMyConsultation()->getSettings()->adminsMayEdit) {
            return;
        }
        if (in_array($this->status, $this->getMyConsultation()->getInvisibleAmendmentStati())) {
            return;
        }
        $this->textFixed = 1;
        if ($save) {
            $this->save(true);
        }
    }

    /**
     *
     */
    public function flushCacheWithChildren()
    {
        $this->flushCache();
        \Yii::$app->cache->delete($this->getPdfCacheKey());
        foreach ($this->sections as $section) {
            $section->flushCache();
        }
    }

    /**
     * @return string
     */
    public function getPdfCacheKey()
    {
        return 'amendment-pdf-' . $this->id;
    }


    /**
     * @param RSSExporter $feed
     */
    public function addToFeed(RSSExporter $feed)
    {
        // @TODO Inline styling
        $content = '';

        /** @var AmendmentSection $section */
        $section    = $this->section;
        $firstLine  = $section->getFirstLineNumber();
        $lineLength = $section->getCachedConsultation()->getSettings()->lineLength;

        foreach ($this->sections as $section) {
            if ($section->getSettings()->type != ISectionType::TYPE_TEXT_SIMPLE) {
                continue;
            }

            $formatter = new AmendmentSectionFormatter();
            $formatter->setTextOriginal($section->getOriginalMotionSection()->data);
            $formatter->setTextNew($section->data);
            $formatter->setFirstLineNo($firstLine);
            $diffGroups = $formatter->getDiffLinesWithNumbers($lineLength, Diff2::FORMATTING_INLINE, true);

            if (count($diffGroups) > 0) {
                $content .= '<h2>' . Html::encode($section->getSettings()->title) . '</h2>';
                $content .= '<div id="section_' . $section->sectionId . '_0" class="paragraph lineNumbers">';
                $content .= TextSimple::formatDiffGroup($diffGroups);
                $content .= '</div>';
                $content .= '</section>';
            }
        }

        if ($this->changeExplanation) {
            $content .= '<h2>' . \Yii::t('amend', 'reason') . '</h2>';
            $content .= '<div class="paragraph"><div class="text">';
            $content .= $this->changeExplanation;
            $content .= '</div></div>';
        }

        $feed->addEntry(
            UrlHelper::createAmendmentUrl($this),
            $this->getTitle(),
            $this->getInitiatorsStr(),
            $content,
            Tools::dateSql2timestamp($this->dateCreation)
        );
    }

    /**
     * @return string
     */
    public function getDate()
    {
        return $this->dateCreation;
    }

    /**
     * @return array
     */
    public function getDataTable()
    {
        $return = [];

        $inits = $this->getInitiators();
        if (count($inits) == 1) {
            $first = $inits[0];
            if ($first->personType == MotionSupporter::PERSON_ORGANIZATION && $first->resolutionDate > 0) {
                $return[\Yii::t('export', 'InitiatorSingle')] = $first->organization;
                $return[\Yii::t('export', 'ResolutionDate')]  = Tools::formatMysqlDate($first->resolutionDate);
            } else {
                $return[\Yii::t('export', 'InitiatorSingle')] = $first->getNameWithResolutionDate(false);
            }
        } else {
            $initiators = [];
            foreach ($this->getInitiators() as $init) {
                $initiators[] = $init->getNameWithResolutionDate(false);
            }
            $return[\Yii::t('export', 'InitiatorMulti')] = implode("\n", $initiators);
        }

        return $return;
    }
}
