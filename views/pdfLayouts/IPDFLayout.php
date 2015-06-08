<?php

namespace app\views\pdfLayouts;

use app\models\db\ConsultationMotionType;
use app\models\db\Motion;
use app\models\exceptions\Internal;
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
            -1 => '- kein PDF -',
            0  => 'LDK Bayern',
            1  => 'BDK',
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
     * @return TCPDF
     */
    abstract public function createPDFClass();

    /**
     * @param Motion $motion
     */
    abstract public function printMotionHeader(Motion $motion);
}
