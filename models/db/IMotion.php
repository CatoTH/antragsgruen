<?php

namespace app\models\db;

use yii\db\ActiveRecord;

abstract class IMotion extends ActiveRecord
{
    const STATUS_DELETED              = -2;
    const STATUS_WITHDRAWN            = -1;
    const STATUS_UNCONFIRMED          = 0;
    const STATUS_DRAFT                = 1;
    const STATUS_SUBMITTED_UNSCREENED = 2;
    const STATUS_SUBMITTED_SCREENED   = 3;
    const STATUS_ACCEPTED             = 4;
    const STATUS_DECLINED             = 5;
    const STATUS_MODIFIED_ACCEPTED    = 6;
    const STATUS_MODIFIED             = 7;
    const STATUS_ADOPTED              = 8;
    const STATUS_COMPLETED            = 9;
    const STATUS_REFERRED             = 10;
    const STATUS_VOTE                 = 11;
    const STATUS_PAUSED               = 12;
    const STATUS_MISSING_INFORMATION  = 13;
    const STATUS_DISMISSED            = 14;

    /**
     * @return string[]
     */
    public static function getStati()
    {
        return [
            static::STATUS_DELETED              => "Gelöscht",
            static::STATUS_WITHDRAWN            => "Zurückgezogen",
            static::STATUS_UNCONFIRMED          => "Unbestätigt",
            static::STATUS_DRAFT                => "Entwurf",
            static::STATUS_SUBMITTED_UNSCREENED => "Eingereicht (ungeprüft)",
            static::STATUS_SUBMITTED_SCREENED   => "Eingereicht",
            static::STATUS_ACCEPTED             => "Angenommen",
            static::STATUS_DECLINED             => "Abgelehnt",
            static::STATUS_MODIFIED_ACCEPTED    => "Modifizierte Übernahme",
            static::STATUS_MODIFIED             => "Modifziert",
            static::STATUS_ADOPTED              => "Übernahme",
            static::STATUS_COMPLETED            => "Erledigt",
            static::STATUS_REFERRED             => "Überweisung",
            static::STATUS_VOTE                 => "Abstimmung",
            static::STATUS_PAUSED               => "Pausiert",
            static::STATUS_MISSING_INFORMATION  => "Informationen fehlen",
            static::STATUS_DISMISSED            => "Nicht zugelassen",
        ];
    }

    /**
     * @return ISupporter[]
     */
    abstract public function getInitiators();

    /**
     * @return string
     */
    public function getInitiatorsStr()
    {
        $inits = $this->getInitiators();
        $str   = [];
        foreach ($inits as $init) {
            $str[] = $init->getNameWithResolutionDate(false);
        }
        return implode(', ', $str);
    }

    /**
     * @return ISupporter[]
     */
    abstract public function getSupporters();

    /**
     * @return ISupporter[]
     */
    abstract public function getLikes();

    /**
     * @return ISupporter[]
     */
    abstract public function getDislikes();
}
