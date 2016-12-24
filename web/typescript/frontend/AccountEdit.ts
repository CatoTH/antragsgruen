class AccountEdit {
    constructor() {
        let pwMinLen = $("#userPwd").data("min-len");

        $('.accountDeleteForm input[name=accountDeleteConfirm]').change(function () {
            if ($(this).prop("checked")) {
                $(".accountDeleteForm button[name=accountDelete]").prop("disabled", false);
            } else {
                $(".accountDeleteForm button[name=accountDelete]").prop("disabled", true);
            }
        }).trigger('change');

        let $emailExisting = $('.emailExistingRow');
        if ($emailExisting.length == 1) {
            let $changeRow = $('.emailChangeRow');
            $changeRow.addClass('hidden');
            $(".requestEmailChange").click(function (ev) {
                ev.preventDefault();
                $changeRow.removeClass("hidden");
                $emailExisting.addClass("hidden");
                $changeRow.find("input").focus();
            });
        }

        $('.userAccountForm').submit(function (ev) {
            let pwd = $("#userPwd").val(),
                pwd2 = $("#userPwd2").val();
            if (pwd != '' || pwd2 != '') {
                if (pwd.length < pwMinLen) {
                    ev.preventDefault();
                    bootbox.alert(__t("std", "pw_x_chars").replace(/%NUM%/, pwMinLen));
                } else if (pwd != pwd2) {
                    ev.preventDefault();
                    bootbox.alert(__t("std", "pw_no_match"));
                }
            }
        });
    }
}

new AccountEdit();
