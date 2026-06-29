<?php

declare(strict_types=1);

namespace app\models\api\consultation;

class ConsultationLinkList
{
    public function __construct(
        /** @var ConsultationLink[] */
        public array $consultations,
    ) {
    }
}
