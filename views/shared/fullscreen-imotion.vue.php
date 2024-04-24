<?php
ob_start();
?>
<main v-if="!isTwoColumnLayout" class="motionTextHolder" :class="{'isAmendment': isAmendment}">
    <fullscreen-imotion-header :imotion="imotion"></fullscreen-imotion-header>
    <section v-for="section in nonEmptySections" class="paragraph lineNumbers" :class="[section.type]">
        <h2 v-if="showSectionTitle(section)">{{ section.title }}</h2>
        <div v-html="section.html"></div>
    </section>
</main>
<main v-if="isTwoColumnLayout" class="motionTextHolder motionTwoCols" :class="{'isAmendment': isAmendment}">
    <div class="motionMainCol">
        <section v-for="section in nonEmptyLeftSections" class="paragraph lineNumbers" :class="[section.type]">
            <h2 v-if="showSectionTitle(section)">{{ section.title }}</h2>
            <div v-html="section.html"></div>
        </section>
    </div>
    <div class="motionRightCol">
        <fullscreen-imotion-header :imotion="imotion"></fullscreen-imotion-header>
        <section v-for="section in nonEmptyRightSections" class="paragraph lineNumbers" :class="[section.type]">
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
    __setVueComponent('fullscreen', 'component', 'fullscreen-imotion-header', {
        template: <?= json_encode($htmlHeader) ?>,
        props: ['imotion']
    });

    __setVueComponent('fullscreen', 'component', 'fullscreen-imotion', {
        template: <?= json_encode($htmlMain) ?>,
        props: ['imotion'],
        computed: {
            isTwoColumnLayout: function () {
                return this.imotion.sections && this.imotion.sections.find(section => {
                    return section.layout_right;
                });
            },
            nonEmptyLeftSections: function () {
                return this.imotion.sections.filter(section => {
                    return !section.layout_right && section.html !== '';
                });
            },
            nonEmptyRightSections: function () {
                return this.imotion.sections.filter(section => {
                    return section.layout_right && section.html !== '';
                });
            },
            nonEmptySections: function () {
                let sections = [];
                if (this.imotion.proposed_procedure && this.imotion.proposed_procedure.sections) {
                    this.imotion.proposed_procedure.sections.forEach(section => {
                        if (section.html !== '') sections.push(section);
                    });
                }
                this.imotion.sections.forEach(section => {
                    if (section.html !== '') sections.push(section);
                });
                return sections;
            },
            isAmendment: function () {
                return this.imotion && this.imotion.type === 'amendment';
            }
        },
        methods: {
            showSectionTitle: function (section) {
                return !section.layout_right && ['Image', 'PDFAttachment', 'PDFAlternative'].indexOf(section.type) === -1;
            },
        }
    });
</script>
