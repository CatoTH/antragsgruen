<?php

namespace app\models\sectionTypes;

use app\components\HTMLTools;
use app\models\exceptions\FormError;

class TextHTML extends ISectionType
{

    /**
     * @return string
     */
    public function getMotionFormField()
    {
        return $this->getTextMotionFormField(true);
    }

    /**
     * @return string
     */
    public function getAmendmentFormField()
    {
        return $this->getTextAmendmentFormField(true);
    }

    /**
     * @param $data
     * @throws FormError
     */
    public function setData($data)
    {
        $this->section->data = HTMLTools::cleanUntrustedHtml($data);
    }

    /**
     * @return string
     */
    public function showSimple()
    {
        return $this->section->data;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return ($this->section->data == '');
    }
}
