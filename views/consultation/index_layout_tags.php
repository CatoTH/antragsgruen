<?php

use app\components\{MotionSorter, UrlHelper};
use app\models\db\{Amendment, Consultation, IMotion, Motion};
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Consultation $consultation
 * @var \app\models\settings\Layout $layout
 */
$tags            = $tagIds = [];
$hasNoTagMotions = false;

list($imotions, $resolutions) = MotionSorter::getIMotionsAndResolutions($consultation->motions);
if (count($resolutions) > 0) {
    echo $this->render('_index_resolutions', ['consultation' => $consultation, 'resolutions' => $resolutions]);
}

foreach ($imotions as $motion) {
    if (in_array($motion->status, $consultation->getInvisibleMotionStatuses())) {
        continue;
    }
    if (count($motion->getMyTags()) === 0) {
        $hasNoTagMotions = true;
        if (!isset($tags[0])) {
            $tags[0] = ['name' => Yii::t('motion', 'tag_none'), 'motions' => []];
        }
        $tags[0]['motions'][] = $motion;
    } else {
        foreach ($motion->getMyTags() as $tag) {
            if (!isset($tags[$tag->id])) {
                $tags[$tag->id] = ['name' => $tag->title, 'motions' => []];
            }
            $tags[$tag->id]['motions'][] = $motion;
        }
    }
}
$sortedTags = $consultation->getSortedTags();
foreach ($sortedTags as $tag) {
    if (isset($tags[$tag->id])) {
        $tagIds[] = $tag->id;
    }
}
if ($hasNoTagMotions) {
    $tagIds[] = 0;
}

echo '<section class="motionListTags">';

if (count($sortedTags) > 0 && mb_stripos($sortedTags[0]->title, Yii::t('motion', 'agenda_filter')) === false) {
    echo '<h3 class="green">' . Yii::t('motion', 'tags_head') . '</h3>';
    echo '<ul id="tagList" class="content">';

    foreach ($tagIds as $tagId) {
        echo '<li><a href="#tag_' . $tagId . '">';
        echo Html::encode($tags[$tagId]['name']) . ' (' . count($tags[$tagId]['motions']) . ')';
        echo '</a></li>';
    }
    echo '</ul>';
    $layout->addOnLoadJS('$("#tagList").find("a").click(function (ev) {
            ev.preventDefault();
            $($(this).attr("href")).scrollintoview({top_offset: -100});
        });');
}

foreach ($tagIds as $tagId) {
    /** @var \app\models\db\ConsultationSettingsTag $tag */
    $tag = $tags[$tagId];
    echo '<h3 class="green" id="tag_' . $tagId . '">' . Html::encode($tag['name']) . '</h3>
    <div class="content">
    <table class="motionTable">
        <thead><tr>';
    if (!$consultation->getSettings()->hideTitlePrefix) {
        echo '<th class="prefixCol">' . Yii::t('motion', 'Prefix') . '</th>';
    }
    echo '
            <th class="titleCol">' . Yii::t('motion', 'Title') . '</th>
            <th class="initiatorCol">' . Yii::t('motion', 'Initiator') . '</th>
        </tr></thead>';
    foreach ($tag['motions'] as $motion) {
        /** @var IMotion $motion */
        $classes = ['motion'];
        if ($motion->getMyMotionType()->getSettingsObj()->cssIcon) {
            $classes[] = $motion->getMyMotionType()->getSettingsObj()->cssIcon;
        }
        if ($motion->status === IMotion::STATUS_WITHDRAWN) {
            $classes[] = 'withdrawn';
        }
        if ($motion->status === IMotion::STATUS_MOVED) {
            $classes[] = 'moved';
        }
        if ($motion->isInScreeningProcess()) {
            $classes[] = 'unscreened';
        }
        echo '<tr class="' . implode(' ', $classes) . '">';
        if (!$consultation->getSettings()->hideTitlePrefix) {
            echo '<td class="prefixCol">' . Html::encode($motion->titlePrefix) . '</td>';
        }
        echo '<td class="titleCol">';
        echo '<div class="titleLink">';
        if (is_a($motion, Amendment::class)) {
            echo Html::a(
                Html::encode($motion->getTitle()),
                UrlHelper::createAmendmentUrl($motion),
                ['class' => 'motionLink' . $motion->id]
            );
        } elseif (is_a($motion, Motion::class)) {
            echo Html::a(
                Html::encode($motion->title),
                UrlHelper::createMotionUrl($motion),
                ['class' => 'motionLink' . $motion->id]
            );
        }
        echo '</div><div class="pdflink">';
        if ($motion->getMyMotionType()->getPDFLayoutClass() !== null && $motion->isVisible()) {
            if (is_a($motion, Amendment::class)) {
                echo Html::a(
                    Yii::t('motion', 'as_pdf'),
                    UrlHelper::createAmendmentUrl($motion, 'pdf'),
                    ['class' => 'pdfLink']
                );
            } elseif (is_a($motion, Motion::class)) {
                echo Html::a(
                    Yii::t('motion', 'as_pdf'),
                    UrlHelper::createMotionUrl($motion, 'pdf'),
                    ['class' => 'pdfLink']
                );
            }
        }
        echo '</div></td><td class="initiatorRow">';
        $initiators = [];
        foreach ($motion->getInitiators() as $init) {
            if ($init->personType === \app\models\db\MotionSupporter::PERSON_NATURAL) {
                $initiators[] = $init->name;
            } else {
                $initiators[] = $init->organization;
            }
        }
        echo Html::encode(implode(', ', $initiators));
        if ($motion->status != Motion::STATUS_SUBMITTED_SCREENED) {
            echo ', ' . Html::encode(Motion::getStatusNames()[$motion->status]);
        }
        echo '</td></tr>';

        if (is_a($motion, Motion::class)) {
            $amends = MotionSorter::getSortedAmendments($consultation, $motion->getVisibleAmendments());
            foreach ($amends as $amend) {
                $classes = ['amendment'];
                if ($amend->status === Amendment::STATUS_WITHDRAWN) {
                    $classes[] = 'withdrawn';
                }
                echo '<tr class="' . implode(' ', $classes) . '">';
                if (!$consultation->getSettings()->hideTitlePrefix) {
                    echo '<td class="prefixCol">' . Html::encode($amend->titlePrefix) . '</td>';
                }
                echo '<td class="titleCol"><div class="titleLink">';
                $title = Yii::t('amend', 'amendment_for') . ' ' . Html::encode($motion->titlePrefix);
                echo Html::a($title, UrlHelper::createAmendmentUrl($amend), ['class' => 'amendment' . $amend->id]);
                if ($amend->status === Amendment::STATUS_WITHDRAWN) {
                    echo ' <span class="status">(' . Html::encode($amend->getStatusNames()[$amend->status]) . ')</span>';
                }
                echo '</div></td>';
                echo '<td class="initiatorRow">';
                $initiators = [];
                foreach ($amend->getInitiators() as $init) {
                    if ($init->personType === \app\models\db\MotionSupporter::PERSON_NATURAL) {
                        $initiators[] = $init->name;
                    } else {
                        $initiators[] = $init->organization;
                    }
                }
                echo Html::encode(implode(', ', $initiators));
                if ($amend->status != Amendment::STATUS_SUBMITTED_SCREENED) {
                    echo ', ' . Html::encode(Amendment::getStatusNames()[$amend->status]);
                }
                echo '</td></tr>';
            }
        }
    }
    echo '</table>
    </div>';
}

echo '</section>';
