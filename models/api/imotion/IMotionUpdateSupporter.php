<?php

declare(strict_types=1);

namespace app\models\api\imotion;

class IMotionUpdateSupporter
{
    public function __construct(
        public string $name,
        public ?string $organization = null,
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
            if ($name === '') {
                continue;
            }
            $organization = isset($post['supporters']['organization'][$i])
                ? trim((string)$post['supporters']['organization'][$i])
                : null;
            $supporters[] = new self(
                name: $name,
                organization: $organization !== '' ? $organization : null,
            );
        }
        return $supporters;
    }
}
