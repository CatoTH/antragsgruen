<?php

use app\components\{HTMLTools, Tools};
use app\models\db\ISupporter;
use app\models\settings\InitiatorForm;
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
 */

/** @var app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$layout->loadDatepicker();
$locale        = Tools::getCurrentDateLocale();
$selectOrganisations = [];
if ($controller->consultation->getSettings()->organisations) {
    $sorted = $controller->consultation->getSettings()->organisations;
    usort($sorted, 'strnatcasecmp');
    foreach ($sorted as $name) {
        $selectOrganisations[$name] = $name;
    }
}

if ($initiator->personType == ISupporter::PERSON_ORGANIZATION) {
    $prePrimaryName = $initiator->organization;
} else {
    $prePrimaryName = $initiator->name;
}
$preContactName = $initiator->contactName;

$currentUser = \app\models\db\User::getCurrentUser();

echo '<fieldset class="supporterForm supporterFormStd" data-antragsgruen-widget="frontend/InitiatorForm"
                data-settings="' . Html::encode(json_encode($settings)) . '"
                data-organisation-list="' . (count($selectOrganisations) > 0 ? "1" : "0") . '"
                data-user-data="' . Html::encode(json_encode([
        'fixed'               => ($currentUser && $currentUser->fixedData),
        'person_name'         => ($currentUser ? $currentUser->name : ''),
        'person_organization' => ($currentUser ? $currentUser->organization : ''),
    ])) . '">';

echo '<legend class="green">' . Yii::t('motion', 'initiators_head') . '</legend>';

echo '<div class="initiatorData form-horizontal content">';

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

if ($settings->initiatorCanBePerson && $settings->initiatorCanBeOrganization) {
    ?>
    <fieldset class="form-group personTypeSelector">
        <legend class="col-sm-3 control-label"><?= Yii::t('initiator', 'iAmA') ?></legend>
        <div class="col-sm-9">
            <label class="radio-inline">
                <?php
                echo Html::radio(
                    'Initiator[personType]',
                    $initiator->personType == ISupporter::PERSON_NATURAL,
                    ['value' => ISupporter::PERSON_NATURAL, 'id' => 'personTypeNatural']
                );
                ?>
                <?= Yii::t('initiator', 'personNatural') ?>
            </label>
            <label class="radio-inline">
                <?php
                echo Html::radio(
                    'Initiator[personType]',
                    $initiator->personType == ISupporter::PERSON_ORGANIZATION,
                    ['value' => ISupporter::PERSON_ORGANIZATION, 'id' => 'personTypeOrga']
                );
                ?>
                <?= Yii::t('initiator', 'personOrganization') ?>
            </label>
        </div>
    </fieldset>
    <?php
}
if ($settings->initiatorCanBePerson && !$settings->initiatorCanBeOrganization) {
    echo Html::hiddenInput('Initiator[personType]', ISupporter::PERSON_NATURAL, ['id' => 'personTypeHidden']);
}
if (!$settings->initiatorCanBePerson && $settings->initiatorCanBeOrganization) {
    echo Html::hiddenInput('Initiator[personType]', ISupporter::PERSON_ORGANIZATION, ['id' => 'personTypeHidden']);
}

if ($adminMode) {
    ?>
    <div class="form-group initiatorCurrentUsername">
        <label class="col-sm-3 control-label" for="initiatorName"><?= Yii::t('initiator', 'username') ?></label>
        <div class="col-sm-6 username">
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
    <div class="form-group initiatorSetUsername hidden">
        <label class="col-sm-3 control-label" for="initiatorName"><?= Yii::t('initiator', 'username') ?></label>
        <?php
        $loginTypes = [
            'email' => Yii::t('admin', 'siteacc_add_email') . ':',
        ];
        if ($controller->getParams()->isSamlActive()) {
            $loginTypes['gruenesnetz'] = Yii::t('admin', 'siteacc_add_ww') . ':';
        }
        if ($initiator->user && $initiator->user->getAuthType() === \app\models\settings\Site::LOGIN_GRUENES_NETZ) {
            $logininit = 'gruenesnetz';
        } else {
            $logininit = 'email';
        }
        ?>
        <div class="col-md-3 admin-type">
            <input type="hidden" name="initiatorSet" value="">
            <?= HTMLTools::fueluxSelectbox('initiatorSetType', $loginTypes, $logininit) ?>
        </div>
        <div class="col-md-4">
            <input type="text" name="initiatorSetUsername" id="initiatorSetUsername" class="form-control"
                   value="<?= Html::encode($initiator->user ? $initiator->user->getAuthUsername() : '') ?>"
                   title="<?= Html::encode(Yii::t('admin', 'siteacc_add_name_title')) ?>"
                   placeholder="<?= Html::encode(Yii::t('admin', 'siteacc_add_name_place')) ?>">
        </div>
    </div>
    <?php
}

?>
    <div class="form-group">
        <label class="col-sm-3 control-label" for="initiatorPrimaryName">
            <span class="only-person"><?= Yii::t('initiator', 'name') ?></span>
            <span class="only-organization"><?= Yii::t('initiator', 'nameOrga') ?></span>
        </label>
        <div class="col-sm-4 fuelux">
            <input type="text" class="form-control" id="initiatorPrimaryName" name="Initiator[primaryName]"
                   value="<?= Html::encode($prePrimaryName) ?>" autocomplete="name" required>
            <?php
            if (count($selectOrganisations) > 0) {
                $selectOrganisations = array_merge(['' => ''], $selectOrganisations);
                echo HTMLTools::fueluxSelectbox('Initiator[primaryOrgaName]', $selectOrganisations, $prePrimaryName, [
                    'id' => 'initiatorPrimaryOrgaName',
                ], true);
            }
            ?>
        </div>
    </div>
<?php

if ($settings->hasOrganizations && $settings->initiatorCanBePerson) {
    $preOrga = $initiator->organization;
    ?>
    <div class="form-group organizationRow fuelux">
        <label class="col-sm-3 control-label" for="initiatorOrga">
            <?= Yii::t('initiator', 'orgaName') ?>
        </label>
        <div class="col-sm-4">
            <?php
            if (count($selectOrganisations) > 0) {
                echo HTMLTools::fueluxSelectbox('Initiator[organization]', $selectOrganisations, $preOrga, [
                    'id' => 'initiatorOrga',
                ], true);
            } else {
                ?>
                <input type="text" class="form-control" id="initiatorOrga" name="Initiator[organization]"
                   value="<?= Html::encode($preOrga) ?>">
                <?php

            }
            ?>
        </div>
    </div>
    <?php
}

if ($settings->hasResolutionDate !== InitiatorForm::CONTACT_NONE && $settings->initiatorCanBeOrganization) {
    $preResolution = Tools::dateSql2bootstrapdate($initiator->resolutionDate);
    ?>
    <div class="form-group resolutionRow">
        <label class="col-sm-3 control-label" for="resolutionDate">
            <?= Yii::t('initiator', 'orgaResolution') ?>
        </label>
        <div class="col-sm-4">
            <div class="input-group date" id="resolutionDateHolder">
                <input type="text" class="form-control" id="resolutionDate" name="Initiator[resolutionDate]"
                       value="<?= Html::encode($preResolution) ?>" data-locale="<?= Html::encode($locale) ?>">
                <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
            </div>
        </div>
    </div>
    <?php
}

if ($settings->contactGender !== InitiatorForm::CONTACT_NONE && $settings->initiatorCanBePerson) {
    $layout->loadFuelux();
    $genderChoices = array_merge(['' => ''], SupportBase::getGenderSelection());
    ?>
    <div class="form-group genderRow fuelux">
        <label class="col-sm-3 control-label" for="initiatorGender"><?= Yii::t('initiator', 'gender') ?></label>
        <div class="col-sm-4">
            <?php
            echo HTMLTools::fueluxSelectbox(
                'Initiator[gender]',
                $genderChoices,
                $initiator->getExtraDataEntry(ISupporter::EXTRA_DATA_FIELD_GENDER),
                ['id' => 'initiatorGender'],
                true
            );
            ?>
        </div>
    </div>
    <?php
}

?>
    <div class="form-group row contact-head">
        <div class="col-sm-9 col-sm-offset-3 contact-head">
            <h3><?= Yii::t('initiator', 'contactHead') ?></h3>
            <div class="hint">(<?= Yii::t('initiator', 'visibilityAdmins') ?>)</div>
        </div>
    </div>

    <div class="form-group contactNameRow">
        <label class="col-sm-3 control-label" for="initiatorContactName">
            <?= Yii::t('initiator', 'orgaContactName') ?>
        </label>
        <div class="col-sm-4">
            <input type="text" class="form-control" id="initiatorContactName" name="Initiator[contactName]"
                   value="<?= Html::encode($preContactName) ?>" autocomplete="name">
        </div>
    </div>

<?php
if ($settings->contactEmail !== InitiatorForm::CONTACT_NONE) {
    $preEmail = $initiator->contactEmail;
    ?>
    <div class="form-group emailRow">
        <label class="col-sm-3 control-label" for="initiatorEmail"><?= Yii::t('initiator', 'email') ?></label>
        <div class="col-sm-4">
            <input type="text" class="form-control" id="initiatorEmail" name="Initiator[contactEmail]"
                <?php
                if ($settings->contactEmail === InitiatorForm::CONTACT_REQUIRED && !$adminMode) {
                    echo 'required ';
                }
                ?> autocomplete="email" value="<?= Html::encode($preEmail) ?>">
        </div>
    </div>
    <?php
}


if ($settings->contactPhone !== InitiatorForm::CONTACT_NONE) {
    $prePhone = $initiator->contactPhone;
    ?>
    <div class="form-group phoneRow">
        <label class="col-sm-3 control-label" for="initiatorPhone"><?= Yii::t('initiator', 'phone') ?></label>
        <div class="col-sm-4">
            <input type="text" class="form-control" id="initiatorPhone" name="Initiator[contactPhone]"
                <?php
                if ($settings->contactPhone === InitiatorForm::CONTACT_REQUIRED && !$adminMode) {
                    echo 'required ';
                }
                ?> autocomplete="tel" value="<?= Html::encode($prePhone) ?>">
        </div>
    </div>
    <?php
}


$getInitiatorRow = function (ISupporter $initiator, InitiatorForm $settings) {
    $str = '<div class="form-group initiatorRow">';
    $str .= '<div class="col-sm-3 control-label">' . Yii::t('initiator', 'moreInitiators') . '</div>';
    $str .= '<div class="col-md-4">';
    $str .= Html::textInput(
        'moreInitiators[name][]',
        $initiator->name,
        ['class' => 'form-control name', 'placeholder' => Yii::t('initiator', 'name')]
    );
    $str .= '</div>';
    if ($settings->hasOrganizations) {
        $str .= '<div class="col-md-4">';
        $str .= Html::textInput(
            'moreInitiators[organization][]',
            $initiator->organization,
            ['class' => 'form-control organization', 'placeholder' => Yii::t('initiator', 'orgaName')]
        );
        $str .= '</div>';
    }
    $str .= '<div class="col-md-1"><a href="#" class="rowDeleter" tabindex="-1">';
    $str .= '<span class="glyphicon glyphicon-minus-sign"></span>';
    $str .= '</a></div>';

    $str .= '</div>';

    return $str;
};


foreach ($moreInitiators as $init) {
    echo $getInitiatorRow($init, $settings);
}


$new    = new \app\models\db\MotionSupporter();
$newStr = $getInitiatorRow($new, $settings);
echo '<div id="newInitiatorTemplate" style="display: none;" data-html="' . Html::encode($newStr) . '"></div>';


echo '</div>';


if ($hasSupporters && !$adminMode) {
    $getSupporterRow = function (ISupporter $supporter, InitiatorForm $settings) {
        $str = '<div class="form-group supporterRow">';
        $str .= '<div class="col-md-6">';
        $str .= Html::textInput(
            'supporters[name][]',
            $supporter->name,
            ['class' => 'form-control name', 'placeholder' => Yii::t('initiator', 'name')]
        );
        $str .= '</div>';
        if ($settings->hasOrganizations) {
            $str .= '<div class="col-md-5">';
            $str .= Html::textInput(
                'supporters[organization][]',
                $supporter->organization,
                ['class' => 'form-control organization', 'placeholder' => Yii::t('initiator', 'orgaName')]
            );
            $str .= '</div>';
        }
        if ($settings->allowMoreSupporters) {
            $str .= '<div class="col-md-1"><a href="#" class="rowDeleter" tabindex="-1">';
            $str .= '<span class="glyphicon glyphicon-minus-sign"></span>';
            $str .= '</a></div>';
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

    echo '<div class="form-group"><div class="col-md-3">';
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

    echo '<div class="col-md-9">';
    foreach ($supporters as $supporter) {
        echo $getSupporterRow($supporter, $settings);
    }

    if ($settings->allowMoreSupporters) {
        echo '<div class="adderRow"><a href="#"><span class="glyphicon glyphicon-plus"></span> ';
        echo Yii::t('initiator', 'addSupporter');
        echo '</a></div>';
    }

    if ($supporterFulltext) {
        $fullTextSyntax = Yii::t('initiator', 'fullTextSyntax');
        ?>
        <div class="fullTextAdder"><a href="#"><?= Yii::t('initiator', 'fullTextField') ?></a></div>
        <div class="form-group hidden" id="fullTextHolder">
            <div class="col-md-9">
                <textarea class="form-control" placeholder="<?= Html::encode($fullTextSyntax) ?>" rows="10"
                          title="<?= Html::encode(Yii::t('initiator', 'fullTextField')) ?>"></textarea>
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-success fullTextAdd">
                    <span class="glyphicon glyphicon-plus"></span> <?= Yii::t('initiator', 'fullTextAdd') ?>
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
