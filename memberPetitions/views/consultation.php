<?php

use app\components\UrlHelper;
use app\memberPetitions\Tools;
use app\models\db\Consultation;
use app\models\db\Motion;
use app\models\settings\Layout;
use yii\helpers\Html;

/**
 * @var Consultation $consultation
 * @var Layout $layout
 * @var bool $admin
 */

/**
 * @param Motion[] $motions
 */
$showMotionList = function ($motions) {
    echo '<ul class="motionList">';
    foreach ($motions as $motion) {
        $url = UrlHelper::createMotionUrl($motion);
        echo '<li>' . Html::a(Html::encode($motion->getTitleWithPrefix()), $url) . '</li>';
    }
    echo '</ul>';
};

?>

<h2 class="green">
    Beantwortete Mitgliederbegehren
</h2>
<div class="content">
    <?php
    $showMotionList(Tools::getMotionsAnswered($consultation));
    ?>
</div>


<h2 class="green">
    Noch nicht beantwortet
</h2>
<div class="content">
    <?php
    $showMotionList(Tools::getMotionsUnanswered($consultation));
    ?>
</div>


<h2 class="green">
    Sammelnde Mitgliederbegehren
</h2>
<div class="content">
    <?php
    $showMotionList(Tools::getMotionsCollecting($consultation));
    ?>
</div>

