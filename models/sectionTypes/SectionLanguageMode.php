<?php

declare(strict_types=1);

namespace app\models\sectionTypes;

/**
 * Controls how IMotion::getSortedSections() deals with sections that are specific to a single
 * language, when a motion type defines several parallel sections (one per language).
 */
enum SectionLanguageMode
{
    // Only sections matching the reader's language (plus language-neutral ones) are returned,
    // falling back to whichever other languages actually have content if none does.
    case ReaderLanguage;

    // Every section is returned, regardless of its language. Used in admin contexts, where all
    // language versions are to be editable/visible at once.
    case AllLanguages;
}
