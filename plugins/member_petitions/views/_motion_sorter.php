<?php

use app\components\UrlHelper;
use app\components\Tools as DateTools;
use app\models\db\{ConsultationSettingsTag, Motion};
use app\plugins\member_petitions\Tools;
use yii\helpers\Html;

/**
 * @var \app\models\db\Consultation[] $myConsultations
 * @var string $bold
 * @var \yii\web\View $this
 */

// Yes, this is far from elegant...
$showArchived = isset($_REQUEST['showArchived']);

?>
<div class="content motionListFilter" id="motionListSorter">
    <?php

    $motions  = Tools::getAllMotions($myConsultations);
    $motions = array_values(array_filter($motions, function (Motion $motion) use ($showArchived) {
        $isArchived = in_array($motion->status, [Motion::STATUS_PAUSED]);
        if ($showArchived) {
            return $isArchived;
        } else {
            return !$isArchived;
        }
    }));
    $tags     = ConsultationSettingsTag::getMostPopularTags($motions);
    $tagsTop3 = array_splice($tags, 0, 3);
    $allTags  = ConsultationSettingsTag::getMostPopularTags($motions);
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
        <div class="btn-group btn-group-sm pull-left motionFilters motionPhaseFilters" role="group"
             aria-label="Filter motions">
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
                <span class="glyphicon glyphicon-flash" aria-label="Änderungsanträge"></span>
            </button>
            <button type="button" class="btn btn-default" data-sort="comments" data-order="desc" aria-label="Kommentare">
                <span class="glyphicon glyphicon-comment"></span>
            </button>
        </div>
    </div>

    <div class="showArchivedRow">
        <?php
        $arrow = '<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>';
        if ($showArchived) {
            $baselink = UrlHelper::createUrl(['consultation/index']) . '#motionListSorter';
            echo Html::a($arrow . ' Aktuelle Begehren anzeigen', $baselink);
        } else {
            $baselink = UrlHelper::createUrl(['consultation/index', 'showArchived' => 1]) . '#motionListSorter';
            echo Html::a($arrow . ' Archivierte Begehren anzeigen', $baselink);
        }
        ?>
    </div>

    <?php
    $comments = \app\models\db\IComment::getNewestForConsultations($myConsultations, 10);
    ?>
    <div class="mostRecentComments <?= (count($comments) > 4 ? 'shortened' : '') ?>">
        <h2 class="green">Aktuell diskutiert</h2>
        <div class="commentListHolder">
            <div class="commentList">
                <?php
                foreach ($comments as $comment) {
                    $text  = $comment->getTextAbstract(150);
                    $title = $comment->getIMotion()->getTitleWithPrefix();
                    $more  = '<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> weiter';
                    ?>
                    <div class="motionCommentHolder">
                        <article class="motionComment">
                            <div class="date"><?= DateTools::formatMysqlDate($comment->dateCreation) ?></div>
                            <h3 class="commentHeader"><?= Html::encode($comment->name) ?></h3>
                            <div class="commentText">
                                <?= Html::encode($text) ?>
                                <?= Html::a($more, $comment->getLink()) ?>
                            </div>
                            <footer class="motionLink">
                                Zu: <?= Html::a(Html::encode($title), $comment->getLink()) ?>
                            </footer>
                        </article>
                    </div>
                    <?php
                }
                ?>
            </div>
            <div class="moreActivitiesLink">
                <?php
                $title = '<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> Weitere aktuelle Aktivitäten';
                echo Html::a($title, UrlHelper::createUrl('consultation/activitylog'));
                ?>
            </div>
            <div class="showAllComments">
                <button class="btn btn-link">
                    <span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>
                    Weitere anzeigen
                </button>
            </div>
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
