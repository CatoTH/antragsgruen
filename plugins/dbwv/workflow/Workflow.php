<?php

declare(strict_types=1);

namespace app\plugins\dbwv\workflow;

use app\models\AdminTodoItem;
use app\models\db\{Consultation, Motion, User};
use app\models\settings\{PrivilegeQueryContext, Privileges};
use app\plugins\dbwv\Module;

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
    public const STEP_NAME_V2 = 'V2: Redaktionell aufbereitet';
    public const STEP_NAME_V3 = 'V3: Ergebnis Antragsberatung des Ausschusses';
    public const STEP_NAME_V4 = 'V4: Beschluss LV';
    public const STEP_NAME_V5 = 'V5: In HV eingereicht';
    public const STEP_NAME_V6 = 'V6: Ergebnis Antragsberatung KoordA';
    public const STEP_NAME_V7 = 'V7: Beschluss der HV';
    public const STEP_NAME_V8 = 'V8: Beschluss im Beschlussumdruck';

    public static function getStepName(string $step): ?string {
        return match ($step) {
            self::STEP_V1 => self::STEP_NAME_V1,
            self::STEP_V2 => self::STEP_NAME_V2,
            self::STEP_V3 => self::STEP_NAME_V3,
            self::STEP_V4 => self::STEP_NAME_V4,
            self::STEP_V5 => self::STEP_NAME_V5,
            self::STEP_V6 => self::STEP_NAME_V6,
            self::STEP_V7 => self::STEP_NAME_V7,
            self::STEP_V8 => self::STEP_NAME_V8,
            default => null,
        };
    }

    public static function canAssignTopic(Motion $motion): bool
    {
        return $motion->getMyConsultation()->havePrivilege(
            Module::PRIVILEGE_DBWV_ASSIGN_TOPIC,
            PrivilegeQueryContext::motion($motion)
        );
    }

    public static function canMakeEditorialChangesV1(Motion $motion): bool
    {
        if (!$motion->isInScreeningProcess()) {
            return false;
        }
        return $motion->getMyConsultation()->havePrivilege(
            Module::PRIVILEGE_DBWV_V1_EDITORIAL,
            PrivilegeQueryContext::motion($motion)
        );
    }

    public static function canSetRecommendationV2(Motion $motion): bool
    {
        if ($motion->getMyConsultation()->havePrivilege(Privileges::PRIVILEGE_CONSULTATION_SETTINGS, null)) {
            return true;
        }
        if (!$motion->isVisible()) {
            return false;
        }
        return $motion->canEditLimitedProposedProcedure();
    }

    public static function canSetResolutionV3(Motion $motion): bool
    {
        return $motion->getMyConsultation()->havePrivilege(
            Privileges::PRIVILEGE_MOTION_STATUS_EDIT,
            PrivilegeQueryContext::motion($motion)
        );
    }

    public static function canMoveToMainV4(Motion $motion): bool
    {
        return $motion->getMyConsultation()->havePrivilege(
            Module::PRIVILEGE_DBWV_V4_MOVE_TO_MAIN,
            PrivilegeQueryContext::motion($motion)
        );
    }

    public static function canMoveToMainGenerally(Consultation $consultation): bool
    {
        return $consultation->havePrivilege(
            Module::PRIVILEGE_DBWV_V4_MOVE_TO_MAIN,
            PrivilegeQueryContext::anyRestriction()
        );
    }

    public static function canMakeEditorialChangesV5(Motion $motion): bool
    {
        return $motion->getMyConsultation()->havePrivilege(
            Module::PRIVILEGE_DBWV_V4_MOVE_TO_MAIN,
            PrivilegeQueryContext::motion($motion)
        );
    }

    public static function canSetRecommendationV5(Motion $motion): bool
    {
        if ($motion->getMyConsultation()->havePrivilege(Privileges::PRIVILEGE_CONSULTATION_SETTINGS, null)) {
            return true;
        }
        if ($motion->isVisible()) {
            return false;
        }
        return $motion->canEditProposedProcedure();
    }

    public static function shouldPublishRecommendationV5(Motion $motion): bool
    {
        if ($motion->getMyConsultation()->havePrivilege(Privileges::PRIVILEGE_CONSULTATION_SETTINGS, null)) {
            return true;
        }
        if ($motion->isVisible()) {
            return false;
        }
        return $motion->getMyConsultation()->havePrivilege(Privileges::PRIVILEGE_CHANGE_PROPOSALS, null); // This is "Arbeitsgruppe Leitung"
    }

    public static function canSetResolutionV6(Motion $motion): bool
    {
        return $motion->getMyConsultation()->havePrivilege(
            Privileges::PRIVILEGE_MOTION_STATUS_EDIT,
            PrivilegeQueryContext::motion($motion)
        );
    }

    public static function canPublishResolutionV7(Motion $motion): bool
    {
        return $motion->getMyConsultation()->havePrivilege(
            Module::PRIVILEGE_DBWV_V7_PUBLISH_RESOLUTION,
            PrivilegeQueryContext::motion($motion)
        );
    }

    /**
     * @return AdminTodoItem[]
     */
    public static function getAdminTodoItems(Consultation $consultation, User $user): array
    {
        $todo = [];
        foreach ($consultation->motions as $motion) {
            switch ($motion->version) {
                case self::STEP_V1:
                    $todo[] = Step1::getAdminTodo($motion);
                    break;
                case self::STEP_V2:
                    $todo[] = Step2::getAdminTodo($motion);
                    break;
                case self::STEP_V3:
                    $todo[] = Step3::getAdminTodo($motion);
                    break;
                case self::STEP_V4:
                    $todo[] = Step4::getAdminTodo($motion);
                    break;
                case self::STEP_V5:
                    $todo[] = Step5::getAdminTodo($motion);
                    break;
                case self::STEP_V6:
                    $todo[] = Step6::getAdminTodo($motion);
                    break;
                case self::STEP_V7:
                    $todo[] = Step7::getAdminTodo($motion);
                    break;
            }
        }
        return array_values(array_filter($todo));
    }
}
