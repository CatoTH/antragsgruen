<?php

namespace app\views\pdfLayouts;

use app\models\db\Amendment;
use app\models\db\ConsultationMotionType;
use app\models\db\IMotionSection;
use app\models\db\Motion;
use app\models\exceptions\Internal;
use app\models\settings\AntragsgruenApp;
use setasign\Fpdi\Tcpdf\Fpdi;
use TCPDF;

abstract class IPDFLayout
{
    /**
     * @param AntragsgruenApp $params
     * @return string[]
     */
    public static function getClasses($params)
    {
        return [
            -1 => [
                'title'   => '- ' . \Yii::t('admin', 'pdf_templ_none') . ' -',
                'preview' => null,
            ],
            0  => [
                'title'   => 'LDK Bayern',
                'preview' => $params->resourceBase . 'img/pdf_preview_byldk.png',
            ],
            1  => [
                'title'   => 'BDK',
                'preview' => $params->resourceBase . 'img/pdf_preview_bdk.png',
            ],
            2  => [
                'title'   => 'DBJR',
                'preview' => $params->resourceBase . 'img/pdf_preview_dbjr.png',
            ],
        ];
    }

    /**
     * @param int $classId
     * @return IPDFLayout|string|null
     * @throws Internal
     */
    public static function getClassById($classId)
    {
        switch ($classId) {
            case -1:
                return null;
            case 0:
                return ByLDK::class;
            case 1:
                return BDK::class;
            case 2:
                return DBJR::class;
            default:
                throw new Internal('Unknown PDF Layout');
        }
    }

    /** @var ConsultationMotionType */
    protected $motionType;

    /** @var TCPDF */
    protected $pdf;

    /**
     * @param ConsultationMotionType $motionType
     */
    public function __construct(ConsultationMotionType $motionType)
    {
        $this->motionType = $motionType;
    }

    /**
     */
    public function getFonts()
    {
    }

    /**
     * @param string $text
     */
    public function printSectionHeading($text)
    {
        $this->pdf->SetFont('helvetica', '', 12);
        $this->pdf->ln(2);
        $this->pdf->MultiCell(0, 0, '<h4>' . $text . '</h4>', 0, 'L', false, 1, '', '', true, 0, true);
    }

    /**
     * @return Fpdi
     */
    abstract public function createPDFClass();

    /**
     * @param Motion $motion
     */
    abstract public function printMotionHeader(Motion $motion);

    /**
     * @param Amendment $amendment
     */
    abstract public function printAmendmentHeader(Amendment $amendment);

    /**
     * @param IMotionSection $section
     * @return bool
     */
    abstract public function isSkippingSectionTitles(IMotionSection $section);
}
