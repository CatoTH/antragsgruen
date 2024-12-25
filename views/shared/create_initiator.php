<?php

use app\components\Tools;
use app\models\db\{ConsultationMotionType, ISupporter};
use app\models\settings\{AntragsgruenApp, ConsultationUserOrganisation, InitiatorForm};
use app\models\supportTypes\SupportBase;
use yii\helpers\Html;

/**
 * @var Yii\web\View $this
 * @var ISupporter $initiator
 * @var ISupporter[] $moreInitiators
 * @var ISupporter[] $supporters
 * @var InitiatorForm $settings
 * @var bool $allowOther
 * @var bool $isForOther
 * @var bool $hasSupporters
 * @var bool $supporterFulltext
 * @var bool $adminMode
 * @var bool $isAmendment
 * @var ConsultationMotionType $motionType
 */

/** @var app\controllers\Base $controller */
$controller = $this->context;
$consultation = $controller->consultation;

$layout     = $controller->layoutParams;
$layout->loadDatepicker();

$locale = Tools::getCurrentDateLocale();
$selectOrganisations = [];
if ($consultation->getSettings()->organisations) {
    $sorted = $consultation->getSettings()->organisations;
    usort($sorted, fn(ConsultationUserOrganisation $orga1, ConsultationUserOrganisation $orga2): int => strnatcasecmp($orga1->name, $orga2->name));
    foreach ($sorted as $orga) {
        $selectOrganisations[$orga->name] = $orga->name;
    }
}

if ($initiator->personType == ISupporter::PERSON_ORGANIZATION) {
    $prePrimaryName = $initiator->organization;
} else {
    $prePrimaryName = $initiator->name;
}
$preContactName = $initiator->contactName;

$currentUser = \app\models\db\User::getCurrentUser();

$canInitiateAsPerson = $settings->canInitiateAsPerson($consultation);
$canInitiateAsOrganization = $settings->canInitiateAsOrganization($consultation);

echo '<fieldset class="supporterForm supporterFormStd" data-antragsgruen-widget="frontend/InitiatorForm"
                data-settings="' . Html::encode(json_encode($settings)) . '"
                data-organisation-list="' . (count($selectOrganisations) > 0 ? "1" : "0") . '"
                data-user-data="' . Html::encode(json_encode([
        'fixed_name'          => ($currentUser && ($currentUser->fixedData & \app\models\db\User::FIXED_NAME)),
        'fixed_orga'          => ($currentUser && ($currentUser->fixedData & \app\models\db\User::FIXED_ORGA)),
        'person_name'         => ($currentUser ? $currentUser->name : ''),
        'person_organization' => ($currentUser ? $currentUser->organization : ''),
    ])) . '">';

echo '<legend class="green">' . Yii::t('motion', 'initiators_head') . '</legend>';

echo '<div class="initiatorData content">';

if ($allowOther) {
    if ($adminMode) {
        echo '<input type="hidden" name="otherInitiator" value="1">';
    } else {
        echo '<div class="checkbox"><label><input type="checkbox" name="otherInitiator" ' .
             ($isForOther ? 'checked' : '') .
             '> ' . Yii::t('initiator', 'createForOther') .
             ' <small>(' . Yii::t('initiator', 'adminFunction') . ')</small>
    </label></div>';
    }
}

if ($canInitiateAsPerson && $canInitiateAsOrganization) {
    ?>
    <div class="personTypeSelector stdTwoCols">
        <div class="leftColumn"><?= Yii::t('initiator', 'iAmA') ?></div>
        <div class="rightColumn">
            <label class="radio-inline">
                <?php
                echo Html::radio(
                    'Initiator[personType]',
                    $initiator->personType === ISupporter::PERSON_NATURAL || $initiator->personType === null,
                    ['value' => ISupporter::PERSON_NATURAL, 'id' => 'personTypeNatural']
                );
                ?>
                <?= Yii::t('initiator', 'personNatural') ?>
            </label>
            <label class="radio-inline">
                <?php
                echo Html::radio(
                    'Initiator[personType]',
                    $initiator->personType === ISupporter::PERSON_ORGANIZATION,
                    ['value' => ISupporter::PERSON_ORGANIZATION, 'id' => 'personTypeOrga']
                );
                ?>
                <?= Yii::t('initiator', 'personOrganization') ?>
            </label>
        </div>
    </div>
    <?php
}

if (!$canInitiateAsPerson && !$canInitiateAsOrganization) {
    echo '<div class="alert alert-danger noProposerTypeFoundError"><p>' . Yii::t('motion', 'err_neither_person_orga') . '</p></div>';
}
if ($canInitiateAsPerson && !$canInitiateAsOrganization) {
    echo Html::hiddenInput('Initiator[personType]', ISupporter::PERSON_NATURAL, ['id' => 'personTypeHidden']);
}
if (!$canInitiateAsPerson && $canInitiateAsOrganization) {
    echo Html::hiddenInput('Initiator[personType]', ISupporter::PERSON_ORGANIZATION, ['id' => 'personTypeHidden']);
    $policy = $settings->getInitiatorPersonPolicy($consultation);
    if ($settings->initiatorCanBePerson && is_a($policy, \app\models\policies\UserGroups::class)) {
        // If submitting as person (delegate) is restricted by group, let's show an information for others.
        $allowedGroups = array_map(fn(\app\models\db\ConsultationUserGroup $group) => $group->getNormalizedTitle(), $policy->getAllowedUserGroups());
        $typeName = ($isAmendment ? Yii::t('amend', 'amendments') : $motionType->titlePlural);
        echo '<div class="alert alert-info noPersonInitiatorPossible"><p>';
        echo str_replace(["%GROUPS%", "%TYPE%"], [implode(", ", $allowedGroups), $typeName], Yii::t('motion', 'err_not_as_person_info'));
        echo '</p></div>';
    }
}

if ($adminMode) {
    ?>
    <div class="stdTwoCols initiatorCurrentUsername">
        <label class="leftColumn" for="initiatorName"><?= Yii::t('initiator', 'username') ?></label>
        <div class="middleColumn username">
            <?php
            if ($initiator->user) {
                echo Html::encode($initiator->user->getAuthName());
            }
            ?>
            <button class="btn-link btnEdit" type="button">
                <span class="glyphicon glyphicon-wrench" aria-hidden="true"></span>
                <span class="sr-only"><?= Yii::t('base', 'edit') ?></span>
            </button>
        </div>
    </div>
    <div class="stdTwoCols initiatorSetUsername hidden">
        <label class="leftColumn" for="initiatorName"><?= Yii::t('initiator', 'username') ?></label>
        <?php
        $loginTypes = [
            'email' => Yii::t('admin', 'siteacc_add_email') . ':',
        ];
        $logininit = 'email';
        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            if ($loginProvider = $plugin::getDedicatedLoginProvider()) {
                $loginTypes[$loginProvider->getId()] = $loginProvider->getName();
                if ($initiator->user && $loginProvider->userWasLoggedInWithProvider($initiator->user)) {
                    $logininit = $loginProvider->getId();
                }
            }
        }
        ?>
        <div class="middleColumn admin-type">
            <input type="hidden" name="initiatorSet" value="">
            <?= Html::dropDownList('initiatorSetType', $logininit, $loginTypes, ['class' => 'stdDropdown']) ?>
        </div>
        <div class="rightColumn">
            <input type="text" name="initiatorSetUsername" id="initiatorSetUsername" class="form-control"
                   value="<?= Html::encode($initiator->user ? $initiator->user->getAuthUsername() : '') ?>"
                   title="<?= Html::encode(Yii::t('admin', 'siteacc_add_name_title')) ?>">
        </div>
    </div>
    <?php
}

?>
    <div class="stdTwoCols">
        <label class="leftColumn" for="initiatorPrimaryName">
            <span class="only-person"><?= Yii::t('initiator', 'name') ?></span>
            <span class="only-organization"><?= Yii::t('initiator', 'nameOrga') ?></span>
        </label>
        <div class="middleColumn">
            <input type="text" class="form-control" id="initiatorPrimaryName" name="Initiator[primaryName]"
                   value="<?= Html::encode($prePrimaryName ?: '') ?>" autocomplete="name" required>
            <?php
            if (count($selectOrganisations) > 0) {
                $selectOrganisations = array_merge(['' => ''], $selectOrganisations);
                echo HTML::dropDownList('Initiator[primaryOrgaName]', $prePrimaryName, $selectOrganisations, [
                    'id' => 'initiatorPrimaryOrgaName',
                    'class' => 'stdDropdown',
                ], true);
            }
            ?>
        </div>
    </div>
<?php

if ($settings->hasOrganizations && $canInitiateAsPerson) {
    $preOrga = $initiator->organization;
    ?>
    <div class="stdTwoCols organizationRow">
        <label class="leftColumn" for="initiatorOrga">
            <?= Yii::t('initiator', 'orgaName') ?>
        </label>
        <div class="middleColumn">
            <?php
            if (count($selectOrganisations) > 0) {
                echo Html::dropDownList('Initiator[organization]', $preOrga, $selectOrganisations, [
                    'id' => 'initiatorOrga',
                    'class' => 'stdDropdown',
                ]);
            } else {
                ?>
                <input type="text" class="form-control" id="initiatorOrga" name="Initiator[organization]"
                   value="<?= Html::encode($preOrga ?: '') ?>">
                <?php

            }
            ?>
        </div>
    </div>
    <?php
}

if ($settings->hasResolutionDate !== InitiatorForm::CONTACT_NONE && $canInitiateAsOrganization) {
    $preResolution = Tools::dateSql2bootstrapdate($initiator->resolutionDate);
    ?>
    <div class="stdTwoCols resolutionRow">
        <label class="leftColumn control-label" for="resolutionDate">
            <?= Yii::t('initiator', 'orgaResolution') ?>
        </label>
        <div class="middleColumn">
            <div class="input-group date" id="resolutionDateHolder">
                <input type="text" class="form-control" id="resolutionDate" name="Initiator[resolutionDate]"
                       value="<?= Html::encode($preResolution) ?>" data-locale="<?= Html::encode($locale) ?>">
                <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
            </div>
        </div>
    </div>
    <?php
}

if ($settings->contactGender !== InitiatorForm::CONTACT_NONE && $canInitiateAsPerson) {
    $genderChoices = array_merge(['' => ''], SupportBase::getGenderSelection());
    ?>
    <div class="stdTwoCols genderRow">
        <label class="leftColumn" for="initiatorGender"><?= Yii::t('initiator', 'gender') ?></label>
        <div class="middleColumn">
            <?php
            echo Html::dropDownList('Initiator[gender]', $initiator->getExtraDataEntry(ISupporter::EXTRA_DATA_FIELD_GENDER), $genderChoices, [
                'id' => 'initiatorGender',
                'class' => 'stdDropdown',
            ]);
            ?>
        </div>
    </div>
    <?php
}

?>
    <div class="contactHead">
        <h3><?= Yii::t('initiator', 'contactHead') ?></h3>
        <div class="hint">(<?= Yii::t('initiator', 'visibilityAdmins') ?>)</div>
    </div>

    <div class="stdTwoCols contactNameRow">
        <label class="leftColumn" for="initiatorContactName">
            <?= Yii::t('initiator', 'orgaContactName') ?>
        </label>
        <div class="middleColumn">
            <input type="text" class="form-control" id="initiatorContactName" name="Initiator[contactName]"
                   value="<?= Html::encode($preContactName ?: '') ?>" autocomplete="name">
        </div>
    </div>

<?php
if ($settings->contactEmail !== InitiatorForm::CONTACT_NONE) {
    $preEmail = $initiator->contactEmail;
    ?>
    <div class="stdTwoCols emailRow">
        <label class="leftColumn control-label" for="initiatorEmail"><?= Yii::t('initiator', 'email') ?></label>
        <div class="middleColumn">
            <input type="text" class="form-control" id="initiatorEmail" name="Initiator[contactEmail]"
                <?php
                if ($settings->contactEmail === InitiatorForm::CONTACT_REQUIRED && !$adminMode) {
                    echo 'required ';
                }
                ?> autocomplete="email" value="<?= Html::encode($preEmail ?: '') ?>">
        </div>
    </div>
    <?php
}


if ($settings->contactPhone !== InitiatorForm::CONTACT_NONE) {
    $prePhone = $initiator->contactPhone;
    ?>
    <div class="stdTwoCols phoneRow">
        <label class="leftColumn" for="initiatorPhone"><?= Yii::t('initiator', 'phone') ?></label>
        <div class="middleColumn">
            <input type="text" class="form-control" id="initiatorPhone" name="Initiator[contactPhone]"
                <?php
                if ($settings->contactPhone === InitiatorForm::CONTACT_REQUIRED && !$adminMode) {
                    echo 'required ';
                }
                ?> autocomplete="tel" value="<?= Html::encode($prePhone ?: '') ?>">
        </div>
    </div>
    <?php
}


$getInitiatorRow = function (ISupporter $initiator, InitiatorForm $settings) {
    $str = '<div class="initiatorRow stdTwoCols">';
    $str .= '<div class="leftColumn">' . Yii::t('initiator', 'moreInitiators') . '</div>';
    $str .= '<div class="rightColumn"><div class="nameCol">';
    $str .= Html::textInput(
        'moreInitiators[name][]',
        $initiator->name,
        ['class' => 'form-control name', 'placeholder' => Yii::t('initiator', 'name')]
    );
    $str .= '</div>';
    if ($settings->hasOrganizations) {
        $str .= '<div class="orgaCal">';
        $str .= Html::textInput(
            'moreInitiators[organization][]',
            $initiator->organization,
            ['class' => 'form-control organization', 'placeholder' => Yii::t('initiator', 'orgaName')]
        );
        $str .= '</div>';
    }
    $str .= '<div class="delRow"><button type="button" class="btn btn-link rowDeleter" title="' . Html::encode(Yii::t('initiator', 'removeInitiator')) . '">';
    $str .= '<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>';
    $str .= '<span class="sr-only">' . Yii::t('initiator', 'removeInitiator') . '</span>';
    $str .= '</button></div>';

    $str .= '</div></div>';

    return $str;
};


foreach ($moreInitiators as $init) {
    echo $getInitiatorRow($init, $settings);
}

if ($adminMode) {
    ?>
    <div class="moreInitiatorsAdder">
        <button type="button" class="btn btn-link adderBtn">
            <span class="glyphicon glyphicon-plus btnAdd" aria-hidden="true"></span>
            <?= Yii::t('initiator', 'addInitiator') ?>
        </button>
    </div>
    <?php

    $new    = new \app\models\db\MotionSupporter();
    $newStr = $getInitiatorRow($new, $settings);
    echo '<div id="newInitiatorTemplate" style="display: none;" data-html="' . Html::encode($newStr) . '"></div>';
}


echo '</div>';


if ($hasSupporters && !$adminMode) {
    $getSupporterRow = function (ISupporter $supporter, InitiatorForm $settings) {
        $str = '<div class="supporterRow">';
        $str .= '<div class="nameCol">';
        $str .= Html::textInput(
            'supporters[name][]',
            $supporter->name,
            ['class' => 'form-control name', 'placeholder' => Yii::t('initiator', 'name')]
        );
        $str .= '</div>';
        if ($settings->hasOrganizations) {
            $str .= '<div class="orgaCal">';
            $str .= Html::textInput(
                'supporters[organization][]',
                $supporter->organization,
                ['class' => 'form-control organization', 'placeholder' => Yii::t('initiator', 'orgaName')]
            );
            $str .= '</div>';
        }
        if ($settings->allowMoreSupporters) {
            $str .= '<div class="delCol"><button type="button" class="btn btn-link rowDeleter" title="' . Html::encode(Yii::t('initiator', 'removeSupporter')) . '">';
            $str .= '<span class="glyphicon glyphicon-minus-sign" aria-hidden="true"></span>';
            $str .= '<span class="sr-only">' . Yii::t('initiator', 'removeSupporter') . '</span>';
            $str .= '</button></div>';
        }

        $str .= '</div>';

        return $str;
    };

    while (count($supporters) < $settings->minSupporters || count($supporters) < 3) {
        $supp         = new \app\models\db\MotionSupporter();
        $supporters[] = $supp;
    }
    echo '<h2 class="green supporterDataHead">' . Yii::t('initiator', 'supportersHead') . '</h2>';
    echo '<div class="supporterData form-horizontal content" ';
    echo 'data-min-supporters="' . Html::encode($settings->minSupporters) . '">';

    echo '<div class="stdTwoCols"><div class="leftColumn">';
    if ($settings->allowMoreSupporters) {
        if ($settings->minSupporters > 1) {
            echo str_replace('%min%', $settings->minSupporters, Yii::t('initiator', 'minSupportersX'));
        } elseif ($settings->minSupporters == 1) {
            echo str_replace('%min%', $settings->minSupporters, Yii::t('initiator', 'minSupporters1'));
        } else {
            echo Yii::t('initiator', 'supporters');
        }
    } else {
        echo Yii::t('initiator', 'supporters');
    }
    echo '</div>';

    echo '<div class="rightColumn">';
    foreach ($supporters as $supporter) {
        echo $getSupporterRow($supporter, $settings);
    }

    if ($settings->allowMoreSupporters) {
        echo '<div class="adderRow"><button type="button" class="btn btn-link adderLink">';
        echo '<span class="glyphicon glyphicon-plus" aria-hidden="true"></span> ';
        echo Yii::t('initiator', 'addSupporter');
        echo '</button></div>';
    }

    if ($supporterFulltext) {
        $fullTextSyntax = Yii::t('initiator', 'fullTextSyntax');
        ?>
        <div class="fullTextAdder"><button type="button" class="btn btn-link"><?= Yii::t('initiator', 'fullTextField') ?></button></div>
        <div class="hidden" id="supporterFullTextHolder">
            <div class="textHolder">
                <textarea class="form-control" placeholder="<?= Html::encode($fullTextSyntax) ?>" rows="10"
                          title="<?= Html::encode(Yii::t('initiator', 'fullTextField')) ?>"></textarea>
            </div>
            <div class="btnHolder">
                <button type="button" class="btn btn-success fullTextAdd">
                    <span class="glyphicon glyphicon-plus" aria-hidden="true"></span> <?= Yii::t('initiator', 'fullTextAdd') ?>
                </button>
            </div>
        </div>
        <?php
    }

    echo '</div>';
    echo '</div>';

    $new    = new \app\models\db\MotionSupporter();
    $newStr = $getSupporterRow($new, $settings);
    echo '<div id="newSupporterTemplate" style="display: none;" data-html="' . Html::encode($newStr) . '"></div>';

    echo '</div>';
}


echo '</fieldset>';
