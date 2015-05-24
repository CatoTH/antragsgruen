<?php

namespace app\models\db;

use yii\helpers\Url;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $userId
 * @property int $amendmentId
 * @property string $text
 * @property string $name
 * @property string $contactEmail
 * @property string $dateCreation
 * @property int $status
 * @property int $replyNotification
 *
 * @property User $user
 * @property Amendment $amendment
 */
class AmendmentComment extends IComment
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'amendmentComment';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'userId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAmendment()
    {
        return $this->hasOne(Amendment::className(), ['id' => 'amendmentId']);
    }

    /**
     * @return Consultation
     */
    public function getConsultation()
    {
        return $this->amendment->motion->consultation;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['amendmentId', 'paragraph', 'status', 'dateCreation'], 'required'],
            ['name', 'required', 'message' => 'Bitte gib deinen Namen an.'],
            ['text', 'required', 'message' => 'Bitte gib etwas Text ein.'],
            [['id', 'amendmentId', 'paragraph', 'status'], 'number'],
            [['text', 'paragraph'], 'safe'],
        ];
    }

    /**
     * @return string
     */
    public function getMotionTitle()
    {
        return $this->amendment->titlePrefix . " zu " . $this->amendment->motion->getTitleWithPrefix();
    }

    /**
     * @param bool $absolute
     * @return string
     */
    public function getLink($absolute = false)
    {
        $url = Url::toRoute(
            [
                'amendment/view',
                'subdomain'        => $this->amendment->motion->consultation->site->subdomain,
                'consultationPath' => $this->amendment->motion->consultation->urlPath,
                'motionId'         => $this->amendment->motion->id,
                'amendmentId'      => $this->amendment->id,
                'commentId'        => $this->id,
                '#'                => 'comment' . $this->id
            ]
        );
        if ($absolute) {
            // @TODO Testen
            $url = \Yii::$app->basePath . $url;
        }
        return $url;
    }
}
