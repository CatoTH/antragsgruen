<?php

ob_start();
?>

<article class="projectorWidget">
    <header>
        <div class="imotionSelector">
            <select v-if="imotions" v-model="dropdownSelection" @change="onChangeSelectedIMotion()" class="stdDropdown">
                <option :value="'speech'" v-if="hasOneSpeakingList"><?= Yii::t('speech', 'fullscreen_title_s') ?></option>
                <!-- @TODO Multiple speaking lists -->
                <template v-for="imotion in imotions">
                    <option :value="imotion.type + '-' + imotion.id">{{ imotion.title_with_prefix }}</option>
                    <option v-for="amendment in getImotionAmendmentLinks(imotion)" :value="'amendment-' + amendment.id">▸ {{ amendment.prefix }}</option>
                </template>
            </select>
        </div>

        <button class="btn btn-link closeBtn" type="button" @click="close()" title="<?= Yii::t('base', 'aria_close') ?>" aria-label="<?= Yii::t('base', 'aria_close') ?>">
            <span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
        </button>

        <h1 v-if="imotion" class="hidden">{{ imotion.title_with_prefix }}</h1>
    </header>

    <fullscreen-imotion v-if="imotion" :imotion="imotion"></fullscreen-imotion>

    <fullscreen-speech v-if="dropdownSelection === 'speech'" :queue="selectedSpeakingList" :user="null" :csrf="null" :title="'Speaking List'"></fullscreen-speech>
</article>
<?php
$html = ob_get_clean();

?>

<script>
    __setVueComponent('fullscreen', 'component', 'fullscreen-projector', {
        template: <?= json_encode($html) ?>,
        props: ['initdata'],
        data() {
            return {
                consultationUrl: null,
                imotions: null,
                speakingLists: null,
                imotion: null,
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
                        widget.imotion = data;
                        widget.dropdownSelection = data.type + '-' + data.id;
                        widget.$emit('changed', data);
                    })
                    .catch(err => alert(err));
            },
            loadInitContent: function () {
                if (this.initdata.init_imotion_url) {
                    this.loadIMotion(this.initdata.init_imotion_url);
                } else if (this.initdata.init_page === 'speech') {
                    this.dropdownSelection = 'speech';
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
            getImotionAmendmentLinks: function (imotion) {
                return imotion.amendment_links ? imotion.amendment_links : [];
            },
            close: function () {
                this.$emit('close', this.imotion.url_html);
            }
        },
        created() {
            this.consultationUrl = this.initdata.consultation_url;
            this.loadIMotionList();
            this.loadInitContent();
        }
    });
</script>
