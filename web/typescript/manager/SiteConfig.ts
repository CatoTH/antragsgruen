export class SiteConfig {
    constructor() {
        $("#smtpAuthType").on("changed.fu.selectlist", this.rebuildVisibility.bind(this));
        $("#emailTransport").on("changed.fu.selectlist", this.rebuildVisibility.bind(this)).trigger("changed.fu.selectlist");
    }

    private rebuildVisibility() {
        let transport = $("[name=\"mailService[transport]\"]").val(),
            auth = $("[name=\"mailService[smtpAuthType]\"]").val();

        $('.emailOption').hide();
        if (transport == 'sendmail') {
            // Nothing to do
        } else if (transport == 'mandrill') {
            $('.emailOption.mandrillApiKey').show();
        } else if (transport == 'mailgun') {
            $('.emailOption.mailgunApiKey').show();
            $('.emailOption.mailgunDomain').show();
        } else if (transport == 'smtp') {
            $('.emailOption.smtpHost').show();
            $('.emailOption.smtpPort').show();
            $('.emailOption.smtpAuthType').show();
            if (auth != 'none') {
                $('.emailOption.smtpUsername').show();
                $('.emailOption.smtpPassword').show();
            }
        }
    }
}
