<?php
namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $userId
 * @property int $consultationId
 * @property string $privilegeView
 * @property string $privilegeCreate
 * @property string $adminSuper
 * @property string $adminContentEdit
 * @property string $adminScreen
 *
 * @property User $user
 * @property Consultation $consultation
 */
class ConsultationUserPrivilege extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return 'consultationUserPrivilege';
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
            [['consultationId', 'userId'], 'required'],
            [['consultationId', 'userId'], 'number'],
            [['privilegeView', 'privilegeCreate'], 'number'],
            [['adminSuper', 'adminContentEdit', 'adminScreen'], 'number'],
        ];
    }
}
