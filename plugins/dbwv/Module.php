<?php

declare(strict_types=1);

namespace app\plugins\dbwv;

use app\plugins\dbwv\workflow\Step2;
use app\models\db\{Consultation, IMotion, Motion, User};
use app\models\settings\{Layout, Privilege, PrivilegeQueryContext};
use app\plugins\dbwv\workflow\Workflow;
use app\plugins\ModuleBase;
use yii\web\View;

class Module extends ModuleBase
{
    public const PRIVILEGE_DBWV_V1_ASSIGN_TOPIC = -100;
    public const PRIVILEGE_DBWV_V1_EDITORIAL = -101;
    public const PRIVILEGE_DBWV_V4_MOVE_TO_MAIN = -102;

    public static function getProvidedTranslations(): array
    {
        return ['de'];
    }

    public static function getForcedLayoutHooks(Layout $layoutSettings, ?Consultation $consultation): array
    {
        return [
            new LayoutHooks($layoutSettings, $consultation)
        ];
    }

    public static function getDefaultLogo(): array
    {
        return [
            'image/png',
            \Yii::$app->basePath . '/plugins/dbwv/assets/dbwv-logo.png'
        ];
    }

    public static function getMotionVersions(Consultation $consultation): ?array
    {
        return [
            Workflow::STEP_V1 => Workflow::STEP_NAME_V1,
            Workflow::STEP_V2 => Workflow::STEP_NAME_V2,
            Workflow::STEP_V3 => Workflow::STEP_NAME_V3,
            Workflow::STEP_V4 => Workflow::STEP_NAME_V4,
            Workflow::STEP_V5 => Workflow::STEP_NAME_V5,
            Workflow::STEP_V6 => Workflow::STEP_NAME_V6,
            Workflow::STEP_V7 => Workflow::STEP_NAME_V7,
            Workflow::STEP_V8 => Workflow::STEP_NAME_V8,
        ];
    }

    /**
     * @param Privilege[] $origPrivileges
     * @return Privilege[]
     */
    public static function addCustomPrivileges(Consultation $consultation, array $origPrivileges): array
    {
        $origPrivileges[] = new Privilege(
            self::PRIVILEGE_DBWV_V1_ASSIGN_TOPIC,
            'V1: Sachgebiete zuordnen',
            true,
            null
        );

        $origPrivileges[] = new Privilege(
            self::PRIVILEGE_DBWV_V1_EDITORIAL,
            'V1: Nummerierung und redaktionelle Änderungen',
            true,
            null
        );

        $origPrivileges[] = new Privilege(
            self::PRIVILEGE_DBWV_V4_MOVE_TO_MAIN,
            'V4: Zur Hauptversammlung übertragen',
            true,
            null
        );

        return $origPrivileges;
    }

    public static function getPermissionsClass(): ?string
    {
        return Permissions::class;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getConsultationSettingsClass(Consultation $consultation): string
    {
        return ConsultationSettings::class;
    }

    public static function getConsultationExtraSettingsForm(Consultation $consultation): string
    {
        return \Yii::$app->controller->renderPartial(
            '@app/plugins/dbwv/views/admin/consultation_settings', ['consultation' => $consultation]
        );
    }

    protected static function getMotionUrlRoutes(): array
    {
        return [
            'assign-main-tag'     => 'dbwv/admin-workflow/assign-main-tag',
            'workflow-step1-assign-number' => 'dbwv/admin-workflow/step1-assign-number',
            'workflow-step2' => 'dbwv/admin-workflow/step2',
            'workflow-step3' => 'dbwv/admin-workflow/step3',
            'workflow-step4' => 'dbwv/admin-workflow/step4',
        ];
    }

    public static function getAllUrlRoutes(array $urls, string $dom, string $dommotion, string $dommotionOld, string $domamend, string $domamendOld): array
    {
        $urls = parent::getAllUrlRoutes($urls, $dom, $dommotion, $dommotionOld, $domamend, $domamendOld);

        $urls[$dom . '<consultationPath:[\w_-]+>/dbwv/propose-title-prefix'] = '/dbwv/ajax-helper/propose-title-prefix';

        return $urls;
    }

    public static function getProvidedLayouts(?View $view = null): array
    {
        return [
            'std' => [
                'title'   => 'DBwV',
                'preview' => null,
                'bundle'  => Assets::class,
            ]
        ];
    }

    public static function overridesDefaultLayout(): string
    {
        return 'layout-plugin-neos-std';
    }

    public static function canSeeFullMotionList(Consultation $consultation, User $user): ?bool
    {
        if (
            $user->hasPrivilege($consultation, self::PRIVILEGE_DBWV_V1_ASSIGN_TOPIC, PrivilegeQueryContext::anyRestriction()) ||
            $user->hasPrivilege($consultation, self::PRIVILEGE_DBWV_V1_EDITORIAL, PrivilegeQueryContext::anyRestriction()) ||
            $user->hasPrivilege($consultation, self::PRIVILEGE_DBWV_V4_MOVE_TO_MAIN, PrivilegeQueryContext::anyRestriction())
        ) {
            return true;
        }

        return null;
    }

    public static function onBeforeProposedProcedureStatusSave(IMotion $imotion): IMotion
    {
        if (is_a($imotion, Motion::class)) {
            // This always switches to V3 and also enforces the proposed procedure to be only available on V2
            return Step2::gotoNext($imotion);
        }
        return $imotion;
    }
}
