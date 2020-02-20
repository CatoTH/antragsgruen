<?php
/**
 * @var \app\models\db\ConsultationFile[] $files
 * @var string|null $msgError
 * @var string|null $msgSuccess
 */
use yii\helpers\Html;
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= Yii::t('pages', 'images_browse_title') ?></title>
    <style>
        .files {
            display: block;
            list-style-type: none;
            margin: 0;
            padding: 0;
            vertical-align: top;
        }

        .files > li {
            display: inline-block;
            width: 150px;
            height: 170px;
            vertical-align: top;
            line-height: 150px;
            text-align: center;
        }

        .files > li * {
            vertical-align: middle;
            text-align: center;
        }

        .files > li > a {
            display: block;
            width: 150px;
            height: 150px;
        }

        .files > li img {
            display: inline-block;
            max-width: 150px;
            max-height: 150px;
        }

        .files > li form {
            display: block;
            height: 20px;
        }

        .msgSuccess {
            text-align: center;
            padding: 20px;
            color: green;
            font-weight: bold;
        }
        .msgError {
            text-align: center;
            padding: 20px;
            color: red;
            font-weight: bold;
        }
        .noImages {
            text-align: center;
            padding: 20px;
            color: grey;
            font-weight: bold;
            font-style: italic;
        }
    </style>
</head>
<body>

<?php
if ($msgError) {
    echo '<div class="msgError">' . $msgError . '</div>';
}
if ($msgSuccess) {
    echo '<div class="msgSuccess">' . $msgSuccess . '</div>';
}
?>

<ul class="files">
    <?php
    foreach ($files as $file) {
        echo '<li><a href="#" data-target-url="' . Html::encode($file->getUrl()) . '">';
        echo '<img src="' . Html::encode($file->getUrl()) . '" alt="' . Html::encode($file->filename) . '">';
        echo '</a>';
        echo Html::beginForm('', 'post', ['class' => 'deleteForm']);
        echo '<input type="hidden" name="id" value="' . $file->id . '">';
        echo '<button type="submit" name="delete">' . Yii::t('pages', 'images_delete') . '</button>';
        echo Html::endForm();
        echo '</li>';
    }
    ?>
</ul>

<?php
if (count($files) === 0) {
    echo '<div class="noImages">' . Yii::t('pages', 'images_none') . '</div>';
}
?>

<script>
    // Helper function to get parameters from the query string.
    function getUrlParam(paramName) {
        var reParam = new RegExp('(?:[\?&]|&)' + paramName + '=([^&]+)', 'i');
        var match = window.location.search.match(reParam);

        return (match && match.length > 1) ? match[1] : null;
    }

    document.addEventListener("DOMContentLoaded", function () {
        var links = document.querySelectorAll('.files a');
        for (var i = 0; i < links.length; i++) {
            (function (link) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    var url = link.getAttribute("data-target-url");
                    var funcNum = getUrlParam('CKEditorFuncNum');
                    window.opener.CKEDITOR.tools.callFunction(funcNum, url);
                    window.close();
                });
            })(links.item(i));
        }
    });
</script>

</body>
</html>
