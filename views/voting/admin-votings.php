<?php

use app\models\layoutHooks\Layout;
use app\models\policies\IPolicy;
use app\components\{HTMLTools, IMotionStatusFilter, UrlHelper};
use app\models\db\{Amendment, Motion};
use app\models\majorityType\IMajorityType;
use app\models\proposedProcedure\Factory;
use app\models\votings\AnswerTemplates;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$consultation = $controller->consultation;
$layout = $controller->layoutParams;
$layout->addBreadcrumb(Yii::t('voting', 'votings_bc'), UrlHelper::createUrl('/consultation/votings'));
$layout->addBreadcrumb(Yii::t('voting', 'admin_bc'));
$this->title = Yii::t('voting', 'admin_title');

$sidebarMode = 'admin';
include(__DIR__ . DIRECTORY_SEPARATOR . '_sidebar.php');

$layout->addCSS('css/backend.css');
$layout->loadSelectize();
$layout->loadSortable();
$layout->loadVue();
$layout->addVueTemplate('@app/views/shared/selectize.vue.php');
$layout->addVueTemplate('@app/views/voting/_voting_common_mixins.vue.php');
$layout->addVueTemplate('@app/views/voting/_policy-select.vue.php');
$layout->addVueTemplate('@app/views/voting/_voting_vote_list.vue.php');
$layout->addVueTemplate('@app/views/voting/admin-votings.vue.php');
$layout->addVueTemplate('@app/views/voting/admin-voting-sort.vue.php');
Layout::registerAdditionalVueVotingTemplates($consultation, $layout);
$layout->loadVueDraggable();

$apiData = [];
foreach (Factory::getAllVotingBlocks($consultation) as $votingBlock) {
    /** @noinspection PhpUnhandledExceptionInspection */
    $apiData[] = $votingBlock->getAdminApiObject();
}

$pollUrl = UrlHelper::createUrl(['/voting/get-admin-voting-blocks']);
$sortUrl = UrlHelper::createUrl(['/voting/post-vote-order']);
$voteCreateUrl = UrlHelper::createUrl(['/voting/create-voting-block']);
$voteSettingsUrl = UrlHelper::createUrl(['/voting/post-vote-settings', 'votingBlockId' => 'VOTINGBLOCKID']);
$voteDownloadUrl = UrlHelper::createUrl(['/voting/download-voting-results', 'votingBlockId' => 'VOTINGBLOCKID', 'format' => 'FORMAT']);

$addableMotionsData = [];
$filter = IMotionStatusFilter::onlyUserVisible($consultation, false)
                             ->noAmendmentsIfMotionIsMoved();
foreach ($filter->getFilteredConsultationIMotionsSorted() as $IMotion) {
    if (is_a($IMotion, Amendment::class)) {
        $addableMotionsData[] = [
            'type' => 'amendment',
            'id' => $IMotion->id,
            'title' => $IMotion->getTitleWithPrefix(),
        ];
    } else {
        /** @var Motion $IMotion */
        $amendments = [];
        foreach ($IMotion->getFilteredAmendments($filter) as $amendment) {
            $amendments[] = [
                'type' => 'amendment',
                'id' => $amendment->id,
                'title' => $amendment->titlePrefix,
            ];
        }
        $addableMotionsData[] = [
            'type' => 'motion',
            'id' => $IMotion->id,
            'title' => $IMotion->getTitleWithPrefix(),
            'amendments' => $amendments,
        ];
    }
}

$userGroups = array_map(function (\app\models\db\ConsultationUserGroup $group): array {
    return $group->getUserAdminApiObject();
}, $consultation->getAllAvailableUserGroups());

?>
<h1><?= Yii::t('voting', 'admin_title') ?></h1>

<?= $layout->getMiniMenu('votingSidebarSmall') ?>

<div class="manageVotings votingCommon"
     data-url-vote-settings="<?= Html::encode($voteSettingsUrl) ?>"
     data-url-vote-download="<?= Html::encode($voteDownloadUrl) ?>"
     data-vote-create="<?= Html::encode($voteCreateUrl) ?>"
     data-url-poll="<?= Html::encode($pollUrl) ?>"
     data-url-sort="<?= Html::encode($sortUrl) ?>"
     data-antragsgruen-widget="backend/VotingAdmin"
     data-addable-motions="<?= Html::encode(json_encode($addableMotionsData)) ?>"
     data-user-groups="<?= Html::encode(json_encode($userGroups)) ?>"
     data-voting="<?= Html::encode(json_encode($apiData)) ?>">

    <?php
    $alternativeHeader = Layout::getVotingAlternativeAdminHeader($consultation);
    if ($alternativeHeader) {
        echo $alternativeHeader;
    } else {
    ?>

    <div class="content">

        <div class="votingOperations">
            <button type="button" class="btn btn-default sortVotings hidden">
                <span class="glyphicon glyphicon-sort" aria-hidden="true"></span>
                <?= Yii::t('voting', 'settings_sort') ?>
            </button>

            <button type="button" class="btn btn-primary createVotingOpener">
                <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
                <?= Yii::t('voting', 'settings_create') ?>
            </button>
        </div>

        <?= Yii::t('voting', 'admin_intro') ?>
    </div>
    <?php
    }
    ?>

    <section class="createVotingHolder hidden" aria-labelledby="createVotingTitle">
        <h2 class="green" id="createVotingTitle">
            <?= Yii::t('voting', 'settings_create') ?>
        </h2>
        <form method="POST" class="content creatingVoting votingSettings">
            <fieldset class="votingType">
                <legend><?= Yii::t('voting', 'settings_votingtype') ?>:</legend>
                <label>
                    <input type="radio" name="votingTypeNew" value="question" required checked>
                    <?= Yii::t('voting', 'settings_votingtype_question') ?>
                </label>
                <label>
                    <input type="radio" name="votingTypeNew" value="motions" required>
                    <?= Yii::t('voting', 'settings_votingtype_motion') ?>
                </label>
            </fieldset>
            <label class="titleSetting">
                <?= Yii::t('voting', 'settings_title') ?>:<br>
                <input type="text" class="form-control settingsTitle">
            </label>
            <label class="specificQuestion">
                <?= Yii::t('voting', 'settings_question') ?>:<br>
                <input type="text" class="form-control settingsQuestion">
            </label>
            <label class="assignedMotion">
                <?= Yii::t('voting', 'settings_motionassign') ?>:
                <?= HTMLTools::getTooltipIcon(Yii::t('voting', 'settings_motionassign_h')) ?>
                <br>
                <select class="stdDropdown settingsAssignedMotion">
                    <option value=""> - <?= Yii::t('voting', 'settings_motionassign_none') ?> -</option>
                    <?php
                    foreach ($addableMotionsData as $motion) {
                        if ($motion['type'] !== 'motion') {
                            continue;
                        }
                        echo '<option value="' . intval($motion['id']) . '">' . Html::encode($motion['title']) . '</option>';
                    }
                    ?>
                </select>
            </label>
            <fieldset class="answerTemplate">
                <legend><?= Yii::t('voting', 'settings_answers') ?>:</legend>
                <label>
                    <input type="radio" name="answersNew" value="<?= AnswerTemplates::TEMPLATE_YES_NO_ABSTENTION ?>" required checked="checked">
                    <?= Yii::t('voting', 'settings_answers_yesnoabst') ?>
                </label>
                <label>
                    <input type="radio" name="answersNew" value="<?= AnswerTemplates::TEMPLATE_YES_NO ?>" required>
                    <?= Yii::t('voting', 'settings_answers_yesno') ?>
                </label>
                <label>
                    <input type="radio" name="answersNew" value="<?= AnswerTemplates::TEMPLATE_YES ?>" required>
                    <?= Yii::t('voting', 'settings_answers_yes') ?>
                    <?= HTMLTools::getTooltipIcon(Yii::t('voting', 'settings_answers_yesh')) ?>
                </label>
                <label>
                    <input type="radio" name="answersNew" value="<?= AnswerTemplates::TEMPLATE_PRESENT ?>" required>
                    <?= Yii::t('voting', 'settings_answers_present') ?>
                    <?= HTMLTools::getTooltipIcon(Yii::t('voting', 'settings_answers_presenth')) ?>
                </label>
            </fieldset>
            <fieldset class="majorityTypeSettings">
                <legend><?= Yii::t('voting', 'settings_majoritytype') ?></legend>
                <?php
                foreach (IMajorityType::getMajorityTypes() as $majorityType) {
                    ?>
                    <label>
                        <input type="radio" value="<?= $majorityType::getID() ?>" name="majorityTypeNew"
                               <?= ($majorityType::getID() === IMajorityType::MAJORITY_TYPE_SIMPLE ? 'checked' : '') ?>>
                        <?= Html::encode($majorityType::getName()) ?>
                        <?= HTMLTools::getTooltipIcon($majorityType::getDescription()) ?>
                    </label>
                    <?php
                }
                ?>
            </fieldset>
            <fieldset class="votePolicy">
                <legend><?= Yii::t('voting', 'settings_votepolicy') ?>:</legend>
                <?php
                $policies = [];
                foreach (IPolicy::getPolicies() as $policy) {
                    $policies[$policy::getPolicyID()] = $policy::getPolicyName();
                }
                if (\app\models\db\ConsultationUserGroup::consultationHasLoadableUserGroups($consultation)) {
                    $groupLoadUrl = UrlHelper::createUrl('/admin/users/search-groups');
                } else {
                    $groupLoadUrl = '';
                }

                echo Html::dropDownList(
                    'votePolicyNew',
                    \app\models\policies\LoggedIn::getPolicyID(),
                    $policies,
                    ['class' => 'stdDropdown policySelect', 'autocomplete' => 'off']
                );
                ?>
                <div class="userGroupSelect" data-load-url="<?= Html::encode($groupLoadUrl) ?>">
                    <select name="votePolicyGroupsNew[]" class="userGroupSelectList" multiple autocomplete="off"
                            placeholder="<?= Yii::t('admin', 'motion_type_group_ph') ?>" title="<?= Yii::t('admin', 'motion_type_group_title') ?>">
                        <?php
                        foreach ($consultation->getAllAvailableUserGroups() as $group) {
                            echo '<option value="' . $group->id . '">' . Html::encode($group->getNormalizedTitle()) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </fieldset>
            <fieldset class="resultsPublicSettings">
                <legend><?= Yii::t('voting', 'settings_resultspublic') ?>:</legend>
                <label>
                    <input type="radio" value="0" name="resultsPublicNew">
                    <?= Yii::t('voting', 'settings_resultspublic_admins') ?>
                </label>
                <label>
                    <input type="radio" value="1" name="resultsPublicNew" checked>
                    <?= Yii::t('voting', 'settings_resultspublic_all') ?>
                </label>
            </fieldset>
            <fieldset class="votesPublicSettings">
                <legend><?= Yii::t('voting', 'settings_votespublic') ?>:</legend>
                <label>
                    <input type="radio" value="0" name="votesPublicNew" checked>
                    <?= Yii::t('voting', 'settings_votespublic_nobody') ?>
                </label>
                <label>
                    <input type="radio" value="1" name="votesPublicNew">
                    <?= Yii::t('voting', 'settings_votespublic_admins') ?>
                </label>
                <label>
                    <input type="radio" value="2" name="votesPublicNew">
                    <?= Yii::t('voting', 'settings_votespublic_all') ?>
                </label>
                <div class="hint"><?= Yii::t('voting', 'settings_votespublic_hint') ?></div>
            </fieldset>
            <fieldset class="votesNamesSettings">
                <legend><?= Yii::t('voting', 'settings_votesnames') ?>:</legend>
                <label>
                    <input type="radio" value="<?= \app\models\settings\VotingBlock::VOTES_NAMES_AUTH ?>" name="votesNames" checked>
                    <?= Yii::t('voting', 'settings_votesnames_auth') ?>
                </label>
                <label>
                    <input type="radio" value="<?= \app\models\settings\VotingBlock::VOTES_NAMES_NAME ?>" name="votesNames">
                    <?= Yii::t('voting', 'settings_votesnames_name') ?>
                </label>
                <label>
                    <input type="radio" value="<?= \app\models\settings\VotingBlock::VOTES_NAMES_ORGANIZATION ?>" name="votesNames">
                    <?= Yii::t('voting', 'settings_votesnames_organization') ?>
                </label>
            </fieldset>
            <button type="submit" class="btn btn-success">
                <?= Yii::t('voting', 'settings_save') ?>
            </button>
        </form>
    </section>

    <div class="votingAdmin"></div>
</div>
