export class AmendmentChangeProposal {
    private $statusDetails: JQuery;

    constructor(private $widget: JQuery) {
        this.$statusDetails = $widget.find('.proposalStatusDetails .statusDetails');
        this.initStatusSetter($widget.find('.statusForm'));
        this.initCommentForm($widget.find('.proposalCommentForm'));
    }

    private initStatusSetter($form: JQuery)
    {
        $form.find("input[type=radio]").change((ev, data: any) => {
            if (!$(ev.currentTarget).prop('checked')) {
                return;
            }
            let newVal = $(ev.currentTarget).val();
            this.$statusDetails.addClass('hidden');
            this.$statusDetails.filter('.status_' + newVal).removeClass('hidden');

            if (data && data.nosave) {
                return;
            }
            $.post($form.attr('action'), {
                setStatus: newVal,
                _csrf: $form.find('input[name=_csrf]').val()
            }, (ev) => {
                console.log(ev);
            }).fail(() => {
                alert("Could not save");
            });
        }).trigger('change', {nosave: 1});
    }

    private initCommentForm($form: JQuery)
    {
        // @TODO
    }
}
