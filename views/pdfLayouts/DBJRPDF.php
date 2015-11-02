<?php

namespace app\views\pdfLayouts;

use Yii;

class DBJRPDF extends \TCPDF
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
            Yii::t('export', 'Page') . ' ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(),
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
