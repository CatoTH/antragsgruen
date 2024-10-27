const POLICY_USER_GROUPS = 6;

class PolicySetter {
    private $select: JQuery;
    private readonly loadUrl: string|null;

    constructor(private $widget: JQuery) {
        this.$select = this.$widget.find('.userGroupSelect')
        this.loadUrl = this.$select.data('load-url');

        this.initGroupSelector();
        this.initPolicySelector();
    }

    private initPolicySelector(): void
    {
        const $policySelect = this.$widget.find(".policySelect");
        $policySelect.on("change", () => {
            if (parseInt($policySelect.val() as string, 10) === POLICY_USER_GROUPS) {
                this.$select.removeClass("hidden");
            } else {
                this.$select.addClass("hidden");
            }
        }).trigger("change");
    }

    private initGroupSelector(): void
    {
        let selectizeOption = {};
        if (this.loadUrl) {
            const loadUrl = this.loadUrl;
            selectizeOption = Object.assign(selectizeOption, {
                loadThrottle: null,
                valueField: 'id',
                labelField: 'label',
                searchField: 'label',
                load: function (query, cb) {
                    if (!query) return cb();
                    return $.get(loadUrl, {query}).then(res => {
                        cb(res);
                    });
                },
                render: {
                    option_create: (data, escape) => {
                        return '<div class="create">' + __t('std', 'add_tag') + ': <strong>' + escape(data.input) + '</strong></div>';
                    }
                }
            });
        }
        (this.$select.find("select") as any).selectize(selectizeOption);
    }
}
