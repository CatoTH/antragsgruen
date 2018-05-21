<?php

use app\plugins\memberPetitions\Tools;
use yii\helpers\Html;

?>
<div class="content motionListFilter">
    <?php

    $motions  = Tools::getAllMotions($myConsultations);
    $tags     = Tools::getMostPopularTags($motions);
    $tagsTop3 = array_splice($tags, 0, 3);
    ?>
    <div class="searchBar clearfix">
        <div class="btn-group btn-group-sm pull-left motionFilters" role="group" aria-label="Filter motions">
            <button type="button" class="btn btn-default active" data-filter="*">Alle</button>
            <?php
            foreach ($tagsTop3 as $tag) {
                echo '<button type="button" class="btn btn-default" data-filter=".tag' . $tag['id'] . '"';
                echo '>' . Html::encode($tag['title']) . ' (' . $tag['num'] . ')</button>';
            }
            ?>
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                    ...
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu">
                    <?php
                    foreach ($tags as $tag) {
                        echo '<li><a href="#" data-filter=".tag' . $tag['id'] . '">';
                        echo Html::encode($tag['title']) . ' (' . $tag['num'] . ')</a></li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
        <div class="btn-group btn-group-sm pull-right motionSort" role="group" aria-label="Sort motions by...">
            <button type="button" class="btn btn-default active" data-sort="phase" data-order="asc">
                Status
            </button>
            <button type="button" class="btn btn-default" data-sort="created" data-order="desc">
                Neueste
            </button>
            <button type="button" class="btn btn-default" data-sort="created" data-order="asc">
                Älteste
            </button>
            <button type="button" class="btn btn-default" data-sort="comments" data-order="desc">
                <span class="glyphicon glyphicon-comment"></span>
            </button>
            <button type="button" class="btn btn-default" data-sort="amendments" data-order="desc">
                <span class="glyphicon glyphicon-flash"></span>
            </button>
        </div>
    </div>
    <div class="motionListFiltered">
        <?= $this->render('_motion_list', [
            'motions' => $motions,
            'bold'    => 'organization'
        ]) ?>
    </div>
</div>
