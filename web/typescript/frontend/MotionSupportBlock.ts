const CONTACT_REQUIRED = 2;

export class MotionSupportBlock {
    private settings: any;

    constructor(private $widget: JQuery) {
        console.log(this.$widget);
        console.log();
        this.settings = this.$widget.data("settings");
        this.$widget.on('submit', (ev) => {
            if (this.settings.contactGender === CONTACT_REQUIRED && this.$widget.find('[name=motionSupportGender]').val() === '') {
                ev.preventDefault();
                bootbox.alert(__t('std', 'missing_gender'));
            }
        });
    }
}
