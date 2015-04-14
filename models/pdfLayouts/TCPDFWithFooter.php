<?php

namespace app\models\pdfLayouts;

use app\models\wording\IWording;

class TCPDFWithFooter extends \TCPDF
{
    /** @var IWording */
    private $wording;

    /** @var IPDFLayout */
    private $layout;

    /**
     * @param IPDFLayout $layout
     * @param IWording $wording
     */
    public function __construct(IPDFLayout $layout, IWording $wording)
    {
        $this->wording = $wording;
        $this->layout  = $layout;
        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    }

    /**
     *
     */
    public function Footer()
    {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', '', 8);
        // Page number
        $this->Cell(
            0,
            10,
            $this->wording->get('Seite') . ' ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(),
            0,
            false,
            'C',
            0,
            '',
            0,
            false,
            'T',
            'M'
        );
    }
}
