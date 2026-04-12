<?php

use app\components\UrlHelper;
use yii\helpers\Html;

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$consultation = $controller->consultation;

$loginUrl = UrlHelper::createUrl(['user/login', 'backUrl' => Yii::$app->request->url]);

$pollUrl       = UrlHelper::createUrl(['/speech/get-queue', 'queueIds' => 'QUEUEIDS']);
$registerUrl   = UrlHelper::createUrl(['/speech/register', 'queueId' => 'QUEUEID']);
$unregisterUrl = UrlHelper::createUrl(['/speech/unregister', 'queueId' => 'QUEUEID']);
?>

<script type="module">
    import { createApp } from '/npm/vue.esm-browser.prod.js';
    import { getSpeechCommonMixins, setSpeechUrls } from "/js/vue/speech/SpeechCommonMixins.js";
    import translateDirective from "/js/vue/Translate.vue.js";
    translateDirective.registerTranslation("speech", <?= json_encode(\app\components\JsTools::getTranslations($consultation, "speech")) ?>);
    import userInlineWidget from "/js/vue/speech/UserInlineWidget.js";

    setSpeechUrls(
        <?= json_encode($pollUrl) ?>,
        <?= json_encode($registerUrl) ?>,
        <?= json_encode($unregisterUrl) ?>
    );
    const SPEECH_MIXINS = getSpeechCommonMixins();

    const $element = $('.currentSpeechInline');

    /** @type {import('vue').App} */
    const widget = createApp({
            template: `
                    <speech-user-inline-widget :initQueue="initQueue" :user="user" :csrf="csrf" :title="title"></speech-user-inline-widget>`,
            data() { return {
                initQueue: $element.data('queue'),
                user: $element.data('user'),
                csrf: $("head").find("meta[name=csrf-token]").attr("content"),
                title: $element.data('title'),
                adminUrl: $element.data('admin-url'),
            } }
        });

    widget.mixin(SPEECH_MIXINS);
    widget.component('speech-user-inline-widget', userInlineWidget);
    widget.directive('t', translateDirective);

    widget.config.compilerOptions.whitespace = 'condense';
    widget.mount('.currentSpeechInline .currentSpeechList');
</script>
