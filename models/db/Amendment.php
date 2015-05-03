<?php

namespace app\models\db;

use yii\db\ActiveQuery;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $motionId
 * @property string $titlePrefix
 * @property string $changeMetatext
 * @property string $changeText
 * @property string $changeExplanation
 * @property int $changeExplanationHtml
 * @property string $cache
 * @property string $dateCreation
 * @property string $dateResolution
 * @property int $status
 * @property string $statusString
 * @property string $noteInternal
 * @property int $textFixed
 *
 * @property Motion $motion
 * @property AmendmentComment[] $comments
 * @property AmendmentSupporter[] $amendmentSupporters
 * @property AmendmentSection[] $sections
 */
class Amendment extends IMotion
{

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
        return $this->hasOne(Motion::className(), ['id' => 'motionId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getComments()
    {
        return $this->hasMany(AmendmentComment::className(), ['amendmentId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAmendmentSupporters()
    {
        return $this->hasMany(AmendmentSupporter::className(), ['amendmentId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSections()
    {
        return $this->hasMany(AmendmentSection::className(), ['amendmentId' => 'id']);
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
            return $this->titlePrefix . ' zu ' . $this->motion->titlePrefix . ': ' . $this->motion->title;
        } else {
            return $this->titlePrefix . ' zu ' . $this->motion->title;
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
     * @return int
     */
    public function getFirstDiffLine()
    {
        // @TODO
        return 0;
        /*
        if ($this->cacheFirstLineChanged > -1) return $this->cacheFirstLineChanged;

        $text_vorher = $this->motion->text;
        $paragraphs  = $this->motion->getParagraphs(false, false);
        $text_neu    = array();
        $diff        = $this->getDiffParagraphs();
        foreach ($paragraphs as $i => $para) {
            if (isset($diff[$i]) && $diff[$i] != "") $text_neu[] = $diff[$i];
            else $text_neu[] = $para->str_bbcode;
        }
        $diff = DiffUtils::getTextDiffMitZeilennummern(trim($text_vorher), trim(implode("\n\n", $text_neu)),
        $this->antrag->veranstaltung->getEinstellungen()->zeilenlaenge);

        $this->aenderung_first_line_cache = DiffUtils::getFistDiffLine($diff, $this->antrag->getFirstLineNo());
        $this->save();
        return $this->aenderung_first_line_cache;
        */
    }

    /**
     * @return int
     */
    public function getFirstAffectedLineOfParagraphAbsolute()
    {
        return 0; // @TODO
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

        $tit1 = explode("-", $ae1->titlePrefix);
        $tit2 = explode("-", $ae2->titlePrefix);
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
                $ams[] = $am;
            }
        }

        usort($ams, array(Amendment::className(), 'sortVisibleByLineNumbersSort'));

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

        if ($this->motion->consultation->getSettings()->adminsMayEdit) {
            if (User::currentUserHasPrivilege($this->motion->consultation, User::PRIVILEGE_SCREENING)) {
                return true;
            }
        }

        if ($this->motion->consultation->getSettings()->iniatorsMayEdit && $this->iAmInitiator()) {
            if ($this->motion->consultation->amendmentDeadlineIsOver()) {
                return false;
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * @return int
     */
    public function getNumberOfCountableLines()
    {
        return 0; // @TODO
    }

    /**
     * @return int
     */
    public function getFirstLineNumber()
    {
        return 1; // @TODO
    }
}
