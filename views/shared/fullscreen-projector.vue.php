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
                    <optgroup label="" v-if="imotion.amendment_links">
                        <option v-for="amendment in imotion.amendment_links" :value="'amendment-' + amendment.id">{{ amendment.prefix }}</option>
                    </optgroup>
                </template>
            </select>
        </div>

        <h1 v-if="imotion">{{ imotion.title_with_prefix }}</h1>
    </header>
    <main v-if="imotion" class="motionTextHolder">
        <section v-for="section in imotion.sections" class="paragraph lineNumbers" v-if="section.html !== ''">
            <h2>{{ section.title }}</h2>
            <div v-if="section.type === 'TextSimple'" v-html="section.html"></div>
        </section>
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
        computed: {},
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
            }
        },
        created() {
            this.consultationUrl = this.initdata.consultation_url;
            this.loadIMotionList();
            this.loadInitIMotion();
        }
    });
</script>
