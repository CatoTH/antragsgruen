<?php

/** @var \app\models\db\Site $site */

use app\components\UrlHelper;

$json = [];
foreach ($site->consultations as $consultation) {
    if ($consultation->getSettings()->maintenanceMode || $consultation->urlPath === null || $consultation->dateDeletion) {
        continue;
    }
    $json[] = [
        'title' => $consultation->title,
        'title_short' => $consultation->titleShort,
        'date_published' => ($consultation->getDateTime() ? $consultation->getDateTime()->format('c') : null),
        'url_path' => $consultation->urlPath,
        'url_json' => UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/rest', $consultation)),
        'url_html' => UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/index', $consultation)),
    ];
}

echo json_encode($json);
