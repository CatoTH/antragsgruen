<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\models\motionTypeTemplates\Motion as MotionTemplate;
use Codeception\Attribute\Group;
use app\models\db\{Consultation, ConsultationMotionType};
use Tests\Support\Helper\DBTestBase;

/**
 * Covers the per-language translation of ConsultationMotionType::$titleSingular/$titlePlural/
 * $createTitle (stored in MotionType::$labelTranslations, see multilanguage-implementation.md §20):
 * getTitleSingularForDisplay()/getTitlePluralForDisplay()/getCreateTitleForDisplay() resolve to the
 * DB column itself for the consultation's primary language (or when no override is defined), and to
 * the stored override otherwise. DB-backed for the same reason as MotionTitleForDisplayTest -
 * Consultation 1's wordingBase makes German the primary language.
 */
#[Group('database')]
class ConsultationMotionTypeLabelTranslationTest extends DBTestBase
{
    private static function createMotionType(): ConsultationMotionType
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne(1);
        $motionType   = MotionTemplate::doCreateMotionType($consultation);

        $motionType->titleSingular = 'Antrag';
        $motionType->titlePlural   = 'Anträge';
        $motionType->createTitle   = 'Antrag stellen';

        return $motionType;
    }

    public function testDisplayLabelsAreTheMainLanguageColumnsForThePrimaryLanguage(): void
    {
        $motionType = self::createMotionType();

        $this->assertSame('Antrag', $motionType->getTitleSingularForDisplay('de'));
        $this->assertSame('Anträge', $motionType->getTitlePluralForDisplay('de'));
        $this->assertSame('Antrag stellen', $motionType->getCreateTitleForDisplay('de'));
    }

    public function testDisplayLabelsFallBackToTheMainLanguageColumnsWithoutATranslation(): void
    {
        $motionType = self::createMotionType();

        $this->assertSame('Antrag', $motionType->getTitleSingularForDisplay('en'));
        $this->assertSame('Anträge', $motionType->getTitlePluralForDisplay('en'));
        $this->assertSame('Antrag stellen', $motionType->getCreateTitleForDisplay('en'));
    }

    public function testDisplayLabelsUseTheTranslationWhenOneIsDefined(): void
    {
        $motionType = self::createMotionType();
        $motionType->setLabelTranslations([
            'en' => ['titleSingular' => 'Motion', 'titlePlural' => 'Motions'],
        ]);

        $this->assertSame('Motion', $motionType->getTitleSingularForDisplay('en'));
        $this->assertSame('Motions', $motionType->getTitlePluralForDisplay('en'));
        // createTitle has no override for "en" - falls back to the main-language column, same as if
        // "en" had no entry in labelTranslations at all.
        $this->assertSame('Antrag stellen', $motionType->getCreateTitleForDisplay('en'));
    }

    public function testTheMainLanguageIsNeverOverriddenEvenIfATranslationExistsForIt(): void
    {
        // Should not normally happen (the admin form never offers the primary language as a
        // translation target), but a stray/leftover entry must not shadow the DB columns, which are
        // always the source of truth for the primary language.
        $motionType = self::createMotionType();
        $motionType->setLabelTranslations([
            'de' => ['titleSingular' => 'Stray override'],
        ]);

        $this->assertSame('Antrag', $motionType->getTitleSingularForDisplay('de'));
    }

    public function testGetLabelTranslationReturnsTheRawOverrideWithoutFallingBack(): void
    {
        // Used to prefill the admin translation form: an empty result must mean "no override", not
        // silently repeat the main-language value as if it were one.
        $motionType = self::createMotionType();

        $this->assertSame('', $motionType->getLabelTranslation('en', 'titleSingular'));

        $motionType->setLabelTranslations(['en' => ['titleSingular' => 'Motion']]);

        $this->assertSame('Motion', $motionType->getLabelTranslation('en', 'titleSingular'));
        $this->assertSame('', $motionType->getLabelTranslation('en', 'titlePlural'));
    }

    public function testLabelTranslationsSurviveASaveAndReload(): void
    {
        $motionType = self::createMotionType();
        $motionType->setLabelTranslations([
            'en' => ['titleSingular' => 'Motion', 'titlePlural' => 'Motions', 'createTitle' => 'Create a motion'],
        ]);
        $motionType->save();

        $reloaded = ConsultationMotionType::findOne($motionType->id);
        $this->assertNotNull($reloaded);
        $this->assertSame('Motion', $reloaded->getTitleSingularForDisplay('en'));
        $this->assertSame('Motions', $reloaded->getTitlePluralForDisplay('en'));
        $this->assertSame('Create a motion', $reloaded->getCreateTitleForDisplay('en'));
    }
}
