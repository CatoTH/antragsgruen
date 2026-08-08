<?php

declare(strict_types=1);

namespace app\models\motionTypeTemplates;

use app\components\LanguageTools;
use app\models\db\{ConsultationMotionType, ConsultationSettingsMotionSection};

/**
 * Shared by the motion type templates in this namespace (Motion, Manifesto, Statutes, ...): creates
 * one section per "slot" a template defines - title, body text, and so on - or, on a site with
 * several supported languages, one section per language for slots whose content is free text meant
 * to be read, grouped via languageGrouping so the reading path (MotionSectionLanguageFilter) treats
 * them as translations of each other. Non-text slots (image, tabular data, PDF, ...) always stay a
 * single, language-neutral section - their content is an upload/structured value, not prose, so
 * duplicating them would just force redundant uploads per language.
 */
class SectionTemplateBuilder
{
    private ConsultationMotionType $motionType;

    /** @var string[] primary language first, if it is among the supported ones */
    private array $languages;

    private int $position = 0;

    public function __construct(ConsultationMotionType $motionType)
    {
        $this->motionType = $motionType;

        $consultation    = $motionType->getConsultation();
        $languages       = LanguageTools::getSupportedLanguages($consultation->site);
        $primaryLanguage = LanguageTools::getPrimaryLanguage($consultation);
        if (in_array($primaryLanguage, $languages, true)) {
            $languages = array_merge([$primaryLanguage], array_values(array_diff($languages, [$primaryLanguage])));
        }
        $this->languages = $languages;
    }

    /**
     * @param callable(?string): ConsultationSettingsMotionSection $factory builds a fresh, fully
     *        configured but not-yet-saved section - motionTypeId, position and (for translatable
     *        slots) language/languageGrouping are set here and must not be set by the factory. Called
     *        with the language the section is being built for (null for a single-language site or a
     *        non-translatable slot), so the factory can resolve the section's own title into that
     *        language via `\Yii::t($category, $message, [], $language)` instead of the ambient
     *        `\Yii::$app->language`.
     * @param string $groupingKey only meaningful (and required to be non-empty) when $translatable
     */
    public function addSection(callable $factory, bool $translatable, string $groupingKey = ''): void
    {
        $languages = ($translatable && count($this->languages) >= 2) ? $this->languages : [null];

        foreach ($languages as $language) {
            /** @var ConsultationSettingsMotionSection $section */
            $section                = $factory($language);
            $section->motionTypeId  = $this->motionType->id;
            $section->position      = $this->position++;

            if ($language !== null) {
                $settings                   = $section->getSettingsObj();
                $settings->language         = $language;
                $settings->languageGrouping = $groupingKey;
                $section->setSettingsObj($settings);
            }

            $section->save();
        }
    }
}
