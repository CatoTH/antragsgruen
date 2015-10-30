<?php

namespace app\models\db;

use app\components\diff\AmendmentSectionFormatter;
use app\components\diff\Diff;
use app\components\RSSExporter;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\exceptions\MailNotSent;
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
 * @property Motion $motion
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
    public function getMotion()
    {
        return $this->hasOne(Motion::class, ['id' => 'motionId'])
            ->andWhere(Motion::tableName() . '.status != ' . Motion::STATUS_DELETED);
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
        if ($this->motion->titlePrefix != '') {
            $showMotionPrefix = (mb_stripos($this->titlePrefix, $this->motion->titlePrefix) === false);
        } else {
            $showMotionPrefix = false;
        }
        $prefix = ($this->titlePrefix != '' ? $this->titlePrefix : \yii::t('amend', 'amendment'));
        if ($this->getMyConsultation()->getSettings()->hideTitlePrefix) {
            return $prefix . \yii::t('amend', 'amend_for') . $this->motion->title;
        } else {
            if ($this->motion->titlePrefix != '') {
                if ($showMotionPrefix) {
                    $str = $prefix . \yii::t('amend', 'amend_for');
                    $str .= $this->motion->titlePrefix . ': ' . $this->motion->title;
                    return $str;
                } else {
                    return $prefix . ': ' . $this->motion->title;
                }
            } else {
                return $prefix . \yii::t('amend', 'amend_for') . $this->motion->title;
            }
        }
    }

    /**
     * @return string
     */
    public function getShortTitle()
    {
        if ($this->motion->titlePrefix != '') {
            $showMotionPrefix = (mb_stripos($this->titlePrefix, $this->motion->titlePrefix) === false);
        } else {
            $showMotionPrefix = false;
        }
        if ($this->getMyConsultation()->getSettings()->hideTitlePrefix) {
            return $this->titlePrefix . \Yii::t('amend', 'amend_for') . $this->motion->title;
        } else {
            if ($this->motion->titlePrefix != '') {
                if ($showMotionPrefix) {
                    return $this->titlePrefix . \Yii::t('amend', 'amend_for') . $this->motion->titlePrefix;
                } else {
                    return $this->titlePrefix;
                }
            } else {
                return $this->titlePrefix . \Yii::t('amend', 'amend_for') . $this->motion->title;
            }
        }
    }

    /**
     * @return Consultation
     */
    public function getMyConsultation()
    {
        return $this->motion->consultation;
    }

    /**
     * @return ConsultationSettingsMotionSection
     */
    public function getMySections()
    {
        return $this->motion->motionType->motionSections;
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


    private $firstDiffLine = null;

    /**
     * @return int
     */
    public function getFirstDiffLine()
    {
        if ($this->firstDiffLine === null) {
            foreach ($this->sections as $section) {
                if ($section->consultationSetting->type != ISectionType::TYPE_TEXT_SIMPLE) {
                    continue;
                }
                $formatter = new AmendmentSectionFormatter($section, Diff::FORMATTING_CLASSES);
                $diff      = $formatter->getGroupedDiffLinesWithNumbers();
                if (count($diff) > 0) {
                    return $diff[0]['lineFrom'];
                }
            }

            // Nothing changed in a simple text section
            $this->firstDiffLine = $this->motion->getFirstLineNumber();
        }
        return $this->firstDiffLine;
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
        $ams = array();
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
                'motion' => function ($query) use ($invisibleStati, $consultation) {
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
                'motion' => function ($query) use ($consultation) {
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
            return true;
        }

        if ($this->textFixed) {
            return false;
        }

        if ($this->motion->consultation->getSettings()->iniatorsMayEdit && $this->iAmInitiator()) {
            if ($this->motion->motionType->amendmentDeadlineIsOver()) {
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

    /**
     * @param bool $lineNumbers
     * @return MotionSectionParagraphAmendment[]
     */
    public function getChangedParagraphs($lineNumbers)
    {
        $paragraphs = [];
        foreach ($this->motion->sections as $section) {
            if ($section->consultationSetting->type != ISectionType::TYPE_TEXT_SIMPLE) {
                continue;
            }
            $paras = $section->getTextParagraphObjects($lineNumbers, true, true);
            foreach ($paras as $para) {
                foreach ($para->amendmentSections as $amSec) {
                    if ($amSec->amendmentSection->amendmentId == $this->id) {
                        $paragraphs[] = $amSec;
                    }
                }
            }
        }
        return $paragraphs;
    }

    /**
     */
    public function withdraw()
    {
        if (in_array($this->status, $this->motion->consultation->getInvisibleMotionStati())) {
            $this->status = static::STATUS_DRAFT;
        } else {
            $this->status = static::STATUS_WITHDRAWN;
        }
        $this->save();
        $this->motion->flushCacheStart();

        ConsultationLog::logCurrUser($this->motion->consultation, ConsultationLog::AMENDMENT_WITHDRAW, $this->id);
    }

    /**
     */
    public function setScreened()
    {
        $this->status = Amendment::STATUS_SUBMITTED_SCREENED;
        if ($this->titlePrefix == '') {
            $numbering         = $this->motion->consultation->getAmendmentNumbering();
            $this->titlePrefix = $numbering->getAmendmentNumber($this, $this->motion);
        }
        $this->save(true);
        $this->onPublish();
        ConsultationLog::logCurrUser($this->motion->consultation, ConsultationLog::AMENDMENT_SCREEN, $this->id);
    }

    /**
     */
    public function setUnscreened()
    {
        $this->status = Amendment::STATUS_SUBMITTED_UNSCREENED;
        $this->save();
        ConsultationLog::logCurrUser($this->motion->consultation, ConsultationLog::AMENDMENT_UNSCREEN, $this->id);
    }

    /**
     */
    public function setDeleted()
    {
        $this->status = Amendment::STATUS_DELETED;
        $this->save();
        ConsultationLog::logCurrUser($this->motion->consultation, ConsultationLog::AMENDMENT_DELETE, $this->id);
    }

    /**
     */
    public function onPublish()
    {
        $this->flushCacheWithChildren();
        $this->setTextFixedIfNecessary();

        $init   = $this->getInitiators();
        $initId = (count($init) > 0 ? $init[0]->userId : null);
        ConsultationLog::log($this->motion->consultation, $initId, ConsultationLog::AMENDMENT_PUBLISH, $this->id);

        if ($this->datePublication === null) {
            $motionType = UserNotification::NOTIFICATION_NEW_AMENDMENT;
            $notified   = [];
            foreach ($this->motion->consultation->userNotifications as $noti) {
                if ($noti->notificationType == $motionType && !in_array($noti->userId, $notified)) {
                    $noti->user->notifyAmendment($this);
                    $notified[]             = $noti->userId;
                    $noti->lastNotification = date('Y-m-d H:i:s');
                    $noti->save();
                }
            }
            $this->datePublication = date('Y-m-d H:i:s');
            $this->save();

            if ($this->motion->consultation->getSettings()->initiatorConfirmEmails) {
                $initiator = $this->getInitiators();
                if (count($initiator) > 0 && $initiator[0]->contactEmail != '') {
                    try {
                        $text          = \Yii::t('amend', 'published_email_body');
                        $amendmentLink = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($this));
                        \app\components\mail\Tools::sendWithLog(
                            EMailLog::TYPE_MOTION_SUBMIT_CONFIRM,
                            $this->motion->consultation->site,
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
        if ($this->motion->consultation->getSettings()->adminsMayEdit) {
            return;
        }
        if (in_array($this->status, $this->motion->consultation->getInvisibleAmendmentStati())) {
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
        foreach ($this->sections as $section) {
            $section->flushCache();
        }
    }


    /**
     * @param RSSExporter $feed
     */
    public function addToFeed(RSSExporter $feed)
    {
        // @TODO Inline styling
        $content = '';
        foreach ($this->sections as $section) {
            if ($section->consultationSetting->type != ISectionType::TYPE_TEXT_SIMPLE) {
                continue;
            }
            $formatter  = new AmendmentSectionFormatter($section, Diff::FORMATTING_INLINE);
            $diffGroups = $formatter->getGroupedDiffLinesWithNumbers();

            if (count($diffGroups) > 0) {
                $content .= '<h2>' . Html::encode($section->consultationSetting->title) . '</h2>';
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
                $return[\Yii::t('pdf', 'InitiatorSingle')] = $first->organization;
                $return[\Yii::t('pdf', 'ResolutionDate')]  = Tools::formatMysqlDate($first->resolutionDate);
            } else {
                $return[\Yii::t('pdf', 'InitiatorSingle')] = $first->getNameWithResolutionDate(false);
            }
        } else {
            $initiators = [];
            foreach ($this->getInitiators() as $init) {
                $initiators[] = $init->getNameWithResolutionDate(false);
            }
            $return[\Yii::t('pdf', 'InitiatorMulti')] = implode("\n", $initiators);
        }

        return $return;
    }
}
