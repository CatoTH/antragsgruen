<?php

declare(strict_types=1);

namespace app\models\api\imotion;

use app\models\db\ISupporter;

class Supporter
{
    public function __construct(
        public ?SupporterType $type = null,
        public ?string $name = null,
        public ?string $organization = null,
    ) {
    }

    public static function fromEntity(ISupporter $supporter): self
    {
        return new self(
            type: $supporter->personType === ISupporter::PERSON_ORGANIZATION ? SupporterType::ORGANIZATION : SupporterType::PERSON,
            name: $supporter->name,
            organization: $supporter->organization,
        );
    }
}
