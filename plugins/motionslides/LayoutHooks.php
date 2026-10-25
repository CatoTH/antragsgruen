<?php

namespace app\plugins\motionslides;

use app\components\view\View;
use app\models\db\ConsultationText;
use app\models\layoutHooks\Hooks;

class LayoutHooks extends Hooks
{
    public function getContentPageContent(string $before, ConsultationText $text, bool $admin): string
    {
        if ($admin) {
            $before .= View::getCurrent()->render(
                '@app/plugins/motionslides/views/admin-content-page', ['pageData' => $text]
            );
        }

        return $before;
    }
}
