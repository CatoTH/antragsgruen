class AccountEdit {
    constructor() {
        let pwMinLen = $("#userPwd").data("min-len");

        $('.accountDeleteForm input[name=accountDeleteConfirm]').on("change", function () {
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
            $(".requestEmailChange").on("click", (ev) => {
                ev.preventDefault();
                $changeRow.removeClass("hidden");
                $emailExisting.addClass("hidden");
                $changeRow.find("input").trigger("focus");;
            });
        }

        $('.userAccountForm').on('submit', function (ev) {
            let pwd = $("#userPwd").val() as string,
                pwd2 = $("#userPwd2").val() as string;
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

        if (document.querySelector('.btn2FaAdderOpen')) {
            document.querySelector('.btn2FaAdderOpen').addEventListener('click', () => {
                document.querySelector('.secondFactorAdderOpener').classList.add('hidden');
                document.querySelector('.secondFactorAdderBody').classList.remove('hidden');
            });
        }
        if (document.querySelector('.btn2FaRemoveOpen')) {
            document.querySelector('.btn2FaRemoveOpen').addEventListener('click', () => {
                document.querySelector('.secondFactorRemoveOpener').classList.add('hidden');
                document.querySelector('.secondFactorRemoveBody').classList.remove('hidden');
            });
        }
    }
}

new AccountEdit();
