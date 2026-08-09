<?php

namespace app\models\settings;

class MotionType implements \JsonSerializable
{
    use JsonConfigTrait;

    public string $pdfIntroduction = '';
    public ?string $cssIcon = null;
    public string $motionTitleIntro = '';

    public bool $screeningMotions = false;
    public bool $screeningAmendments = false;
    public bool $hasProposedProcedure = false;
    public bool $proposedProcedureVersioning = true;
    public bool $hasResponsibilities = false;
    public bool $twoColMerging = false; // Can only be set manually as of yet
    public bool $commentsRestrictViewToWritables = false;
    public bool $allowAmendmentsToAmendments = false;
    public bool $showProposalsInExports = false;

    /**
     * Per-language overrides for ConsultationMotionType::$titleSingular/$titlePlural/$createTitle,
     * for sites with more than one supported language (see LanguageTools). The consultation's
     * primary language is never a key here - it is always represented by the DB columns themselves.
     * Only languages with at least one non-empty override are present; within a language, only the
     * overridden fields are present.
     *
     * @var array<string, array{titleSingular?: string, titlePlural?: string, createTitle?: string}>
     */
    public array $labelTranslations = [];
}
