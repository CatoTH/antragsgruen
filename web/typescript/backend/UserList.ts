export class UserList {
    constructor(private $el: JQuery) {
        this.initDelUser();
    }

    private initDelUser() {
        this.$el.find(".deleteUser").on("click", (ev) => {
            ev.preventDefault();
            const $button = $(ev.currentTarget),
                $form = $(ev.currentTarget).parents("form").first();

            const msg = __t("admin", "deleteUserConfirm").replace(/%NAME%/, $button.data("name"));
            bootbox.confirm(msg, (result) => {
                if (result) {
                    let id = $button.data("id");
                    $form.append('<input type="hidden" name="deleteUser" value="' + id + '">');
                    $form.trigger("submit");
                }
            });
        });
    }
}
