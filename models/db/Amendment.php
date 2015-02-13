<?php

namespace app\models\db;

use yii\db\Query;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $motionId
 * @property string $titlePrefix
 * @property string $changedTitle
 * @property string $changedParagraphs
 * @property string $changedExplanation
 * @property string $changeMetatext
 * @property string $changeText
 * @property string $changeExplanation
 * @property int $changeExplanationHtml
 * @property int $cacheFirstLineChanged
 * @property int $cacheFirstLineRel
 * @property int $cacheFirstLineAbs
 * @property string $dateCreation
 * @property string $dateResolution
 * @property int $status
 * @property string $statusString
 * @property string $noteInternal
 * @property int $textFixed
 *
 * @property Motion $motion
 * @property AmendmentComment[] $comments
 * @property AmendmentSupporter[] $supporters
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
    public function getSupporters()
    {
        return $this->hasMany(AmendmentSupporter::className(), ['amendmentId' => 'id']);
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

    private static function sortVisibleByLineNumbersSort($ae1, $ae2)
    {
        /** @var Amendment $ae1 */
        /** @var Amendment $ae2 */
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
     * @param Amendment[] $amendments
     * @return Amendment[]
     */
    public static function sortVisibleByLineNumbers($amendments)
    {
        $ams = array();
        foreach ($amendments as $am) {
            if (!in_array($am->status, IMotion::getInvisibleStati())) {
                $ams[] = $am;
            }
        }

        usort($aes, array(Amendment::className(), 'sortVisibleByLineNumbersSort'));

        return $aes;
    }

    /**
     * @param Consultation $consultation
     * @param int $limit
     * @return Amendment[]
     */
    public static function getNewestByConsultation(Consultation $consultation, $limit = 5)
    {
        $invisibleStati = array_map('IntVal', static::getInvisibleStati());

        $query = (new Query())->select('amendment.*')->from('amendment');
        $query->innerJoin('motion', 'motion.id = amendment.motionId');
        $query->where('amendment.status NOT IN (' . implode(', ', $invisibleStati) . ')');
        $query->where('motion.status NOT IN (' . implode(', ', $invisibleStati) . ')');
        $query->where('motion.consultationId = ' . IntVal($consultation->id));
        $query->orderBy("dateCreation DESC");
        $query->offset(0)->limit($limit);

        return $query->all();
    }


    /**
     * @return User[]
     */
    public function getInitiators()
    {
        // TODO: Implement getInitiators() method.
    }

    /**
     * @return User[]
     */
    public function getLikes()
    {
        // TODO: Implement getLikes() method.
    }

    /**
     * @return User[]
     */
    public function getDislikes()
    {
        // TODO: Implement getDislikes() method.
    }
}
