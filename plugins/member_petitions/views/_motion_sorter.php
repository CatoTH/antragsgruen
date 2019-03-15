<?php

use app\plugins\member_petitions\Tools;
use yii\helpers\Html;

/**
 * @var \app\models\db\Consultation[] $myConsultations
 * @var string $bold
 */

// Yes, this is far from elegant...
$showArchived = isset($_REQUEST['showArchived']);

?>
<div class="content motionListFilter">

    <label class="showArchivedRow">
        <input type="checkbox" name="showArchived" <?= ($showArchived ? 'checked' : '') ?>>
        Archivierte Begehren anzeigen
    </label>
    <?php

    $motions  = Tools::getAllMotions($myConsultations, $showArchived);
    $tags     = Tools::getMostPopularTags($motions);
    $tagsTop3 = array_splice($tags, 0, 3);
    $allTags  = Tools::getMostPopularTags($motions);
    usort($allTags, function ($tag1, $tag2) {
        if (strpos($tag1['title'], 'Cluster') !== false && strpos($tag2['title'], 'Cluster') === false) {
            return -1;
        }
        if (strpos($tag1['title'], 'Cluster') === false && strpos($tag2['title'], 'Cluster') !== false) {
            return 1;
        }
        return strnatcasecmp($tag1['title'], $tag2['title']);
    });

    ?>
    <div class="tagList">
        <a href="#" class="btn btn-info btn-xs" data-filter="*">Alle</a>
        <?php
        $inCluster = true;
        foreach ($allTags as $tag) {
            if (strpos($tag['title'], 'Cluster') !== false) {
                if ($inCluster === false) {
                    echo '<br>';
                }
                $inCluster = true;
            } else {
                if ($inCluster === true) {
                    echo '<br>';
                }
                $inCluster = false;
            }
            $btn = ($inCluster ? 'btn-info' : 'btn-default');
            echo '<a href="#" data-filter=".tag' . $tag['id'] . '" class="btn ' . $btn . ' btn-xs">';
            echo Html::encode($tag['title']) . ' <span class="num">(' . $tag['num'] . ')</span></a>';
        }
        ?>
    </div>

    <div class="searchBar clearfix">
        <div class="btn-group btn-group-sm pull-left motionFilters motionPhaseFilters" role="group" aria-label="Filter motions">
            <button type="button" class="btn btn-default active" data-filter="*">Alle</button>
            <button type="button" class="btn btn-default" data-filter=".phase1">
                Offene Diskussion
            </button>
            <button type="button" class="btn btn-default" data-filter=".phase2">
                Unterstützer*innen sammeln
            </button>
        </div>
            <?php
            /*
        <div class="btn-group btn-group-sm pull-left motionFilters" role="group" aria-label="Filter motions">
            <button type="button" class="btn btn-default active" data-filter="*">Alle</button>
            foreach ($tagsTop3 as $tag) {
                echo '<button type="button" class="btn btn-default" data-filter=".tag' . $tag['id'] . '"';
                echo '>' . Html::encode($tag['title']) . ' (' . $tag['num'] . ')</button>';
            }
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
            */
            ?>
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
            <button type="button" class="btn btn-default" data-sort="amendments" data-order="desc">
                <span class="glyphicon glyphicon-flash"></span>
            </button>
            <button type="button" class="btn btn-default" data-sort="comments" data-order="desc">
                <span class="glyphicon glyphicon-comment"></span>
            </button>
        </div>
    </div>
    <div class="motionListFiltered">
        <?= $this->render('_motion_list', [
            'motions'          => $motions,
            'bold'             => $bold,
            'statusClustering' => true,
        ]) ?>
    </div>
</div>
