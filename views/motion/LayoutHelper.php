<?php

namespace app\views\motion;

use app\models\db\Consultation;
use app\models\db\ISupporter;
use app\models\db\User;
use yii\helpers\Html;

class LayoutHelper
{
    /**
     * @param ISupporter[] $initiators
     * @param Consultation $consultation
     * @return string
     */
    public static function formatInitiators($initiators, Consultation $consultation)
    {
        $inits = [];
        foreach ($initiators as $supp) {
            $name = $supp->getNameWithResolutionDate(true);
            if ($supp->user && $supp->user->isWurzelwerkUser()) {
                $url = 'https://wurzelwerk.gruene.de/web/' . $supp->user->getWurzelwerkName();
                $name .= ' (<a href="' . Html::encode($url) . '">Wurzelwerk-Profil</a>)';
            }
            $admin = User::currentUserHasPrivilege($consultation, User::PRIVILEGE_SCREENING);
            if ($admin && ($supp->contactEmail != "" || $supp->contactPhone != "")) {
                $name .= " <small>(Kontaktdaten, nur als Admin sichtbar: ";
                if ($supp->contactEmail != "") {
                    $name .= "E-Mail: " . Html::encode($supp->contactEmail);
                }
                if ($supp->contactEmail != "" && $supp->contactPhone != "") {
                    $name .= ", ";
                }
                if ($supp->contactPhone != "") {
                    $name .= "Telefon: " . Html::encode($supp->contactPhone);
                }
                $name .= ")</small>";
            }
            $inits[] = $name;
        }
        return implode(", ", $inits);
    }
}
