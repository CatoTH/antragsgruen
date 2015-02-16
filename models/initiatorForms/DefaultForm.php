<?php

namespace app\models\initiatorForms;

use app\models\db\Amendment;
use app\models\db\AmendmentSupporter;
use app\models\db\Consultation;
use app\models\db\Motion;
use app\models\db\MotionSupporter;
use app\models\db\User;
use app\models\exceptions\FormError;
use app\models\forms\MotionEditForm;
use yii\web\View;

class DefaultForm implements IInitiatorView
{
    /** @var Consultation $consultation */
    protected $consultation;

    /**
     * @param Consultation $consultation
     */
    public function __construct(Consultation $consultation)
    {
        $this->consultation = $consultation;
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
     * @throws FormError
     */
    public function validateInitiatorViewMotion()
    {
        // @TODO
    }

    /**
     * @param Motion $motion
     * @throws FormError
     */
    public function submitInitiatorViewMotion(Motion $motion)
    {
        if (\Yii::$app->user->isGuest) {
            $init = new MotionSupporter();
        } else {
            // @TODO

            /** @var \app\models\db\User $user */
            $user = \Yii::$app->user->identity;

            $init = MotionSupporter::findOne(
                [
                    "motionId" => $motion->id,
                    "role"     => MotionSupporter::ROLE_INITIATOR,
                    "userId"   => $user->id,
                ]
            );
            if (!$init) {
                $init = new MotionSupporter();
                $init->userId = $user->id;
            }
        }

        $init->setAttributes($_POST['Initiator']);
        $init->motionId = $motion->id;
        $init->role     = MotionSupporter::ROLE_INITIATOR;
        $init->position = 0;
        /*
        if (isset($_REQUEST["OrganizationResolutionDate"]) && $_REQUEST["OrganizationResolutionDate"] != "") {
            $regexp = "/^(?<day>[0-9]{2})\. *(?<month>[0-9]{2})\. *(?<year>[0-9]{4})$/";
            if (preg_match($regexp, $_REQUEST["OrganizationResolutionDate"], $matches)) {
                $init->resolutionDate = $matches["year"] . "-" . $matches["month"] . "-" . $matches["day"];
            }
        }
        */
        var_dump($init->getAttributes());
        die();
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
     * @param MotionEditForm $editForm
     * @return string
     */
    public function getMotionInitiatorForm(Consultation $consultation, MotionEditForm $editForm)
    {
        $labelOrganization = 'Gremium, LAG...';
        $labelName         = 'Name';
        $view              = new View();
        $initiator         = null;
        foreach ($editForm->supporters as $supporter) {
            if ($supporter->role == MotionSupporter::ROLE_INITIATOR) {
                $initiator = $supporter;
            }
        }
        return $view->render(
            '@app/views/initiatorForms/std',
            [
                'consultation'      => $consultation,
                'initiator'         => $initiator,
                'labelName'         => $labelName,
                'labelOrganization' => $labelOrganization,
                'allowOther'        => $consultation->isAdminCurUser(),
            ]
        );

    }
}
