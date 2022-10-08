<?php

namespace app\plugins\motionslides;

use app\components\UrlHelper;
use app\models\db\ConsultationText;
use app\models\layoutHooks\{Hooks, Layout};
use yii\helpers\Html;

class LayoutHooks extends Hooks
{
    public function getContentPageContent(string $before, ConsultationText $text, bool $admin): string
    {
        if ($admin) {
            $before .= \Yii::$app->controller->renderPartial(
                '@app/plugins/motionslides/views/admin-content-page', ['pageData' => $text]
            );
        }

        return $before;
    }
}
