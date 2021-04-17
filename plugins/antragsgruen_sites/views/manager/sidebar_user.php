<?php

use app\components\HTMLTools;
use app\components\UrlHelper;
use app\models\db\Site;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var \app\controllers\Base $controller
 * @var Site[] $sites
 * @var bool $showAll
 */

$controller = $this->context;
$layout = $controller->layoutParams;

$sitesCurrent = [];
foreach ($sites as $site) {
    if ($site->status !== Site::STATUS_ACTIVE) {
        continue;
    }
    if (!$site->currentConsultation) {
        continue;
    }
    $consultation = $site->currentConsultation;

    $url = UrlHelper::createUrl(['/consultation/home', 'subdomain' => $site->subdomain]);
    $siteData = [
        'title' => $consultation->title,
        'organization' => $site->organization,
        'url' => $url,
    ];
    $sitesCurrent[] = $siteData;
}

$html = '<section class="sidebar-box" id="sidebarYourSites" aria-labelledby="sidebarYourSitesTitle">' .
        '<h2 class="box-header" id="sidebarYourSitesTitle">' . Yii::t('antragsgruen_sites', 'your_sites') . '</h2>
    <ul class="box-content consultationList">';
foreach ($sitesCurrent as $data) {
    $html .= '<li>';
    if ($data['organization'] != '') {
        $html .= '<span class="orga">' . HTMLTools::encodeAddShy($data['organization']) . '</span>';
    }
    $html .= Html::a(HTMLTools::encodeAddShy($data['title']), $data['url']) . '</li>' . "\n";
}

$allSitesUrl = UrlHelper::createUrl('/antragsgruen_sites/manager/allsites');

$html .= '</ul>';

if ($showAll) {
    $html .= '<div class="box-content allsites">
        <a href="' . Html::encode($allSitesUrl) . '" class="btn btn-default">' .
             '<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> ' .
             Yii::t('antragsgruen_sites', 'btn_all_sites') . '</a>
    </div>';
}

$html .= '</div>
</section>';

$layout->menusHtml[] = $html;

$html = '';
foreach ($sitesCurrent as $data) {
    $html .= '<li class="manager-navbar-orgaLink">';
    if ($data['organization'] != '') {
        $html .= '<div class="orga">' . HTMLTools::encodeAddShy($data['organization']) . '</div>';
    }
    $html .= Html::a(HTMLTools::encodeAddShy($data['title']), $data['url']) . '</li>' . "\n";
}
if ($showAll) {
    $html .= '<li class="manager-navbar-orgaLink">
        <a href="' . Html::encode($allSitesUrl) . '" class="btn btn-default">' .
             '<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> ' .
             Yii::t('antragsgruen_sites', 'btn_all_sites') . '</a>
    </li>';
}
$layout->menusHtmlSmall[] = $html;


