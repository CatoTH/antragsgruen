declare let __t: any;

class AdminIndex {
    constructor() {
        this.initDelSite();
        this.initUpdates();
    }

    private initDelSite() {
        let $delForm = $(".delSiteCaller");
        $delForm.find("button").click(function (ev) {
            ev.preventDefault();
            let $button = $(this);
            bootbox.confirm(__t('admin', 'consDeleteConfirm'), function (result) {
                if (result) {
                    let $input = $('<input type="hidden">').attr("name", $button.attr("name")).attr("value", $button.attr("value"));
                    $delForm.append($input);
                    $delForm.submit();
                }
            });
        });
    }

    private initUpdates() {
        let $updateWidget = $(".adminCardUpdates main");
        $.get($updateWidget.data("src"), function(data) {
            console.log(data);
            $updateWidget.html(data);
        });
    }
}

new AdminIndex();
