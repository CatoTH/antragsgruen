<?php

declare(strict_types=1);

namespace app\models\api\imotion;

use app\models\db\ISupporter;

class IMotionUpdateSupporter
{
    public function __construct(
        public string $name,
        public ?int $id = null,
        public ?SupporterType $personType = null,
        public ?string $organization = null,
        public ?string $gender = null,
    ) {
    }

    /**
     * @param array<string, mixed> $post
     * @return self[]
     */
    public static function fromPostData(array $post): array
    {
        $supporters = [];
        if (!isset($post['supporters']) || !is_array($post['supporters']['name'] ?? null)) {
            return $supporters;
        }
        foreach ($post['supporters']['name'] as $i => $name) {
            $name = trim((string)$name);
            $organization = isset($post['supporters']['organization'][$i])
                ? trim((string)$post['supporters']['organization'][$i])
                : null;
            $personType = (intval($post['supporters']['personType'][$i] ?? ISupporter::PERSON_NATURAL) === ISupporter::PERSON_ORGANIZATION ?
                SupporterType::ORGANIZATION : SupporterType::PERSON);

            // Organizations are identified by their organization name, natural persons by their name.
            // Rows without the respective name are considered empty and are skipped.
            if ($personType === SupporterType::ORGANIZATION ? ($organization ?? '') === '' : $name === '') {
                continue;
            }
            $supporters[] = new self(
                id: isset($post['supporters']['id']) && $post['supporters']['id'][$i] > 0 ? intval($post['supporters']['id'][$i]) : null,
                name: $name,
                personType: $personType,
                organization: $organization !== '' ? $organization : null,
                gender: $post['supporters']['gender'][$i] ?? null,
            );
        }
        return $supporters;
    }
}
