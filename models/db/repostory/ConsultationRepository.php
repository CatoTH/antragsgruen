<?php

declare(strict_types=1);

namespace app\models\db\repostory;

use app\models\db\Consultation;

class ConsultationRepository
{
    /** @var array<int|string, Consultation|null> */
    private static array $knownConsultations = [];

    public static function getConsultationById(int $consultationId): ?Consultation
    {
        $current = Consultation::getCurrent();
        if ($current && $current->id === $consultationId) {
            return $current;
        }

        if (!in_array($consultationId, array_keys(self::$knownConsultations))) {
            self::$knownConsultations[$consultationId] = Consultation::findOne($consultationId);
        }

        return self::$knownConsultations[$consultationId];
    }

    public static function flushCache(): void
    {
        self::$knownConsultations = [];
    }
}
