<?php

namespace app\models\sectionTypes;

use app\components\UrlHelper;
use app\models\exceptions\FormError;
use yii\helpers\Html;

class Image extends ISectionType
{

    /**
     * @return string
     */
    public function getMotionFormField()
    {
        $type = $this->section->consultationSetting;
        return '<fieldset class="form-group">
            <label for="sections_' . $type->id . '">' . Html::encode($type->title) . '</label>
            <input type="file" class="form-control" id="sections_' . $type->id . '"' .
        ' name="sections[' . $type->id . ']">
        </fieldset>';
    }

    /**
     * @return string
     */
    public function getAmendmentFormField()
    {
        return $this->getMotionFormField();
    }

    /**
     * @param string $data
     * @throws FormError
     */
    public function setMotionData($data)
    {
        if (!isset($data['tmp_name'])) {
            throw new FormError('Invalid Image');
        }
        $mime = mime_content_type($data['tmp_name']);
        if (!in_array($mime, ['image/png', 'image/jpg', 'image/jpeg', 'image/gif'])) {
            throw new FormError('Image type not supported. Supported formats are: JPEG, PNG and GIF.');
        }
        $imagedata = getimagesize($data['tmp_name']);
        if (!$imagedata) {
            throw new FormError('Could not read image.');
        }
        $metadata                = [
            'width'    => $imagedata[0],
            'height'   => $imagedata[1],
            'filesize' => filesize($data['tmp_name']),
            'mime'     => $mime
        ];
        $this->section->data     = base64_encode(file_get_contents($data['tmp_name']));
        $this->section->metadata = json_encode($metadata);
    }

    /**
     * @param string $data
     * @throws FormError
     */
    public function setAmendmentData($data)
    {
        $this->setMotionData($data);
    }

    /**
     * @return string
     */
    public function showSimple()
    {
        if ($this->isEmpty()) {
            return '';
        }

        $type = $this->section->consultationSetting;
        $url  = UrlHelper::createUrl(
            [
                'motion/viewimage',
                'motionId'  => $this->section->motionId,
                'sectionId' => $this->section->sectionId
            ]
        );
        $str  = '<div style="text-align: center; padding: 10px;"><img src="' . Html::encode($url) . '" ';
        $str .= 'alt="' . Html::encode($type->title) . '" style="max-height: 200px;"></div>';
        return $str;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return ($this->section->data == '');
    }
}
