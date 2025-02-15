<?php

use app\components\UrlHelper;
use app\models\forms\{ConsultationCreateForm, SiteCreateForm};
use yii\helpers\{Html, Url};

/**
 * @var yii\web\View $this
 * @var \app\models\db\Site $site
 * @var \app\models\db\Consultation[] $consultations
 * @var ConsultationCreateForm $createForm
 * @var SiteCreateForm $wizardModel
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = 'Veranstaltungen verwalten';
$layout->addCSS('css/formwizard.css');
$layout->addCSS('css/manager.css');
$layout->addCSS('css/backend.css');
$layout->addAMDModule('backend/ConsultationCreate');
$layout->loadDatepicker();
$layout->addBreadcrumb(Yii::t('admin', 'bread_settings'), UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb(Yii::t('admin', 'cons_breadcrumb'));

$settings = $site->getSettings();

echo '<h1>' . Yii::t('admin', 'cons_title') . '</h1>';

echo $controller->showErrors();

echo Html::beginForm('', 'post', ['class' => 'consultationEditForm']);
echo '<h2 class="green">' . Yii::t('admin', 'cons_created_list') . '</h2>';
echo '<div class="content"><ul id="consultationsList">';
foreach ($consultations as $consultation) {
    $isStandard = ($consultation->id == $site->currentConsultationId);
    $params     = ['consultationPath' => $consultation->urlPath];
    if ($controller->getParams()->multisiteMode) {
        $params['subdomain'] = $site->subdomain;
    }

    echo '<li class="consultation' . $consultation->id . '">';

    echo '<div class="stdbox">';
    if ($isStandard) {
        echo '<strong><span class="glyphicon glyphicon-ok" style="color: green;" aria-hidden="true"></span> ' .
            Yii::t('admin', 'cons_std_con') . '</strong>';
    } else {
        echo '<button type="submit" name="setStandard[' . $consultation->id . ']" class="link">' .
            Yii::t('admin', 'cons_set_std') . '</button>';
    }
    echo '</div>';

    if (!$isStandard) {
        echo '<div class="delbox"><button type="submit" name="delete[' . $consultation->id . ']" class="link" title="' .
            Yii::t('admin', 'cons_delete_title') . '">';
        echo '<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>';
        echo '<span class="sr-only">' . Yii::t('admin', 'cons_delete_title') . '</span>';
        echo '</button></div>';
    }

    echo '<h3>';
    echo Html::encode($consultation->title) . ' <small>(' . Html::encode($consultation->titleShort) . ')</small>';
    echo '</h3>';

    echo '<div class="homeLink">';
    $url = Url::toRoute(array_merge(['consultation/index'], $params));
    echo '<a href="' . Html::encode($url) . '"><span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> ' .
        Yii::t('admin', 'cons_goto_site') . '</a>';
    echo '</div><div class="adminLink">';
    $url = Url::toRoute(array_merge(['admin/index'], $params));
    echo '<a href="' . Html::encode($url) . '"><span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> ' .
        Yii::t('admin', 'cons_goto_admin') . '</a>';
    echo '</div>';

    echo '</li>';
}
echo '</ul></div>';
echo Html::endForm();


echo Html::beginForm('', 'post', ['class' => 'consultationCreateForm']);

$textOpts = ['required' => 'required', 'class' => 'form-control'];
?>
<h2 class="green"><?= Yii::t('admin', 'cons_create') ?></h2>

<div class="content">

    <div class="stdTwoCols">
        <label for="newTitle" class="leftColumn control-label"><?= Yii::t('admin', 'cons_create_title') ?>:</label>
        <div class="rightColumn"><?php
            echo Html::input(
                'text',
                'newConsultation[title]',
                $createForm->title,
                array_merge($textOpts, ['id' => 'newTitle'])
            ); ?>
        </div>
    </div>

    <div class="stdTwoCols">
        <label for="newShort" class="leftColumn control-label"><?= Yii::t('admin', 'cons_create_short') ?>:</label>
        <div class="rightColumn"><?php
            echo Html::input(
                'text',
                'newConsultation[titleShort]',
                $createForm->titleShort,
                array_merge($textOpts, ['id' => 'newShort'])
            ); ?>
        </div>
    </div>

    <div class="stdTwoCols">
        <label for="newPath" class="leftColumn control-label"><?= Yii::t('admin', 'cons_create_url') ?>:</label>
        <div class="rightColumn fakeUrl">
            <?php
            $input = Html::input(
                'text',
                'newConsultation[urlPath]',
                $createForm->urlPath,
                array_merge($textOpts, ['id' => 'newPath', 'pattern' => '[\w_-]+'])
            );
            $routeParams = [
                'consultation/index',
                'consultationPath' => '--CON--'
            ];
            if ($controller->getParams()->multisiteMode) {
                $routeParams['subdomain'] = $site->subdomain;
            }
            $url   = UrlHelper::absolutizeLink(Url::toRoute($routeParams));
            echo str_replace('--CON--', $input, $url);
            ?>
        </div>
    </div>

    <div class="stdTwoCols">
        <label for="newSetStandard" class="leftColumn control-label">
            <?= Yii::t('admin', 'cons_create_std') ?>:
        </label>
        <div class="rightColumn">
            <label><?php
                echo Html::checkbox(
                    'newConsultation[setStandard]',
                    $createForm->setAsDefault,
                    ['id' => 'newSetStandard']
                ); ?>
                <?= Yii::t('admin', 'cons_create_std_do') ?>
            </label>
        </div>
    </div>

    <div class="stdTwoCols settingsType">
        <div class="leftColumn control-label"><?= Yii::t('admin', 'cons_create_settings') ?>:</div>
        <div class="rightColumn">
            <label class="settingsTypeLabel">
                <input type="radio" name="newConsultation[settingsType]" id="settingsTypeWizard" required
                       value="<?= ConsultationCreateForm::SETTINGS_TYPE_WIZARD ?>">
                <?= Yii::t('admin', 'cons_create_wizard') ?>
            </label>
            <label class="settingsTypeLabel">
                <input type="radio" name="newConsultation[settingsType]" id="settingsTypeTemplate" required
                       value="<?= ConsultationCreateForm::SETTINGS_TYPE_TEMPLATE ?>" checked>
                <?= Yii::t('admin', 'cons_create_template') ?>:
            </label>
            <?php
            $templates = [];
            foreach ($site->consultations as $cons) {
                $templates[$cons->id] = $cons->title;
            }
            echo '<div class="settingsTypeTemplate">';
            echo Html::dropDownList(
                'newConsultation[template]',
                ($createForm->template ? $createForm->template->id : 0),
                $templates,
                ['class' => 'stdDropdown']
            );
            echo '</div>';
            ?>
        </div>
    </div>

    <div class="stdTwoCols templateSubselect">
        <div class="leftColumn control-label"><?= Yii::t('admin', 'cons_template_subsel') ?>:</div>
        <div class="rightColumn">
            <label class="templateSubselectSetting">
                <input type="checkbox" name="newConsultation[templateSubselect][]" id="templateSubselectSetting"
                       value="settings" checked disabled>
                <?= Yii::t('admin', 'cons_template_subsel_sett') ?>
            </label>

            <label class="templateSubselectTags">
                <input type="checkbox" name="newConsultation[templateSubselect][]" id="templateSubselectTags"
                       value="<?= ConsultationCreateForm::SUBSELECTION_TAGS ?>" checked>
                <?= Yii::t('admin', 'cons_template_subsel_tags') ?>
            </label>

            <label class="templateSubselectTypes">
                <input type="checkbox" name="newConsultation[templateSubselect][]" id="templateSubselectTypes"
                       value="<?= ConsultationCreateForm::SUBSELECTION_MOTION_TYPES ?>" checked>
                <?= Yii::t('admin', 'cons_template_subsel_types') ?>
            </label>

            <label class="templateSubselectTexts">
                <input type="checkbox" name="newConsultation[templateSubselect][]" id="templateSubselectTexts"
                       value="<?= ConsultationCreateForm::SUBSELECTION_TEXTS ?>" checked>
                <?= Yii::t('admin', 'cons_template_subsel_texts') ?>
            </label>

            <label class="templateSubselectAgenda">
                <input type="checkbox" name="newConsultation[templateSubselect][]" id="templateSubselectAgenda"
                       value="<?= ConsultationCreateForm::SUBSELECTION_AGENDA ?>" checked>
                <?= Yii::t('admin', 'cons_template_subsel_agenda') ?>
            </label>

            <label class="templateSubselectUsers">
                <input type="checkbox" name="newConsultation[templateSubselect][]" id="templateSubselectUsers"
                       value="<?= ConsultationCreateForm::SUBSELECTION_USERS ?>" checked>
                <?= Yii::t('admin', 'cons_template_subsel_users') ?>
            </label>
        </div>
    </div>

    <div class="settingsTypeTemplate">
        <div class="saveholder">
            <button type="submit" name="createConsultation" class="btn btn-primary">
                <?= Yii::t('admin', 'cons_create_submit') ?>
            </button>
        </div>
    </div>
    <div class="settingsTypeWizard siteCreate"><?php
        echo $this->render(
            '../../createsiteWizard/index',
            ['model' => $wizardModel, 'errors' => [], 'mode' => 'consultation']
        );
    ?></div>

</div>
<?= Html::endForm() ?>
