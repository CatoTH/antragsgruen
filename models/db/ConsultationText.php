<?php
namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $consultationId
 * @property string $category
 * @property string $textId
 * @property string $text
 * @property string $editDate
 *
 * @property Consultation $consultation
 */
class ConsultationText extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return 'consultationText';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultation()
    {
        return $this->hasOne(Consultation::className(), ['id' => 'consultationId']);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['consultationId', 'category', 'textId'], 'required'],
            [['category', 'textId', 'text'], 'safe'],
        ];
    }
}
