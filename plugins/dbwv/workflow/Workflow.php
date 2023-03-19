<?php

declare(strict_types=1);

namespace app\plugins\dbwv\workflow;

class Workflow
{
    public const STEP_V1 = '1';
    public const STEP_V2 = '2';
    public const STEP_V3 = '3';
    public const STEP_V4 = '4';
    public const STEP_V5 = '5';
    public const STEP_V6 = '6';
    public const STEP_V7 = '7';
    public const STEP_V8 = '8';

    public const STEP_NAME_V1 = 'V1: Originalantrag Kameradschaft';
    public const STEP_NAME_V2 = 'V2: Redaktionell aufbereitet durch BGSt';
    public const STEP_NAME_V3 = 'V3: Ergebnis Antragsberatung der AG';
    public const STEP_NAME_V4 = 'V4: Beschluss LV';
    public const STEP_NAME_V5 = 'V5: Redaktionell aufbereitet durch BGSt';
    public const STEP_NAME_V6 = 'V6: Ergebnis Antragsberatung KoordA';
    public const STEP_NAME_V7 = 'V7: Beschluss der HV';
    public const STEP_NAME_V8 = 'V8: Beschluss im Beschlussumdruck';

    public static function getStepName(string $step): string {
        switch ($step) {
            case self::STEP_V1:
                return self::STEP_NAME_V1;
            case self::STEP_V2:
                return self::STEP_NAME_V2;
            case self::STEP_V3:
                return self::STEP_NAME_V3;
            case self::STEP_V4:
                return self::STEP_NAME_V4;
            case self::STEP_V5:
                return self::STEP_NAME_V5;
            case self::STEP_V6:
                return self::STEP_NAME_V6;
            case self::STEP_V7:
                return self::STEP_NAME_V7;
            case self::STEP_V8:
                return self::STEP_NAME_V8;
            default:
                return 'Unknown';
        }
    }
}
