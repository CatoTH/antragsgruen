<?php

namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $userId
 * @property int $consultationId
 * @property int $actionType
 * @property int $actionReferenceId
 * @property string $actionTime
 *
 * @property Consultation $consultation
 * @property User $user
 */
class ConsultationLog extends ActiveRecord
{
    const MOTION_PUBLISH           = 0;
    const MOTION_WITHDRAW          = 1;
    const MOTION_DELETE            = 2;
    const MOTION_SCREEN            = 3;
    const MOTION_UNSCREEN          = 4;
    const MOTION_COMMENT           = 5;
    const MOTION_COMMENT_DELETE    = 6;
    const MOTION_COMMENT_SCREEN    = 7;
    const MOTION_LIKE              = 8;
    const MOTION_UNLIKE            = 9;
    const MOTION_DISLIKE           = 10;
    const MOTION_CHANGE            = 12;
    const AMENDMENT_PUBLISH        = 13;
    const AMENDMENT_WITHDRAW       = 14;
    const AMENDMENT_DELETE         = 15;
    const AMENDMENT_SCREEN         = 16;
    const AMENDMENT_UNSCREEN       = 17;
    const AMENDMENT_COMMENT        = 18;
    const AMENDMENT_COMMENT_DELETE = 19;
    const AMENDMENT_COMMENT_SCREEN = 20;
    const AMENDMENT_LIKE           = 21;
    const AMENDMENT_UNLIKE         = 22;
    const AMENDMENT_DISLIKE        = 23;
    const AMENDMENT_CHANGE         = 25;

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'consultationLog';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultation()
    {
        return $this->hasOne(Consultation::className(), ['id' => 'consultationId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'userId']);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['consultationId', 'actionTime'], 'required'],
            [['id', 'consultationId', 'userId', 'actionType', 'actionReferenceId'], 'number'],
        ];
    }

    /**
     * @param Consultation $consultation
     * @param int|null $userId
     * @param int $type
     * @param int $typeRefId
     */
    public static function log(Consultation $consultation, $userId, $type, $typeRefId)
    {
        $log                    = new static();
        $log->userId            = $userId;
        $log->consultationId    = $consultation->id;
        $log->actionType        = $type;
        $log->actionReferenceId = $typeRefId;
        $log->actionTime        = date('Y-m-d H:i:s');
        $log->save();
    }

    /**
     * @param Consultation $consultation
     * @param int $type
     * @param int $typeRefId
     */
    public static function logCurrUser(Consultation $consultation, $type, $typeRefId)
    {
        $user = User::getCurrentUser();

        $log                    = new static();
        $log->userId            = ($user ? $user->id : null);
        $log->consultationId    = $consultation->id;
        $log->actionType        = $type;
        $log->actionReferenceId = $typeRefId;
        $log->actionTime        = date('Y-m-d H:i:s');
        $log->save();
    }
}
