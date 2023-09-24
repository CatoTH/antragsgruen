<?php

namespace app\plugins\member_petitions;

use app\components\RequestContext;
use app\models\http\{HtmlResponse, ResponseInterface};
use app\models\policies\IPolicy;
use app\models\db\{Consultation, Motion};
use app\models\settings\Layout;
use app\plugins\ModuleBase;
use yii\base\Event;
use yii\web\Controller;

class Module extends ModuleBase
{
    public function init(): void
    {
        parent::init();

        Event::on(Motion::class, Motion::EVENT_MERGED, [Tools::class, 'onMerged']);
        Event::on(Motion::class, Motion::EVENT_PUBLISHED_FIRST, [Tools::class, 'onPublishedFirst']);
    }

    /**
     * @return \yii\web\AssetBundle[]|string[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getActiveAssetBundles(Controller $controller): array
    {
        return [
            Assets::class
        ];
    }

    protected static function getMotionUrlRoutes(): array
    {
        return [
            'write-petition-response' => 'member_petitions/backend/write-response',
        ];
    }

    public static function getPermissionsClass(): ?string
    {
        return Permissions::class;
    }

    /**
     * @return string[]|IPolicy[]
     */
    public static function getCustomPolicies(): array
    {
        return [
            MotionPolicy::class,
        ];
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
            '@app/plugins/member_petitions/views/admin/consultation_settings', ['consultation' => $consultation]
        );
    }

    public static function getForcedLayoutHooks(Layout $layoutSettings, ?Consultation $consultation): array
    {
        return [
            new LayoutHooks($layoutSettings, $consultation)
        ];
    }

    public static function hasSiteHomePage(): bool
    {
        return true;
    }

    public static function getSiteHomePage(): ResponseInterface
    {
        return new HtmlResponse(RequestContext::getController()->render('@app/plugins/member_petitions/views/index'));
    }
}
