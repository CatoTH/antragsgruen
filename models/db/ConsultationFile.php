<?php

namespace app\models\db;

use app\components\UrlHelper;
use yii\db\ActiveRecord;

/**
 * Class ConsultationFile
 * @package app\models\db
 *
 * @property int $id
 * @property int $consultationId
 * @property string $filename
 * @property int $filesize
 * @property string $mimetype
 * @property int $width
 * @property int $height
 * @property string $data
 * @property string $dateCreation
 *
 * @property Consultation $consultation
 */
class ConsultationFile extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'consultationFile';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultation()
    {
        return $this->hasOne(Consultation::class, ['id' => 'consultationId']);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['consultationId', 'filename', 'filesize', 'mimetype', 'data', 'dateCreation'], 'required'],
            [['mimetype', 'data'], 'safe'],
            [['id', 'consultationId', 'filesize', 'width', 'height'], 'number']
        ];
    }

    /**
     * @param string $suggestion
     */
    public function setFilename($suggestion)
    {
        $this->filename = $suggestion; // @TODO
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return UrlHelper::createUrl(['pages/file', 'filename' => $this->filename]);
    }
}
