<?php

/**
 * @var \yii\web\View $this
 * @var \app\models\db\Consultation $consultation
 */

use app\components\UrlHelper;
use yii\helpers\Html;

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = \Yii::t('admin', 'list_head_title');
$layout->addBreadcrumb(\Yii::t('admin', 'bread_list'));
$layout->addJS('js/colResizable-1.6.min.js');
$layout->addCSS('css/backend.css');
$layout->fullWidth  = true;
$layout->fullScreen = true;

echo '<h1>' . \Yii::t('admin', 'list_head_title') . '</h1>';

echo $this->render('@app/views/admin/motion-list/_list_all_export');


$initData = json_encode([
    'motions'    => \app\async\models\Motion::getCollection($consultation),
    'amendments' => \app\async\models\Amendment::getCollection($consultation),
]);
/** @var \app\models\settings\AntragsgruenApp $app */
$app = \Yii::$app->params;

$linkTemplates = json_encode([
    'motion/view'    => UrlHelper::createUrl(['/motion/view', 'motionSlug' => '_SLUG_']),
    'amendment/view' => UrlHelper::createUrl(['/amendment/view', 'motionSlug' => '_MOTION_SLUG_', 'amendmentId' => '0123456789']),

    'admin/motion/update' => UrlHelper::createUrl(['/admin/motion/update', 'motionId' => '0123456789']),
    'admin/amendment/update' => UrlHelper::createUrl(['/admin/amendment/update', 'amendmentId' => '0123456789']),
]);
$params        = [
    'init-collections' => $initData,
    'ajax-backend'     => UrlHelper::createUrl('/admin/motion-list/index'),
    'link-templates'   => $linkTemplates,
];
if ($app->asyncConfig) {
    $params['cookie']  = $_COOKIE['PHPSESSID'];
    $params['ws-port'] = IntVal($app->asyncConfig['port-external']);
}
$paramsStr = implode(' ', array_map(function ($key) use ($params) {
    return $key . '="' . Html::encode($params[$key]) . '"';
}, array_keys($params)));


?>
<div class="content">
    <admin-index <?= $paramsStr ?>></admin-index>
    <script type="text/javascript" src="/angular/runtime.js"></script>
    <script type="text/javascript" src="/angular/polyfills.js"></script>
    <script type="text/javascript" src="/angular/vendor.js"></script>
    <script type="text/javascript" src="/angular/main.js"></script>
</div>
