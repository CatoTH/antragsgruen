declare let __t: any;

class AdminIndex {
    constructor() {
        this.initDelSite();
        this.initUpdates();
    }

    private initDelSite() {
        let $delForm = $(".delSiteCaller");
        $delForm.find("button").on("click", function (ev) {
            ev.preventDefault();
            let $button = $(this);
            bootbox.confirm(__t('admin', 'consDeleteConfirm'), function (result) {
                if (result) {
                    let $input = $('<input type="hidden">').attr("name", $button.attr("name")).attr("value", $button.attr("value"));
                    $delForm.append($input);
                    $delForm.trigger("submit");
                }
            });
        });
    }

    private onSubmitGotoUpdateForm(ev, data) {
        if (data && typeof(data.confirmed) && data.confirmed === true) {
            return;
        }
        ev.preventDefault();
        bootbox.confirm({
            message: __t('admin', 'gotoUpdateModeConfirm'),
            callback: (result) => {
                if (result) {
                    $('.adminCardUpdates .updateForm').trigger('submit', {'confirmed': true});
                }
            }
        });
    }

    private initUpdates() {
        let $updateWidget = $('.adminCardUpdates main');
        $.get($updateWidget.data('src'), function(data) {
            $updateWidget.html(data);
        });
        $updateWidget.on('click', '.showChanges', (ev) => {
            $(ev.currentTarget).parents('li').first().find('.changes').toggleClass('hidden');
        });
        $updateWidget.on('submit', '.updateForm', this.onSubmitGotoUpdateForm);
    }
}

new AdminIndex();
