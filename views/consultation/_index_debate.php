<?php

use app\components\{Tools, UrlHelper};
use app\models\api\debate\DebateState;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var \app\models\db\Consultation $consultation
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout = $controller->layoutParams;

$layout->addJsTranslation('debate');

$initState = Tools::getSerializer()->serialize(DebateState::fromConsultation($consultation), 'json');
$pollUrl = UrlHelper::createUrl(['/rest/debate/index']);

?>
<section class="currentDebateInline currentSpeechPageWidth" aria-labelledby="currentDebateWidgetTitle"
         data-init-state="<?= Html::encode($initState) ?>"
         data-poll-url="<?= Html::encode($pollUrl) ?>"
>
    <h2 class="green" id="currentDebateWidgetTitle"><?= Yii::t('debate', 'currently_debated') ?></h2>
    <div class="currentDebateWidget"></div>
</section>

<script type="module" crossorigin="anonymous">
    import { createApp, h, resolveComponent } from '/npm/vue.runtime.esm-browser.prod.js';
    import translateDirective from "/js/vue/Translate.vue.js";
    import currentDebateWidget from "/js/vue/debate/CurrentDebateWidget.js";

    const $element = $('.currentDebateInline');

    /** @type {import('vue').App} */
    const widget = createApp({
        render() {
            return h(resolveComponent('current-debate-widget'), {
                initState: this.initState,
                pollUrl: this.pollUrl,
            });
        },
        data() {
            return {
                initState: $element.data('init-state'),
                pollUrl: $element.data('poll-url'),
            };
        }
    });

    widget.component('current-debate-widget', currentDebateWidget);
    widget.directive('t', translateDirective);

    widget.mount('.currentDebateInline .currentDebateWidget');
</script>
