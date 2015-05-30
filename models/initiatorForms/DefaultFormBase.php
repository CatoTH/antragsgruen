<?php

namespace app\models\initiatorForms;

use app\controllers\Base;
use app\models\db\Amendment;
use app\models\db\AmendmentSupporter;
use app\models\db\Consultation;
use app\models\db\ConsultationMotionType;
use app\models\db\ISupporter;
use app\models\db\Motion;
use app\models\db\MotionSupporter;
use app\models\db\User;
use app\models\exceptions\FormError;
use app\models\forms\AmendmentEditForm;
use app\models\forms\MotionEditForm;
use yii\web\View;

abstract class DefaultFormBase extends IInitiatorForm
{
    /** @var Consultation $motionType $motionType */
    protected $motionType;

    /**
     * @param ConsultationMotionType $motionType
     */
    public function __construct(ConsultationMotionType $motionType)
    {
        $this->motionType = $motionType;
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
    public static function hasSupporters()
    {
        return false;
    }

    /**
     * @return int
     */
    public function getMinNumberOfSupporters()
    {
        return 0;
    }

    /**
     * @return bool
     */
    public function hasFullTextSupporterField()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function supportersHaveOrganizations()
    {
        return false;
    }

    /**
     * @param ISupporter $model
     * @return ISupporter[]
     */
    protected function parseSupporters(ISupporter $model)
    {
        $ret = [];
        if (isset($_POST["supporters"]) && is_array($_POST["supporters"]['name'])) {
            foreach ($_POST["supporters"]['name'] as $i => $name) {
                if (!$this->isValidName($name)) {
                    continue;
                }
                $sup             = clone $model;
                $sup->name       = trim($name);
                $sup->role       = ISupporter::ROLE_SUPPORTER;
                $sup->userId     = null;
                $sup->personType = ISupporter::PERSON_NATURAL;
                $sup->position   = $i;
                if (isset($_POST["supporters"]['organization']) && isset($_POST["supporters"]['organization'][$i])) {
                    $sup->organization = trim($_POST["supporters"]['organization'][$i]);
                }
                $ret[] = $sup;
            }
        }
        return $ret;
    }


    /**
     * @throws FormError
     */
    public function validateMotion()
    {
        if (!isset($_POST['Initiator'])) {
            throw new FormError('No Initiator data given');
        }

        $initiator = $_POST['Initiator'];
        $required = ConsultationMotionType::CONTACT_REQUIRED;

        $errors = [];

        if (!isset($initiator['name']) || !$this->isValidName($initiator['name'])) {
            $errors[] = 'No valid name entered.';
        }

        $checkEmail = ($this->motionType->contactEmail == $required || $initiator['contactEmail'] != '');
        if ($checkEmail && !filter_var($initiator['contactEmail'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'No valid e-mail-address given.';
        }

        $checkPhone = ($this->motionType->contactPhone == $required || $initiator['contactPhone'] != '');
        if ($checkPhone && empty($initiator['contactPhone'])) {
            $errors[] = 'No valid phone number given given.';
        }

        $types = array_keys(ISupporter::getPersonTypes());
        if (!isset($initiator['personType']) || !in_array($initiator['personType'], $types)) {
            $errors[] = 'Invalid person type.';
        }
        if ($initiator['personType'] == ISupporter::PERSON_ORGANIZATION) {
            if (empty($initiator['organization'])) {
                $errors[] = 'No organization entered.';
            }
            if (empty($initiator['resolutionDate'])) {
                $errors[] = 'No resolution date entered.';
            }
        }

        if ($this->hasSupporters()) {
            $supporters = $this->parseSupporters(new MotionSupporter());
            if ($this->hasSupporters() && count($supporters) < $this->getMinNumberOfSupporters()) {
                $errors[] = 'Not enough supporters.';
            }
        }

        if (count($errors) > 0) {
            throw new FormError($errors);
        }
    }

    /**
     * @throws FormError
     */
    public function validateAmendment()
    {
        $this->validateMotion();
    }

    /**
     * @param Motion $motion
     * @throws FormError
     */
    public function submitMotion(Motion $motion)
    {
        // Supporters
        foreach ($motion->motionSupporters as $supp) {
            if (in_array($supp->role, [MotionSupporter::ROLE_INITIATOR, MotionSupporter::ROLE_SUPPORTER])) {
                $supp->delete();
            }
        }

        $supporters = $this->getMotionSupporters($motion);
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
    public function submitAmendment(Amendment $amendment)
    {
        // Supporters
        foreach ($amendment->amendmentSupporters as $supp) {
            if (in_array($supp->role, [AmendmentSupporter::ROLE_INITIATOR, AmendmentSupporter::ROLE_SUPPORTER])) {
                $supp->delete();
            }
        }

        $supporters = $this->getAmendmentSupporters($amendment);
        foreach ($supporters as $sup) {
            /** @var AmendmentSupporter $sup */
            $sup->amendmentId = $amendment->id;
            $sup->save();
        }

    }


    /**
     * @param ConsultationMotionType $motionType
     * @param MotionEditForm $editForm
     * @param Base $controller
     * @return string
     */
    public function getMotionForm(ConsultationMotionType $motionType, MotionEditForm $editForm, Base $controller)
    {
        $view       = new View();
        $initiator  = null;
        $supporters = [];
        foreach ($editForm->supporters as $supporter) {
            if ($supporter->role == MotionSupporter::ROLE_INITIATOR) {
                $initiator = $supporter;
            }
            if ($supporter->role == MotionSupporter::ROLE_SUPPORTER) {
                $supporters[] = $supporter;
            }
        }
        $screeningPrivilege = User::currentUserHasPrivilege($motionType->consultation, User::PRIVILEGE_SCREENING);
        return $view->render(
            '@app/views/initiatorForms/default_form',
            [
                'motionType'        => $motionType,
                'initiator'         => $initiator,
                'supporters'        => $supporters,
                'allowOther'        => $screeningPrivilege,
                'hasSupporters'     => $this->hasSupporters(),
                'minSupporters'     => $this->getMinNumberOfSupporters(),
                'supporterFulltext' => $this->hasFullTextSupporterField(),
                'supporterOrga'     => $this->supportersHaveOrganizations(),
            ],
            $controller
        );
    }

    /**
     * @param ConsultationMotionType $motionType
     * @param AmendmentEditForm $editForm
     * @param Base $controller
     * @return string
     */
    public function getAmendmentForm(ConsultationMotionType $motionType, AmendmentEditForm $editForm, Base $controller)
    {
        $view       = new View();
        $initiator  = null;
        $supporters = [];
        foreach ($editForm->supporters as $supporter) {
            if ($supporter->role == AmendmentSupporter::ROLE_INITIATOR) {
                $initiator = $supporter;
            }
            if ($supporter->role == AmendmentSupporter::ROLE_INITIATOR) {
                $supporters[] = $supporter;
            }
        }
        $screeningPrivilege = User::currentUserHasPrivilege($motionType->consultation, User::PRIVILEGE_SCREENING);
        return $view->render(
            '@app/views/initiatorForms/default_form',
            [
                'motionType'        => $motionType,
                'initiator'         => $initiator,
                'supporters'        => $supporters,
                'allowOther'        => $screeningPrivilege,
                'hasSupporters'     => $this->hasSupporters(),
                'minSupporters'     => $this->getMinNumberOfSupporters(),
                'supporterFulltext' => $this->hasFullTextSupporterField(),
                'supporterOrga'     => $this->supportersHaveOrganizations(),
            ],
            $controller
        );
    }

    /**
     * @param Motion $motion
     * @return MotionSupporter[]
     */
    public function getMotionSupporters(Motion $motion)
    {
        /** @var MotionSupporter[] $return */
        $return = [];

        if (\Yii::$app->user->isGuest) {
            $init         = new MotionSupporter();
            $init->userId = null;
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
        $return[] = $init;

        $supporters = $this->parseSupporters(new MotionSupporter());
        foreach ($supporters as $sup) {
            /** @var MotionSupporter $sup */
            $sup->motionId = $motion->id;
            $return[]      = $sup;
        }

        return $return;
    }

    /**
     * @param Amendment $amendment
     * @return AmendmentSupporter[]
     */
    public function getAmendmentSupporters(Amendment $amendment)
    {
        /** @var AmendmentSupporter[] $return */
        $return = [];

        if (\Yii::$app->user->isGuest) {
            $init         = new AmendmentSupporter();
            $init->userId = null;
        } else {
            // @TODO

            /** @var \app\models\db\User $user */
            $user = \Yii::$app->user->identity;

            $init = AmendmentSupporter::findOne(
                [
                    'amendmentId' => $amendment->id,
                    'role'        => AmendmentSupporter::ROLE_INITIATOR,
                    'userId'      => $user->id,
                ]
            );
            if (!$init) {
                $init         = new AmendmentSupporter();
                $init->userId = $user->id;
            }
        }

        $init->setAttributes($_POST['Initiator']);
        $init->amendmentId = $amendment->id;
        $init->role        = AmendmentSupporter::ROLE_INITIATOR;
        $init->position    = 0;

        $dateRegexp = '/^(?<day>[0-9]{2})\. *(?<month>[0-9]{2})\. *(?<year>[0-9]{4})$/';
        if (preg_match($dateRegexp, $init->resolutionDate, $matches)) {
            $init->resolutionDate = $matches['year'] . '-' . $matches['month'] . '-' . $matches['day'];
        }
        $return[] = $init;

        $supporters = $this->parseSupporters(new MotionSupporter());
        foreach ($supporters as $sup) {
            /** @var AmendmentSupporter $sup */
            $sup->amendmentId = $amendment->id;
            $return[]         = $sup;
        }

        return $return;
    }
}
