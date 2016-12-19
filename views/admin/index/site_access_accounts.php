<?php

use app\models\db\Consultation;
use yii\helpers\Html;

/**
 * @var \app\controllers\Base $controller
 * @var Consultation $consultation
 */

echo '<section class="showManagedUsers">';

echo '<h2 class="green">' . \Yii::t('admin', 'siteacc_accounts_title') . '</h2>';
echo '<div class="content">';


$preEmails    = '';
$preNames     = '';
$prePasswords = '';
$preText      = \Yii::t('admin', 'siteacc_email_text_pre');
$hasEmail     = ($controller->getParams()->mailService['transport'] != 'none');

echo $controller->showErrors();


if ($hasEmail) {
    echo '<div class="accountEditExplanation alert alert-info" role="alert">' .
        \Yii::t('admin', 'siteacc_acc_expl_mail') .
        '</div>';
} else {
    echo '<div class="accountEditExplanation alert alert-info" role="alert">' .
        \Yii::t('admin', 'siteacc_acc_expl_nomail') .
        '</div>';
}

if (count($consultation->userPrivileges) > 0) {
    echo Html::beginForm('', 'post', ['id' => 'accountsEditForm', 'class' => 'adminForm form-horizontal']);

    echo '<h3 class="lightgreen">' . \Yii::t('admin', 'siteacc_existing_users') . '</h3>';

    echo '<table class="accountListTable table table-condensed">
<thead>
<tr>
<th class="nameCol">' . \Yii::t('admin', 'siteacc_user_name') . '</th>
<th class="emailCol">' . \Yii::t('admin', 'siteacc_user_login') . '</th>
<th class="accessViewCol">' . \Yii::t('admin', 'siteacc_user_read') . '</th>
<th class="accessCreateCol">' . \Yii::t('admin', 'siteacc_user_write') . '</th>
</tr>
</thead>
<tbody>
';
    foreach ($consultation->userPrivileges as $privilege) {
        $checkView   = ($privilege->privilegeView == 1 ? 'checked' : '');
        $checkCreate = ($privilege->privilegeCreate == 1 ? 'checked' : '');
        echo '<tr class="user' . $privilege->userId . '">
    <td class="nameCol">' . Html::encode($privilege->user->name) . '</td>
    <td class="emailCol">' . Html::encode($privilege->user->getAuthName()) . '</td>
    <td class="accessViewCol">
        <label>
            <span class="sr-only">' . \Yii::t('admin', 'siteacc_perm_read') . '</span>
            <input type="checkbox" name="access[' . $privilege->userId . '][]" value="view" ' . $checkView . '>
        </label>
    </td>
    <td class="accessCreateCol">
        <label>
            <span class="sr-only">' . \Yii::t('admin', 'siteacc_perm_write') . '</span>
            <input type="checkbox" name="access[' . $privilege->userId . '][]" value="create" ' . $checkCreate . '>
        </label>
    </td>
    </tr>' . "\n";
    }
    echo '</tbody></table>

<div class="saveholder">
    <button type="submit" name="saveUsers" class="btn btn-primary">' . \Yii::t('base', 'save') . '</button>
</div>
';
}


echo Html::endForm();


echo Html::beginForm('', 'post', ['id' => 'accountsCreateForm', 'class' => 'adminForm form-horizontal']);
echo '<h3 class="lightgreen">' . \Yii::t('admin', 'siteacc_new_users') . '</h3>';


if ($hasEmail) {
    echo '<div class="row">
    <label class="col-md-6">' . \Yii::t('admin', 'siteacc_new_emails') . '
    <textarea id="emailAddresses" name="emailAddresses" rows="15">' .
        Html::encode($preEmails) .
        '</textarea>
    </label>

    <label class="col-md-6">' . \Yii::t('admin', 'siteacc_new_names') . '
    <textarea id="names" name="names" rows="15">' . Html::encode($preNames) .
        '</textarea>
    </label>
</div>

<label for="emailText">' . \Yii::t('admin', 'siteacc_new_text') . ':</label>
<textarea id="emailText" name="emailText" rows="15" cols="80">' . Html::encode($preText) . '</textarea>';

} else {
    echo '
    <div class="row">
    <label class="col-md-4">' . \Yii::t('admin', 'siteacc_new_emails') . '
    <textarea id="emailAddresses" name="emailAddresses" rows="15">' .
        Html::encode($preEmails) .
        '</textarea>
    </label>

    <label class="col-md-4">' . \Yii::t('admin', 'siteacc_new_pass') . '
    <textarea id="passwords" name="passwords" rows="15">' . Html::encode($prePasswords) .
        '</textarea>
    </label>

    <label class="col-md-4">' . \Yii::t('admin', 'siteacc_new_names') . '
    <textarea id="names" name="names" rows="15">' . Html::encode($preNames) .
        '</textarea>
    </label>
</div>';
}


echo '<br><br>
<div class="saveholder">
    <button type="submit" name="addUsers" class="btn btn-primary">' . \Yii::t('admin', 'siteacc_new_do') . '</button>
</div>
';


echo Html::endForm();


echo '</div></section>';
