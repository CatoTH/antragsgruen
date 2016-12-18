import "../shared/SiteCreateWizard";

declare let __t: any;

export class ConsultationCreate {
    constructor() {
        $(".settingsType").find("input[type=radio]").change(ConsultationCreate.settingsTypeChanged).change();

        let $consultationEditForm = $(".consultationEditForm");
        $consultationEditForm.find(".delbox button").click(function (ev) {
            ev.preventDefault();
            let $button = $(this);
            bootbox.confirm(__t('admin', 'consDeleteConfirm'), function (result) {
                if (result) {
                    let id = $button.data("id"),
                        $input = $('<input type="hidden">').attr("name", $button.attr("name")).attr("value", $button.attr("value"));
                    $consultationEditForm.append($input);
                    $consultationEditForm.submit();
                }
            });
        });

        new SiteCreateWizard($(".siteCreate"));
    }

    private static settingsTypeChanged(): void {
        if ($("#settingsTypeWizard").prop("checked")) {
            $(".settingsTypeWizard").removeClass("hidden");
            $(".settingsTypeTemplate").addClass("hidden");
        } else {
            $(".settingsTypeWizard").addClass("hidden");
            $(".settingsTypeTemplate").removeClass("hidden");
        }
    }
}

new ConsultationCreate();
