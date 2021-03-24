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
    <section id="ui"></section>
    <section id="raw-data" class="hidden">
        <hr />
        <strong>JSON response</strong>
        <pre id="raw-data__json"></pre>
    </section>

    <script>
        // grab DOM elements
        var $ui = document.getElementById('ui');
        var $response = document.getElementById('raw-data');
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
                $response.classList.add('hidden');
            } else {
                $json.innerHTML = json;
                $response.classList.remove('hidden');
            }
        }

        // print ui
        function setUI(html) {
            if(html === '') {
                $ui.innerHTML = '';
                $ui.classList.add('hidden');
            } else {
                $ui.innerHTML = html;
                $ui.classList.remove('hidden');
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

        // call API, making intemediary call to private PHP to access request hash
        async function callAPI(path,callback) {
            var api_base = 'https://api.comcar.co.uk/v1/vehicles/';
            setUI('');
            setResponse('');
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
                    try {
                        setResponse(api_data);
                        var response = JSON.parse(api_data);
                        callback(response);
                    } catch (err) {
                        setStatus('Could not decode response: ' + err, false);
                        setResponse('');
                    }
                }
            } catch (err) {
                // set the app status, success is false
                setStatus(err,false);
            }
        }

        // load makes
        function loadMakes() {
            setUI('');
            callAPI('makes',(response) => {
                setStatus(response.msg);
                if(response.success && response.data.length) {
                    arr_el = response.data.map((item) => {
                        return `
                            <div class="api-item">
                                <a href="#" onclick="loadModels('${item.make}')">
                                    ${item.make}
                                </a>
                            </div>
                        `;
                    });
                    setUI(arr_el.join(''));
                }
            });
        }

        // load models
        function loadModels(make) {
            setUI('')
            callAPI('makes/'+make+'/models',(response) => {
                setStatus(response.msg);
                if(response.success && response.data.length) {
                    arr_el = [`<h2>${make}</h2>`];
                    arr_el = response.data.map((item) => {
                        return `
                            <div class="api-item">
                                <a href="#" onclick="loadVehicles('${item.make}','${item.model}')">
                                    ${item.model}
                                </a>
                            </div>
                        `;
                    });
                    setUI(arr_el.join(''));
                }
            });
        }

        // load vehicles
        function loadVehicles(make,model) {
            setUI('');
            callAPI('makes/'+make+'/models/'+model+'/vehicles',(response) => {
                setStatus(response.msg);
                if(response.success && response.data.length) {
                    arr_el = [`<h2>${make} ${model}</h2>`];
                    arr_el = response.data.map((item) => {
                        return `
                            <div class="api-item">
                                <a href="#" onclick="loadDetail('${item.vehicle_id}')">
                                    ${item.grade}
                                    ${item.engine}
                                </a>
                            </div>
                        `;
                    });
                    setUI(arr_el.join(''));
                }
            });
        }

        // load vehicle detail
        function loadDetail(id) {
            setUI('');
            callAPI('vehicle/'+id,(response) => {
                setStatus(response.msg);
                if(response.success && response.data.length) {
                    const item = response.data[0];
                    console.log(item);
                    arr_el = [
                        `<h2>${item.description.make} ${item.description.model} ${item.description.derivative}</h2>`,
                        `<h3>Details</h3>`,
                        `<ul>
                        <li>Fuel Type: ${item.description.fueltype}</li>
                        <li>Transmission: ${item.description.transmission}</li>
                        <li>Doors: ${item.description.doors}</li>
                        <li>Seats: ${item.description.seats}</li>
                        <li>WLTP CO<sub>2</sub>: ${item.emissions.wltp_co2_gpkm}</li>
                        <li>WLTP MPG: ${item.economy.mpg_combined_tel}-${item.economy.mpg_combined_teh}</li>
                        </ul>`,
                        `<h3>Tax</h3>`,
                        `<ul>
                            <li>Percentage: ${parseInt(item.tax[0].co2_percentage*100)}</li>
                            <li>Percentage: ${(item.tax[0].bands[1].vehicle_tax)}</li>
                        </ul>`
                    ];
                    setUI(arr_el.join(''));
                }
            });
        }

        loadMakes();
    </script>
</body>
</html>