class LoginForm {
    constructor() {
        let $form = $("#usernamePasswordForm"),
            pwMinLen = parseInt($("#passwordInput").data("min-len") as string, 10);
        $form.find("input[name=createAccount]").on("change", function () {
            if ($(this).prop("checked")) {
                $("#pwdConfirm").removeClass('hidden');
                $("#regName").removeClass('hidden').find("input").attr("required", "required");
                $("#passwordInput").attr("placeholder", __t("std", "pw_min_x_chars").replace(/%NUM%/, pwMinLen.toString(10)));
                $("#createStr").removeClass('hidden');
                $("#loginStr").addClass('hidden');
                $("#regConfirmation").removeClass('hidden');
                $("#regConfirmation").find("input").attr("required", "required");
                $(".managedAccountHint").removeClass('hidden');
            } else {
                $("#pwdConfirm").addClass('hidden');
                $("#regName").addClass('hidden').find("input").removeAttr("required");
                $("#passwordInput").attr("placeholder", "");
                $("#createStr").addClass('hidden');
                $("#loginStr").removeClass('hidden');
                $("#regConfirmation").addClass('hidden');
                $("#regConfirmation").find("input").removeAttr("required");
                $(".managedAccountHint").addClass('hidden');
            }
        }).trigger("change");
        $form.on("submit", (ev) => {
            let pwd = $("#passwordInput").val() as string;
            if (pwd.length < pwMinLen && $form.find("input[name=createAccount]").prop("checked")) {
                ev.preventDefault();
                bootbox.alert(__t("std", "pw_x_chars").replace(/%NUM%/, pwMinLen.toString(10)));
            }
            if ($form.find("input[name=createAccount]").prop("checked")) {
                if (pwd != $("#passwordConfirm").val()) {
                    ev.preventDefault();
                    bootbox.alert(__t("std", "pw_no_match"));
                }
            }
        });
    }
}

new LoginForm();
