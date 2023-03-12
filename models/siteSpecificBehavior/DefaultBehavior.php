<?php /** @noinspection PhpUnusedParameterInspection */

namespace app\models\siteSpecificBehavior;

use app\components\MotionSorter;
use app\models\amendmentNumbering\IAmendmentNumbering;
use app\models\db\Consultation;
use app\models\http\ResponseInterface;
use app\models\policies\IPolicy;

class DefaultBehavior
{
    /**
     * @return string[]|IPolicy[]
     */
    public static function getCustomPolicies(): array
    {
        return [];
    }

    /**
     * @return string[]|IAmendmentNumbering[]
     */
    public static function getCustomAmendmentNumberings(): array
    {
        return [];
    }

    public static function hasSiteHomePage(): bool
    {
        return false;
    }

    public static function getSiteHomePage(): ?ResponseInterface
    {
        return null;
    }

    public static function getConsultationHomePage(Consultation $consultation): ?ResponseInterface
    {
        return null;
    }

    public static function preferConsultationSpecificHomeLink(): bool
    {
        return false;
    }

    public static function siteHomeIsAlwaysPublic(): bool
    {
        return false;
    }
}
