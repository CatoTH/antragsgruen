<?php

namespace app\models\initiatorForms;

use app\controllers\Base;
use app\models\db\Amendment;
use app\models\db\AmendmentSupporter;
use app\models\db\Consultation;
use app\models\db\ISupporter;
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
     * @return bool
     */
    protected function hasSupporters()
    {
        return false;
    }

    /**
     * @return int
     */
    protected function getMinNumberOfSupporters()
    {
        return 0;
    }

    /**
     * @return bool
     */
    protected function hasFullTextSupporterField()
    {
        return false;
    }

    /**
     * @return bool
     */
    protected function supportersHaveOrganizations()
    {
        return false;
    }

    /**
     * @param ISupporter $model
     * @return ISupporter[]
     */
    protected function parseSupportersFromFulltext(ISupporter $model)
    {
        // @TODO
        return [];
    }

    /**
     * @param ISupporter $model
     * @return ISupporter[]
     */
    protected function parseSupportersFromStdField(ISupporter $model)
    {
        $ret = [];
        foreach ($_REQUEST["SupporterName"] as $i => $name) {
            if (!$this->isValidName($name)) {
                continue;
            }
            $sup             = clone $model;
            $sup->name       = trim($name);
            $sup->role       = ISupporter::ROLE_SUPPORTER;
            $sup->userId     = null;
            $sup->personType = ISupporter::PERSON_NATURAL;
            $sup->position   = $i;
            if (isset($_REQUEST["SupporterOrganization"]) && isset($_REQUEST["SupporterOrganization"][$i])) {
                $sup->organization = $_REQUEST["SupporterOrganization"][$i];
            }
            $ret[] = $sup;
        }
        return $ret;
    }

    /**
     * @param ISupporter $model
     * @return ISupporter[]
     */
    protected function parseSupporters(ISupporter $model)
    {
        if (isset($_POST['SupporterFulltext'])) {
            return $this->parseSupportersFromFulltext($model);
        } elseif (isset($_REQUEST["SupporterName"]) && is_array($_REQUEST["SupporterName"])) {
            return $this->parseSupportersFromStdField($model);
        } else {
            return [];
        }
    }


    /**
     * @throws FormError
     */
    public function validateInitiatorViewMotion()
    {
        if (!isset($_POST['Initiator'])) {
            throw new FormError("No Initiator data given");
        }

        $initiator = $_POST['Initiator'];
        $settings  = $this->consultation->getSettings();

        if (!isset($initiator['name'])) {
            throw new FormError("No Initiator name data given");
        }
        if ($settings->motionNeedsEmail && !filter_var($initiator['contactEmail'], FILTER_VALIDATE_EMAIL)) {
            throw new FormError("No valid e-mail-address given");
        }
        if ($settings->motionNeedsPhone && empty($initiator['contactPhone'])) {
            throw new FormError("No valid phone number given given");
        }
        $types = array_keys(ISupporter::getPersonTypes());
        if (!isset($initiator['personType']) || !in_array($initiator['personType'], $types)) {
            throw new FormError("Invalid person type");
        }
        if ($initiator['personType'] == ISupporter::PERSON_ORGANIZATION) {
            if (empty($initiator['organization'])) {
                throw new FormError("No organization entered");
            }
            if (empty($initiator['resolutionDate'])) {
                throw new FormError("No resolution date entered");
            }
        }

        if ($this->hasSupporters()) {
            $supporters = $this->parseSupporters(new MotionSupporter());
            if ($this->hasSupporters() && count($supporters) < $this->getMinNumberOfSupporters()) {
                throw new FormError("Not enough supporters");
            }
        }
    }

    /**
     * @throws FormError
     */
    public function validateInitiatorViewAmendment()
    {
        $this->validateInitiatorViewMotion();
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
                $init         = new MotionSupporter();
                $init->userId = $user->id;
            }
        }

        $init->setAttributes($_POST['Initiator']);
        $init->motionId = $motion->id;
        $init->role     = MotionSupporter::ROLE_INITIATOR;
        $init->position = 0;

        $dateRegexp = '/^(?<day>[0-9]{2})\. *(?<month>[0-9]{2})\. *(?<year>[0-9]{4})$/';
        if (preg_match($dateRegexp, $init->resolutionDate, $matches)) {
            $init->resolutionDate = $matches['year'] . '-' . $matches['month'] . '-' . $matches['day'];
        }

        $init->save();

        $supporters = $this->parseSupporters(new MotionSupporter());
        foreach ($supporters as $sup) {
            /** @var MotionSupporter $sup */
            $sup->motionId = $motion->id;
            $sup->save();
        }

    }


    /**
     * @param Amendment $amendment
     * @throws FormError
     */
    public function submitInitiatorViewAmendment(Amendment $amendment)
    {
        // @TODO
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

        $supporters = $this->parseSupporters(new AmendmentSupporter());
        foreach ($supporters as $sup) {
            /** @var AmendmentSupporter $sup */
            $sup->amendmentId = $amendment->id;
            $sup->save();
        }

    }


    /**
     * @param Consultation $consultation
     * @param MotionEditForm $editForm
     * @param Base $controller
     * @return string
     */
    public function getMotionInitiatorForm(Consultation $consultation, MotionEditForm $editForm, Base $controller)
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
                'hasSupporters'     => $this->hasSupporters(),
                'minSupporters'     => $this->getMinNumberOfSupporters(),
                'supporterFulltext' => $this->hasFullTextSupporterField(),
                'supporterOrga'     => $this->supportersHaveOrganizations(),
            ],
            $controller
        );

    }
}
