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
    <main v-if="imotion && !isTwoColumnLayout" class="motionTextHolder">
        <section v-for="section in imotion.sections" class="paragraph lineNumbers" :class="[section.type]" v-if="section.html !== ''">
            <h2 v-if="showSectionTitle(section)">{{ section.title }}</h2>
            <div v-html="section.html"></div>
        </section>
    </main>
    <main v-if="imotion && isTwoColumnLayout" class="motionTextHolder row">
        <div class="col-md-8">
            <section v-for="section in leftSections" class="paragraph lineNumbers" :class="[section.type]"  v-if="section.html !== ''">
                <h2 v-if="showSectionTitle(section)">{{ section.title }}</h2>
                <div v-html="section.html"></div>
            </section>
        </div>
        <div class="col-md-4">
            <section v-for="section in rightSections" class="paragraph lineNumbers" :class="[section.type]"  v-if="section.html !== ''">
                <h2 v-if="showSectionTitle(section)">{{ section.title }}</h2>
                <div v-html="section.html"></div>
            </section>
        </div>
    </main>
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
        computed: {
            isTwoColumnLayout: function () {
                return this.imotion && this.imotion.sections && this.imotion.sections.find(section => {
                    return section.layout_right;
                });
            },
            leftSections: function () {
                return this.imotion.sections.filter(section => {
                    return !section.layout_right;
                });
            },
            rightSections: function () {
                return this.imotion.sections.filter(section => {
                    return section.layout_right;
                });
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
                        widget.imotion = data;
                        widget.selectedIMotionId = data.type + '-' + data.id;
                        widget.$emit('changed', data);
                    })
                    .catch(err => alert(err));
            },
            loadInitIMotion: function () {
                this.loadIMotion(this.initdata.init_imotion_url);
            },
            showSectionTitle: function (section) {
                return !section.layout_right && ['Image', 'PDFAttachment', 'PDFAlternative'].indexOf(section.type) === -1;
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
