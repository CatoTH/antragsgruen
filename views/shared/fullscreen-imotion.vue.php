<?php
ob_start();
?>
<main v-if="!isTwoColumnLayout" class="motionTextHolder">
    <fullscreen-imotion-header :imotion="imotion"></fullscreen-imotion-header>
    <section v-for="section in imotion.sections" class="paragraph lineNumbers" :class="[section.type]" v-if="section.html !== ''">
        <h2 v-if="showSectionTitle(section)">{{ section.title }}</h2>
        <div v-html="section.html"></div>
    </section>
</main>
<main v-if="isTwoColumnLayout" class="motionTextHolder row">
    <div class="col-md-8">
        <section v-for="section in leftSections" class="paragraph lineNumbers" :class="[section.type]"  v-if="section.html !== ''">
            <h2 v-if="showSectionTitle(section)">{{ section.title }}</h2>
            <div v-html="section.html"></div>
        </section>
    </div>
    <div class="col-md-4">
        <fullscreen-imotion-header :imotion="imotion"></fullscreen-imotion-header>
        <section v-for="section in rightSections" class="paragraph lineNumbers" :class="[section.type]"  v-if="section.html !== ''">
            <h2 v-if="showSectionTitle(section)">{{ section.title }}</h2>
            <div v-html="section.html"></div>
        </section>
    </div>
</main>

<?php
$htmlMain = ob_get_clean();

ob_start();
?>
<table class="motionDataTable">
    <tbody>
    <tr v-if="imotion.initiators_html && imotion.initiators.length === 1">
        <th><?= Yii::t('motion', 'initiators_1') ?>:</th>
        <td>{{ imotion.initiators_html }}</td>
    </tr>
    <tr v-if="imotion.initiators_html && imotion.initiators.length > 1">
        <th><?= Yii::t('motion', 'initiators_x') ?>:</th>
        <td>{{ imotion.initiators_html }}</td>
    </tr>
    <tr v-if="imotion.status_title">
        <th><?= Yii::t('motion', 'status') ?>:</th>
        <td v-html="imotion.status_title"></td>
    </tr>
    <tr v-if="imotion.proposed_procedure && imotion.proposed_procedure.status_title">
        <th><?= Yii::t('amend', 'proposed_status') ?>:</th>
        <td v-html="imotion.proposed_procedure.status_title"></td>
    </tr>
    </tbody>
</table>
<?php
$htmlHeader = ob_get_clean();
?>

<script>
    Vue.component('fullscreen-imotion-header', {
        template: <?= json_encode($htmlHeader) ?>,
        props: ['imotion']
    });

    Vue.component('fullscreen-imotion', {
        template: <?= json_encode($htmlMain) ?>,
        props: ['imotion'],
        computed: {
            isTwoColumnLayout: function () {
                return this.imotion.sections && this.imotion.sections.find(section => {
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
            showSectionTitle: function (section) {
                return !section.layout_right && ['Image', 'PDFAttachment', 'PDFAlternative'].indexOf(section.type) === -1;
            },
        }
    });
</script>
