<?php

namespace app\plugins\egp\controllers;

use app\controllers\Base;
use app\models\db\IMotion;
use app\models\http\{HtmlErrorResponse, HtmlResponse, ResponseInterface};

class CandidaturesController extends Base
{
    public function actionIndex(int $agendaItemId = 0, int $motionTypeId = 0): ResponseInterface
    {
        if ($agendaItemId) {
            $agendaItem = $this->consultation->getAgendaItem($agendaItemId);
            if (!$agendaItem) {
                return new HtmlErrorResponse(404, 'Agenda item not found');
            }
            $motions = $agendaItem->getVisibleIMotionsSorted(false);
            $motionType = null;
        } elseif ($motionTypeId) {
            $motionType = $this->consultation->getMotionType($motionTypeId);
            $motions = $motionType->getVisibleMotions(false);
            $agendaItem = null;
        } else {
            return new HtmlErrorResponse(400, 'Either an motion type or an agenda item needs to be provided');
        }

        usort($motions, function (IMotion $motion1, IMotion $motion2): int {
            return strnatcasecmp($motion1->getTitleWithPrefix(), $motion2->getTitleWithPrefix());
        });

        return new HtmlResponse($this->render('@app/plugins/egp/views/candidatures', [
            'agendaItem' => $agendaItem,
            'motionType' => $motionType,
            'motions' => $motions,
        ]));
    }
}
