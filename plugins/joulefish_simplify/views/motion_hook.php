<?php
use yii\helpers\Url;
use yii\helpers\Html;
?>

<div class="joulefish-simplify-panel panel panel-default mt-4 mb-4">
    <div class="panel-heading d-flex justify-content-between align-items-center">
        <h4 class="panel-title mb-0">
            <i class="fa fa-universal-access"></i>
            <?= Html::encode(Yii::t('joulefish_simplify', 'title')) ?>
        </h4>
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-primary btn-sm" id="btn-simplify-plain" data-mode="plain">
                <?= Html::encode(Yii::t('joulefish_simplify', 'mode_plain')) ?>
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm" id="btn-simplify-easy" data-mode="easy">
                <?= Html::encode(Yii::t('joulefish_simplify', 'mode_easy')) ?>
            </button>
        </div>
    </div>

    <div class="panel-body p-3">
        <div id="simplify-spinner" style="display: none;" class="text-center p-3">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted"><?= Html::encode(Yii::t('joulefish_simplify', 'loading')) ?></p>
        </div>

        <div id="simplify-content">
            <p class="text-muted mb-0">
                <em><?= Html::encode(Yii::t('joulefish_simplify', 'prompt')) ?></em>
            </p>
        </div>
    </div>
</div>

<style>
.joulefish-simplify-panel .simplify-result-content {
    line-height: 1.6;
    font-size: 1.05rem;
}
.joulefish-simplify-panel .simplify-result-content h1,
.joulefish-simplify-panel .simplify-result-content h2,
.joulefish-simplify-panel .simplify-result-content h3,
.joulefish-simplify-panel .simplify-result-content h4 {
    margin-top: 0.8rem;
    margin-bottom: 0.5rem;
    font-weight: 600;
}
.joulefish-simplify-panel .simplify-result-content p {
    margin-bottom: 0.75rem;
}
.joulefish-simplify-panel .simplify-result-content p:last-child {
    margin-bottom: 0;
}
.joulefish-simplify-panel .simplify-result-content ul,
.joulefish-simplify-panel .simplify-result-content ol {
    margin-left: 1.5rem;
    margin-bottom: 0.75rem;
}
.joulefish-simplify-panel .simplify-result-content li {
    margin-bottom: 0.25rem;
}
</style>

<script>
(function() {
    const plainBtn = document.getElementById('btn-simplify-plain');
    const easyBtn = document.getElementById('btn-simplify-easy');
    if (!plainBtn || !easyBtn) return;

    const errorPrefix = <?= json_encode(Yii::t('joulefish_simplify', 'error_prefix')) ?>;
    const networkErrorPrefix = <?= json_encode(Yii::t('joulefish_simplify', 'network_error_prefix')) ?>;
    const defaultStyleTitle = <?= json_encode(Yii::t('joulefish_simplify', 'default_style_title')) ?>;
    const currentLang = <?= json_encode(Yii::$app->language) ?>;

    [plainBtn, easyBtn].forEach(button => {
        button.addEventListener('click', function() {
            const mode = this.getAttribute('data-mode');
            const spinner = document.getElementById('simplify-spinner');
            const content = document.getElementById('simplify-content');

            if (spinner) spinner.style.display = 'block';
            if (content) content.style.opacity = '0.4';

            const postData = new URLSearchParams({
                mode: mode,
                id: '<?= (int)$motion->id ?>',
                lang: currentLang
            });

            fetch('<?= Url::to(['/joulefish_simplify/simplify/process', 'id' => $motion->id]) ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>'
                },
                body: postData
            })
            .then(response => response.json())
            .then(data => {
                if (spinner) spinner.style.display = 'none';
                if (content) content.style.opacity = '1';

                if (data.success) {
                    const styleTitle = data.styleApplied
                        ? ('<h5 class="mb-3 font-weight-bold"><strong>' + data.styleApplied + '</strong></h5>')
                        : ('<h5 class="mb-3 font-weight-bold"><strong>' + defaultStyleTitle + '</strong></h5>');
                    const renderedHtml = data.simplifiedHtml || data.simplifiedText.replace(/\n/g, '<br>');
                    content.innerHTML = '<div class="alert alert-success mb-0 simplify-result-content">' +
                        styleTitle +
                        renderedHtml +
                        '</div>';
                } else {
                    content.innerHTML = '<div class="alert alert-danger mb-0">' +
                        '<strong>' + errorPrefix + ':</strong> ' + (data.error || 'Unknown error') +
                        '</div>';
                }
            })
            .catch(err => {
                if (spinner) spinner.style.display = 'none';
                if (content) content.style.opacity = '1';
                content.innerHTML = '<div class="alert alert-danger mb-0">' +
                    '<strong>' + networkErrorPrefix + ':</strong> ' + err.message +
                    '</div>';
            });
        });
    });
})();
</script>
