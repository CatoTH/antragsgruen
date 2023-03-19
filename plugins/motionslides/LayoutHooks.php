<?php

namespace app\plugins\motionslides;

use app\components\RequestContext;
use app\models\db\ConsultationText;
use app\models\layoutHooks\Hooks;

class LayoutHooks extends Hooks
{
    public function getContentPageContent(string $before, ConsultationText $text, bool $admin): string
    {
        if ($admin) {
            $before .= RequestContext::getController()->renderPartial(
                '@app/plugins/motionslides/views/admin-content-page', ['pageData' => $text]
            );
        }

        return $before;
    }
}
