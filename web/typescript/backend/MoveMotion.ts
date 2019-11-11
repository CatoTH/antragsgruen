export class MoveMotion {
    constructor(private $form: JQuery) {
        this.initTarget();

        this.initConsultation();
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
            if (selected === "consultation") {
                this.$form.find(".moveToConsultationItem").removeClass('hidden');
            } else {
                this.$form.find(".moveToConsultationItem").addClass('hidden');
            }

            this.rebuildMotionTypes();
        }).trigger("change");
    }

    private initConsultation() {
        $("#consultationId").on("changed.fu.selectlist", this.rebuildMotionTypes.bind(this));
    }

    private rebuildMotionTypes() {
        const consultationId = $("#consultationId").find("input[name=consultation]").val();
        $(".moveToMotionTypeId").addClass("hidden");
        if (this.$form.find("input[name=target]:checked").val() === "consultation") {
            $(".moveToMotionTypeId" + consultationId).removeClass("hidden");
        }
    }
}
