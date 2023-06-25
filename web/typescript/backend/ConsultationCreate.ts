import "../shared/SiteCreateWizard";

declare let __t: any;

export class ConsultationCreate {
    constructor() {
        $(".settingsType").find("input[type=radio]").on("change", ConsultationCreate.settingsTypeChanged).trigger("change");

        let $consultationEditForm = $(".consultationEditForm");
        $consultationEditForm.find(".delbox button").on("click", function (ev) {
            ev.preventDefault();
            let $button = $(this);
            bootbox.confirm(__t('admin', 'consDeleteConfirm'), function (result) {
                if (result) {
                    let $input = $('<input type="hidden">').attr("name", $button.attr("name")).attr("value", $button.attr("value"));
                    $consultationEditForm.append($input);
                    $consultationEditForm.trigger("submit");
                }
            });
        });

        new SiteCreateWizard($(".siteCreate"));
    }

    private static settingsTypeChanged(): void {
        if ((document.getElementById('settingsTypeWizard') as HTMLInputElement).checked) {
            console.log("checked");
            document.querySelector('.settingsTypeWizard').classList.remove('hidden');
            document.querySelectorAll('.settingsTypeTemplate').forEach(el => el.classList.add('hidden'));
            document.querySelector('.templateSubselect').classList.add('hidden');
        } else {
            document.querySelector('.settingsTypeWizard').classList.add('hidden');
            document.querySelectorAll('.settingsTypeTemplate').forEach(el => el.classList.remove('hidden'));
            document.querySelector('.templateSubselect').classList.remove('hidden');
        }
    }
}

new ConsultationCreate();
