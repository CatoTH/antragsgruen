export class DeadlineDebugBar {
    constructor(private $widget: JQuery) {
        const csrf = $widget.find('> input[name=_csrf]').val();

        $widget.on("submit", ev => ev.preventDefault());

        $widget.find('.closeCol button').on("click", () => {
            $.post($widget.attr('action'), {
                '_csrf': csrf,
                'action': 'close'
            }, (ret) => {
                if (ret['success']) {
                    window.location.assign(window.location.href.split('#')[0]);
                } else {
                    alert(ret['error']);
                }
            });
        });

        const $picker = $widget.find('#simulateAdminTime');
        $picker.datetimepicker({
            locale: $picker.find('input').data('locale')
        });

        $widget.find('.setTime').on("click", () => {
            const time = $picker.find('input').val();
            $.post($widget.attr('action'), {
                '_csrf': csrf,
                'action': 'setTime',
                'time': time
            }, (ret) => {
                if (ret['success']) {
                    window.location.assign(window.location.href.split('#')[0])
                } else {
                    alert(ret['error']);
                }
            });
        });
    }
}
