<?php
use app\models\forms\SiteCreateForm;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var yii\web\View $this
 * @var SiteCreateForm $model
 * @var array $errors
 * @var \app\controllers\Base $controller
 */

$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = 'Antragsgrün-Seite anlegen';
$controller->layoutParams->addCSS('css/formwizard.css');
$controller->layoutParams->addCSS('css/manager.css');
$controller->layoutParams->addJS("js/manager.js");
$layout->loadDatepicker();
$controller->layoutParams->addOnLoadJS('$.SiteManager.createInstance2();');

$t = function ($string) {
    return \Yii::t('wizard', $string);
};

?>
<h1>Antragsgrün-Seite anlegen</h1>
<div class="fuelux">
    <?php echo Html::beginForm(Url::toRoute('manager/createsite'), 'post', ['class' => 'siteCreate']); ?>

    <div id="SiteCreateWizard" class="wizard">
        <ul class="steps">
            <li data-target="#stepPurpose" class="stepPurpose">
                <?= $t('step_purpose') ?><span class="chevron"></span>
            </li>
            <li data-target="#stepMotions" class="stepMotions">
                <?= $t('step_motions') ?><span class="chevron"></span>
            </li>
            <li data-target="#stepAmendments" class="stepAmendments">
                <?= $t('step_amendments') ?><span class="chevron"></span>
            </li>
            <li data-target="#stepSpecial" class="stepSpecial">
                <?= $t('step_special') ?><span class="chevron"></span>
            </li>
            <li data-target="#stepSite" class="stepSite">
                <?= $t('step_site') ?><span class="chevron"></span>
            </li>
        </ul>
    </div>
    <div class="content">
        <?= $this->render('_createsite_purpose', ['model' => $model, 'errors' => $errors, 't' => $t]) ?>
        <?= $this->render('_createsite_single_motion', ['model' => $model, 't' => $t]) ?>
        <?= $this->render('_createsite_motion_who', ['model' => $model, 't' => $t]) ?>
        <?= $this->render('_createsite_motion_deadline', ['model' => $model, 't' => $t]) ?>
        <!--
        <div class="step-pane" id="step2">
            <br><br>
            <div class="row">
                <div class="col-md-7">

                    <div class="form-group">
                        <label class="name" for="siteOrganization">Name der Organisation:</label>
                        <?php
                        $opts = ['id' => 'siteOrganization', 'class' => 'form-control'];
                        echo Html::input('text', 'SiteCreateForm[organization]', $model->title, $opts);
                        ?>
                    </div>

                    <div class="form-group">
                        <label class="name" for="siteTitle">Name der Veranstaltung / des Programms:</label>
                        <?= Html::input('text', 'SiteCreateForm[title]', $model->title, ['id' => 'siteTitle', 'class' => 'form-control']) ?>
                    </div>

                    <?php

                    /** @var \app\models\settings\AntragsgruenApp $params */
                    $params = \yii::$app->params;
                    $input  = Html::input('text', 'SiteCreateForm[subdomain]', $model->subdomain, ['id' => 'subdomain']);
                    ?>
                    <div class="form-group">
                        <label class="url" for="subdomain">Unter folgender Adresse soll es erreichbar sein:</label>
                        <div class="fakeurl">
                            <?php
                            if (strpos($params->domainSubdomain, '<subdomain:[\w_-]+>') !== false) {
                                echo str_replace('&lt;subdomain:[\w_-]+&gt;', $input, Html::encode($params->domainSubdomain));
                            } else {
                                echo $input;
                            }
                            ?>
                        </div>
                        <div class="labelSubInfo">Für die Subdomain sind nur Buchstaben, Zahlen, "_" und "-" möglich.
                        </div>
                    </div>

                </div>
            </div>
            <br>
            <?php
            echo '<label class="policy">';
            echo Html::checkbox('SiteCreateForm[hasComments]', $model->hasComments, ['class' => 'hasComments']);
            echo 'Benutzer*innen können (Änderungs-)Anträge kommentieren
</label>';

            echo '<label class="policy">';
            echo Html::checkbox('SiteCreateForm[hasAmendments]', $model->hasAmendments, ['class' => 'hasAmendments']);
            echo 'Benutzer*innen können Änderungsanträge stellen
</label>';

            echo '<label class="policy">';
            echo Html::checkbox('SiteCreateForm[openNow]', $model->openNow, ['class' => 'openNow']);
            echo 'Die neue Antragsgrün-Seite soll sofort aufrufbar sein<br>
    <span class="labelSubInfo">(ansonsten: erst, wenn du exlizit den Wartungsmodus abschaltest)</span>
</label>';

            ?>
            <br>

            <div class="next">
                <button class="btn btn-primary" id="next-2"><span class="icon-chevron-right"></span> Weiter</button>
            </div>

        </div>
        <div class="step-pane" id="step3">
            <br>

            <div class="contact">
                <label>
                    <strong>Kontaktadresse:</strong>
                    <small>(Name, E-Mail, postalische Adresse; wird standardmäßig im Impressum genannt)</small>
                    <?= Html::textarea('SiteCreateForm[contact]', $model->contact, ['rows' => 5]) ?>
                </label>

            </div>
            <br><br>

            <div class="zahlung">
                <strong>Wärst du bereit, einen freiwilligen Beitrag über 30€ für den Betrieb von Antragsgrün zu
                    leisten?</strong><br>
                (Wenn ja, schicken wir dir eine Rechnung an die oben eingegebene Adresse)<br>

                <?php
                foreach (\app\models\settings\Site::getPaysValues() as $payId => $payName) {
                    echo '<div class="radio"><label>';
                    $checked = ($model->isWillingToPay !== null && $model->isWillingToPay == $payId);
                    echo Html::radio('SiteCreateForm[isWillingToPay]', $checked, ['value' => $payId, 'required' => 'required']);
                    echo Html::encode($payName);
                    echo '</label></div>';
                }
                ?>

            </div>

            <div class="next">

                <button class="btn btn-success" type="submit" name="create"><i class="icon-ok"></i> Anlegen</button>

            </div>

        </div>
        -->
    </div>

    <?= Html::endForm() ?>

</div>

