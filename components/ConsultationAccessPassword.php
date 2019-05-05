<?php

namespace app\components;

use app\models\db\Consultation;
use app\models\db\Site;

class ConsultationAccessPassword
{
    /** @var Consultation */
    private $consultation;

    /** @var Site */
    private $site;

    /**
     * ConsultationAccessPassword constructor.
     * @param Consultation $consultation
     */
    public function __construct(Consultation $consultation)
    {
        $this->consultation = $consultation;
        $this->site         = $consultation->site;
    }

    public function isPasswordSet()
    {
        return ($this->consultation->getSettings()->accessPwd !== null);
    }

    public function allHaveSamePwd()
    {
        foreach ($this->site->consultations as $consultation) {
            if ($consultation->getSettings()->accessPwd !== $this->consultation->getSettings()->accessPwd) {
                return false;
            }
        }
        return true;
    }

    public function setPwdForOtherConsultations($pwd)
    {
        foreach ($this->site->consultations as $otherCon) {
            if ($otherCon->id !== $this->consultation->id) {
                $otherSett = $otherCon->getSettings();
                $otherSett->accessPwd = password_hash($pwd, PASSWORD_DEFAULT);
                $otherCon->setSettings($otherSett);
                $otherCon->save();
            }
        }
    }
}
