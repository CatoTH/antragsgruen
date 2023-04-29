<?php

declare(strict_types=1);

namespace app\plugins\dbwv\workflow;

use app\models\db\IMotion;
use app\components\{MotionNumbering, RequestContext, UrlHelper};
use app\models\db\Motion;
use app\models\exceptions\Access;
use app\models\forms\MotionDeepCopy;
use app\models\http\RedirectResponse;

class Step3
{

    public static function renderMotionAdministration(Motion $motion): string
    {
        $html = '';

        if (Workflow::canSetResolutionV3($motion)) {
            RequestContext::getController()->layoutParams->loadCKEditor();

            $html .= RequestContext::getController()->renderPartial(
                '@app/plugins/dbwv/views/admin_step_3_decide', ['motion' => $motion]
            );
        }

        return $html;
    }

    public static function setDecision(Motion $motion, int $status, ?string $comment, bool $protocolPublic, ?string $protocol): RedirectResponse
    {
        if (!Workflow::canSetResolutionV3($motion)) {
            throw new Access('Not allowed to perform this action (generally)');
        }
        if ($motion->version === Workflow::STEP_V3) {
            if (MotionNumbering::findMotionInHistoryOfVersion($motion, Workflow::STEP_V4)) {
                throw new Access('A new version of this motion was already created');
            }

            $motion->status = $status;
            $motion->save();

            if ($status === IMotion::STATUS_MODIFIED_ACCEPTED) {
                $motion->setProtocol($protocol, $protocolPublic);
                return new RedirectResponse(UrlHelper::createMotionUrl($motion, 'merge-amendments-init'));
            }

            $v4Motion = MotionDeepCopy::copyMotion(
                $motion,
                $motion->getMyMotionType(),
                $motion->agendaItem,
                $motion->titlePrefix,
                Workflow::STEP_V4,
                true
            );
        } else {
            $v4Motion = $motion;
        }
        unset($motion);

        $v4Motion->status = $status;
        $v4Motion->proposalComment = $comment;
        $v4Motion->save();

        $v4Motion->setProtocol($protocol, $protocolPublic);

        return new RedirectResponse(UrlHelper::createMotionUrl($v4Motion));
    }
}
