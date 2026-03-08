// @ts-check

export class LoginForm {

    /** @type {JQueryEl} */
    $form;

    /** @type {number} */
    pwMinLen;

    constructor() {
        this.$form = $("#usernamePasswordForm");
        this.pwMinLen = parseInt($("#passwordInput").data("min-len"), 10);

        // Toggle registration fields when "createAccount" checkbox changes
        this.$form.find("input[name=createAccount]").on("change", this.toggleCreateAccountFields).trigger("change");

        // Form submission validation
        this.$form.on("submit", this.onSubmit);
    }

    /** @param {JQuery.TriggeredEvent} ev */
    toggleCreateAccountFields = (ev) => {
        const $checkbox = $(ev.currentTarget);
        if ($checkbox.prop("checked")) {
            $("#pwdConfirm").removeClass('hidden');
            $("#regName").removeClass('hidden').find("input").attr("required", "required");
            $("#passwordInput").attr("placeholder", __t("std", "pw_min_x_chars").replace(/%NUM%/, this.pwMinLen.toString()));
            $("#createStr").removeClass('hidden');
            $("#loginStr").addClass('hidden');
            $("#regConfirmation").removeClass('hidden').find("input").attr("required", "required");
            $(".managedAccountHint").removeClass('hidden');
        } else {
            $("#pwdConfirm").addClass('hidden');
            $("#regName").addClass('hidden').find("input").removeAttr("required");
            $("#passwordInput").attr("placeholder", "");
            $("#createStr").addClass('hidden');
            $("#loginStr").removeClass('hidden');
            $("#regConfirmation").addClass('hidden').find("input").removeAttr("required");
            $(".managedAccountHint").addClass('hidden');
        }
    }

    /** @param {JQuery.TriggeredEvent} ev */
    onSubmit = (ev) => {
        const pwd = $("#passwordInput").val() || "";
        const isCreate = this.$form.find("input[name=createAccount]").prop("checked");

        if (isCreate && pwd.length < this.pwMinLen) {
            ev.preventDefault();
            bootbox.alert(__t("std", "pw_x_chars").replace(/%NUM%/, this.pwMinLen.toString()));
            return;
        }

        if (isCreate) {
            const pwdConfirm = $("#passwordConfirm").val() || "";
            if (pwd !== pwdConfirm) {
                ev.preventDefault();
                bootbox.alert(__t("std", "pw_no_match"));
            }
        }
    }
}
