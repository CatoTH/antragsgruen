<?php

use app\components\Tools;
use app\models\db\ConsultationMotionType;
use app\models\db\ISupporter;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var ConsultationMotionType $motionType
 * @var ISupporter $initiator
 * @var ISupporter[] $moreInitiators
 * @var ISupporter[] $supporters
 * @var bool $allowOther
 * @var bool $isForOther
 * @var bool $hasSupporters
 * @var bool $minSupporters
 * @var bool $allowMoreSupporters
 * @var bool $supporterFulltext
 * @var bool $hasOrganizations
 * @var bool $adminMode
 */

/** @var app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$layout->loadDatepicker();
$locale = Tools::getCurrentDateLocale();

if ($initiator->personType == ISupporter::PERSON_NATURAL) {
    $prePrimaryName = Html::encode($initiator->name);
} else {
    $prePrimaryName = Html::encode($initiator->organization);
}
$preOrga        = Html::encode($initiator->organization);
$preContactName = Html::encode($initiator->contactName);
$preEmail       = Html::encode($initiator->contactEmail);
$prePhone       = Html::encode($initiator->contactPhone);
$preResolution  = Tools::dateSql2bootstrapdate($initiator->resolutionDate);

$currentUser = \app\models\db\User::getCurrentUser();

echo '<fieldset class="supporterForm supporterFormStd" data-antragsgruen-widget="frontend/DefaultInitiatorForm"
                data-contact-name="' . IntVal($motionType->contactName) . '"
                data-user-data="' . Html::encode(json_encode([
        'fixed'               => ($currentUser && $currentUser->fixedData),
        'person_name'         => ($currentUser ? $currentUser->name : ''),
        'person_organization' => ($currentUser ? $currentUser->organization : ''),
    ])) . '">';

echo '<legend class="green">' . \Yii::t('motion', 'initiators_head') . '</legend>';

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

echo '<div class="form-group">
<label class="col-sm-3 control-label">' . Yii::t('initiator', 'iAmA') . '</label>
<div class="col-sm-9">
<label class="radio-inline">';
echo Html::radio(
    'Initiator[personType]',
    $initiator->personType == ISupporter::PERSON_NATURAL,
    [
        'value' => ISupporter::PERSON_NATURAL,
        'id'    => 'personTypeNatural',
    ]
);
echo ' ' . Yii::t('initiator', 'personNatural') . '
</label>
<label class="radio-inline">';
echo Html::radio(
    'Initiator[personType]',
    $initiator->personType == ISupporter::PERSON_ORGANIZATION,
    [
        'value' => ISupporter::PERSON_ORGANIZATION,
        'id'    => 'personTypeOrga',
    ]
);

echo ' ' . Yii::t('initiator', 'personOrganization') . '
</label>
</div>
</div>';

if ($adminMode) {
    echo '<div class="form-group">
  <label class="col-sm-3 control-label" for="initiatorName">' . Yii::t('initiator', 'username') . '</label>
  <div class="col-sm-4">';
    if ($initiator->user) {
        echo Html::encode($initiator->user->getAuthName());
    }
    echo '</div>
</div>';
}

echo '<div class="form-group">
  <label class="col-sm-3 control-label" for="initiatorPrimaryName">';

echo '<span class="only-person">' . Yii::t('initiator', 'name') . '</span>';
echo '<span class="only-organization">' . Yii::t('initiator', 'nameOrga') . '</span>';

echo '</label>
  <div class="col-sm-4">
    <input type="text" class="form-control" id="initiatorPrimaryName" name="Initiator[primaryName]"
        value="' . $prePrimaryName . '" autocomplete="name" required>
  </div>
</div>';

if ($hasOrganizations) {
    echo '<div class="form-group organizationRow">
  <label class="col-sm-3 control-label" for="initiatorOrga">' . Yii::t('initiator', 'orgaName') . '</label>
  <div class="col-sm-4">
    <input type="text" class="form-control" id="initiatorOrga" name="Initiator[organization]" value="' . $preOrga . '">
  </div>
</div>';
}

echo '<div class="form-group resolutionRow">
  <label class="col-sm-3 control-label" for="resolutionDate">' . Yii::t('initiator', 'orgaResolution') . '</label>
  <div class="col-sm-4"><div class="input-group date" id="resolutionDateHolder">
    <input type="text" class="form-control" id="resolutionDate" name="Initiator[resolutionDate]"
        value="' . Html::encode($preResolution) . '" data-locale="' . Html::encode($locale) . '">';
echo '<span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>';
echo '</div></div>
</div>';


echo '<div class="form-group row contact-head"><div class="col-sm-9 col-sm-offset-3 contact-head">' .
    '<h3>' . \Yii::t('initiator', 'contactHead') . '</h3>' .
    '<div class="hint">(' . \Yii::t('initiator', 'visibilityAdmins') . ')</div></div></div>';

echo '<div class="form-group contactNameRow">
  <label class="col-sm-3 control-label" for="initiatorContactName">' . Yii::t('initiator', 'orgaContactName') .
    '</label>
  <div class="col-sm-4">
    <input type="text" class="form-control" id="initiatorContactName" name="Initiator[contactName]"
    value="' . $preContactName . '" autocomplete="name">
  </div>
</div>';

if ($motionType->contactEmail != ConsultationMotionType::CONTACT_NONE) {
    echo '<div class="form-group">
  <label class="col-sm-3 control-label" for="initiatorEmail">' . Yii::t('initiator', 'email') . '</label>
  <div class="col-sm-4">
    <input type="text" class="form-control" id="initiatorEmail" name="Initiator[contactEmail]" ';
    if ($motionType->contactEmail == ConsultationMotionType::CONTACT_REQUIRED && !$adminMode) {
        echo 'required ';
    }

    echo ' autocomplete="email" value="' . Html::encode($preEmail) . '">
  </div>
</div>';
}


if ($motionType->contactPhone != ConsultationMotionType::CONTACT_NONE) {
    echo '<div class="form-group phone_row">
        <label class="col-sm-3 control-label" for="initiatorPhone">' . Yii::t('initiator', 'phone') . '</label>
  <div class="col-sm-4">
    <input type="text" class="form-control" id="initiatorPhone" name="Initiator[contactPhone]" ';
    if ($motionType->contactPhone == ConsultationMotionType::CONTACT_REQUIRED && !$adminMode) {
        echo 'required ';
    }
    echo 'autocomplete="tel" value="' . Html::encode($prePhone) . '">
  </div>
</div>';
}


$getInitiatorRow = function (ISupporter $initiator, $initiatorOrga) {
    $str = '<div class="form-group initiatorRow">';
    $str .= '<div class="col-sm-3 control-label">' . Yii::t('initiator', 'moreInitiators') . '</div>';
    $str .= '<div class="col-md-4">';
    $str .= Html::textInput(
        'moreInitiators[name][]',
        $initiator->name,
        ['class' => 'form-control name', 'placeholder' => Yii::t('initiator', 'name')]
    );
    $str .= '</div>';
    if ($initiatorOrga) {
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
    echo $getInitiatorRow($init, $hasOrganizations);
}

/*
echo '<div class="adderRow row"><div class="col-sm-3"></div><div class="col-md-9">';
echo '<a href="#"><span class="glyphicon glyphicon-plus"></span> ';
echo Yii::t('initiator', 'addInitiator');
echo '</a></div></div>';
*/

$new    = new \app\models\db\MotionSupporter();
$newStr = $getInitiatorRow($new, $hasOrganizations);
echo '<div id="newInitiatorTemplate" style="display: none;" data-html="' . Html::encode($newStr) . '"></div>';


echo '</div>';


if ($hasSupporters && !$adminMode) {
    $getSupporterRow = function (ISupporter $supporter, $hasOrganizations, $allowMoreSupporters) {
        $str = '<div class="form-group supporterRow">';
        $str .= '<div class="col-md-6">';
        $str .= Html::textInput(
            'supporters[name][]',
            $supporter->name,
            ['class' => 'form-control name', 'placeholder' => Yii::t('initiator', 'name')]
        );
        $str .= '</div>';
        if ($hasOrganizations) {
            $str .= '<div class="col-md-5">';
            $str .= Html::textInput(
                'supporters[organization][]',
                $supporter->organization,
                ['class' => 'form-control organization', 'placeholder' => Yii::t('initiator', 'orgaName')]
            );
            $str .= '</div>';
        }
        if ($allowMoreSupporters) {
            $str .= '<div class="col-md-1"><a href="#" class="rowDeleter" tabindex="-1">';
            $str .= '<span class="glyphicon glyphicon-minus-sign"></span>';
            $str .= '</a></div>';
        }

        $str .= '</div>';
        return $str;
    };

    while (count($supporters) < $minSupporters || count($supporters) < 3) {
        $supp         = new \app\models\db\MotionSupporter();
        $supporters[] = $supp;
    }
    echo '<h2 class="green supporterDataHead">' . Yii::t('initiator', 'supportersHead') . '</h2>';
    echo '<div class="supporterData form-horizontal content" ';
    echo 'data-min-supporters="' . Html::encode($minSupporters) . '">';

    echo '<div class="form-group"><div class="col-md-3">';
    if ($allowMoreSupporters) {
        if ($minSupporters > 1) {
            echo str_replace('%min%', $minSupporters, Yii::t('initiator', 'minSupportersX'));
        } elseif ($minSupporters == 1) {
            echo str_replace('%min%', $minSupporters, Yii::t('initiator', 'minSupporters1'));
        } else {
            echo Yii::t('initiator', 'supporters');
        }
    } else {
        echo Yii::t('initiator', 'supporters');
    }
    echo '</div>';

    echo '<div class="col-md-9">';
    foreach ($supporters as $supporter) {
        echo $getSupporterRow($supporter, $hasOrganizations, $allowMoreSupporters);
    }

    if ($allowMoreSupporters) {
        echo '<div class="adderRow"><a href="#"><span class="glyphicon glyphicon-plus"></span> ';
        echo \Yii::t('initiator', 'addSupporter');
        echo '</a></div>';
    }

    if ($supporterFulltext) {
        $fullTextSyntax = Yii::t('initiator', 'fullTextSyntax');
        echo '<div class="fullTextAdder"><a href="#">' . Yii::t('initiator', 'fullTextField') . '</a></div>';
        echo '<div class="form-group hidden" id="fullTextHolder">';
        echo '<div class="col-md-9">';
        echo '<textarea class="form-control" placeholder="' . Html::encode($fullTextSyntax) . '" rows="10"></textarea>';
        echo '</div><div class="col-md-3">';
        echo '<button type="button" class="btn btn-success fullTextAdd">';
        echo '<span class="glyphicon glyphicon-plus"></span> ' . Yii::t('initiator', 'fullTextAdd') . '</button>';
        echo '</div>';
        echo '</div>';
    }

    echo '</div>';
    echo '</div>';

    $new    = new \app\models\db\MotionSupporter();
    $newStr = $getSupporterRow($new, $hasOrganizations, $allowMoreSupporters);
    echo '<div id="newSupporterTemplate" style="display: none;" data-html="' . Html::encode($newStr) . '"></div>';

    echo '</div>';
}


echo '</fieldset>';
