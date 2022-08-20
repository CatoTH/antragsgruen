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
echo Html::beginForm(\app\components\UrlHelper::createUrl('/european_youth_forum/admin/create-yfj-voting'), 'POST');
?>
<h2 class="green">Create a YFJ Voting</h2>
<div class="content">
    <button type="submit" class="btn btn-primary">Create</button>
</div>
<?php
echo Html::endForm();
?>

<?php
echo Html::beginForm(\app\components\UrlHelper::createUrl('/european_youth_forum/admin/create-roll-call'), 'POST');
?>
<h2 class="green">Create a Roll Call</h2>
<div class="content">
    <button type="submit" class="btn btn-primary">Create</button>
</div>
<?php
echo Html::endForm();
?>

<script>

</script>
