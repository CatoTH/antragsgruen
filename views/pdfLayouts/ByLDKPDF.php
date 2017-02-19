<?php

namespace app\views\pdfLayouts;

use Yii;

class ByLDKPDF extends \FPDI
{
    /** @var IPDFLayout */
    private $layout;

    /**
     * @param IPDFLayout $layout
     */
    public function __construct(IPDFLayout $layout)
    {
        $this->layout  = $layout;
        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    }

    /**
     * rewrite AddPage() for correct functionalities with PDF Concatenation
     */
    public function AddPage ($orientation = PDF_PAGE_ORIENTATION, $format = PDF_PAGE_FORMAT, $keepmargins = false, $tocpage = false, $footer = true) {
        parent::AddPage($orientation, $format, $keepmargins, $tocpage);
        $this->setPrintFooter($footer);
    }

    // @codingStandardsIgnoreStart
    /**
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
            Yii::t('export', 'Page') . ' ' . $this->getGroupPageNo() . ' / ' . $this->getPageGroupAlias(),
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
    // @codingStandardsIgnoreEnd
}
