declare let __t: any;

class AdminIndex {
    constructor() {
        let $delForm = $(".del-site-caller");
        $delForm.find("button").click(function(ev) {
            ev.preventDefault();
            let $button = $(this);
            bootbox.confirm(__t('admin', 'consDeleteConfirm'), function (result) {
                if (result) {
                    let id = $button.data("id"),
                        $input = $('<input type="hidden">').attr("name", $button.attr("name")).attr("value", $button.attr("value"));
                    $delForm.append($input);
                    $delForm.submit();
                }
            });
        });
    }
}

new AdminIndex();
