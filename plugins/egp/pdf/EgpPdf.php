<?php

namespace app\plugins\egp\pdf;

use app\models\db\MotionSection;
use app\views\pdfLayouts\{IPDFLayout, IPdfWriter};

class EgpPdf extends IPdfWriter
{
    private IPDFLayout $layout;

    public string $roboto;
    public string $robotoBold;
    public string $robotoItalic;
    public string $robotoItalicBold;

    public function __construct(IPDFLayout $layout)
    {
        $this->layout  = $layout;
        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $this->roboto           = (string)\TCPDF_FONTS::addTTFfont(__DIR__ . '/../fonts/Roboto-Regular.ttf', 'TrueTypeUnicode', '', 96);
        $this->robotoBold       = (string)\TCPDF_FONTS::addTTFfont(__DIR__ . '/../fonts/Roboto-Bold.ttf', 'TrueTypeUnicode', '', 96);
        $this->robotoItalic     = (string)\TCPDF_FONTS::addTTFfont(__DIR__ . '/../fonts/Roboto-Italic.ttf', 'TrueTypeUnicode', '', 96);
        $this->robotoItalicBold = (string)\TCPDF_FONTS::addTTFfont(__DIR__ . '/../fonts/Roboto-BoldItalic.ttf', 'TrueTypeUnicode', '', 96);
    }

    public function getMotionFont(?MotionSection $section): string
    {
        return $this->roboto;
    }

    public function getMotionFontSize(?MotionSection $section): int
    {
        return 11;
    }

    /**
     * rewrite AddPage() for correct functionalities with PDF Concatenation
     * @param string $orientation
     * @param string $format
     * @param bool $keepmargins
     * @param bool $tocpage
     */
    public function AddPage(
        $orientation = PDF_PAGE_ORIENTATION,
        $format = PDF_PAGE_FORMAT,
        $keepmargins = false,
        $tocpage = false,
        bool $footer = true
    ): void {
        parent::AddPage($orientation, $format, $keepmargins, $tocpage);
        $this->setPrintFooter($footer);
    }

    // @codingStandardsIgnoreStart
    /**
     */
    public function Footer(): void
    {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', '', 8);
        // Page number
        $this->Cell(
            0,
            10,
            \Yii::t('export', 'Page') . ' ' . $this->getGroupPageNo() . ' / ' . $this->getPageGroupAlias(),
            0,
            0,
            'C',
            false,
            '',
            0,
            false,
            'T',
            'M'
        );
    }
    // @codingStandardsIgnoreEnd
}
