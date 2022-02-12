<?php

use app\models\policies\IPolicy;
use app\components\{HTMLTools, UrlHelper};
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
/** @var \app\models\db\Consultation */
$consultation = $controller->consultation;
$layout       = $controller->layoutParams;
$layout->addBreadcrumb(Yii::t('voting', 'bc'), UrlHelper::createUrl('consultation/voting-results'));
$layout->addBreadcrumb(Yii::t('voting', 'admin_bc'));
$this->title = Yii::t('voting', 'admin_title');

$layout->addCSS('css/backend.css');
$layout->loadSelectize();
$layout->loadVue();
$layout->loadVueSelect();
$layout->addVueTemplate('@app/views/voting/_voting_common_mixins.vue.php');
$layout->addVueTemplate('@app/views/voting/_policy-select.vue.php');
$layout->addVueTemplate('@app/views/voting/_voting_vote_list.vue.php');
$layout->addVueTemplate('@app/views/voting/admin-votings.vue.php');

$apiData = [];
foreach (Factory::getAllVotingBlocks($consultation) as $votingBlock) {
    /** @noinspection PhpUnhandledExceptionInspection */
    $apiData[] = $votingBlock->getAdminApiObject();
}

$pollUrl = UrlHelper::createUrl(['/voting/get-admin-voting-blocks']);
$voteCreateUrl = UrlHelper::createUrl(['/voting/create-voting-block']);
$voteSettingsUrl = UrlHelper::createUrl(['/voting/post-vote-settings', 'votingBlockId' => 'VOTINGBLOCKID']);
$voteDownloadUrl = UrlHelper::createUrl(['/voting/download-voting-results', 'votingBlockId' => 'VOTINGBLOCKID', 'format' => 'FORMAT']);

$addableMotionsData = [];
foreach ($consultation->getVisibleIMotionsSorted(false) as $IMotion) {
    if (is_a($IMotion, Amendment::class)) {
        $addableMotionsData[] = [
            'type' => 'amendment',
            'id' => $IMotion->id,
            'title' => $IMotion->getTitleWithPrefix(),
        ];
    } else {
        /** @var Motion $IMotion */
        $amendments = [];
        foreach ($IMotion->getVisibleAmendmentsSorted(false, false) as $amendment) {
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

<div class="manageVotings votingCommon"
     data-url-vote-settings="<?= Html::encode($voteSettingsUrl) ?>"
     data-url-vote-download="<?= Html::encode($voteDownloadUrl) ?>"
     data-vote-create="<?= Html::encode($voteCreateUrl) ?>"
     data-url-poll="<?= Html::encode($pollUrl) ?>"
     data-antragsgruen-widget="backend/VotingAdmin"
     data-addable-motions="<?= Html::encode(json_encode($addableMotionsData)) ?>"
     data-user-groups="<?= Html::encode(json_encode($userGroups)) ?>"
     data-voting="<?= Html::encode(json_encode($apiData)) ?>">
    <div class="content">
        <button type="button" class="btn btn-default createVotingOpener">
            <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
            <?= Yii::t('voting', 'settings_create') ?>
        </button>

        <?= Yii::t('voting', 'admin_intro') ?>
    </div>

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

                echo Html::dropDownList(
                    'votePolicyNew',
                    \app\models\policies\LoggedIn::getPolicyID(),
                    $policies,
                    ['class' => 'stdDropdown policySelect', 'autocomplete' => 'off']
                );
                ?>
                <div class="userGroupSelect">
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
                <legend><?= Yii::t('voting', 'settings_resultspublic') ?></legend>
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
                <legend><?= Yii::t('voting', 'settings_votespublic') ?></legend>
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
            <button type="submit" class="btn btn-success">
                <?= Yii::t('voting', 'settings_save') ?>
            </button>
        </form>
    </section>

    <div class="votingAdmin"></div>
</div>
