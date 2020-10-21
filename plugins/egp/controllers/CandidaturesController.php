<?php

namespace app\plugins\egp\controllers;

use app\controllers\Base;

class CandidaturesController extends Base
{
    public function actionIndex($agendaItemId = 0, $motionTypeId = 0)
    {
        if ($agendaItemId) {
            $agendaItem = $this->consultation->getAgendaItem($agendaItemId);
            if (!$agendaItem) {
                $this->showErrorpage(404, 'Agenda item not found');

                return false;
            }
            $motions = $agendaItem->getVisibleMotionsSorted(false);
            $motionType = null;
        } elseif ($motionTypeId) {
            $motionType = $this->consultation->getMotionType($motionTypeId);
            if (!$motionType) {
                $this->showErrorpage(404, 'Motion type not found');

                return false;
            }
            $motions = $motionType->getVisibleMotions(false);
            $agendaItem = null;
        } else {
            $this->showErrorpage(400, 'Either an motion type or an agenda item needs to be provided');

            return false;
        }

        return $this->render('@app/plugins/egp/views/candidatures', [
            'agendaItem' => $agendaItem,
            'motionType' => $motionType,
            'motions' => $motions,
        ]);
    }
}
