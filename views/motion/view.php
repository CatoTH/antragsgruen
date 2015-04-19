<?php

use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\Motion;
use app\models\db\MotionComment;
use app\models\db\MotionSupporter;
use app\models\db\User;
use app\models\forms\CommentForm;
use app\models\policies\IPolicy;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Motion $motion
 * @var Amendment[] $amendments
 * @var bool $editLink
 * @var int[] $openedComments
 * @var string|null $adminEdit
 * @var null|string $supportStatus
 * @var null|CommentForm $commentForm
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$layout->addBreadcrumb($motion->getTypeName());

$this->title = $motion->getTitleWithPrefix() . " (" . $motion->consultation->title . ", Antragsgrün)";

$rows = 4;
if ($motion->dateResolution != "") {
    $rows++;
}
// if (count($antrag->antraege) > 0) $rows++; // @TODO

$html = '<ul class="sidebarActions">';

if ($motion->motionType->hasAmendments) {
    $policy = $motion->consultation->getAmendmentPolicy();
    if ($policy->checkCurUserHeuristically()) {
        $html .= '<li class="amendmentCreate">';
        $amendCreateUrl = UrlHelper::createUrl(["amendment/create", 'motionId' => $motion->id]);
        $title          = '<span class="icon glyphicon glyphicon-flash"></span>';
        $title .= Yii::t('motion', 'Änderungsantrag stellen');
        $html .= Html::a($title, $amendCreateUrl) . '</li>';
    } else {
        $msg = $policy->getPermissionDeniedAmendmentMsg();
        if ($msg != "") {
            $html .= '<li class="amendmentCreate">';
            $html .= '<span style="font-style: italic;"><span class="icon glyphicon glyphicon-flash"></span>';
            $html .= Html::encode(Yii::t('motion', 'Änderungsantrag stellen'));
            $html .= '</span><br><span style="font-size: 13px; color: #dbdbdb; text-transform: none;">';
            $html .= Html::encode($msg) . '</span></li>';
        }
    }
}

if ($motion->consultation->getSettings()->hasPDF && $motion->isVisible()) {
    $html .= '<li class="download">';
    $title = '<span class="icon glyphicon glyphicon-download-alt"></span>' .
        Yii::t('motion', 'PDF-Version herunterladen');
    $html .= Html::a($title, UrlHelper::createMotionUrl($motion, 'pdf')) . '</li>';
}

if ($editLink) {
    $html .= '<li class="edit">';
    $title = '<span class="icon glyphicon glyphicon-scissors"></span>' .
        Yii::t('motion', 'Änderungsanträge einpflegen');
    $html .= Html::a($title, UrlHelper::createMotionUrl($motion, 'mergeamendments')) . '</li>';

    $amendLink = UrlHelper::createUrl(['amendment/create', 'motionId' => $motion->id]);
    $html .= '<li class="edit">';
    $title = '<span class="icon glyphicon glyphicon-edit"></span>' .
        Yii::t('motion', 'Antrag bearbeiten');
    $html .= Html::a($title, $amendLink) . '</li>';
}

if ($adminEdit) {
    $html .= '<li class="adminEdit">';
    $title = '<span class="icon glyphicon glyphicon-wrench"></span>' . "Admin: bearbeiten";
    $html .= Html::a($title, $adminEdit) . '</li>';
} else {
    $backUrl = UrlHelper::createUrl('consultation/index');
    $html .= '<li class="back">';
    $title = '<span class="icon glyphicon glyphicon-chevron-left"></span>' . "Zurück zur Übersicht";
    $html .= Html::a($title, $backUrl) . '</li>';
}
$html .= '</ul>';
$layout->menusHtml[] = $html;

$minimalisticUi = $motion->consultation->getSettings()->minimalisticUI;
$minHeight      = ($minimalisticUi && \Yii::$app->user->isGuest ? 110 : 164);

echo '<h1>' . Html::encode($motion->getTitleWithPrefix()) . '</h1>';

echo '<div class="motionData" style="min-height: ' . $minHeight . 'px;">';

if (!$minimalisticUi) {
    echo '<div class="content">';
    /*
    if (count($antrag->antraege) > 0) { ?>
                <div class="alert alert-error" style="margin-top: 10px; margin-bottom: 25px;">
                    <?php if (count($antrag->antraege) == 1) {
                        echo 'Achtung: dies ist eine alte Fassung; die aktuelle Fassung gibt es hier:<br>';
                        $a = $antrag->antraege[0];
                        echo CHtml::link($a->revision_name . " - " . $a->name, $this->createUrl("antrag/anzeige",
    array("antrag_id" => $a->id)));
                    } else {
                        echo 'Achtung: dies ist eine alte Fassung. Aktuellere Fassungen gibt es hier:<br>';
                        foreach ($antrag->antraege as $a) {
                            echo "- " . CHtml::link($a->revision_name . " - " . $a->name, $this->createUrl(
    "antrag/anzeige", array("antrag_id" => $a->id))) . "<br>";
                        }
                    } ?>
                </div>
            <?php } */

    echo '<table class="motionDataTable">
                <tr>
                    <th>' . Yii::t('motion', 'Veranstaltung') . ':</th>
                    <td>' .
        Html::a($motion->consultation->title, UrlHelper::createUrl('consultation/index')) . '</td>
                </tr>
                <tr>
                    <th>' . Yii::t('motion', 'AntragsstellerIn'), ':</th>
                    <td>';


    $x = array();
    foreach ($motion->getInitiators() as $supp) {
        $name  = $supp->getNameWithResolutionDate(true);
        $admin = User::currentUserHasPrivilege($controller->consultation, User::PRIVILEGE_SCREENING);
        if ($admin && ($supp->contactEmail != "" || $supp->contactPhone != "")) {
            $name .= " <small>(Kontaktdaten, nur als Admin sichtbar: ";
            if ($supp->contactEmail != "") {
                $name .= "E-Mail: " . Html::encode($supp->contactEmail);
            }
            if ($supp->contactEmail != "" && $supp->contactPhone != "") {
                $name .= ", ";
            }
            if ($supp->contactPhone != "") {
                $name .= "Telefon: " . Html::encode($supp->contactPhone);
            }
            $name .= ")</small>";
        }
        $x[] = $name;
    }
    echo implode(", ", $x);

    echo '</td></tr>
                <tr><th>Status:</th><td>';

    $screeningMotionsShown = $motion->consultation->getSettings()->screeningMotionsShown;
    $statiNames            = Motion::getStati();
    if ($motion->status == Motion::STATUS_SUBMITTED_UNSCREENED) {
        echo '<span class="unscreened">' . Html::encode($statiNames[$motion->status]) . '</span>';
    } elseif ($motion->status == Motion::STATUS_SUBMITTED_SCREENED && $screeningMotionsShown) {
        echo '<span class="screened">Von der Programmkommission geprüft</span>';
    } else {
        echo Html::encode($statiNames[$motion->status]);
    }
    if (trim($motion->statusString) != "") {
        echo " <small>(" . Html::encode($motion->statusString) . ")</string>";
    }
    echo '</td>
                </tr>';

    if ($motion->dateResolution != "") {
        echo '<tr><th>Entschieden am:</th>
       <td>' . Tools::formatMysqlDate($motion->dateResolution) . '</td>
     </tr>';
    }
    echo '<tr><th>Eingereicht:</th>
       <td>' . Tools::formatMysqlDateTime($motion->dateCreation) . '</td>
                </tr>';

    $admin = User::currentUserHasPrivilege($controller->consultation, User::PRIVILEGE_SCREENING);
    if ($admin && count($motion->consultation->tags) > 0) {
        echo '<tr><th>Themenbereiche:</th><td class="tags">';

        $tags         = array();
        $used_tag_ids = array();
        foreach ($motion->tags as $tag) {
            $used_tag_ids[] = $tag->id;
            $str            = Html::encode($tag->title);
            $str .= Html::beginForm('', 'post', ['class' => 'form-inline delTagForm delTag' . $tag->id]);
            $str .= '<input type="hidden" name="tagId" value="' . $tag->id . '">';
            $str .= '<button type="submit" name="motionDelTag">del</button>';
            $str .= Html::endForm();
            $tags[] = $str;
        }
        echo implode(", ", $tags);

        echo '&nbsp; &nbsp; <a href="#" class="tagAdderHolder">Neu</a>';
        echo Html::beginForm('', 'post', ['id' => 'tagAdderForm', 'class' => 'form-inline']);
        echo '<select name="tagId" size="1" title="Schlagwort aussuchen" class="form-control">
        <option>-</option>';

        foreach ($motion->consultation->tags as $tag) {
            if (!in_array($tag->id, $used_tag_ids)) {
                echo '<option value="' . IntVal($tag->id) . '">' . Html::encode($tag->title) . '</option>';
            }
        }
        echo '</select>
            <button class="btn btn-primary" type="submit" name="motionAddTag">Hinzufügen</button>';
        echo Html::endForm();
        echo '</td> </tr>';

    } elseif (count($motion->tags) > 0) {
        echo '<tr>
       <th>' . (count($motion->tags) > 1 ? "Themenbereiche" : "Themenbereich") . '</th>
       <td>';

        $tags = array();
        foreach ($motion->tags as $tag) {
            $tags[] = $tag->title;
        }
        echo Html::encode(implode(", ", $tags));

        echo '</td></tr>';

    }
    /*
    if ($motion->abgeleitetVon) {
                    ?>
                    <tr>
                        <th>Ersetzt diesen Antrag:</th>
                        <td><?php echo CHtml::link($antrag->abgeleitetVon->revision_name . " - " .
    $antrag->abgeleitetVon->name, $this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->abgeleitetVon->id)));
    ?> </td>
                    </tr>
                <?php }
    */


    echo '</table>

    <div class="visible-xs-block">
        <div style="width: 49%; display: inline-block; text-align: center; padding-top: 25px;">
            <a href="' . Html::encode(UrlHelper::createMotionUrl($motion, 'pdf')) . '"
               class="btn" style="color: black;"><span class="glyphicon glyphicon-download-alt"></span> PDF-Version</a>
        </div>';

    $policy = $motion->consultation->getAmendmentPolicy();
    if ($policy->checkCurUserHeuristically()) {
        echo '<div style="width: 49%; display: inline-block; text-align: center; padding-top: 25px;">
            <a href="' . Html::encode(UrlHelper::createUrl(["amendment/create", 'motionId' => $motion->id])) . '"
               class="btn btn-danger" style="color: white;"><span class="icon-aender-stellen"></span> ' .
            Html::encode(Yii::t('motion', 'Änderungsantrag stellen')) . '</a>
        </div>';
    }
    echo '</div></div>';
}

echo $controller->showErrors();

echo '</div>';


foreach ($motion->getSortedSections(true) as $i => $section) {
    if ($section->getSectionType()->isEmpty()) {
        continue;
    }
    echo '<section class="motionTextHolder motionTextHolder' . $i;
    if ($motion->consultation->getSettings()->lineLength > 80) {
        echo " smallFont";
    }
    echo '" id="section_' . $section->sectionId . '">';
    echo '<h3>' . Html::encode($section->consultationSetting->title) . '</h3>';

    $commOp = (isset($openedComments[$section->sectionId]) ? $openedComments[$section->sectionId] : []);
    echo $section->getSectionType()->showMotionView($controller, $commentForm, $commOp);

    echo '</section>';
}


$currUserId = (\Yii::$app->user->isGuest ? 0 : \Yii::$app->user->id);
$supporters = $motion->getSupporters();
$likes      = $motion->getLikes();
$dislikes   = $motion->getDislikes();
$enries     = (count($likes) > 0 || count($dislikes) > 0);

$supportPolicy = $motion->consultation->getSupportPolicy();
$canSupport = $supportPolicy->checkCurUserHeuristically();
$cantSupportMsg = $supportPolicy->getPermissionDeniedSupportMsg();
foreach ($motion->getInitiators() as $supp) {
    if ($supp->userId == $currUserId) {
        $canSupport = false;
    }
}

if (count($supporters) > 0) {
    echo '<section class="supporters"><h2>UnterstützerInnen</h2>
    <div class="content">';

    echo "<strong>UnterstützerInnen:</strong><br>";
    if (count($supporters) > 0) {
        echo '<ul>';
        foreach ($supporters as $supp) {
            echo '<li>';
            if ($supp->id == $currUserId) {
                echo '<span class="label label-info">Du!</span> ';
            }
            echo Html::encode($supp->getNameWithOrga());
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<em>keine</em><br>';
    }
    echo "<br>";
    echo '</div></section>';
}

if ($enries || $canSupport || $cantSupportMsg != "") {
    echo '<section class="likes"><h2>Zustimmung</h2>
    <div class="content">';

    if (count($likes) > 0) {
        echo "<strong>Zustimmung von:</strong><br>";
        echo '<ul>';
        foreach ($likes as $supp) {
            echo '<li>';
            if ($supp->id == $currUserId) {
                echo '<span class="label label-info">Du!</span> ';
            }
            echo Html::encode($supp->getNameWithOrga());
            echo '</li>';
        }
        echo '</ul>';
        echo "<br>";
    }

    if (count($dislikes) > 0) {
        echo "<strong>Abgelehnt von:</strong><br>";
        echo '<ul>';
        foreach ($dislikes as $supp) {
            echo '<li>';
            if ($supp->id == $currUserId) {
                echo '<span class="label label-info">Du!</span> ';
            }
            echo Html::encode($supp->getNameWithOrga());
            echo '</li>';
        }
        echo '</ul>';
        echo "<br>";
    }
    echo '</div>';

    if ($motion->consultation->getSupportPolicy()->checkSupportSubmit()) {
        echo Html::beginForm();

        echo "<div style='text-align: center; margin-bottom: 20px;'>";
        switch ($supportStatus) {
            case MotionSupporter::ROLE_INITIATOR:
                break;
            case MotionSupporter::ROLE_LIKE:
                echo '<button type="submit" name="motionSupportRevoke" class="btn">';
                echo '<span class="glyphicon glyphicon-remove-sign"></span> Doch nicht';
                echo '</button>';
                break;
            case MotionSupporter::ROLE_DISLIKE:
                echo '<button type="submit" name="motionSupportRevoke" class="btn">';
                echo '<span class="glyphicon glyphicon-remove-sign"></span> Doch nicht';
                echo '</button>';
                break;
            default:
                echo '<button type="submit" name="motionLike" class="btn btn-success">';
                echo '<span class="glyphicon glyphicon-thumbs-up"></span> Zustimmung';
                echo '</button>';

                echo '<button type="submit" name="motionDislike" class="btn btn-alert">';
                echo '<span class="glyphicon glyphicon-thumbs-down"></span> Widerspruch';
                echo '</button>';
        }
        echo "</div>";
        echo Html::endForm();
    } else {
        if ($cantSupportMsg != "") {
            echo '<div class="alert alert-danger" role="alert">
                <span class="icon glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
                <span class="sr-only">Error:</span>
                ' . Html::encode($cantSupportMsg) . '
            </div>';
        }
    }
    echo '</section>';
}

if (count($amendments) > 0 || $motion->consultation->getAmendmentPolicy()->getPolicyID() != IPolicy::POLICY_NOBODY) {
    echo '<section class="amendments"><h2>' . Yii::t('motion', 'Änderungsanträge') . '</h2>
    <div class="content">';

    if (count($amendments) > 0) {
        echo '<ul class="amendments">';
        foreach ($amendments as $amend) {
            echo '<li>';
            $aename = $amend->titlePrefix;
            if ($aename == "") {
                $aename = $amend->id;
            }
            $amendLink  = UrlHelper::createUrl(
                [
                    'amendment/view',
                    'motionId'    => $motion->id,
                    'amendmentId' => $amend->id
                ]
            );
            $amendStati = Amendment::getStati();
            echo Html::a($aename, $amendLink);
            echo " (" . Html::encode($amendStati[$amend->status]) . ")";
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<em>keine</em>';
    }

    echo '</div></section>';
}


if ($motion->consultation->getSettings()->commentWholeMotions) {
    echo '<section class="comments"><h2>Kommentare</h2>';

    $comments = array();
    foreach ($motion->comments as $comm) {
        if ($comm->paragraph == -1 && $comm->status != MotionComment::STATUS_DELETED) {
            $comments[] = $comm;
        }
    }

    echo $this->render(
        'showComments',
        [
            'motion'      => $motion,
            'paragraphNo' => -1,
            'comments'    => $comments,
            'form'        => $commentForm,
        ]
    );
    echo '</section>';
}

if (!$motion->consultation->site->getBehaviorClass()->isLoginForced()) {
    // @TODO Social Sharing
}
$layout->addOnLoadJS('$.Antragsgruen.motionShow();');
