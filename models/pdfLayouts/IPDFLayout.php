<?php

namespace app\models\pdfLayouts;

use app\models\db\Consultation;
use TCPDF;

class IPDFLayout
{
    /** @var Consultation */
    private $consultation;

    /**
     * @param Consultation $consultation
     */
    public function __construct(Consultation $consultation)
    {
        $this->consultation = $consultation;
    }

    /**
     * @return TCPDF
     */
    public function createPDFClass()
    {
        return new TCPDFWithFooter($this, $this->consultation->getWording());
    }

    /**
     * @param TCPDF $tcpdf
     */
    public function getFonts(TCPDF $tcpdf)
    {

    }

    /**
     * @param TCPDF $tcpdf
     */
    public function printHeader(TCPDF $tcpdf)
    {

    }
}
