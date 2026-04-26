<?php

use app\components\UrlHelper;
use yii\helpers\Html;

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$consultation = $controller->consultation;
$layout = $controller->layoutParams;

$layout->addJsTranslation('speech');

$loginUrl = UrlHelper::createUrl(['user/login', 'backUrl' => Yii::$app->request->url]);

$pollUrl       = UrlHelper::createUrl(['/speech/get-queue', 'queueIds' => 'QUEUEIDS']);
$registerUrl   = UrlHelper::createUrl(['/speech/register', 'queueId' => 'QUEUEID']);
$unregisterUrl = UrlHelper::createUrl(['/speech/unregister', 'queueId' => 'QUEUEID']);
?>

<script type="module">
    import { createApp, h, resolveComponent } from '/npm/vue.runtime.esm-browser.prod.js';
    import { getSpeechCommonMixins, setSpeechUrls } from "/js/vue/speech/SpeechCommonMixins.js";
    import translateDirective from "/js/vue/Translate.vue.js";
    import userFullList from "/js/vue/speech/UserFullListWidget.js";

    setSpeechUrls(
        <?= json_encode($pollUrl) ?>,
        <?= json_encode($registerUrl) ?>,
        <?= json_encode($unregisterUrl) ?>
    );
    const SPEECH_MIXINS = getSpeechCommonMixins();

    const $element = $('.currentSpeechFullPage');

    /** @type {import('vue').App} */
    const widget = createApp({
        render() {
            return h(resolveComponent('speech-user-full-list-widget'), {
                initQueue: this.initQueue,
                user: this.user,
                csrf: this.csrf,
                title: this.title,
                loginUrl: this.loginUrl,
            });
        },
        data() { return {
            initQueue: $element.data('queue'),
            user: $element.data('user'),
            csrf: $("head").find("meta[name=csrf-token]").attr("content"),
            title: $element.data('title'),
            adminUrl: $element.data('admin-url'),
            loginUrl: $element.data('login-url'),
        } }
    });

    widget.mixin(SPEECH_MIXINS);
    widget.component('speech-user-full-list-widget', userFullList);
    widget.directive('t', translateDirective);

    widget.mount('.currentSpeechFullPage .currentSpeechList');
</script>
