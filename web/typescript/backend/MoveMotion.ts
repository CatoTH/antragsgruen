export class MoveMotion {
    private checkBackend: string;

    constructor(private $form: JQuery) {
        this.checkBackend = $form.data('check-backend');
        this.initTarget();
        this.initConsultation();
        this.initButtonEnabled();
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

    private isPrefixAvailable(prefix: string, consultation: number): Promise<boolean> {
        return $.get(this.checkBackend, {
            checkType: 'prefix',
            newMotionPrefix: prefix,
            newConsultationId: consultation
        }).then(res => {
            console.log(res);
            return res;
        });
    }

    private async rebuildButtonEnabled() {
        let isEnabled = true;

        let consultationId;
        if (this.$form.find('input[name=target]:checked').val() === 'consultation') {
            consultationId = parseInt(this.$form.find('input[name=consultation]').val() as string);
        } else {
            consultationId = null;
        }

        const prefixIsAvailable = await this.isPrefixAvailable(this.$form.find('#motionTitlePrefix').val() as string, consultationId);
        if (prefixIsAvailable) {
            this.$form.find(".prefixAlreadyTaken").addClass("hidden");
        } else {
            this.$form.find(".prefixAlreadyTaken").removeClass("hidden");
            isEnabled = false;
        }

        if (!this.$form.find('input[name=operation]:checked').val()) {
            isEnabled = false;
        }
        if (!this.$form.find('input[name=target]:checked').val()) {
            isEnabled = false;
        }

        this.$form.find("button[type=submit]").prop("disabled", !isEnabled);
    }

    private initButtonEnabled() {
        this.$form.find('#motionTitlePrefix').on('change keyup', this.rebuildButtonEnabled.bind(this));
        this.$form.find('input[name=operation]').on('change', this.rebuildButtonEnabled.bind(this));
        this.$form.find('input[name=target]').on('change', this.rebuildButtonEnabled.bind(this));
        $("#consultationId").on("changed.fu.selectlist", this.rebuildButtonEnabled.bind(this));
        this.rebuildButtonEnabled();
    }
}
