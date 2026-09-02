<?php

declare(strict_types=1);

namespace app\components;

use app\models\api\voting\VotingSingleVote;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * The one hot spot of the voting payloads, written out by hand.
 *
 * A running voting carries one of these per vote cast, so this is the only object in the API whose
 * count grows with the size of the meeting: at a thousand votes, letting the ObjectNormalizer
 * reflect its way through two thousand objects (each VotingSingleVote holds a VotingVoter) costs
 * around 16 ms per payload, against 0.6 ms for the same output written directly. Nothing else in the
 * API appears often enough for that difference to be worth hand-maintaining a normalizer.
 *
 * The result is byte-identical to what the ObjectNormalizer produces, snake_case names and property
 * order included - VotingSingleVoteNormalizerTest holds it to that. Both DTOs are plain scalars, so
 * no other normalizer has to be consulted for their contents.
 *
 * Registered in Tools::getSerializer(), where it has to come before the ObjectNormalizer.
 */
class VotingSingleVoteNormalizer implements NormalizerInterface
{
    /**
     * @return array<string, mixed>
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        /** @var VotingSingleVote $data */
        return [
            'answer' => $data->answer,
            'weight' => $data->weight,
            'voter' => [
                'user_id' => $data->voter->userId,
                'user_group_ids' => $data->voter->userGroupIds,
                'user_name' => $data->voter->userName,
            ],
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof VotingSingleVote;
    }

    /**
     * @return array<class-string|string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [VotingSingleVote::class => true];
    }
}
