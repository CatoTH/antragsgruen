declare let Isotope: any;

export class ConsultationDiscussionTags {
    private grid;
    private currTagFilter = '*';
    private currSort = 'phase';
    private currSortAsc = true;

    constructor(private $widget: JQuery) {
        this.initComments();
        this.initTags();
    }

    private initComments() {
        this.$widget.find(".commentListHolder .showAllComments button").on("click", () => {
            this.$widget.find(".expandableRecentComments").removeClass("shortened");
        });
    }

    private initTags() {
        this.grid = new Isotope('.motionListFilter .motionListFiltered', {
            itemSelector: '.sortitem',
            layoutMode: 'vertical',
            getSortData: {
                title: '[data-title]',
                titlePrefix: '[data-title-prefix]',
                created: '[data-created] parseInt',
                comments: '[data-num-comments] parseInt',
                amendments: '[data-num-amendments] parseInt'
            }
        });

        this.$widget.find(".tagList a").on("click", (ev) => {
            ev.preventDefault();

            const $tagBtn = $(ev.currentTarget);
            const filter = $tagBtn.data("filter");
            if (!filter) {
                return;
            }

            this.currTagFilter = filter;
            this.setFilters();

            this.$widget.find(".tagList a").removeClass("active");
            $tagBtn.addClass("active");
        });

        this.$widget.find(".motionListFilter .motionSort button").on("click", (ev) => {
            ev.preventDefault();

            const $sortBtn = $(ev.currentTarget);
            this.currSort = $sortBtn.data("sort");
            this.currSortAsc = ($sortBtn.data("order") !== 'desc');
            this.setFilters();

            this.$widget.find(".motionListFilter .motionSort button").removeClass("active");
            $sortBtn.addClass("active");
        });
    }

    private setFilters() {
        let filter = '';
        if (this.currTagFilter !== '*') {
            filter += this.currTagFilter;
        }
        if (this.currSort !== 'phase') {
            filter += '.motion';
        }
        if (filter === '') {
            filter = '*'
        }

        this.grid.arrange({
            filter: filter,
            sortBy: this.currSort,
            sortAscending: this.currSortAsc
        });
    }
}
