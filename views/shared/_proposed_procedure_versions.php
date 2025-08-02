<?php

/**
 * @var Yii\web\View $this
 * @var IMotion $imotion
 * @var IProposal $proposal
 */

use app\components\UrlHelper;
use app\models\db\{IMotion, IProposal};
use yii\helpers\Html;

if (count($imotion->proposals) > 1) {
    ?>
    <section class="proposalHistory">
        <div class="versionList">
            <ol>
                <?php
                foreach ($imotion->proposals as $itProp) {
                    $versionName = str_replace('%VERSION%', $itProp->version, Yii::t('amend', 'proposal_version_x'));
                    if ($itProp->id === $proposal->id) {
                        echo '<li>' . Html::encode($versionName) . '</li>';
                    } else {
                        $versionLink = UrlHelper::createIMotionUrl($imotion, 'view', ['proposalVersion' => $itProp->version]);
                        echo '<li>' . Html::a(Html::encode($versionName), $versionLink, ['class' => 'version' . $itProp->version]) . '</li>';
                    }
                }
                ?>
            </ol>
        </div>
    </section>
    <?php
}
