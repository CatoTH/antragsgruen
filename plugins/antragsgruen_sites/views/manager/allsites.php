<?php

use app\components\HTMLTools;
use app\models\forms\SiteCreateForm;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var \Yii\web\View $this
 * @var \app\models\db\Site[] $sites
 */

/** @var \app\controllers\ConsultationController $controller */
$controller = $this->context;

$this->title = Yii::t('antragsgruen_sites', 'allsites_title');

?>
<h1><?= Yii::t('antragsgruen_sites', 'allsites_title') ?></h1>
<div class="content">
    <div class="alert alert-info">
        <p>
            <?= Yii::t('antragsgruen_sites', 'allsites_desc') ?>
        </p>
    </div>

    <div class="managerAllsitesList">
        <ul>
            <?php
            foreach ($sites as $data) {
                echo '<li>';
                if ($data['organization'] != '') {
                    echo '<span class="orga">' . HTMLTools::encodeAddShy($data['organization']) . '</span>';
                }
                echo  Html::a(HTMLTools::encodeAddShy($data['title']), $data['url']) . '</li>' . "\n";
            }
            ?>
        </ul>
    </div>
</div>
