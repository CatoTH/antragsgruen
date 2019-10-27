<?php

namespace app\models\forms;

use app\models\db\Consultation;
use app\models\db\ConsultationAgendaItem;
use app\models\db\IMotion;
use app\models\db\Motion;

class MotionMover
{
    /** @var Consultation */
    private $consultation;

    /** @var Motion */
    private $motion;

    public function __construct(Consultation $consultation, Motion $motion)
    {
        $this->consultation = $consultation;
        $this->motion       = $motion;
    }

    /**
     * @param $post
     *
     * @return Motion|null
     */
    public function move($post)
    {
        if (!isset($post['target']) || !isset($post['operation']) || !isset($post['titlePrefix'])) {
            return null;
        }

        $titlePrefix = $post['titlePrefix'];

        switch ($post['target']) {
            case 'agenda':
                $agendaItemId = IntVal($post['agendaItem'][$this->consultation->id]);
                $agendaItem   = $this->consultation->getAgendaItem($agendaItemId);
                if ($post['operation'] === 'copy') {
                    return $this->copyToAgendaItem($agendaItem, $titlePrefix);
                }
                if ($post['operation'] === 'move') {
                    return $this->moveToAgendaItem($agendaItem, $titlePrefix);
                }
                break;
        }

        return null;
    }

    private function copyToAgendaItem(ConsultationAgendaItem $agendaItem, string $titlePrefix): Motion
    {
        $newMotion = MotionDeepCopy::copyMotion($this->motion, $agendaItem->getMyConsultation(), $agendaItem, $titlePrefix);

        $newMotion->parentMotionId = $this->motion->id;
        $newMotion->save();

        $this->motion->status = IMotion::STATUS_MOVED;
        $this->motion->save();

        return $newMotion;
    }

    private function moveToAgendaItem(ConsultationAgendaItem $agendaItem, $titlePrefix): Motion
    {
        $this->motion->agendaItemId = $agendaItem->id;
        $this->motion->titlePrefix  = $titlePrefix;
        $this->motion->save();
        $this->motion->refresh();

        return $this->motion;
    }
}
