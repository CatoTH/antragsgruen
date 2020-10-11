<?php

namespace app\plugins\egp\controllers;

use app\controllers\Base;

class CandidaturesController extends Base
{
    public function actionIndex($agendaItemId = '')
    {
        $agendaItem = $this->consultation->getAgendaItem($agendaItemId);
        if (!$agendaItem) {
            $this->showErrorpage(404, 'Agenda item not found');
            return;
        }

        $motions = $agendaItem->getVisibleMotionsSorted(false);
        return $this->render('@app/plugins/egp/views/candidatures', [
            'agendaItem' => $agendaItem,
            'motions' => $motions,
        ]);
    }
}
