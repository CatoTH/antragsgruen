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
    const STATUS_ADPTED               = 8;
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
            -2 => "Gelöscht",
            -1 => "Zurückgezogen",
            0  => "Unbestätigt", // Noch nicht bestätigt
            1  => "Entwurf",
            2  => "Eingereicht (ungeprüft)",
            3  => "Eingereicht",
            4  => "Angenommen",
            5  => "Abgelehnt",
            6  => "Modifizierte Übernahme",
            7  => "Modifziert",
            8  => "Übernahme",
            9  => "Erledigt",
            10 => "Überweisung",
            11 => "Abstimmung",
            12 => "Pausiert",
            13 => "Informationen fehlen",
            14 => "Nicht zugelassen",
        ];
    }

    /**
     * @return int[]
     */
    public static function getInvisibleStati()
    {
        return [0, 2, -2];
    }

    /**
     * @return User[]
     */
    abstract public function getInitiators();

    /**
     * @return User[]
     */
    abstract public function getSupporters();

    /**
     * @return User[]
     */
    abstract public function getLikes();

    /**
     * @return User[]
     */
    abstract public function getDislikes();
}
