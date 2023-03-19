<?php

declare(strict_types=1);

namespace app\plugins\dbwv;

use app\models\db\Consultation;
use app\models\settings\Layout;
use app\models\settings\Privilege;
use app\plugins\dbwv\workflow\Workflow;
use app\plugins\ModuleBase;
use yii\web\View;

class Module extends ModuleBase
{
    public const PRIVILEGE_DBWV_V1_ASSIGN_TOPIC = -100;
    public const PRIVILEGE_DBWV_V4_MOVE_TO_MAIN = -101;

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
            self::PRIVILEGE_DBWV_V4_MOVE_TO_MAIN,
            'V4: Zur Hauptversammlung übertragen',
            true,
            null
        );

        return $origPrivileges;
    }

    protected static function getMotionUrlRoutes(): array
    {
        return [
            'workflow-step1-next' => 'dbwv/admin-workflow/step1next',
            'workflow-step2-edit' => 'dbwv/admin-workflow/step2edit',
            'workflow-step2-next' => 'dbwv/admin-workflow/step2next',
            'workflow-step3-next' => 'dbwv/admin-workflow/step3next',
            'workflow-step4-next' => 'dbwv/admin-workflow/step4next',
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
}
