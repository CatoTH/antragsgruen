<?php

use yii\helpers\Html;

/**
 * @var string $toTranslateUrl
 */


$service = \app\components\UrlHelper::getCurrentConsultation()->getSettings()->translationService;
if (!$service) {
    return;
}

$urlParts = parse_url($toTranslateUrl);
if (!isset($urlParts['host'])) {
    $toTranslateUrl = \app\components\UrlHelper::absolutizeLink($toTranslateUrl);
}

$languages = [
    [
        'htmlLang'   => 'ar',
        'googleLang' => 'ar',
        'bingLang'   => 'ar',
        'nameNative' => 'العربية',
    ],
    [
        'htmlLang'   => 'en',
        'googleLang' => 'en',
        'bingLang'   => 'en',
        'nameNative' => 'English',
    ],
    [
        'htmlLang'   => 'es',
        'googleLang' => 'es',
        'bingLang'   => 'es',
        'nameNative' => 'Español',
    ],
    [
        'htmlLang'   => 'fr',
        'googleLang' => 'fr',
        'bingLang'   => 'fr',
        'nameNative' => 'Français',
    ],
    [
        'htmlLang'   => 'hr',
        'googleLang' => 'hr',
        'bingLang'   => 'hr',
        'nameNative' => 'Hrvatski',
    ],
    [
        'htmlLang'   => 'it',
        'googleLang' => 'it',
        'bingLang'   => 'it',
        'nameNative' => 'Italiano',
    ],
    [
        'htmlLang'   => 'nl',
        'googleLang' => 'nl',
        'bingLang'   => 'nl',
        'nameNative' => 'Nederlands',
    ],
    [
        'htmlLang'   => 'pl',
        'googleLang' => 'pl',
        'bingLang'   => 'pl',
        'nameNative' => 'Polski',
    ],
    [
        'htmlLang'   => 'ru',
        'googleLang' => 'ru',
        'bingLang'   => 'ru',
        'nameNative' => 'русский',
    ],
    [
        'htmlLang'   => 'tr',
        'googleLang' => 'tr',
        'bingLang'   => 'tr',
        'nameNative' => 'Türkçe',
    ],
    [
        'htmlLang'   => 'zh',
        'googleLang' => 'zh',
        'bingLang'   => 'zh-Hans',
        'nameNative' => '中文',
    ],
];

?>
<div class="translateWidget">
    <div class="dropdown">
        <button class="btn btn-default btn-xs dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true"
                lang="en" aria-label="Translate this page to..." id="translatePageBtn"
        >
            <span class="glyphicon glyphicon-globe" aria-hidden="true"></span>
            Translate to...
            <span class="caret"></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="translatePageBtn">
            <li class="dropdown-header"><?php
                if ($service === 'google') {
                    echo 'Google Translate';
                }
                if ($service === 'bing') {
                    echo 'Bing Translator';
                }
            ?></li>
            <?php
            foreach ($languages as $language) {
                $url = '';
                if ($service === 'google') {
                    $url = 'https://translate.google.com/translate?sl=auto&tl=' . $language['googleLang'] . '&u=' . urlencode($toTranslateUrl);
                }
                if ($service === 'bing') {
                    $url = 'https://www.translatetheweb.com/?ref=TVert&from=&to=' . $language['bingLang'] . '&a=' . urlencode($toTranslateUrl);
                }
                echo '<li><a href="' . Html::encode($url) . '" lang="' . Html::encode($language['htmlLang']) . '" ';
                echo '>' . $language['nameNative'] . '</a></li>';
            }
            ?>
        </ul>
    </div>
</div>
