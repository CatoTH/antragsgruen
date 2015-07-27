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
