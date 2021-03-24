<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Demo</title>
    <style>
        /** required styles */
        * {
            font-family: sans-serif;
        }

        .hidden {
            display: none;
        }

        .status {
            background-color: skyBlue;
            padding: 1em;
        }

        .status.error {
            background-color: salmon;
        }

        pre {
            background-color: #eee;
            padding: 1em;
            font-family: monospace;
            white-space: pre-wrap;       /* Since CSS 2.1 */
            white-space: -moz-pre-wrap;  /* Mozilla, since 1999 */
            white-space: -pre-wrap;      /* Opera 4-6 */
            white-space: -o-pre-wrap;    /* Opera 7 */
            word-wrap: break-word;       /* Internet Explorer 5.5+ */
        }
    </style>
</head>
<body>
    <h1>Comcar API simple demo</h1>
    <p>
        Simple demonstration page to access vehicle details from the
        <a href="https://api.comcar.co.uk">Comcar API</a>
    </p>
    <p class="status" id="status"></p>
    <section class="vehicle-selection hidden">
        <select class="vehicle-selection__selector" id="make-selector"></select>
        <select class="vehicle-selection__selector" id="model-selector"></select>
        <select class="vehicle-selection__selector" id="vehicle-selector"></select>
    </section>
    <section class="vehicle-details hidden"></section>
    <section class="raw-data">
        <pre id="raw-data__json" class="hidden"></pre>
    </section>

    <script>
        // grab DOM elements
        var $selectors = {
            make: document.getElementById('make-selector'),
            model: document.getElementById('model-selector'),
            vehicle: document.getElementById('vehicle-selector'),
        };
        var $details = document.getElementById('vehicle-selector');
        var $json = document.getElementById('raw-data__json');
        var $status = document.getElementById('status');

        // global vars
        var api_key = '';

        // update status bar
        function setStatus(msg, success) {
            if (typeof success === 'undefined') {
                success = true;
            }
            $status.innerHTML = msg;
            if (success) {
                $status.classList.remove('error');
            } else {
                $status.classList.add('error');
            }
        }

        // show response data
        function setResponse(json) {
            if(json === '') {
                $json.innerHTML = '';
                $json.classList.add('hidden');
            } else {
                $json.innerHTML = json;
                $json.classList.remove('hidden');
            }
        }

        // make an ajax call with a promise
        function xhrCall(url,headers) {
            setStatus('loading...');
            return new Promise(function(resolve, reject) {
                    var xhr = new XMLHttpRequest();
                    xhr.onload = function() {
                        if (this.status >= 200 && this.status < 300) {
                            resolve(xhr.response);
                        } else {
                            try {
                                var response = JSON.parse(xhr.responseText);
                                if (typeof response.msg !== 'undefined') {
                                    err_message = response.msg;
                                }
                            } catch (err) {
                                // if there's no reponse object, send status text if we have it
                                var err_message = xhr.statusText !== ''
                                    ? xhr.statusText
                                    : 'HTTP STATUS CODE:' + this.status;
                            }
                            reject(err_message);
                        }
                    };
                    xhr.onerror = function() {
                        reject(
                            xhr.statusText !== ''
                                ? xhr.statusText
                                : 'HTTP STATUS CODE:' + this.status
                        );
                    };
                    xhr.open("GET", url);
                    // set any supplied headers
                    if (typeof headers !== 'undefined') {
                        headers.forEach(function(header){
                            xhr.setRequestHeader(
                                header.name,
                                header.value
                            );
                        });
                    }
                    xhr.send();
            });
        }

        // call hash page to get HMAC auth hash
        async function getCallData() {
            try {
                callData = await xhrCall('api.php');
                return JSON.parse(callData);
            } catch (err) {
                setStatus(err,false);
            }
            return {};
        }

        // on page load grab all makes
        function processAPIResponse(api_data) {
            try {
                setResponse(api_data);
                var response = JSON.parse(api_data);
                console.log(response);
                setStatus(
                    response.msg,
                    response.success
                );
            } catch (err) {
                setStatus('Could not decode response: ' + err, false);
                setResponse('');
            }
        }

        // call API, making intemediary call to private PHP to access request hash
        async function callAPI(path) {
            var api_base = 'https://api.comcar.co.uk/v1/vehicles/';
            try {
                var call_data = await getCallData();
                if(typeof call_data.hash !== 'undefined') {
                    var api_data = await xhrCall(
                        api_base + path,
                        [
                            {name:'hash',value: call_data.hash},
                            {name:'key',value: call_data.key},
                            {name:'nonce',value: call_data.nonce},
                            {name:'time',value: call_data.time},
                        ]
                    );
                    processAPIResponse(api_data);
                }
            } catch (err) {
                // set the app status, success is false
                setStatus(err,false);
            }
        }

        callAPI('makes');
    </script>
</body>
</html>