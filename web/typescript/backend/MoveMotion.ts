export class MoveMotion {
    constructor(private $form: JQuery) {
        this.initTarget();
    }

    private initTarget() {
        const $target = this.$form.find("input[name=target]");
        $target.on("change", () => {
            const selected = $target.filter(":checked").val();
            if (selected === "agenda") {
                this.$form.find(".moveToAgendaItem").removeClass('hidden');
            } else {
                this.$form.find(".moveToAgendaItem").addClass('hidden');
            }
        }).trigger("change");
    }
}
