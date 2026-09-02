<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\components\VotingSingleVoteNormalizer;
use app\models\api\voting\{VotingSingleVote, VotingVoter};
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\{CamelCaseToSnakeCaseNameConverter, MetadataAwareNameConverter};
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Tests\Support\Helper\TestBase;

/**
 * VotingSingleVoteNormalizer writes out by hand what the ObjectNormalizer would derive from the two
 * DTOs, so a property added to either of them would silently be dropped from every voting payload.
 * These tests are what makes that a failing build instead.
 */
class VotingSingleVoteNormalizerTest extends TestBase
{
    private function buildVote(): VotingSingleVote
    {
        return new VotingSingleVote(
            answer: 'yes',
            weight: 3,
            voter: new VotingVoter(userId: 42, userGroupIds: [1, 7], userName: 'Example Person'),
        );
    }

    public function testNormalizesToTheDocumentedShape(): void
    {
        $normalized = (new VotingSingleVoteNormalizer())->normalize($this->buildVote());

        $this->assertSame([
            'answer' => 'yes',
            'weight' => 3,
            'voter' => [
                'user_id' => 42,
                'user_group_ids' => [1, 7],
                'user_name' => 'Example Person',
            ],
        ], $normalized);
    }

    /**
     * The names and the order the ObjectNormalizer would have produced: every public property of the
     * DTO, in declaration order, converted from camelCase to snake_case.
     *
     * @return string[]
     */
    private function expectedKeys(string $className): array
    {
        $keys = [];
        foreach ((new \ReflectionClass($className))->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $keys[] = strtolower((string)preg_replace('/(?<!^)[A-Z]/', '_$0', $property->getName()));
        }

        return $keys;
    }

    public function testCoversEveryPropertyOfBothDtos(): void
    {
        $normalized = (new VotingSingleVoteNormalizer())->normalize($this->buildVote());

        $this->assertSame(
            $this->expectedKeys(VotingSingleVote::class),
            array_keys($normalized),
            'VotingSingleVote gained or lost a property that VotingSingleVoteNormalizer does not write'
        );
        $this->assertSame(
            $this->expectedKeys(VotingVoter::class),
            array_keys($normalized['voter']),
            'VotingVoter gained or lost a property that VotingSingleVoteNormalizer does not write'
        );
    }

    /**
     * The point of the whole class: the same bytes the ObjectNormalizer would have produced. Asking
     * the serializer Tools hands out would prove nothing, since that is the one this normalizer is
     * registered in - so the comparison is against an ObjectNormalizer configured the same way.
     */
    public function testProducesWhatTheObjectNormalizerWouldHave(): void
    {
        $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
        $reference = new Serializer(
            [new ObjectNormalizer(
                $classMetadataFactory,
                new MetadataAwareNameConverter($classMetadataFactory, new CamelCaseToSnakeCaseNameConverter())
            )],
            [new JsonEncoder()]
        );

        foreach ([$this->buildVote(), new VotingSingleVote('abstention', 1, new VotingVoter(7, []))] as $vote) {
            $this->assertSame(
                $reference->serialize($vote, 'json'),
                json_encode((new VotingSingleVoteNormalizer())->normalize($vote)),
                'VotingSingleVoteNormalizer drifted away from the ObjectNormalizer it replaces'
            );
        }
    }

    public function testKeepsAnAbsentVoterNameNull(): void
    {
        $vote = new VotingSingleVote(
            answer: 'abstention',
            weight: 1,
            voter: new VotingVoter(userId: 7, userGroupIds: []),
        );

        $normalized = (new VotingSingleVoteNormalizer())->normalize($vote);

        $this->assertNull($normalized['voter']['user_name']);
        $this->assertSame([], $normalized['voter']['user_group_ids']);
    }
}
