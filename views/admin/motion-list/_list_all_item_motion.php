<?php
use app\components\UrlHelper;
use app\models\settings\{PrivilegeQueryContext, Privileges};
use app\models\db\{Motion, User};
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Motion $entry
 * @var \app\models\forms\AdminMotionFilterForm $search
 * @var boolean $colMark
 * @var boolean $colProposals
 * @var boolean $colAction
 * @var boolean $colResponsible
 * @var boolean $colDate
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$consultation = $controller->consultation;

$hasTags        = (count($consultation->tags) > 0);
$motionStatuses = $consultation->getStatuses()->getStatusNames();
$viewUrl        = UrlHelper::createMotionUrl($entry);
if (User::haveOneOfPrivileges($consultation, \app\controllers\admin\MotionController::REQUIRED_PRIVILEGES, PrivilegeQueryContext::motion($entry))) {
    $editUrl = UrlHelper::createUrl(['admin/motion/update', 'motionId' => $entry->id]);
} else {
    $editUrl = null;
}

echo '<tr class="motion motion' . $entry->id . '">';
if ($colMark) {
    echo '<td><input type="checkbox" name="motions[]" value="' . $entry->id . '" class="selectbox"></td>';
}
echo '<td>';
if ($entry->getMyMotionType()->motionPrefix) {
    echo Html::encode(trim($entry->getMyMotionType()->motionPrefix, ":-. \t\n\r\0\x0B"));
} else {
    echo Yii::t('admin', 'list_motion_short');
}
echo '<td class="prefixCol"><a href="' . Html::encode($viewUrl) . '">';
echo Html::encode($entry->titlePrefix !== '' ? $entry->titlePrefix : '-');
if ($entry->version > Motion::VERSION_DEFAULT || count($entry->replacedByMotions) > 0) {
    echo ' <small>(' . Yii::t('motion', 'version') . ' ' . $entry->version . ')</small>';
}
echo '</a></td>';
echo '<td class="titleCol"><span>';
if ($editUrl) {
    echo Html::a(Html::encode(trim($entry->title) != '' ? $entry->title : '-'), $editUrl);
} else {
    echo Html::encode(trim($entry->title) !== '' ? $entry->title : '-');
}
echo '</span></td>';
echo '<td>' . Html::encode($motionStatuses[$entry->status]);
if ($entry->status === Motion::STATUS_COLLECTING_SUPPORTERS) {
    echo ' (' . count($entry->getSupporters(true)) . ')';
}
if ($entry->statusString !== null && $entry->statusString !== '') {
    echo ' <small>(' . Html::encode($entry->statusString) . ')</small>';
}
echo '</td>';
if ($colDate) {
    echo '<td class="dateCol">';
    if ($entry->datePublication) {
        echo \app\components\Tools::formatMysqlDateTime($entry->datePublication);
    }
    echo '</td>';
}
if ($colResponsible) {
    ?>
    <td class="responsibilityCol">
        <?= $this->render('_responsibility_dropdown', ['imotion' => $entry, 'type' => 'motion']) ?>
    </td>
    <?php
}
if ($colProposals) {
    echo '<td class="proposalCol">';

    echo $this->render('../proposed-procedure/_status_icons', ['entry' => $entry, 'show_visibility' => true]);

    $name = $entry->getFormattedProposalStatus();
    if ($entry->status === Motion::STATUS_MOVED) {
        echo $name;
    } else {
        echo Html::a(($name ?: '-'), UrlHelper::createMotionUrl($entry));
    }
    echo '</td>';
}
echo '<td>' . Html::encode($entry->getInitiatorsStr()) . '</td>';
if ($hasTags) {
    $tags = [];
    foreach ($entry->getProposedProcedureTags() as $tag) {
        $tags[] = Html::encode($tag->title) . ' <small>(' . Yii::t('admin', 'filter_tag_pp') . ')</small>';
    }
    foreach ($entry->getPublicTopicTags() as $tag) {
        $tags[] = Html::encode($tag->title);
    }
    echo '<td class="tagsCol">' . implode(', ', $tags) . '</td>';
}
echo '<td class="exportCol"><div class="btn-group">
  <button class="btn btn-link dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
    <span class="caret" aria-hidden="true"></span>
    PDF
  </button>
  <ul class="dropdown-menu">';
if ($entry->getMyMotionType()->texTemplateId || $entry->getMyMotionType()->pdfLayout !== -1) {
    echo '<li>' . Html::a(
        str_replace('%TITLE%', $entry->getMyMotionType()->titleSingular, Yii::t('admin', 'list_export_motion_only')),
        UrlHelper::createMotionUrl($entry, 'pdf'),
        ['class' => 'pdf']
    ) . '</li>';
    echo '<li>' . Html::a(
        Yii::t('admin', 'list_export_amend_attach'),
        UrlHelper::createMotionUrl($entry, 'pdfamendcollection'),
        ['class' => 'pdfamend']
    ) . '</li>';
    echo '<li>' . Html::a(
        Yii::t('admin', 'list_export_amend_embed'),
        UrlHelper::createMotionUrl($entry, 'embedded-amendments-pdf'),
        ['class' => 'pdfEmbeddedAmendments']
    ) . '</li>';
}
echo '</ul></div>';

echo ' / ' . Html::a('ODT', UrlHelper::createMotionUrl($entry, 'odt'), ['class' => 'odt']);
echo ' / ' . Html::a('HTML', UrlHelper::createMotionUrl($entry, 'plainhtml'), ['class' => 'plainHtml']);

foreach ($controller->getParams()->getPluginClasses() as $pluginClass) {
    foreach ($pluginClass::getCustomMotionExports($entry) as $title => $url) {
        echo ' / ' . Html::a(Html::encode($title), $url);
    }
}

echo '</td>';

if ($colAction) {
    $canScreen = User::havePrivilege($consultation, Privileges::PRIVILEGE_SCREENING, PrivilegeQueryContext::motion($entry));
    $canDelete = User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_DELETE, PrivilegeQueryContext::motion($entry));

    echo '<td class="actionCol">';
    if ($canDelete || $canScreen) {
        echo '<div class="btn-group">
  <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
    ' . Yii::t('admin', 'list_action') . '
    <span class="caret"></span>
  </button>
  <ul class="dropdown-menu">';
        if ($canScreen) {
            $screenable = [
                Motion::STATUS_DRAFT,
                Motion::STATUS_DRAFT_ADMIN,
                Motion::STATUS_SUBMITTED_UNSCREENED,
                Motion::STATUS_SUBMITTED_UNSCREENED_CHECKED,
            ];
            if (in_array($entry->status, $screenable)) {
                $link = Html::encode($search->getCurrentUrl(['motionScreen' => $entry->id]));
                $name = Html::encode(Yii::t('admin', 'list_screen'));
                echo '<li><a tabindex="-1" href="' . $link . '" class="screen">' . $name . '</a>';
            } else {
                $link = Html::encode($search->getCurrentUrl(['motionUnscreen' => $entry->id]));
                $name = Html::encode(Yii::t('admin', 'list_unscreen'));
                echo '<li><a tabindex="-1" href="' . $link . '" class="unscreen">' . $name . '</a>';
            }
            $link = Html::encode(UrlHelper::createUrl(['motion/create', 'cloneFrom' => $entry->id]));
            $name = Html::encode(Yii::t('admin', 'list_template_motion'));
            echo '<li><a tabindex="-1" href="' . $link . '" class="asTemplate">' . $name . '</a>';
        }
        if ($canDelete) {
            $delLink = Html::encode($search->getCurrentUrl(['motionDelete' => $entry->id]));
            echo '<li><a tabindex="-1" href="' . $delLink . '" class="delete" ' .
                'onClick="return confirm(\'' . addslashes(Yii::t('admin', 'list_confirm_del_motion')) . '\');">' .
                Yii::t('admin', 'list_delete') . '</a></li>';
        }
        echo '</ul></div>';
    }
    echo '</td>';
}
echo '</tr>';
