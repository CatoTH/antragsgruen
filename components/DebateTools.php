<?php

declare(strict_types=1);

namespace app\components;

use app\models\db\{Amendment, Consultation, ConsultationAgendaItem, DebateItem, Motion};

/**
 * Domain logic of the "Currently debated" module. All changes to the debate state go through this class,
 * which enforces the invariant that at most one debateItem per consultation is open (dateStopped IS NULL).
 */
class DebateTools
{
    /**
     * Makes the given motion, amendment, or agenda item the consultation's currently debated item,
     * ending a debate over another item if one is going on.
     * If the given item is already being debated, the open debate is returned unchanged
     * (no new history entry is created).
     */
    public static function startDebate(Consultation $consultation, Motion|Amendment|ConsultationAgendaItem $target): DebateItem
    {
        $current = DebateItem::getCurrentForConsultation($consultation);
        if ($current && self::isDebateOver($current, $target)) {
            return $current;
        }

        $transaction = DebateItem::getDb()->beginTransaction();
        try {
            self::endDebate($consultation);

            $debate = new DebateItem();
            $debate->consultationId = $consultation->id;
            $debate->motionId = (is_a($target, Motion::class) ? $target->id : null);
            $debate->amendmentId = (is_a($target, Amendment::class) ? $target->id : null);
            $debate->agendaItemId = (is_a($target, ConsultationAgendaItem::class) ? $target->id : null);
            $debate->dateStarted = date('Y-m-d H:i:s');
            if (!$debate->save()) {
                throw new \RuntimeException('Could not save the debate item: ' . print_r($debate->getErrors(), true));
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        return $debate;
    }

    /**
     * Ends the ongoing debate, if there is one.
     */
    public static function endDebate(Consultation $consultation): void
    {
        /** @var DebateItem[] $openDebates */
        $openDebates = DebateItem::find()
            ->where(['consultationId' => $consultation->id, 'dateStopped' => null])
            ->all();
        foreach ($openDebates as $openDebate) {
            $openDebate->dateStopped = date('Y-m-d H:i:s');
            if (!$openDebate->save()) {
                throw new \RuntimeException('Could not end the debate: ' . print_r($openDebate->getErrors(), true));
            }
        }
    }

    private static function isDebateOver(DebateItem $debate, Motion|Amendment|ConsultationAgendaItem $target): bool
    {
        if (is_a($target, Motion::class)) {
            return $debate->motionId === $target->id;
        } elseif (is_a($target, Amendment::class)) {
            return $debate->amendmentId === $target->id;
        } else {
            return $debate->agendaItemId === $target->id;
        }
    }
}
