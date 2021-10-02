<?php

use app\components\UrlHelper;

ob_start();
?>

<article class="projectorWidget">
    <header>
        <div class="imotionSelector">
            <select v-if="imotions">
                <option v-for="imotion in imotions" :value="imotion.type + '-' + imotion.id">{{ imotion.title_with_prefix }}</option>
            </select>
        </div>

        <h1 v-if="imotion">{{ imotion.title_with_prefix }}</h1>
    </header>
    <main v-if="imotion" class="motionTextHolder">
        <section v-for="section in imotion.sections" class="paragraph lineNumbers">
            <h2>{{ section.title }}</h2>
            <div v-if="section.type === 'TextSimple'" class="text" v-html="section.html" class="text motionTextFormattings textOrig fixedWidthFont"></div>
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
                imotion: null
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
            loadInitIMotion: function () {
                const widget = this;
                fetch(this.initdata.init_imotion_url)
                    .then(response => {
                        if (!response.ok) throw response.statusText;
                        return response.json();
                    })
                    .then(data => widget.imotion = data)
                    .catch(err => alert(err));
            }
        },
        created() {
            this.consultationUrl = this.initdata.consultation_url;
            this.loadIMotionList();
            this.loadInitIMotion();
        }
    });
</script>
