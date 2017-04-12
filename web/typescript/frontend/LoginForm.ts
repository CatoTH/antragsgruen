class LoginForm {
    constructor() {
        let $form = $("#usernamePasswordForm"),
            pwMinLen = $("#passwordInput").data("min-len");
        $form.find("input[name=createAccount]").change(function () {
            if ($(this).prop("checked")) {
                $("#pwdConfirm").removeClass('hidden');
                $("#regName").removeClass('hidden').find("input").attr("required", "required");
                $("#passwordInput").attr("placeholder", __t("std", "pw_min_x_chars").replace(/%NUM%/, pwMinLen));
                $("#createStr").removeClass('hidden');
                $("#loginStr").addClass('hidden');
            } else {
                $("#pwdConfirm").addClass('hidden');
                $("#regName").addClass('hidden').find("input").removeAttr("required");
                $("#passwordInput").attr("placeholder", "");
                $("#createStr").addClass('hidden');
                $("#loginStr").removeClass('hidden');
            }
        }).trigger("change");
        $form.submit(function (ev) {
            let pwd = $("#passwordInput").val();
            if (pwd.length < pwMinLen) {
                ev.preventDefault();
                bootbox.alert(__t("std", "pw_x_chars").replace(/%NUM%/, pwMinLen));
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
