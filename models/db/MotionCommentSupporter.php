<?php

namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property string $ipHash
 * @property string $cookieId
 * @property int $motionCommentId
 * @property int $likes
 *
 * @property MotionComment $motionComment
 */
class MotionCommentSupporter extends ActiveRecord
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'motionCommentSupporter';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotionComment()
    {
        return $this->hasOne(MotionComment::className(), ['id' => 'motionCommentId']);
    }

    /**
     * @static
     * @param Motion $motion
     * @return null|MotionCommentSupporter
     */
    public static function mySupport(Motion $motion) {
        /* TODO
        if (isset(Yii::app()->request->cookies['kommentar_bewertung'])) {
            $unt = AntragKommentarUnterstuetzerInnen::model()->findByAttributes(array(
            "antrag_kommentar_id" => $antrag_id,
            "cookie_id" => Yii::app()->request->cookies['kommentar_bewertung']->value));
            if ($unt !== null) return $unt;
        }
        $unt = AntragKommentarUnterstuetzerInnen::model()->findByAttributes(array("antrag_kommentar_id" => $antrag_id,
            "ip_hash" => md5($_SERVER["REMOTE_ADDR"])));
        return $unt;
        */
        return null;
    }
}
