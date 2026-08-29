<?php

declare(strict_types=1);

namespace app\models\api;

use app\components\LanguageTools;
use app\models\db\Consultation;

/**
 * A string of an API payload that depends on the language the reader is browsing the site in -
 * either because it is translated (\Yii::t) or because the content itself exists in several
 * languages (motion titles).
 *
 * The same payload objects are used for two different consumers, which is why the concrete
 * rendering is deferred until serialization time (see LocalizedStringNormalizer):
 * - REST responses are read by exactly one user, so they contain the plain string in that user's
 *   language, exactly as before this class existed.
 * - Live events are sent to RabbitMQ once and delivered to all readers of a consultation by the
 *   Live server, which knows each subscriber's language and picks the matching entry. These
 *   therefore contain an object with one entry per language of the consultation.
 *
 * Rendering happens lazily and is memoized per language: the REST path never renders more than the
 * one language it needs, and a single-language site never renders more than one either.
 */
final class LocalizedString
{
    /** @var array<string, string> */
    private array $rendered = [];

    /**
     * @param \Closure(): string $renderer
     */
    private function __construct(
        private readonly ?Consultation $consultation,
        private readonly \Closure $renderer,
    ) {
    }

    /**
     * The callback is called once per language needed, with the reader language set to that
     * language. It therefore must not be built from anything that was already rendered in the
     * current user's language, and must not rely on caches that are not keyed by language
     * (IMotion::getInitiatorsStr() being the notable example within these payloads).
     */
    public static function build(?Consultation $consultation, callable $renderer): self
    {
        return new self($consultation, $renderer(...));
    }

    /**
     * A string that happens to be the same in every language (user-entered content, mostly), but
     * sits in a field that is localized for other kinds of debate items.
     */
    public static function fromString(?Consultation $consultation, string $string): self
    {
        return new self($consultation, static fn (): string => $string);
    }

    public function get(?string $language = null): string
    {
        $language ??= LanguageTools::getCurrentLanguage();

        if (!isset($this->rendered[$language])) {
            $this->rendered[$language] = LanguageTools::renderInLanguage($this->consultation, $language, $this->renderer);
        }

        return $this->rendered[$language];
    }

    /**
     * @return array<string, string>
     */
    public function getAll(): array
    {
        $strings = [];
        foreach (LanguageTools::getContentLanguages($this->consultation) as $language) {
            $strings[$language] = $this->get($language);
        }

        return $strings;
    }
}
