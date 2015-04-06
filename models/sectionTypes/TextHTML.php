<?php

namespace app\models\sectionTypes;

use app\components\HTMLTools;
use app\models\db\AmendmentSection;
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
     * @param string $data
     * @throws FormError
     */
    public function setMotionData($data)
    {
        $this->section->data = HTMLTools::cleanUntrustedHtml($data);
    }



    /**
     * @param string $data
     * @throws FormError
     */
    public function setAmendmentData($data)
    {
        /** @var AmendmentSection $section */
        $section = $this->section;
        $section->data = HTMLTools::cleanUntrustedHtml($data['consolidated']);
        $section->dataRaw = $data['raw'];
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
