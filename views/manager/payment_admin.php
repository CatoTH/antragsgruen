<?php
use app\components\UrlHelper;
use app\models\db\Site;
use app\models\settings\Site as SiteSettins;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Site[] $sites
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$controller->layoutParams->addCSS('css/manager.css');

$this->title = 'Zahlungsverwaltung';
echo '<h1>Seitenverweltung</h1>';

echo Html::beginForm(UrlHelper::createUrl('manager/paymentadmin'), 'post', ['class' => 'content paymentAdmin']);

echo '<table><thead><tr><th>Seite</th><th>Zahlungsbereit</th><th>Rechnung</th><th>Aktiv</th></tr></thead><tbody>';
foreach ($sites as $site) {
    $siteUrl  = UrlHelper::createUrl(['consultation/index', 'subdomain' => $site->subdomain]);
    $settings = $site->getSettings();
    echo '<tr>';
    echo '<td>' . Html::a($site->title, $siteUrl) . '<br>';
    echo '<span class="organization">' . Html::encode($site->organization) . '</span></td>';
    echo '<td>';
    if ($settings->willingToPay == SiteSettins::PAYS_MAYBE) {
        echo 'Vielleicht';
    } else {
        echo Html::encode(SiteSettins::getPaysValues()[$settings->willingToPay]);
    }
    echo '</td>';
    echo '<td><input type="checkbox" name="billSent[]" value="' . $site->id . '" title="Rechnung verschickt"';
    if ($settings->billSent) {
        echo ' checked';
    }
    if ($settings->willingToPay == SiteSettins::PAYS_NOT) {
        echo ' disabled';
    }
    echo '></td>';

    echo '<td><input type="checkbox" name="siteActive[]" value="' . $site->id . '" title="Akitiv (Sidebar)"';
    if ($site->status == Site::STATUS_ACTIVE) {
        echo ' checked';
    }
    echo '></td>';

    echo '</tr>';
}
echo '</tbody></table>';

echo '<div class="saveholder">
<button type="submit" name="save" class="btn btn-primary">Speichern</button>
</div>';

echo Html::endForm();
