<?php

namespace app\views\pdfLayouts;

use app\models\db\Amendment;
use app\models\db\ConsultationMotionType;
use app\models\db\IMotionSection;
use app\models\db\Motion;
use app\models\exceptions\Internal;
use app\models\sectionTypes\ISectionType;
use TCPDF;
use Yii;

abstract class IPDFLayout
{
    /**
     * @return string[]
     */
    public static function getClasses()
    {
        return [
            -1 => '- ' . \Yii::t('admin', 'pdf_templ_none') . ' -',
            0  => 'LDK Bayern',
            1  => 'BDK',
            2  => 'DBJR',
        ];
    }

    /**
     * @param int $classId
     * @return IPDFLayout|null
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
        $this->pdf->writeHTML('<h4>' . $text . '</h4>');
    }

    /**
     * @return \FPDI
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
