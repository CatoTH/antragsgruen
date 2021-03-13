<?php

use app\models\db\ConsultationAgendaItem;
use app\models\db\ConsultationMotionType;
use app\models\db\Motion;
use app\models\sectionTypes\ISectionType;
use app\models\sectionTypes\PDF;
use app\models\sectionTypes\VideoEmbed;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var ConsultationAgendaItem|null $agendaItem
 * @var ConsultationMotionType|null $motionType
 * @var Motion[] $motions
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout = $controller->layoutParams;

if ($agendaItem) {
    $this->title = $agendaItem->title;
} elseif ($motionType) {
    $this->title = $motionType->titlePlural;
} else {
    $this->title = 'Candidatures';
}
$layout->addCSS('css/backend.css');
$layout->addBreadcrumb('Candidatures');
$layout->fullWidth = true;

echo '<h1>' . Html::encode($this->title) . '</h1>';

usort($motions, function (Motion $motion1, Motion $motion2) {
    return $motion1->title <=> $motion2->title;
});

?>
<div class="content egpCandidatures">
    <?php
    if (count($motions) === 0) {
        ?>
        <div class="alert alert-danger">
            <p>
                No candidatures yet
            </p>
        </div>
        <?php
    } else {
        ?>
        <ul>
            <?php
            foreach ($motions as $motion) {
                /** @var \app\models\db\MotionSection|null $image */
                $image = null;
                /** @var \app\models\db\MotionSection[] $pdfs */
                $pdfs = [];
                /** @var \app\models\db\MotionSection[] $videos */
                $videos = [];
                /** @var \app\models\db\MotionSection|null $nominatedBy */
                $nominatedBy = null;
                foreach ($motion->sections as $section) {
                    $settings = $section->getSettings();
                    if ($settings->type === ISectionType::TYPE_IMAGE && $image === null) {
                        $image = $section;
                    } elseif ($settings->type === ISectionType::TYPE_PDF_ATTACHMENT) {
                        $pdfs[] = $section;
                    } elseif ($settings->type === ISectionType::TYPE_PDF_ALTERNATIVE) {
                        $pdfs[] = $section;
                    } elseif ($settings->type === ISectionType::TYPE_VIDEO_EMBED) {
                        $videos[] = $section;
                    } elseif ($settings->type === ISectionType::TYPE_TITLE && stripos($settings->title, 'Nominated') !== false) {
                        $nominatedBy = $section;
                    }
                }
                ?>
                <li>
                    <div class="imageHolder">
                        <?php
                        if ($image) {
                            echo $image->getSectionType()->getSimple(false, false);
                        }
                        ?>
                    </div>
                    <div class="contentHolder">
                        <h2 class="nameHolder"><?= Html::encode($motion->getTitleWithIntro()) ?></h2>
                        <div class="nominatedByHolder">Nominated by:
                            <?php
                            if ($nominatedBy) {
                                echo Html::encode($nominatedBy->getData());
                            }
                            ?>
                        </div>
                        <div class="supportedByHolder">Supported by:
                            <ul>
                                <?php
                                foreach ($motion->getSupporters() as $supporter) {
                                    echo '<li>' . Html::encode($supporter->organization) . '</li>';
                                }
                                ?>
                            </ul>
                        </div>
                        <ul class="documentList">
                            <?php
                            foreach ($pdfs as $pdf) {
                                echo '<li>';
                                /** @var PDF $sectionType */
                                $sectionType = $pdf->getSectionType();
                                echo Html::a(Html::encode($pdf->getSettings()->title), $sectionType->getPdfUrl());
                                echo '</li>';
                            }
                            foreach ($videos as $video) {
                                echo '<li>';
                                /** @var VideoEmbed $sectionType */
                                $sectionType = $video->getSectionType();
                                echo Html::a(Html::encode($video->getSettings()->title), $sectionType->getVideoUrl());
                                echo '</li>';
                            }
                            ?>
                        </ul>
                        <a href="<?= Html::encode(\app\components\UrlHelper::createMotionUrl($motion)) ?>">
                            <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                            Show full candidacy
                        </a>
                    </div>
                </li>
            <?php
            }
            ?>
        </ul>
        <?php
    }
    ?>
</div>
