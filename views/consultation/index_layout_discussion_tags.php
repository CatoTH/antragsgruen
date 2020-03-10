<?php

use app\components\{MotionSorter, Tools, UrlHelper};
use app\models\db\{Consultation, ConsultationSettingsTag, Motion};
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Consultation $consultation
 * @var \app\models\settings\Layout $layout
 */

$layout->addJS('npm/isotope.pkgd.min.js');
$showPrefix = !$consultation->getSettings()->hideTitlePrefix;

list($motions, $resolutions) = MotionSorter::getMotionsAndResolutions($consultation->motions);
if (count($resolutions) > 0) {
    echo $this->render('_index_resolutions', ['consultation' => $consultation, 'resolutions' => $resolutions]);
}
if (!$showPrefix) {
    usort($motions, function (Motion $motion1, Motion $motion2) {
        return $motion2->getTimestamp() <=> $motion1->getTimestamp();
    });
}

echo '<section class="consultationDiscussionTags" data-antragsgruen-widget="frontend/ConsultationDiscussionTags">';

$comments = \app\models\db\IComment::getNewestForConsultations([$consultation], 10);
if (count($comments) > 0) {
    ?>
    <section class="expandableRecentComments <?= (count($comments) > 4 ? 'shortened' : '') ?>">
        <h2 class="green"><?= Yii::t('con', 'discuss_comments_title') ?></h2>
        <div class="commentListHolder content">
            <div class="commentList">
                <?php
                foreach ($comments as $comment) {
                    $text  = $comment->getTextAbstract(150);
                    $title = $comment->getIMotion()->getTitleWithPrefix();
                    $more  = '<span class="glyphicon glyphicon-chevron-right"></span> ' . Yii::t('con', 'discuss_comment_link');
                    ?>
                    <div class="motionCommentHolder">
                        <article class="motionComment">
                            <div class="date"><?= Tools::formatMysqlDate($comment->dateCreation) ?></div>
                            <h3 class="commentHeader"><?= Html::encode($comment->name) ?></h3>
                            <div class="commentText">
                                <?= Html::encode($text) ?>
                                <?= Html::a($more, $comment->getLink()) ?>
                            </div>
                            <footer class="motionLink">
                                <?= Yii::t('con', 'discuss_comment_link') ?>:
                                <?= Html::a(Html::encode($title), $comment->getLink()) ?>
                            </footer>
                        </article>
                    </div>
                    <?php
                }
                ?>
            </div>
            <div class="moreActivitiesLink">
                <?php
                $title = '<span class="glyphicon glyphicon-chevron-right"></span> ' . Yii::t('con', 'discuss_comments_more');
                echo Html::a($title, UrlHelper::createUrl('consultation/activitylog'));
                ?>
            </div>
            <div class="showAllComments">
                <button class="btn btn-link">
                    <span class="glyphicon glyphicon-chevron-down"></span>
                    <?= Yii::t('con', 'discuss_comments_expand') ?>
                </button>
            </div>
        </div>
    </section>
    <?php
}
?>

    <h2 class="green" id="motionListSorterTitle"><?= Yii::t('con', 'All Motions') ?></h2>
    <section class="motionListFilter content" id="motionListSorter" aria-labelledby="motionListSorterTitle">
        <?php
        $tags = ConsultationSettingsTag::getMostPopularTags($motions);
        ?>
        <div>
            <div class="tagList">
                <a href="#" class="btn btn-info btn-xs" data-filter="*"><?= Yii::t('con', 'discuss_filter_all') ?></a>
                <?php
                foreach ($tags as $tag) {
                    $btn = 'btn-default';
                    echo '<a href="#" data-filter=".tag' . $tag['id'] . '" class="btn ' . $btn . ' btn-xs tag' . $tag['id'] . '">';
                    echo Html::encode($tag['title']) . ' <span class="num">(' . $tag['num'] . ')</span></a>';
                }
                ?>
            </div>
            <div class="searchBar clearfix">
                <div class="btn-group btn-group-sm pull-right motionSort" role="group" aria-label="Sort motions by...">
                    <?php
                    if ($showPrefix) {
                        ?>
                        <button type="button" class="btn btn-default" data-sort="titlePrefix" data-order="asc">
                            <?= Yii::t('con', 'discuss_sort_prefix') ?>
                        </button>
                        <?php
                    }
                    ?>
                    <button type="button" class="btn btn-default" data-sort="title" data-order="asc">
                        <?= Yii::t('con', 'discuss_sort_title') ?>
                    </button>
                    <button type="button" class="btn btn-default active" data-sort="created" data-order="desc">
                        <?= Yii::t('con', 'discuss_sort_newest') ?>
                    </button>
                    <button type="button" class="btn btn-default" data-sort="created" data-order="asc">
                        <?= Yii::t('con', 'discuss_sort_oldest') ?>
                    </button>
                    <button type="button" class="btn btn-default" data-sort="amendments" data-order="desc"
                            title="<?= Yii::t('con', 'discuss_sort_amend') ?>">
                        <span class="glyphicon glyphicon-flash"></span>
                    </button>
                    <button type="button" class="btn btn-default" data-sort="comments" data-order="desc"
                            title="<?= Yii::t('con', 'discuss_sort_comment') ?>">
                        <span class="glyphicon glyphicon-comment"></span>
                    </button>
                </div>
            </div>
        </div>

        <div class="motionListFiltered">
            <?php
            echo '<ul class="motionList motionListFilterTags">';
            foreach ($motions as $motion) {
                $status = $motion->getFormattedStatus();

                $cssClasses   = ['sortitem', 'motion'];
                $cssClasses[] = 'motionRow' . $motion->id;
                foreach ($motion->tags as $tag) {
                    $cssClasses[] = 'tag' . $tag->id;
                }

                $commentCount   = $motion->getNumOfAllVisibleComments(false);
                $amendmentCount = count($motion->getVisibleAmendments(false));

                echo '<li class="' . implode(' ', $cssClasses) . '" ' .
                     'data-created="' . $motion->getTimestamp() . '" ' .
                     'data-title="' . Html::encode($motion->title) . '" ' .
                     'data-title-prefix="' . Html::encode($motion->titlePrefix) . '" ' .
                     'data-num-comments="' . $commentCount . '" ' .
                     'data-num-amendments="' . $amendmentCount . '">';
                echo '<p class="stats">';

                if ($amendmentCount > 0) {
                    echo '<span class="amendments"><span class="glyphicon glyphicon-flash"></span> ' . $amendmentCount . '</span>';
                }
                if ($commentCount > 0) {
                    echo '<span class="comments"><span class="glyphicon glyphicon-comment"></span> ' . $commentCount . '</span>';
                }
                echo '</p>' . "\n";
                echo '<p class="title">' . "\n";

                $motionUrl = UrlHelper::createMotionUrl($motion);
                echo '<a href="' . Html::encode($motionUrl) . '" class="motionLink' . $motion->id . '">';

                echo ' <span class="motionTitle">' . Html::encode($motion->getTitleWithPrefix()) . '</span>';

                echo '</a>';
                echo "</p>\n";
                echo '<p class="info">';
                echo Html::encode($motion->getInitiatorsStr()) . ', ';
                echo Tools::formatMysqlDate($motion->dateCreation);
                echo '</p>';
                $abstract = null;
                foreach ($motion->getSortedSections(true) as $section) {
                    if ($section->getSettings()->type === \app\models\sectionTypes\ISectionType::TYPE_TEXT_SIMPLE &&
                        $section->getSettings()->maxLen !== 0) {
                        $abstract = \app\components\HTMLTools::toPlainText($section->getData(), true);
                    }
                }
                if ($abstract) {
                    echo '<blockquote class="abstract">' . Html::encode($abstract) . '</blockquote>';
                }
                echo '</li>';
            }
            echo '</ul>';

            ?>
        </div>
    </section>

<?php
echo '</section>';
