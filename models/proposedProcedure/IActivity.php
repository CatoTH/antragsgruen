<?php

declare(strict_types=1);

namespace app\models\proposedProcedure;

use app\models\db\ConsultationLog;
use app\models\db\IAdminComment;
use app\models\db\IMotion;

abstract class IActivity
{
    public \DateTimeInterface $date;

    /**
     * @return IActivity[]
     */
    public static function getListFromIMotion(IMotion $imotion): array
    {
        $activities = [];

        foreach ($imotion->getAdminComments([IAdminComment::TYPE_PROPOSED_PROCEDURE], IAdminComment::SORT_ASC) as $comment) {
            $activities[] = new ActivityAdminComment($comment);
        }

        foreach (ConsultationLog::getLogForProposedProcedure($imotion) as $log) {
            $activities[] = new ActivityConsultationLog($log);
        }

        usort($activities, function ($a, $b) {
            return $a->getDate() <=> $b->getDate();
        });

        return $activities;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }
}
