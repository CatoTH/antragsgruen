export class MergeInit {
    private exportLinkTpl: string;

    constructor(private $widget: JQuery) {
        this.initExportBtn();
    }

    private recalcExportBtn() {
        let ids = [];
        this.$widget.find('.toMergeAmendments input[type=checkbox]:checked').each((idx, el: Element) => {
            ids.push(parseInt(el.getAttribute('name').split('[')[1]));
        });
        let link = this.exportLinkTpl.replace(/IDS/, ids.join(','));
        this.$widget.find('.exportHolder a').attr('href', link);
        console.log(ids);
    }

    private initExportBtn() {
        this.exportLinkTpl = this.$widget.find('.exportHolder a').attr('href');
        console.log(this.exportLinkTpl);

        this.$widget.on('change', '.toMergeAmendments input[type=checkbox]', () => {
            console.log('changed');
            this.recalcExportBtn();
        });
        this.recalcExportBtn();
    }
}
