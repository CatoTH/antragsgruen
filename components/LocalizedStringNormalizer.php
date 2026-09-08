<?php

declare(strict_types=1);

namespace app\components;

use app\models\api\LocalizedString;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Decides which of the two forms of a LocalizedString ends up in a serialized payload:
 * the plain string in the reader's language (REST responses), or an object with one entry per
 * language of the consultation (live events, which are rendered once for all readers and resolved
 * per subscriber by the Live server).
 *
 * Registered in Tools::getSerializer(); LiveTools is the only place setting the context flag.
 */
class LocalizedStringNormalizer implements NormalizerInterface
{
    public const CONTEXT_ALL_LANGUAGES = 'localized_all_languages';

    /**
     * @return string|array<string, string>
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): string|array
    {
        /** @var LocalizedString $data */
        if ($context[self::CONTEXT_ALL_LANGUAGES] ?? false) {
            return $data->getAll();
        }

        return $data->get();
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof LocalizedString;
    }

    /**
     * @return array<class-string|string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [LocalizedString::class => true];
    }
}
