<?php

use app\components\UrlHelper;
use app\components\HTMLTools;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var yii\web\View $this
 * @var \app\models\db\Site $site
 * @var \app\models\forms\ConsultationCreateForm $createForm
 * @var \app\models\forms\SiteCreateForm $wizardModel
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = 'Veranstaltungen verwalten';
$layout->addCSS('css/formwizard.css');
$layout->addCSS('css/manager.css');
$layout->addCSS('css/backend.css');
$layout->addJS('js/backend.js');
$layout->addJS("js/build/SiteCreateWizard.js");
$layout->loadDatepicker();
$layout->addOnLoadJS('$.AntragsgruenAdmin.consultationCreate();');
$layout->addBreadcrumb(\Yii::t('admin', 'bread_settings'), UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb('Veranstaltungen');
$layout->loadFuelux();

$settings = $site->getSettings();

echo '<h1>Veranstaltungen</h1>';

echo $controller->showErrors();

echo Html::beginForm('', 'post', ['class' => 'consultationEditForm']);
echo '<h2 class="green">' . 'Angelegte Veranstaltungen' . '</h2>';
echo '<div class="content"><ul id="consultationsList">';
foreach ($site->consultations as $consultation) {
    $isStandard = ($consultation->id == $site->currentConsultationId);
    $params     = ['subdomain' => $site->subdomain, 'consultationPath' => $consultation->urlPath];

    echo '<li class="consultation' . $consultation->id . '">';

    echo '<div class="stdbox">';
    if ($isStandard) {
        echo '<strong><span class="glyphicon glyphicon-ok" style="color: green;"></span> ' .
            'Standard-Veranstaltung' . '</strong>';
    } else {
        echo '<button type="submit" name="setStandard[' . $consultation->id . ']" class="link">' .
            'Als Standard setzen</button>';
    }
    echo '</div>';

    echo '<h3>';
    echo Html::encode($consultation->title) . ' <small>(' . Html::encode($consultation->titleShort) . ')</small>';
    echo '</h3>';

    echo '<div class="homeLink">';
    $url = Url::toRoute(array_merge(['consultation/index'], $params));
    echo '<a href="' . Html::encode($url) . '"><span class="glyphicon glyphicon-chevron-right"></span> ' .
        'Zur Seite' . '</a>';
    echo '</div><div class="adminLink">';
    $url = Url::toRoute(array_merge(['admin/index'], $params));
    echo '<a href="' . Html::encode($url) . '"><span class="glyphicon glyphicon-chevron-right"></span> ' .
        'Zur Administration' . '</a>';
    echo '</div>';

    echo '</li>';
}
echo '</ul></div>';
echo Html::endForm();


echo Html::beginForm('', 'post', ['class' => 'consultationCreateForm form-horizontal']);

$textOpts = ['required' => 'required', 'class' => 'form-control'];
?>
<h2 class="green"><?= 'Veranstaltung anlegen' ?></h2>

<div class="content">

    <div class="form-group">
        <label for="newTitle" class="col-md-4 control-label"><?= 'Titel der Veranstaltung' ?>:</label>
        <div class="col-md-8">
            <?= Html::input('text', 'newConsultation[title]', $createForm->title, array_merge($textOpts, ['id' => 'newTitle'])) ?>
        </div>
    </div>

    <div class="form-group">
        <label for="newShort" class="col-md-4 control-label"><?= 'Kurzversion' ?>:</label>
        <div class="col-md-4">
            <?= Html::input(
                'text',
                'newConsultation[titleShort]',
                $createForm->titleShort,
                array_merge($textOpts, ['id' => 'newShort'])
            ) ?>
        </div>
    </div>

    <div class="form-group">
        <label for="newPath" class="col-md-4 control-label">Internet-Adresse:</label>
        <div class="col-md-8 fakeUrl">
            <?php
            $input = Html::input(
                'text',
                'newConsultation[urlPath]',
                $createForm->urlPath,
                array_merge($textOpts, ['id' => 'newPath'])
            );
            $url   = Url::toRoute(['consultation/index', 'subdomain' => $site->subdomain, 'consultationPath' => '--CON--']);
            $url   = UrlHelper::absolutizeLink($url);
            echo str_replace('--CON--', $input, $url);
            ?>
        </div>

        <div class="form-group">
            <label for="newSetStandard" class="col-md-4 control-label">Standard:</label>
            <div class="col-md-8 checkbox">
                <label>
                    <?= Html::checkbox(
                        'newConsultation[setStandard]',
                        $createForm->setAsDefault,
                        ['id' => 'newSetStandard']
                    ) ?>
                    Sofort als Standard-Veranstaltung festlegen
                </label>
            </div>
        </div>

        <div class="form-group settingsType">
            <div class="col-md-4 control-label">Einstellungen:</div>
            <div class="col-md-8">
                <label class="radio settingsTypeLabel">
                    <input type="radio" name="newConsultation[settingsType]" id="settingsTypeWizard" required
                           value="wizard">
                    Einrichtungs-Assistenten verwenden
                </label>
                <label class="radio settingsTypeLabel">
                    <input type="radio" name="newConsultation[settingsType]" id="settingsTypeTemplate" required
                           value="template" checked>
                    Einstellungen übernehmen von:
                </label>
                <?php
                $templates = [];
                foreach ($site->consultations as $cons) {
                    $templates[$cons->id] = $cons->title;
                }
                echo '<div class="settingsTypeTemplate">';
                echo HTMLTools::fueluxSelectbox(
                    'newConsultation[template]',
                    $templates,
                    ($createForm->template ? $createForm->template->id : 0)
                );
                echo '</div>';
                ?>
            </div>
        </div>

        <div class="settingsTypeTemplate">
            <div class="saveholder">
                <button type="submit" name="createConsultation" class="btn btn-primary">Veranstaltung anlegen</button>
            </div>
        </div>
        <div class="settingsTypeWizard siteCreate fuelux">
            <?= $this->render(
                '../../createsite_wizard/index',
                ['model' => $wizardModel, 'errors' => [], 'mode' => 'consultation']
            ) ?>
        </div>

    </div>

</div>
<?= Html::endForm() ?>
