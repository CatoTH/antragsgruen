<script>
    const selectizeComponent = {
        template: '<div><select class="form-control" :multiple="multiple" :disabled="disabled" :values="values" :create="create"></select></div>',
        props: {
            multiple: {
                type: Boolean,
                default: false
            },
            create: {
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
        watch: {
            values: function (newVal, oldVal) {
                this.selectizeElement[0].selectize.setValue(newVal);
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
                    items: this.values,
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
                if (this.create) {
                    selectizeOption.create = true;
                    selectizeOption.render = {
                        option_create: (data, escape) => {
                            return '<div class="create"><strong>' + escape(data.input) + '</strong></div>';
                        }
                    }
                }
                this.selectizeElement = $(this.$el).find("select").selectize(selectizeOption);

                $(this.$el).find("select").on("change", () => {
                    var val = this.selectizeElement.val();
                    if (typeof val !== "object") {
                        val = [val];
                    }
                    this.$emit('change', val);
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
