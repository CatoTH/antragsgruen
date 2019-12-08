<?php

namespace app\models\db;

use app\components\UrlHelper;
use app\models\exceptions\FormError;
use app\models\settings\Stylesheet;
use yii\db\ActiveRecord;

/**
 * Class ConsultationFile
 * @package app\models\db
 *
 * @property int $id
 * @property int $consultationId
 * @property int $siteId
 * @property int|null $downloadPosition
 * @property string $filename
 * @property int $filesize
 * @property string $mimetype
 * @property int $width
 * @property int $height
 * @property string $data
 * @property string $dataHash
 * @property string $dateCreation
 * @property int|null $uploadedById
 *
 * @property Consultation $consultation
 * @property User|null $uploadedBy
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
     * @return \yii\db\ActiveQuery
     */
    public function getUploadedBy()
    {
        return $this->hasOne(User::class, ['id' => 'uploadedById']);
    }

    public function getMyConsultation(): ?Consultation
    {
        if (Consultation::getCurrent() && Consultation::getCurrent()->id === $this->consultationId) {
            return Consultation::getCurrent();
        } else {
            return $this->consultation;
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSite()
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['siteId', 'filename', 'filesize', 'mimetype', 'data', 'dataHash', 'dateCreation'], 'required'],
            [['mimetype', 'width', 'height', 'downloadPosition'], 'safe'],
            [['id', 'consultationId', 'uploadedById', 'downloadPosition', 'siteId', 'filesize', 'width', 'height'], 'number']
        ];
    }

    public function setFilename(string $suggestion): void
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

    public function setData(string $data): void
    {
        $this->data     = $data;
        $this->filesize = strlen($data);
        $this->dataHash = sha1($data);
    }

    public static function findFileByContent(Consultation $consultation, string $content): ?ConsultationFile
    {
        return ConsultationFile::findOne([
            'consultationId' => $consultation->id,
            'dataHash'       => sha1($content),
        ]);
    }

    public static function findStylesheetCache(Site $site, Stylesheet $stylesheet): ?ConsultationFile
    {
        return ConsultationFile::findOne([
            'siteId'   => $site->id,
            'dataHash' => $stylesheet->getSettingsHash(),
        ]);
    }

    public static function createStylesheetCache(Site $site, Stylesheet $stylesheet, string $data): ?ConsultationFile
    {
        $file = ConsultationFile::findOne([
            'siteId'   => $site->id,
            'filename' => 'styles.css',
        ]);
        if (!$file) {
            $file                 = new ConsultationFile();
            $file->siteId         = $site->id;
            $file->consultationId = null;
            $file->filename       = 'styles.css';
        }
        $file->dateCreation     = date('Y-m-d H:i:s');
        $file->downloadPosition = null;
        $file->data             = $data;
        $file->dataHash         = $stylesheet->getSettingsHash();
        $file->filesize         = strlen($data);
        $file->mimetype         = 'text/css';
        $file->width            = null;
        $file->height           = null;
        $file->uploadedById     = null;
        $file->save();

        return $file;
    }

    public static function findFileByName(Consultation $consultation, string $filename): ?ConsultationFile
    {
        return ConsultationFile::findOne([
            'consultationId' => $consultation->id,
            'filename'       => $filename,
        ]);
    }

    public static function findFileByUrl(Consultation $consultation, string $url): ?ConsultationFile
    {
        if (preg_match('/^\/(?<consultation>[\w_-]+)\/page\/files\/(?<filename>.*)$/siu', $url, $matches)) {
            $conFound = null;
            if (mb_strtolower($matches['consultation']) === mb_strtolower($consultation->urlPath)) {
                $conFound = $consultation;
            } else {
                foreach ($consultation->site->consultations as $con) {
                    if (mb_strtolower($matches['consultation']) === mb_strtolower($con->urlPath)) {
                        $conFound = $con;
                    }
                }
            }
            if (!$conFound) {
                return null;
            }
            return static::findFileByName($conFound, urldecode($matches['filename']));
        } else {
            return null;
        }
    }


    /**
     * @param Consultation $consultation
     * @param string $formName
     * @param User|null $user
     *
     * @return ConsultationFile
     * @throws FormError
     */
    public static function uploadImage(Consultation $consultation, string $formName, ?User $user): ConsultationFile
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

        $file                   = new ConsultationFile();
        $file->consultationId   = $consultation->id;
        $file->siteId           = $consultation->siteId;
        $file->downloadPosition = null;
        $file->mimetype         = $mime;
        $file->width            = $width;
        $file->height           = $height;
        $file->dateCreation     = date('Y-m-d H:i:s');
        $file->uploadedById     = ($user ? $user->id : null);
        $file->setFilename($filename);
        $file->setData($content);
        if (!$file->save()) {
            throw new FormError($file->getErrors());
        }

        return $file;
    }

    public function getUrl(): string
    {
        return UrlHelper::createUrl(['pages/file', 'filename' => $this->filename], $this->getMyConsultation());
    }
}
