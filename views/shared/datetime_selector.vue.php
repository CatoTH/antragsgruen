<script>
    const dateTimePickerComponent = {
        template: '<div class="input-group date datetimepicker">\
            <input type="text" class="form-control" value="" autocomplete="off">\
            <span class="input-group-addon" aria-hidden="true"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></span>\
        </div>',
        props: {
            modelValue: {
                type: String,
                default: null
            },
            type: {
                type: String,
                default: null
            },
            locale: {
                type: String,
                default: null
            }
        },
        watch: {
            value: function (newVal, oldVal) {
                console.log(newVal);
            }
        },
        data() {
            return {
                pickerElement: null
            };
        },
        computed: {},
        methods: {
            initElement: function () {
                var format = '';
                let initDate = null;

                if (this.type === 'date') {
                    format = 'dddd, Do MMMM YYYY';
                    if (this.modelValue) {
                        initDate = moment(this.modelValue, "YYYY-MM-DD", this.locale);
                    }
                }
                if (this.type === 'time') {
                    format = 'LT';
                    if (this.modelValue) {
                        console.log(this.modelValue);
                        initDate = moment(this.modelValue, "HH:mm", this.locale);
                    }
                }

                this.pickerElement = $(this.$el).datetimepicker({
                    locale: this.locale,
                    format: format,
                    defaultDate: initDate
                });
                this.pickerElement.on("dp.change", (newDate) => {
                    if (this.type === 'date') {
                        this.$emit('update:modelValue', newDate.date.format("YYYY-MM-DD"));
                    }
                    if (this.type === 'time') {
                        this.$emit('update:modelValue', newDate.date.format("HH:mm"));
                    }
                })
            }
        },
        mounted() {
            this.initElement();
        },
        beforeUnmount() {
            this.pickerElement.datetimepicker("destroy");
        }
    };

    __setVueComponent('agenda', 'component', 'v-datetime-picker', dateTimePickerComponent);
</script>
