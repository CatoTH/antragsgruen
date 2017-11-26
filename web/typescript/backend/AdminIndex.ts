declare let __t: any;

class AdminIndex {
    constructor() {
        let $delForm = $(".delSiteCaller");
        $delForm.find("button").click(function(ev) {
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
}

new AdminIndex();
