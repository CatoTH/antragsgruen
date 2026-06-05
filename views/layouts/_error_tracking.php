<script>
    var ERROR_TRACK_URL = <?= json_encode(\app\components\UrlHelper::createUrl("/error-tracking/js")) ?>;
    ERROR_TRACK_URL += (ERROR_TRACK_URL.indexOf("?") !== -1 ? "&" : "?");

    var CSRF = document.querySelector('head meta[name=csrf-token]').getAttribute('content');
    function sendError(payload) {
        var data = JSON.stringify(payload);
        fetch(ERROR_TRACK_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
            body: data,
            keepalive: true
        });
    }

    window.addEventListener('error', function (event) {
        sendError({
            type: 'error',
            message: event.message || null,
            source: event.filename || null,
            line: event.lineno || null,
            stack: (event.error && event.error.stack) ? event.error.stack : null,
            url: window.location.href
        });
    });

    window.addEventListener('unhandledrejection', function (event) {
        var reason = event.reason;
        sendError({
            type: 'unhandledrejection',
            message: (reason instanceof Error) ? reason.message : String(reason),
            stack: (reason instanceof Error && reason.stack) ? reason.stack : null,
            url: window.location.href
        });
    });
</script>
