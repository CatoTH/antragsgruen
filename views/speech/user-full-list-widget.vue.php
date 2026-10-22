<?php

use app\components\UrlHelper;
use app\components\Html;

/**
 * @var \app\models\settings\Layout $layout
 * @var \app\models\db\Consultation|null $consultation
 */

$layout->addJsTranslation('speech');

$loginUrl = UrlHelper::createUrl(['user/login', 'backUrl' => Yii::$app->request->url]);

$registerUrl   = UrlHelper::createUrl(['/rest/speech/register', 'queueId' => 'QUEUEID']);
$unregisterUrl = UrlHelper::createUrl(['/rest/speech/unregister', 'queueId' => 'QUEUEID']);
?>

<script type="module" crossorigin="anonymous">
    import { createApp, h, resolveComponent } from '/npm/vue.runtime.esm-browser.prod.js';
    import { getSpeechCommonMixins, setSpeechActionUrls } from "/js/vue/speech/SpeechCommonMixins.js";
    import translateDirective from "/js/vue/Translate.vue.js";
    import userFullList from "/js/vue/speech/UserFullListWidget.js";

    setSpeechActionUrls(
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
