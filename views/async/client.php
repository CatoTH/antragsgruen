<?php

?>
<h1>Async-Client</h1>
<div class="content">
    <pre id="debug"></pre>
    <script>
        function log(str) {
            $("#debug").append(str + "\n");
        }
        var wsServer = 'ws://127.0.0.1:9501';
        var websocket = new WebSocket(wsServer);
        websocket.onopen = function (evt) {
            websocket.send(JSON.stringify({"operation":"auth", "auth": "<?= $_COOKIE['PHPSESSID'] ?>"}));
            log('Connected to WebSocket server.');
        };

        websocket.onclose = function (evt) {
            log('Disconnected');
        };

        websocket.onmessage = function (evt) {
            log('Retrieved data from server: ' + evt.data);
        };

        websocket.onerror = function (evt, e) {
            log('Error occured: ' + evt.data);
        };
    </script>
</div>
