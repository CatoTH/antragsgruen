<?php

declare(strict_types=1);

namespace app\models\supportTypes;

use app\models\db\IMotion;

/**
 * How far a motion / amendment in the "collecting supporters" phase is from reaching its quorum.
 * Same counting as the support collection hint on the motion page:
 * the general count excludes the initiators, the female count includes them.
 */
class SupportCollectionProgress
{
    public readonly int $missing;
    public readonly int $missingFemale;

    public function __construct(
        public readonly int $min,
        public readonly int $curr,
        public readonly ?int $minFemale,
        public readonly int $currFemale,
        public readonly bool $initiatedByOrganization = false,
    ) {
        $this->missing = max(0, $min - $curr);
        $this->missingFemale = ($minFemale ? max(0, $minFemale - $currFemale) : 0);
    }

    public static function fromIMotion(IMotion $imotion, SupportBase $supportType): self
    {
        $settings = $supportType->getSettingsObj();

        return new self(
            min: $settings->minSupporters,
            curr: count($imotion->getSupporters(true)),
            minFemale: $settings->minSupportersFemale,
            currFemale: $imotion->getSupporterCountByGender('female'),
            initiatedByOrganization: $imotion->isInitiatedByOrganization(),
        );
    }

    public function hasFemaleQuorum(): bool
    {
        return $this->minFemale > 0;
    }

    public function isReached(): bool
    {
        if ($this->initiatedByOrganization) {
            return true;
        }
        return $this->missing === 0 && $this->missingFemale === 0;
    }

    /**
     * The number of additional supporters needed at least: missing women can't be replaced by other supporters.
     */
    public function getNeeded(): int
    {
        if ($this->isReached()) {
            return 0;
        }
        return max($this->missing, $this->missingFemale);
    }

    /**
     * The key of the "still needed" text within the "con" category, chosen to get singular / plural right.
     */
    public function getNeededTextKey(): string
    {
        if ($this->isReached()) {
            return 'collecting_reached';
        }
        if ($this->missingFemale === 0) {
            return ($this->getNeeded() === 1 ? 'collecting_needed_1' : 'collecting_needed');
        }
        if ($this->getNeeded() === 1) {
            return 'collecting_needed_1_female';
        }
        return ($this->missingFemale === 1 ? 'collecting_needed_female_1' : 'collecting_needed_female');
    }

    public static function getPercentage(int $curr, int $min): int
    {
        if ($min <= 0) {
            return 100;
        }
        return (int)min(100, round($curr / $min * 100));
    }

    /**
     * Closest to the quorum first, entries that already reached it last.
     */
    public static function compare(self $progress1, self $progress2): int
    {
        if ($progress1->isReached() !== $progress2->isReached()) {
            return ($progress1->isReached() ? 1 : -1);
        }
        return $progress1->getNeeded() <=> $progress2->getNeeded();
    }
}
