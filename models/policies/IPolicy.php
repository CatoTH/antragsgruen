<?php

namespace app\models\policies;

use app\models\db\Amendment;
use app\models\db\AmendmentSupporter;
use app\models\db\Consultation;
use app\models\db\Motion;
use app\models\db\MotionSupporter;
use app\models\db\User;
use app\models\exceptions\FormError;
use app\models\exceptions\Internal;
use yii\helpers\Html;

abstract class IPolicy
{
    const POLICY_HESSEN_LMV = "HeLMV";
    const POLICY_BAYERN_LDK = "ByLDK";
    const POLICY_BDK        = "BDK";
    const POLICY_ADMINS     = "Admins";
    const POLICY_ALL        = "All";
    const POLICY_LOGGED_IN  = "LoggedIn";

    /**
     * @return string[]
     */
    public static function getPolicies()
    {
        // @TODO
        return [
            "BDK"         => "PolicyAntraegeBDK",
            "ByLDK"       => "PolicyAntraegeByLDK",
            "HeLMB"       => "PolicyAntraegeHeLMV",
            "Admins"      => "PolicyAntraegeAdmins",
            "Alle"        => "PolicyAntraegeAlle",
            "Eingeloggte" => "PolicyAntraegeEingeloggte",
        ];
    }

    /** @var Consultation */
    protected $consultation;

    /**
     * @param Consultation $consultation
     */
    public function __construct(Consultation $consultation)
    {
        $this->consultation = $consultation;
    }


    /**
     * @static
     * @abstract
     * @return string
     */
    public static function getPolicyID()
    {
        return "";
    }

    /**
     * @static
     * @abstract
     * @return string
     */
    public static function getPolicyName()
    {
        return "";
    }


    /**
     * @static
     * @abstract
     * @return bool
     */
    abstract public function checkCurUserHeuristically();

    /**
     * @abstract
     * @return string
     */
    abstract public function getOnCreateDescription();

    /**
     * @return bool
     */
    public function checkHeuristicallyAssumeLoggedIn()
    {
        return $this->checkCurUserHeuristically();
    }


    /**
     * @return string
     */
    public function getInitiatorView()
    {
        return "initiatorStd";
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isValidName($name)
    {
        return (trim($name) != "");
    }

    /**
     * @param bool $allowOtherInitiators
     * @return User|null
     */
    protected function getSubmitPerson($allowOtherInitiators)
    {
        if (\Yii::$app->user->isGuest) {
            $initiator = null;
        } elseif ($allowOtherInitiators && isset($_REQUEST["otherInitiator"])) {
            $initiator = null;
        } else {
            /** @var User $initiator */
            $initiator = User::findOne(["auth" => \Yii::$app->user->id]);
            if ($initiator) {
                $nameChanged = (isset($_REQUEST["User"]["name"]) && $initiator->name !== $_REQUEST["User"]["name"]);
                if ($nameChanged) {
                    $initiator->name = $_REQUEST["User"]["name"];
                    if (isset($_REQUEST["User"]["organisation"])) {
                        // $antragstellerIn->organisation = $_REQUEST["Person"]["organisation"];
                        // @TODO
                    }
                    $initiator->save();
                }
            }
        }

        if (isset($_REQUEST["User"])) {
            if ($initiator === null) {
                $initiator = new User();
                $initiator->setAttributes($_REQUEST["User"]);
                //$initiator->telefon        =
                //(isset($_REQUEST["Person"]["telefon"]) ? $_REQUEST["Person"]["telefon"] : "");
                //$initiator->typ            =
                //(isset($_REQUEST["Person"]["typ"]) && $_REQUEST["Person"]["typ"] ==
                //"organisation" ? Person::$TYP_ORGANISATION : Person::$TYP_PERSON);
                $initiator->dateCreation = date("Y-m-d H:i:s");
                $initiator->status       = User::STATUS_UNCONFIRMED;
                $initiator->save();
            } else {
                /*
                if (!$antragstellerIn->telefon && isset($_REQUEST["Person"]["telefon"])
                && $_REQUEST["Person"]["telefon"] != "") {
                    $antragstellerIn->telefon = $_REQUEST["Person"]["telefon"];
                    $antragstellerIn->save();
                }
                @TODO
                */
            }
        }

        return $initiator;
    }


    /**
     * @return bool
     */
    public function checkMotionSubmit()
    {
        if (isset($_REQUEST["Person"])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param Motion $motion
     * @throws FormError
     */
    public function submitInitiatorViewMotion(Motion $motion)
    {
        $initiator = $this->getSubmitPerson($motion->consultation->isAdminCurUser());
        if ($initiator === null) {
            throw new FormError("Keine AntragstellerIn gefunden");
        }

        $initiatorIn_pre = MotionSupporter::findAll(
            [
                "antrag_id"          => $motion->id,
                "rolle"              => MotionSupporter::ROLE_INITIATOR,
                "unterstuetzerIn_id" => $initiator->id
            ]
        );
        if (count($initiatorIn_pre) == 0) {
            $init = new MotionSupporter();
        } else {
            $init = $initiatorIn_pre[0];
        }

        $init->motionId = $motion->id;
        $init->role     = MotionSupporter::ROLE_INITIATOR;
        $init->userId   = $initiator->id;
        $init->position = 0;
        if (isset($_REQUEST["OrganizationResolutionDate"]) && $_REQUEST["OrganizationResolutionDate"] != "") {
            $regexp = "/^(?<day>[0-9]{2})\. *(?<month>[0-9]{2})\. *(?<year>[0-9]{4})$/";
            if (preg_match($regexp, $_REQUEST["OrganizationResolutionDate"], $matches)) {
                $init->resolutionDate = $matches["year"] . "-" . $matches["month"] . "-" . $matches["day"];
            }
        }
        $init->save();

        if (isset($_REQUEST["SupporterFulltext"]) && trim($_REQUEST["SupporterFulltext"]) != "") {
            $user               = new User;
            $user->name         = trim($_REQUEST["SupporterFulltext"]);
            $user->status       = User::STATUS_UNCONFIRMED;
            $user->dateCreation = date("Y-m-d H:i:s");
            //$person->organisation   = "";
            if ($user->save()) {
                $unt           = new MotionSupporter();
                $unt->motionId = $motion->id;
                $unt->userId   = $user->id;
                $unt->role     = MotionSupporter::ROLE_SUPPORTER;
                $unt->position = 0;
                $unt->save();
            }
        } elseif (isset($_REQUEST["SupporterName"]) && is_array($_REQUEST["SupporterName"])) {
            foreach ($_REQUEST["SupporterName"] as $i => $name) {
                if (!$this->isValidName($name)) {
                    continue;
                }

                $name               = trim($name);
                $user               = new User;
                $user->name         = $name;
                $user->status       = User::STATUS_UNCONFIRMED;
                $user->dateCreation = date("Y-m-d H:i:s");
                /*
                if (isset($_REQUEST["SupporterOrganization"]) && isset($_REQUEST["SupporterOrganization"][$i])) {
                    $person->organisation = $_REQUEST["SupporterOrganization"][$i];
                }
                */
                if ($user->save()) {
                    $unt           = new MotionSupporter();
                    $unt->motionId = $motion->id;
                    $unt->userId   = $user->id;
                    $unt->role     = MotionSupporter::ROLE_SUPPORTER;
                    $unt->position = $i;
                    $unt->save();
                }
            }
        }
    }


    /**
     * @return bool
     */
    public function checkAmendmentSubmit()
    {
        if (isset($_REQUEST["Person"])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param Amendment $amendment
     * @throws FormError
     */
    public function submitInitiatorViewAmendment(Amendment $amendment)
    {
        $initiator = $this->getSubmitPerson($amendment->motion->consultation->isAdminCurUser());
        if ($initiator === null) {
            throw new FormError("Keine AntragstellerIn gefunden");
        }

        $init              = new AmendmentSupporter();
        $init->amendmentId = $amendment->id;
        $init->role        = AmendmentSupporter::ROLE_INITIATOR;
        $init->userId      = $initiator->id;
        $init->position    = 0;
        if (isset($_REQUEST["OrganizationResolutionDate"]) && $_REQUEST["OrganizationResolutionDate"] != "") {
            $regexp = "/^(?<day>[0-9]{2})\. *(?<month>[0-9]{2})\. *(?<year>[0-9]{4})$/";
            if (preg_match($regexp, $_REQUEST["OrganizationResolutionDate"], $matches)) {
                $init->resolutionDate = $matches["year"] . "-" . $matches["month"] . "-" . $matches["day"];
            }
        }
        $init->save();

        if (isset($_REQUEST["SupporterFulltext"]) && trim($_REQUEST["SupporterFulltext"]) != "") {
            $user               = new User;
            $user->name         = trim($_REQUEST["UnterstuetzerInnen_fulltext"]);
            $user->status       = User::STATUS_UNCONFIRMED;
            $user->dateCreation = date("Y-m-d H:i:s");
            if ($user->save()) {
                $unt              = new AmendmentSupporter();
                $unt->amendmentId = $amendment->id;
                $unt->userId      = $user->id;
                $unt->role        = AmendmentSupporter::ROLE_SUPPORTER;
                $unt->position    = 0;
                $unt->save();
            }
        } elseif (isset($_REQUEST["SupporterName"]) && is_array($_REQUEST["SupporterName"])) {
            foreach ($_REQUEST["SupporterName"] as $i => $name) {
                $name = trim($name);
                if (!$this->isValidName($name)) {
                    continue;
                }

                $user               = new User;
                $user->name         = $name;
                $user->status       = User::STATUS_UNCONFIRMED;
                $user->dateCreation = date("Y-m-d H:i:s");
                /*
                if (isset($_REQUEST["UnterstuetzerInnen_orga"]) && isset($_REQUEST["UnterstuetzerInnen_orga"][$i])) {
                    $person->organisation = $_REQUEST["UnterstuetzerInnen_orga"][$i];
                }
                */
                if ($user->save()) {
                    $unt              = new AmendmentSupporter();
                    $unt->amendmentId = $amendment->id;
                    $unt->userId      = $user->id;
                    $unt->role        = AmendmentSupporter::ROLE_SUPPORTER;
                    $unt->position    = $i;
                    $unt->save();
                }
            }
        }
    }

    /**
     * @param Consultation $consultation
     * @param User $initiator
     * @param string $label_name
     * @param string $label_organisation
     * @return string
     */
    public function getAntragsstellerInStdForm(
        Consultation $consultation,
        User $initiator,
        $label_name = "Name",
        $label_organisation = "Gremium, LAG..."
    ) {
        $str      = '';
        $settings = $consultation->getSettings();

        if ($consultation->isAdminCurUser()) {
            $str .= '<label><input type="checkbox" name="andere_antragstellerIn"> ' .
                'Ich lege diesen Antrag für eine andere AntragstellerIn an
                <small>(Admin-Funktion)</small>
            </label>';
        }

        $str .= '<div class="antragstellerIn_daten">
			<div class="control-group name_row"><label class="control-label" for="Person_name">' . $label_name . '</label>
				<div class="controls name_row"><input name="User[name]" id="Person_name" type="text" maxlength="100" value="';
        if ($initiator) {
            $str .= Html::encode($initiator->name);
        }
        $str .= '"></div>
			</div>

			<div class="control-group organisation_row">
			<label class="control-label" for="Person_organisation">' . $label_organisation . '</label>
			<div class="controls organisation_row">
			<input name="User[organisation]" id="Person_organisation" type="text" maxlength="100" value="';
        /*
        if ($initiator) {
            $str .= Html::encode($initiator->organisation);
        }
        */
        $str .= '"></div>
			</div>

			<div class="control-group email_row"><label class="control-label" for="Person_email">E-Mail</label>
				<div class="controls email_row"><input';
        if ($settings->motionNeedsEmail) {
            $str .= ' required';
        }
        $str .= ' name="User[email]" id="Person_email" type="text" maxlength="200" value="';
        if ($initiator) {
            $str .= Html::encode($initiator->email);
        }
        $str .= '"></div>
			</div>';

        if ($settings->motionHasPhone) {
            $str .= '<div class="control-group telefon_row">
                <label class="control-label" for="Person_telefon">Telefon</label>
				<div class="controls telefon_row"><input';
            if ($settings->motionNeedsPhone) {
                $str .= ' required';
            }
            $str .= ' name="User[telefon]" id="Person_telefon" type="text" maxlength="100" value="';
            /*
            if ($initiator) {
                $str .= Html::encode(Us->telefon);
            }
            */
            $str .= '"></div>
			</div>';
        }
        $str .= '</div>';

        return $str;
    }


    /**
     * @abstract
     * @return string
     */
    abstract public function getPermissionDeniedMsg();


    /**
     * @static
     * @param string $policyId
     * @param Consultation $consultation
     * @throws Internal
     * @return IPolicy
     */
    public static function getInstanceByID($policyId, Consultation $consultation)
    {
        /** @var IPolicy $polClass */
        foreach (static::getPolicies() as $polId => $polClass) {
            if ($polId == $policyId) {
                return new $polClass($consultation);
            }
        }
        throw new Internal("Unbekannte Policy: " . $policyId);
    }


    /**
     * @static
     * @return array
     */
    public static function getAllInstances()
    {
        $arr = array();
        /** @var IPolicy $polClass */
        foreach (static::getPolicies() as $polId => $polClass) {
            $arr[$polId] = $polClass::getPolicyName();
        }
        return $arr;
    }
}
