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
 * @property string $dataHash
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
            [['consultationId', 'filename', 'filesize', 'mimetype', 'data', 'dataHash', 'dateCreation'], 'required'],
            [['mimetype', 'width', 'height'], 'safe'],
            [['id', 'consultationId', 'filesize', 'width', 'height'], 'number']
        ];
    }

    /**
     * @param string $suggestion
     */
    public function setFilename($suggestion)
    {
        $counter  = 1;
        if (in_array($suggestion, ['upload', 'browse-images', 'delete'])) {
            $suggestion .= '_file';
        }
        $filename = $suggestion;
        while (ConsultationFile::findOne(['consultationId' => $this->consultationId, 'filename' => $filename])) {
            $counter++;
            $fileparts = explode('.', $suggestion);
            if (count($fileparts) > 1) {
                $fileparts[count($fileparts) - 2] .= '-' . $counter;
            } else {
                $fileparts[count($fileparts) - 1] .= '-' . $counter;
            }
            $filename = implode('.', $fileparts);
        }

        $this->filename = $filename;
    }

    /**
     * @param string $data
     */
    public function setData($data)
    {
        $this->data     = $data;
        $this->filesize = strlen($data);
        $this->dataHash = sha1($data);
    }

    /**
     * @param Consultation $consultation
     * @param string $content
     * @return ConsultationFile|null
     */
    public static function findFileByContent(Consultation $consultation, $content)
    {
        return ConsultationFile::findOne([
            'consultationId' => $consultation->id,
            'dataHash'       => sha1($content),
        ]);
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return UrlHelper::createUrl(['pages/file', 'filename' => $this->filename]);
    }
}
