<?php
ob_start();
?>
<article class="projectorWidget">
    <header>
        <div class="imotionSelector">
            <select v-if="imotions || pages" v-model="dropdownSelection" @change="onChangeSelectedContent()" class="stdDropdown">
                <option :value="'speech'" v-if="hasOneSpeakingList"><?= Yii::t('speech', 'fullscreen_title_s') ?></option>
                <!-- @TODO Multiple speaking lists -->
                <template v-for="imotion in imotions">
                    <option :value="imotion.type + '-' + imotion.id">{{ imotion.title_with_prefix }}</option>
                    <option v-for="amendment in getImotionAmendmentLinks(imotion)" :value="'amendment-' + amendment.id">▸ {{ amendment.prefix }}</option>
                </template>
                <option v-if="pages" disabled><?= Yii::t('pages', 'fullscr_select') ?>:</option>
                <option v-for="page in pages" :value="'page-' + page.id">▸ {{ page.title }}</option>
            </select>
        </div>

        <h1 v-if="imotion" class="hidden">{{ imotion.title_with_prefix }}</h1>
    </header>

    <fullscreen-imotion v-if="imotion" :imotion="imotion"></fullscreen-imotion>

    <fullscreen-speech v-if="dropdownSelection === 'speech'" :initQueue="selectedSpeakingList" :user="null" :csrf="null" :title="'Speaking List'"></fullscreen-speech>

    <main class="contentPage" v-if="page">
        <div class="content" v-html="page.html"></div>
    </main>

</article>
<?php
$htmlPanel = ob_get_clean();


ob_start();
?>
<div class="fullscreenMainHolder" :class="{'splitscreen': splitscreen}">
    <header>
        <button class="btn btn-link splitscreenBtn" type="button" @click="toggleSplitscreen()"
                title="<?= Yii::t('pages', 'fullscr_split') ?>" aria-label="<?= Yii::t('pages', 'fullscr_split') ?>">
            <span class="glyphicon glyphicon-pause" aria-hidden="true"></span>
        </button>
        <button class="btn btn-link closeBtn" type="button" @click="close()" title="<?= Yii::t('base', 'aria_close') ?>" aria-label="<?= Yii::t('base', 'aria_close') ?>">
            <span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
        </button>
    </header>

    <fullscreen-panel class="primary" :class="{'splitscreen': splitscreen}" :initdata="initdata" @changed="onFirstContentChanged($event)"></fullscreen-panel>

    <fullscreen-panel class="secondary" :class="{'splitscreen': splitscreen}" :initdata="initdata" v-if="splitscreen"></fullscreen-panel>
</div>
<?php
$htmlMain = ob_get_clean();
?>


<script>
    __setVueComponent('fullscreen', 'component', 'fullscreen-projector', {
        template: <?= json_encode($htmlMain) ?>,
        props: ['initdata'],
        data() {
            return {
                splitscreen: false,
                url_html: null
            }
        },
        methods: {
            close: function () {
                this.$emit('close', this.url_html);
            },
            onFirstContentChanged: function ($event) {
                this.url_html = $event.url_html;
                this.$emit('changed', $event);
            },
            toggleSplitscreen: function () {
                this.splitscreen = !this.splitscreen;
            }
        }
    });

    __setVueComponent('fullscreen', 'component', 'fullscreen-panel', {
        template: <?= json_encode($htmlPanel) ?>,
        props: ['initdata'],
        data() {
            return {
                consultationUrl: null,
                imotions: null,
                speakingLists: null,
                pages: null,
                imotion: null,
                page: null,
                dropdownSelection: null
            }
        },
        computed: {
            hasOneSpeakingList: function() {
                return this.speakingLists && this.speakingLists.length === 1;
            },
            hasMultipleSpeakingLists: function () {
                return this.speakingLists && this.speakingLists.length > 1;
            },
            selectedSpeakingList: function () {
                return this.dropdownSelection === 'speech' && this.speakingLists ? this.speakingLists[0] : null;
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
                    .then(data => {
                        widget.imotions = data.motion_links;
                        widget.speakingLists = data.speaking_lists;
                        widget.pages = data.page_links;
                    })
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
                        widget.page = null;
                        widget.imotion = data;
                        widget.dropdownSelection = data.type + '-' + data.id;
                        widget.$emit('changed', data);
                    })
                    .catch(err => alert(err));
            },
            loadPage: function (url) {
                const widget = this;
                fetch(url)
                    .then(response => {
                        if (!response.ok) throw response.statusText;
                        return response.json();
                    })
                    .then(data => {
                        widget.page = data;
                        widget.imotion = null;
                        widget.dropdownSelection = 'page-' + data.id;
                        widget.$emit('changed', data);
                    })
                    .catch(err => alert(err));
            },
            loadInitContent: function () {
                const documentType = this.initdata.init_page ? this.initdata.init_page.split("-")[0] : '';
                if (documentType === 'motion' || documentType === 'amendment') {
                    this.loadIMotion(this.initdata.init_content_url);
                }
                if (documentType === 'page') {
                    this.loadPage(this.initdata.init_content_url);
                }
                if (documentType === 'speech') {
                    this.dropdownSelection = 'speech';
                }
            },
            onChangeSelectedContent: function () {
                const documentType = this.dropdownSelection ? this.dropdownSelection.split("-")[0] : '';
                if (documentType === 'motion' || documentType === 'amendment') {
                    this.onChangeSelectedIMotion();
                }
                if (documentType === 'page') {
                    this.onChangeSelectedPage();
                }
                if (documentType === 'speech') {
                    this.imotion = null;
                    this.page = null;
                }
            },
            onChangeSelectedIMotion: function () {
                let foundImotion = null;
                this.imotions.forEach(imotion => {
                    if ((imotion.type + '-' + imotion.id) === this.dropdownSelection) {
                        foundImotion = imotion;
                    }
                    if (imotion.amendment_links) {
                        imotion.amendment_links.forEach(amendment => {
                            if (('amendment-' + amendment.id) === this.dropdownSelection) {
                                foundImotion = amendment;
                            }
                        });
                    }
                });
                if (foundImotion) {
                    this.loadIMotion(foundImotion.url_json);
                } else {
                    this.imotion = null;
                }
            },
            onChangeSelectedPage: function () {
                let foundPage = null;
                this.pages.forEach(page => {
                    if ('page-' + page.id === this.dropdownSelection) {
                        foundPage = page;
                    }
                });
                console.log(foundPage);
                if (foundPage) {
                    this.loadPage(foundPage.url_json);
                } else {
                    this.page = null;
                }
            },
            getImotionAmendmentLinks: function (imotion) {
                return imotion.amendment_links ? imotion.amendment_links : [];
            }
        },
        created() {
            this.consultationUrl = this.initdata.consultation_url;
            this.loadIMotionList();
            this.loadInitContent();
        }
    });
</script>
