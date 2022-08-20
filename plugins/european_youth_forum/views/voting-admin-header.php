<?php
use yii\helpers\Html;
?>
<div class="content">
    &nbsp;

    <div class="votingOperations">
        <button type="button" class="btn btn-default sortVotings hidden">
            <span class="glyphicon glyphicon-sort" aria-hidden="true"></span>
            <?= Yii::t('voting', 'settings_sort') ?>
        </button>

        <button type="button" class="btn btn-default createVotingOpener">
            <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
            <?= Yii::t('voting', 'settings_create') ?>
        </button>

        <button type="button" class="btn btn-default createRollCall">
            <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
            Create Roll Call
        </button>

        <button type="button" class="btn btn-default createYfjVoting">
            <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
            Create YFJ Voting
        </button>
    </div>
</div>

<?php
echo Html::beginForm(
    \app\components\UrlHelper::createUrl('/european_youth_forum/admin/create-yfj-voting'),
    'POST',
    ['class' => 'hidden createYfjVotingForm']
);
?>
<h2 class="green">Create a YFJ Voting</h2>
<div class="content">
    <div class="row">
        <label for="voting_number" class="col-md-3">Roll Call number:</label>
        <div class="col-md-9"><input type="number" name="voting[number]" id="voting_number" class="form-control"></div>
    </div>
    <div class="row">
        <label for="voting_title" class="col-md-3">Title:</label>
        <div class="col-md-9"><input type="text" name="voting[title]" id="voting_title" class="form-control"></div>
    </div>
    <div class="row">
        <label class="col-md-3">Type of voting:</label>
        <div class="col-md-9">
            <label>
                <input type="radio" name="voting[type]" value="question" checked> Simple question
            </label>
            &nbsp;
            <label>
                <input type="radio" name="voting[type]" value="motions"> Motions &amp; Amendments
            </label>
        </div>
    </div>
    <button type="submit" class="btn btn-primary">Create</button>
</div>
<?php
echo Html::endForm();
?>

<?php
echo Html::beginForm(
    \app\components\UrlHelper::createUrl('/european_youth_forum/admin/create-roll-call'),
    'POST',
    ['class' => 'hidden createRollCallForm']
);
?>
<h2 class="green">Create a Roll Call</h2>
<div class="content">
    <div class="row">
        <label for="roll_call_number" class="col-md-3">Roll call number:</label>
        <div class="col-md-9"><input type="number" name="rollcall[number]" id="roll_call_number" class="form-control"></div>
    </div>
    <div class="row">
        <label for="roll_call_name" class="col-md-3">Roll call name:</label>
        <div class="col-md-9"><input type="text" name="rollcall[name]" id="roll_call_name" placeholder="e.g. &quot;Friday evening&quot;" class="form-control"></div>
    </div>
    <div class="row">
        <label for="roll_call_create_groups" class="col-md-3">Create user groups:</label>
        <div class="col-md-9"><input type="checkbox" name="rollcall[create_groups]" id="roll_call_create_groups" checked></div>
    </div>
    <button type="submit" class="btn btn-primary">Create</button>
</div>
<?php
echo Html::endForm();
?>

<script>
    document.querySelector('.createRollCall').addEventListener('click', () => {
        document.querySelector('.createRollCallForm').classList.remove('hidden');
        document.querySelector('.createYfjVotingForm').classList.add('hidden');
    });
    document.querySelector('.createYfjVoting').addEventListener('click', () => {
        document.querySelector('.createYfjVotingForm').classList.toggle('hidden');
        document.querySelector('.createRollCallForm').classList.add('hidden');
    });
</script>
