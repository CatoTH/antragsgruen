<script>
    let selectizeComponent = {
        template: '<div><select class="form-control" :multiple="multiple" :disabled="disabled"></select></div>',
        props: {
            multiple: {
                type: Boolean,
                default: false
            },
            disabled: {
                type: Boolean,
                default: false
            },
            options: {
                type: Array,
                default: () => []
            },
            values: {
                type: Array,
                default: () => []
            },
            loadUrl: {
                type: String,
                default: null
            }
        },
        data() {
            return {
                selectizeElement: null
            };
        },
        methods: {
            initElement: function () {
                // https://selectize.dev/docs.html#usage
                let selectizeOption = {
                    valueField: 'id',
                    labelField: 'label',
                    searchField: 'label',
                    options: this.options,
                    items: this.values
                };
                if (this.loadUrl) {
                    const loadUrl = this.loadUrl;
                    selectizeOption = Object.assign(selectizeOption, {
                        loadThrottle: null,
                        load: function (query, cb) {
                            if (!query) return cb();
                            return $.get(loadUrl, {query}).then(res => {
                                cb(res);
                            });
                        }
                    });
                }
                this.selectizeElement = $(this.$el).find("select").selectize(selectizeOption);

                $(this.$el).find("select").on("change", () => {
                    this.$emit('change', this.selectizeElement.val());
                });
            },
        },
        mounted() {
            this.initElement();
        },
        beforeUnmount() {
            this.selectizeElement.selectize("destroy");
        }
    };
    __setVueComponent('voting', 'component', 'v-selectize', selectizeComponent);
    __setVueComponent('users', 'component', 'v-selectize', selectizeComponent);
</script>
