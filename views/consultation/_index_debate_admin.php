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
$layout->provideJwt = true;

$initState = Tools::getSerializer()->serialize(DebateState::fromConsultation($consultation), 'json');
$debateUrl = UrlHelper::createUrl(['/rest/debate/index']);
$selectableUrl = UrlHelper::createUrl(['/rest/debate/selectable']);

?>
<section class="currentDebateAdmin currentSpeechPageWidth" aria-labelledby="currentDebateAdminTitle"
         data-init-state="<?= Html::encode($initState) ?>"
         data-debate-url="<?= Html::encode($debateUrl) ?>"
         data-selectable-url="<?= Html::encode($selectableUrl) ?>"
>
    <h2 class="green" id="currentDebateAdminTitle"><?= Yii::t('debate', 'admin_title') ?></h2>
    <div class="currentDebateAdminWidget"></div>
</section>

<script type="module" crossorigin="anonymous">
    import { createApp, h, resolveComponent } from '/npm/vue.runtime.esm-browser.prod.js';
    import translateDirective from "/js/vue/Translate.vue.js";
    import debateAdminWidget from "/js/vue/debate/DebateAdminWidget.js";

    const $element = $('.currentDebateAdmin');

    /** @type {import('vue').App} */
    const widget = createApp({
        render() {
            return h(resolveComponent('debate-admin-widget'), {
                initState: this.initState,
                debateUrl: this.debateUrl,
                selectableUrl: this.selectableUrl,
            });
        },
        data() {
            return {
                initState: $element.data('init-state'),
                debateUrl: $element.data('debate-url'),
                selectableUrl: $element.data('selectable-url'),
            };
        }
    });

    widget.component('debate-admin-widget', debateAdminWidget);
    widget.directive('t', translateDirective);

    widget.mount('.currentDebateAdmin .currentDebateAdminWidget');
</script>
