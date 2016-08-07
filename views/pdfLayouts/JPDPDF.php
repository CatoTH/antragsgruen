<?php

namespace app\views\pdfLayouts;

use Yii;

class JPDPDF extends \TCPDF
{
    /** @var IPDFLayout */
    private $layout;

    private $footerlogo = array();

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
        $site = \yii::$app->params;

        $position = 'C';
        $dim = $this->getPageDimensions();

        if ($site->getAbsolutePdfLogo()) {
            if (empty($this->footerlogo)) {
                $this->footerlogo['dim'] = getimagesize($site->getAbsolutePdfLogo());
                $this->footerlogo['h'] = 8;
                $this->footerlogo['scale'] = $this->footerlogo['h'] / $this->footerlogo['dim'][1];
                $this->footerlogo['w'] = $this->footerlogo['dim'][0] * $this->footerlogo['scale'];
                $this->footerlogo['x'] = $dim['wk'] - $dim['rm'] - $this->footerlogo['w'];
                $this->footerlogo['y'] = $dim['hk'] - $dim['bm'] + $this->footerlogo['h'] / 2;
            }
            $position = 'L';
            $this->setJPEGQuality(100);
            $this->Image($site->getAbsolutePdfLogo(), $this->footerlogo['x'], $this->footerlogo['y'], $this->footerlogo['w'], $this->footerlogo['h']);
        }

        $this->SetTextColor(100, 0, 80, 10);

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
            $position,
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
