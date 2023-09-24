<?php

declare(strict_types=1);

namespace app\plugins\dbwv;

use app\components\RequestContext;
use app\models\AdminTodoItem;
use app\models\exceptions\{Access, Internal};
use app\models\layoutHooks\Hooks;
use app\models\http\{HtmlResponse, ResponseInterface};
use app\models\db\{Consultation, ConsultationMotionType, IMotion, Motion, User};
use app\models\settings\{Layout, Privilege, PrivilegeQueryContext, Privileges};
use app\plugins\dbwv\workflow\{Step2, Step5, Workflow};
use app\plugins\ModuleBase;
use yii\web\View;

class Module extends ModuleBase
{
    public const PRIVILEGE_DBWV_ASSIGN_TOPIC = -100;
    public const PRIVILEGE_DBWV_V1_EDITORIAL = -101;
    public const PRIVILEGE_DBWV_V4_MOVE_TO_MAIN = -102;
    public const PRIVILEGE_DBWV_V7_PUBLISH_RESOLUTION = -103;

    public const CONSULTATION_URL_BUND = 'hv';
    public const GROUP_NAME_DELEGIERTE = 'Delegierte';
    public const GROUP_NAME_ANTRAGSBERECHTIGT = 'Antragsberechtigte';

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
            self::PRIVILEGE_DBWV_ASSIGN_TOPIC,
            'Sachgebiete zuordnen',
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

        $origPrivileges[] = new Privilege(
            self::PRIVILEGE_DBWV_V7_PUBLISH_RESOLUTION,
            'V7: Beschlüsse veröffentlichen',
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
     * @return class-string<\app\models\settings\Consultation>
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
            'workflow-step3-decide' => 'dbwv/admin-workflow/step3decide',
            'workflow-step4' => 'dbwv/admin-workflow/step4next',
            'workflow-step5-assign-number' => 'dbwv/admin-workflow/step5-assign-number',
            'workflow-step6-decide' => 'dbwv/admin-workflow/step6decide',
            'workflow-step7-publish-resolution' => 'dbwv/admin-workflow/step7-publish-resolution',
        ];
    }

    public static function getAllUrlRoutes(array $urls, string $dom, string $dommotion, string $dommotionOld, string $domamend, string $domamendOld): array
    {
        $urls = parent::getAllUrlRoutes($urls, $dom, $dommotion, $dommotionOld, $domamend, $domamendOld);

        $urls[$dom . '<consultationPath:[\w_-]+>/dbwv/propose-title-prefix'] = '/dbwv/ajax-helper/propose-title-prefix';

        return $urls;
    }

    /**
     * @return array<string, array{title: string, preview: string|null, bundle: class-string, hooks?: class-string<Hooks>, odtTemplate?: string}>
     */
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
        return 'layout-plugin-dbwv-std';
    }

    public static function getFullMotionListClassOverride(): ?string
    {
        return AdminMotionFilterForm::class;
    }

    public static function canSeeFullMotionList(Consultation $consultation, User $user): ?bool
    {
        if (
            $user->hasPrivilege($consultation, self::PRIVILEGE_DBWV_ASSIGN_TOPIC, PrivilegeQueryContext::anyRestriction()) ||
            $user->hasPrivilege($consultation, self::PRIVILEGE_DBWV_V1_EDITORIAL, PrivilegeQueryContext::anyRestriction()) ||
            $user->hasPrivilege($consultation, self::PRIVILEGE_DBWV_V4_MOVE_TO_MAIN, PrivilegeQueryContext::anyRestriction())
        ) {
            return true;
        }

        return null;
    }

    public static function canSeeContactDetails(IMotion $imotion, ?User $user): ?bool
    {
        if (!$user) {
            return false;
        }

        $consultation = $imotion->getMyConsultation();
        if (
            $user->hasPrivilege($consultation, self::PRIVILEGE_DBWV_ASSIGN_TOPIC, PrivilegeQueryContext::imotion($imotion)) ||
            $user->hasPrivilege($consultation, self::PRIVILEGE_DBWV_V1_EDITORIAL, PrivilegeQueryContext::imotion($imotion)) ||
            $user->hasPrivilege($consultation, self::PRIVILEGE_DBWV_V4_MOVE_TO_MAIN, PrivilegeQueryContext::imotion($imotion)) ||
            $user->hasPrivilege($consultation, self::PRIVILEGE_DBWV_V7_PUBLISH_RESOLUTION, PrivilegeQueryContext::imotion($imotion))
        ) {
            return true;
        }

        return null;
    }

    public static function onBeforeProposedProcedureStatusSave(IMotion $imotion): IMotion
    {
        if (is_a($imotion, Motion::class)) {
            if (in_array($imotion->version, [Workflow::STEP_V2, Workflow::STEP_V3], true)) {
                // This always switches to V3 and also enforces the proposed procedure to be only available on V2
                return Step2::gotoNext($imotion);
            }
            if (in_array($imotion->version, [Workflow::STEP_V5, Workflow::STEP_V6], true)) {
                // This always switches to V6 and also enforces the proposed procedure to be only available on V5
                return Step5::gotoNext($imotion);
            }
            throw new Access('Not allowed to perform this action (in this state)');
        }
        return $imotion;
    }

    public static function getBundConsultation(): Consultation
    {
        $consultation = Consultation::getCurrent();
        if ($consultation->urlPath === self::CONSULTATION_URL_BUND) {
            return $consultation;
        }
        foreach ($consultation->site->consultations as $consultation) {
            if ($consultation->urlPath === self::CONSULTATION_URL_BUND) {
                return $consultation;
            }
        }
        throw new Internal('No main consultation found');
    }

    public static function getCorrespondingBundMotionType(ConsultationMotionType $lvType): ConsultationMotionType
    {
        foreach (self::getBundConsultation()->motionTypes as $motionType) {
            if ($motionType->isCompatibleTo($lvType) && $motionType->titleSingular === $lvType->titleSingular) {
                return $motionType;
            }
        }
        throw new Internal('No compatible motion type found');
    }

    /**
     * @return AdminTodoItem[]|null
     */
    public static function getAdminTodoItems(Consultation $consultation, User $user): ?array
    {
        return Workflow::getAdminTodoItems($consultation, $user);
    }

    public static function hasSiteHomePage(): bool
    {
        return true;
    }

    public static function getSiteHomePage(): ?ResponseInterface
    {
        return new HtmlResponse(RequestContext::getController()->render('@app/plugins/dbwv/views/index'));
    }

    public static function preferConsultationSpecificHomeLink(): bool
    {
        return true;
    }

    public static function currentUserCanSeeMotions(): bool
    {
        if (!User::getCurrentUser()) {
            return false;
        }

        foreach (User::getCurrentUser()->userGroups as $group) {
            if ($group->getGroupPermissions()->containsPrivilege(Privileges::PRIVILEGE_ANY, PrivilegeQueryContext::anyRestriction())) {
                return true;
            }
            if ($group->title === Module::GROUP_NAME_DELEGIERTE) {
                return true;
            }
        }

        return false;
    }
}
