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

        th {
            text-align: left;
        }

        td img {
            width: 150px;
        }
    </style>
</head>
<body>
    <h1>Comcar API vehicle search demo</h1>
    <p>
        Simple demonstration page to access the vehicle search from
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
        function xhrCall(url,params=[],headers) {
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
                    // build the url with each param following in the format ?foo=bar&fooz=barz
                    xhr.open("GET", url + '?' + params.map((param) => param.name + '=' + param.value).join('&'));
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
        async function getCallData(params = []) {
            try {
                callData = await xhrCall('api.php',params);
                return JSON.parse(callData);
            } catch (err) {
                setStatus(err,false);
            }
            return {};
        }

        // call API, making intemediary call to private PHP to access request hash
        async function callAPI(path,params,callback) {
            var api_base = 'https://api.comcar.co.uk/v1/vehicles/';
            setUI('');
            setResponse('');
            try {
                var call_data = await getCallData(params);
                if(typeof call_data.hash !== 'undefined') {
                    var api_data = await xhrCall(
                        api_base + path,
                        params,
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

        // load vehicles
        function loadVehicles(page=1) {
            setUI('');
            callAPI(
                'search/',
                [
                    // force electrics and hybrids
                    {name:'min_battery_range', value: 1},
                    // group by cheapest model
                    {name:'group_by', value: 'model'},
                    {name:'group_order_by', value: 'otr asc'},
                    // order results by battery range
                    {name:'order_by', value: 'battery_range desc'},
                    // show 10 results per page
                    {name:'page_size', value: 10},
                    // which page to load
                    {name:'page', value: page},
                ],
                (response) => {
                setStatus(response.msg);
                if(response.success && response.data.length) {
                    console.log(response.data);
                    arr_el = [];
                    arr_el.push(`Page ${response.page}`);
                    if(page > 1) {
                        arr_el.push(` | <a href="#" onClick="loadVehicles(${page-1})">Prev Page</a>`);
                    }
                    if(page < response.page_count) {
                        arr_el.push(` | <a href="#" onClick="loadVehicles(${page+1})">Next Page</a>`);
                    }
                    arr_el.push('<hr />');
                    arr_el.push(`
                        <table>
                            <thead>
                                <tr>
                                    <th>Vehicle</th>
                                    <th>Bodystyle</th>
                                    <th>Fuel type</th>
                                    <th>WLTP CO2g/km</th>
                                    <th>Price from (£)</th>
                                    <th>Battery Capacity (kwh)</th>
                                    <th>Battery range (m)</th>
                                    <th>Efficiency</th>
                                    <th>BIK Band</th>
                                    <th>Rapid Charge</th>
                                    <th>Image</th>
                                </tr>
                            </thead>
                            <tbody>
                    `);
                    arr_el = arr_el.concat(response.data.map((item) => {
                        let rapid_charge = '-';
                        if(item.fastest_charge_time_minutes > 0) {
                            rapid_charge = item.fastest_charge_percent_from
                                + "% to "
                                + item.fastest_charge_percent_to
                                + "% in "
                                + item.fastest_charge_time_minutes
                                + " minutes";
                        }
                        return `
                            <tr>
                                <td>
                                    ${item.make}
                                    ${item.model}
                                    ${item.derivative}
                                    ${item.derivative_extra}
                                </td>
                                <td>${item.bodystyle}</td>
                                <td>${item.fueltype}</td>
                                <td>${item.wltp_co2gpkm}</td>
                                <td>${item.otr}</td>
                                <td>${item.capacity_kwh}</td>
                                <td>${item.battery_range}</td>
                                <td>${item.electric_energy_consumption}</td>
                                <td>${item.co2_percentage_yr_1}</td>
                                <td>${rapid_charge}</td>
                                <td>
                                    <img src="https://s3-eu-west-1.amazonaws.com/media.comcar.co.uk/vehicle/image/clear/350x219/${item.image}.png" />
                                </td>
                            </tr>
                        `;
                    }));
                    arr_el.push(`</tbody></table>`);
                    setUI(arr_el.join(''));
                }
            });
        }

        loadVehicles();
    </script>
</body>
</html>