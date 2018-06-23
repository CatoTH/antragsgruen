<?php

namespace app\models\db;

use app\components\UrlHelper;
use app\models\exceptions\FormError;
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
        $counter = 1;
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
     * @param Consultation $consultation
     * @param $filename
     * @return ConsultationFile|null
     */
    public static function findFileByName(Consultation $consultation, $filename)
    {
        return ConsultationFile::findOne([
            'consultationId' => $consultation->id,
            'filename'       => $filename,
        ]);
    }


    /**
     * @param Consultation $consultation
     * @param string $formName
     * @return ConsultationFile
     * @throws FormError
     */
    public static function uploadImage(Consultation $consultation, $formName)
    {
        $width    = null;
        $height   = null;
        $mime     = null;
        $filename = null;
        $content  = null;
        if (isset($_FILES[$formName]) && is_uploaded_file($_FILES[$formName]['tmp_name'])) {
            $content = file_get_contents($_FILES[$formName]['tmp_name']);
            $info    = getimagesizefromstring($content);
            if ($info && in_array($info['mime'], ['image/png', 'image/jpeg', 'image/gif'])) {
                $mime     = $info['mime'];
                $width    = $info[0];
                $height   = $info[1];
                $filename = $_FILES[$formName]['name'];
            } else {
                throw new FormError('Not a valid image file');
            }
        } else {
            throw new FormError('No image data uploaded');
        }

        $existingFile = ConsultationFile::findFileByContent($consultation, $content);
        if ($existingFile) {
            return $existingFile;
        }

        $file                 = new ConsultationFile();
        $file->consultationId = $consultation->id;
        $file->mimetype       = $mime;
        $file->width          = $width;
        $file->height         = $height;
        $file->dateCreation   = date('Y-m-d H:i:s');
        $file->setFilename($filename);
        $file->setData($content);
        if (!$file->save()) {
            throw new FormError($file->getErrors());
        }

        return $file;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return UrlHelper::createUrl(['pages/file', 'filename' => $this->filename]);
    }
}
