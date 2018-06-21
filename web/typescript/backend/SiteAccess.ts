class SiteAccess {
    private $adminForm: JQuery;

    constructor() {
        this.initSite();
        this.initUserList();
        this.initAddUsers();
        this.initAdmins();
    }

    private initSite() {
        $(".managedUserAccounts input").change(function () {
            if ($(this).prop("checked")) {
                $(".showManagedUsers").removeClass('hidden');
            } else {
                $(".showManagedUsers").addClass('hidden');
            }
        }).trigger("change");
    }

    private initAddUsers() {
        $(".addUsersOpener").click((ev) => {
            const type = $(ev.currentTarget).data("type");
            $(".addUsersByLogin").addClass("hidden");
            $(".addUsersByLogin." + type).removeClass("hidden");
        });

        $("#accountsCreateForm").submit((ev) => {
            if (!$(".addUsersByLogin.email").hasClass("hidden")) {
                let text = $("#emailText").val();
                if (text.indexOf("%ACCOUNT%") == -1) {
                    bootbox.alert(__t("admin", "emailMissingCode"));
                    ev.preventDefault();
                }
                if (text.indexOf("%LINK%") == -1) {
                    bootbox.alert(__t("admin", "emailMissingLink"));
                    ev.preventDefault();
                }

                let emails = $("#emailAddresses").val().split("\n"),
                    names = $("#names").val().split("\n");
                if (emails.length == 1 && emails[0] == "") {
                    ev.preventDefault();
                    bootbox.alert(__t("admin", "emailMissingTo"));
                }
                if (emails.length != names.length) {
                    bootbox.alert(__t("admin", "emailNumberMismatch"));
                    ev.preventDefault();
                }
            }
            if (!$(".addUsersByLogin.samlWW").hasClass("hidden")) {
                if ($("#samlWW").val() === "") {
                    ev.preventDefault();
                    bootbox.alert(__t("admin", "emailMissingUsername"));
                }
            }
        });
    }

    private initUserList() {
        $('.accountListTable .accessViewCol input[type=checkbox]').change(function () {
            if (!$(this).prop("checked")) {
                $(this).parents('tr').first().find('.accessCreateCol input[type=checkbox]').prop('checked', false);
            }
        });
        $('.accountListTable .accessCreateCol input[type=checkbox]').change(function () {
            if ($(this).prop("checked")) {
                $(this).parents('tr').first().find('.accessViewCol input[type=checkbox]').prop('checked', true);
            }
        });
    }

    private initAdmins() {
        this.$adminForm = $("#adminForm");

        this.$adminForm.on('click', '.removeAdmin', (ev) => {
            let $button = $(ev.currentTarget),
                $form = $(ev.currentTarget).parents("form").first();

            bootbox.confirm(__t("admin", "removeAdminConfirm"), function (result) {
                if (result) {
                    let id = $button.data("id");
                    $form.append('<input type="hidden" name="removeAdmin" value="' + id + '">');
                    $form.submit();
                }
            });
        });

        this.$adminForm.on('change', '.adminCard .typeSite input', (ev) => {
            let $card = $(ev.currentTarget).parents('.adminCard').first();
            if ($(ev.currentTarget).prop('checked')) {
                $card.find('.typeCon, .typeProposal').addClass('hidden');
            } else {
                $card.find('.typeCon, .typeProposal').removeClass('hidden');
            }
        });
        this.$adminForm.find('.adminCard .typeSite input').trigger('change');
    }
}

new SiteAccess();
