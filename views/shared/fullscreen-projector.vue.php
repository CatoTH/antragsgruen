<?php

use app\components\UrlHelper;

ob_start();
?>

<article class="projectorWidget">
    <header>
        <div class="imotionSelector">
            <select v-if="imotions" v-model="selectedIMotionId" @change="onChangeSelectedIMotion()" class="stdDropdown">
                <template v-for="imotion in imotions">
                    <option :value="imotion.type + '-' + imotion.id">{{ imotion.title_with_prefix }}</option>
                    <option v-if="imotion.amendment_links" v-for="amendment in imotion.amendment_links" :value="'amendment-' + amendment.id">▸ {{ amendment.prefix }}</option>
                </template>
            </select>
        </div>

        <button class="btn btn-link closeBtn" type="button" @click="close()" title="<?= Yii::t('base', 'aria_close') ?>" aria-label="<?= Yii::t('base', 'aria_close') ?>">
            <span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
        </button>

        <h1 v-if="imotion" class="hidden">{{ imotion.title_with_prefix }}</h1>
    </header>

    <fullscreen-imotion v-if="imotion" :imotion="imotion"></fullscreen-imotion>
</article>
<?php
$html = ob_get_clean();

?>

<script>
    const iMotionListUrl = <?= json_encode(UrlHelper::createUrl(['/consultation/rest'])) ?>;

    Vue.component('fullscreen-projector', {
        template: <?= json_encode($html) ?>,
        props: ['initdata'],
        data() {
            return {
                consultationUrl: null,
                imotions: null,
                imotion: null,
                selectedIMotionId: null
            }
        },
        methods: {
            loadIMotionList: function() {
                const widget = this;
                fetch(this.consultationUrl)
                    .then(response => {
                        if (!response.ok) throw response.statusText;
                        return response.json();
                    })
                    .then(data => widget.imotions = data.motion_links)
                    .catch(err => alert(err));
            },
            loadIMotion: function (url) {
                const widget = this;
                const urlWithParams = url + '?lineNumbers=true';
                fetch(urlWithParams)
                    .then(response => {
                        if (!response.ok) throw response.statusText;
                        return response.json();
                    })
                    .then(data => {
                        console.log(JSON.parse(JSON.stringify(data)));
                        widget.imotion = data;
                        widget.selectedIMotionId = data.type + '-' + data.id;
                        widget.$emit('changed', data);
                    })
                    .catch(err => alert(err));
            },
            loadInitIMotion: function () {
                this.loadIMotion(this.initdata.init_imotion_url);
            },
            onChangeSelectedIMotion: function () {
                let found = null;
                this.imotions.forEach(imotion => {
                    if ((imotion.type + '-' + imotion.id) === this.selectedIMotionId) {
                        found = imotion;
                    }
                    if (imotion.amendment_links) {
                        imotion.amendment_links.forEach(amendment => {
                            if (('amendment-' + amendment.id) === this.selectedIMotionId) {
                                found = amendment;
                            }
                        });
                    }
                });
                if (found) {
                    this.loadIMotion(found.url_json);
                }
            },
            close: function () {
                this.$emit('close', this.imotion.url_html);
            }
        },
        created() {
            this.consultationUrl = this.initdata.consultation_url;
            this.loadIMotionList();
            this.loadInitIMotion();
        }
    });
</script>
